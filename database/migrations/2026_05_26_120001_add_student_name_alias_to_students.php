<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        if (! Schema::hasColumn('students', 'student_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('student_name', 200)->nullable()->after('deoris_user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'student_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('student_name');
            });
        }
    }
};
