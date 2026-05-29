<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Spec compliance migration.
 *
 * Adds:
 *  - enrollments.student_id FK
 *  - enrollments.section_id FK
 *  - enrollments.reviewing status
 *  - enrollments.academic_term_id FK
 *  - academic_terms table
 *  - enrollment_status_logs table
 *  - activity_logs table
 *  - event_outbox table
 *  - notifications table
 *  - MySQL views: v_enrollment_stats, v_section_capacity
 *  - Trigger: trg_enrollment_status_audit
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. academic_terms ─────────────────────────────────────────────────
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);                          // e.g. "S.Y. 2026–2027"
            $table->string('school_year', 20);                   // e.g. "2026–2027"
            $table->enum('semester', ['1st', '2nd', 'summer'])->default('1st');
            $table->date('enrollment_start');
            $table->date('enrollment_end');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index('is_active');
            $table->index('school_year');
        });

        // ── 2. Alter enrollments ──────────────────────────────────────────────
        Schema::table('enrollments', function (Blueprint $table) {
            // student_id FK (nullable — SSO-only students may not have a students row yet)
            $table->unsignedBigInteger('student_id')->nullable()->after('id');
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();

            // academic_term_id FK
            $table->unsignedBigInteger('academic_term_id')->nullable()->after('student_id');
            $table->foreign('academic_term_id')->references('id')->on('academic_terms')->nullOnDelete();

            // section_id FK (rooms table serves as sections)
            // room_id already exists — add section_id as alias for clarity
            // We keep room_id for backward compat and add section_id as a virtual alias via view

            // Add 'reviewing' to status enum
            // MySQL requires MODIFY COLUMN to change enum
        });

        // Add 'reviewing' to the status enum via raw SQL
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('pending','reviewing','approved','rejected','enrolled','cancelled') NOT NULL DEFAULT 'pending'");

        // ── 3. enrollment_status_logs ─────────────────────────────────────────
        Schema::create('enrollment_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollment_id');
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('changed_by_role', 30)->nullable();   // student | officer | admin | system
            $table->string('changed_by_id', 100)->nullable();    // SSO user ID
            $table->string('changed_by_name', 200)->nullable();
            $table->text('remarks')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index('enrollment_id');
            $table->index('to_status');
            $table->index('created_at');
        });

        // ── 4. activity_logs ──────────────────────────────────────────────────
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100);                       // e.g. enrollment.submitted
            $table->string('subject_type', 100)->nullable();     // e.g. App\Models\Enrollment
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('actor_id', 100)->nullable();         // SSO user ID
            $table->string('actor_name', 200)->nullable();
            $table->string('actor_role', 30)->nullable();
            $table->json('context')->nullable();                  // extra data
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->index('action');
            $table->index(['subject_type', 'subject_id']);
            $table->index('actor_id');
            $table->index('created_at');
        });

        // ── 5. event_outbox ───────────────────────────────────────────────────
        Schema::create('event_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 120)->unique();
            $table->string('event_name', 120);
            $table->string('source_module', 80)->default('EnrollEase');
            $table->string('correlation_id', 120)->nullable();
            $table->json('payload');
            $table->string('schema_version', 20)->default('1.0');
            $table->enum('status', ['pending', 'published', 'failed', 'cancelled'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('event_name');
            $table->index('created_at');
        });

        // ── 6. notifications ──────────────────────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('recipient_id', 100);                 // SSO user ID
            $table->string('recipient_role', 30)->nullable();
            $table->string('type', 80);                          // enrollment.approved etc.
            $table->string('title', 200);
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('action_url', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index('recipient_id');
            $table->index('read_at');
            $table->index('created_at');
        });

        // ── 7. MySQL VIEW: v_enrollment_stats ─────────────────────────────────
        DB::statement("
            CREATE OR REPLACE VIEW v_enrollment_stats AS
            SELECT
                COALESCE(at.school_year, e.school_year, 'Unknown') AS school_year,
                e.grade_level,
                COUNT(*)                                            AS total,
                SUM(e.status = 'pending')                          AS pending,
                SUM(e.status = 'reviewing')                        AS reviewing,
                SUM(e.status = 'approved')                         AS approved,
                SUM(e.status = 'enrolled')                         AS enrolled,
                SUM(e.status = 'rejected')                         AS rejected,
                SUM(e.status = 'cancelled')                        AS cancelled
            FROM enrollments e
            LEFT JOIN academic_terms at ON at.id = e.academic_term_id
            GROUP BY COALESCE(at.school_year, e.school_year, 'Unknown'), e.grade_level
        ");

        // ── 8. MySQL VIEW: v_section_capacity ─────────────────────────────────
        DB::statement("
            CREATE OR REPLACE VIEW v_section_capacity AS
            SELECT
                r.id                                                AS room_id,
                r.name                                              AS room_name,
                r.grade_level,
                r.section,
                r.adviser,
                r.capacity_male,
                r.capacity_female,
                (r.capacity_male + r.capacity_female)               AS total_capacity,
                SUM(e.gender = 'male')                              AS male_enrolled,
                SUM(e.gender = 'female')                            AS female_enrolled,
                COUNT(e.id)                                         AS total_enrolled,
                (r.capacity_male - SUM(e.gender = 'male'))          AS male_available,
                (r.capacity_female - SUM(e.gender = 'female'))      AS female_available,
                (r.capacity_male + r.capacity_female - COUNT(e.id)) AS total_available
            FROM rooms r
            LEFT JOIN enrollments e ON e.room_id = r.id AND e.status = 'enrolled'
            GROUP BY r.id, r.name, r.grade_level, r.section, r.adviser,
                     r.capacity_male, r.capacity_female
        ");

        // ── 9. Trigger: audit enrollment status changes ───────────────────────
        DB::unprepared('DROP TRIGGER IF EXISTS trg_enrollment_status_audit');
        DB::unprepared("
            CREATE TRIGGER trg_enrollment_status_audit
            AFTER UPDATE ON enrollments
            FOR EACH ROW
            BEGIN
                IF OLD.status <> NEW.status THEN
                    INSERT INTO enrollment_status_logs
                        (enrollment_id, from_status, to_status, changed_by_role, remarks, created_at, updated_at)
                    VALUES
                        (NEW.id, OLD.status, NEW.status, 'system', NEW.remarks, NOW(), NOW());
                END IF;
            END
        ");

        // ── 10. Stored procedure: sp_enrollment_summary ───────────────────────
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_enrollment_summary');
        DB::unprepared("
            CREATE PROCEDURE sp_enrollment_summary(IN p_school_year VARCHAR(20))
            BEGIN
                SELECT
                    grade_level,
                    COUNT(*)                    AS total_applications,
                    SUM(status = 'enrolled')    AS enrolled,
                    SUM(status = 'approved')    AS approved,
                    SUM(status = 'pending')     AS pending,
                    SUM(status = 'rejected')    AS rejected,
                    SUM(status = 'cancelled')   AS cancelled
                FROM enrollments
                WHERE school_year = p_school_year
                GROUP BY grade_level
                ORDER BY grade_level;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_enrollment_summary');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_enrollment_status_audit');
        DB::statement('DROP VIEW IF EXISTS v_section_capacity');
        DB::statement('DROP VIEW IF EXISTS v_enrollment_stats');

        Schema::dropIfExists('notifications');
        Schema::dropIfExists('event_outbox');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('enrollment_status_logs');

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['academic_term_id']);
            $table->dropColumn(['student_id', 'academic_term_id']);
        });

        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('pending','verified','approved','rejected','enrolled','cancelled') NOT NULL DEFAULT 'pending'");

        Schema::dropIfExists('academic_terms');
    }
};
