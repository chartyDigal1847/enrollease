<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures event_outbox.status includes 'cancelled'.
 * On a fresh database this is already correct (migration 1 creates it with 'cancelled').
 * On an existing database that was migrated before the fix, this alters the column.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only alter if 'cancelled' is not already in the enum
        $column = collect(DB::select("SHOW COLUMNS FROM event_outbox WHERE Field = 'status'"))->first();

        if ($column && ! str_contains($column->Type, 'cancelled')) {
            DB::statement("ALTER TABLE event_outbox MODIFY COLUMN status ENUM('pending','published','failed','cancelled') NOT NULL DEFAULT 'pending'");
        }

        // Cancel any stuck SectionAssigned events (not in DEORIS allowed list)
        if (Schema::hasTable('event_outbox')) {
            DB::table('event_outbox')
                ->where('event_name', 'SectionAssigned')
                ->whereIn('status', ['pending', 'failed'])
                ->update(['status' => 'cancelled']);
        }
    }

    public function down(): void
    {
        // No destructive rollback needed
    }
};
