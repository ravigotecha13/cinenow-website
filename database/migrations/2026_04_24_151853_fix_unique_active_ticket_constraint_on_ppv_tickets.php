<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The old `unique_active_ticket` index was on (user_id, entertainment_id, status),
     * which prevents a user from ever having more than one 'consumed' ticket for the
     * same content (i.e. repeat purchases crash on consume).
     *
     * Fix: replace it with a MySQL generated column that is 'active' only when the
     * ticket is active, and NULL otherwise. NULL values do not collide in unique
     * indexes, so consumed/expired rows are unrestricted while only one active
     * ticket per (user, entertainment) is enforced.
     */
    public function up(): void
    {
        // Drop the broken wide unique index
        DB::statement('ALTER TABLE ppv_tickets DROP INDEX IF EXISTS unique_active_ticket');

        // Add a generated (virtual) column that is only non-null for active rows
        DB::statement("
            ALTER TABLE ppv_tickets
            ADD COLUMN IF NOT EXISTS active_marker VARCHAR(10)
                GENERATED ALWAYS AS (IF(status = 'active', 'active', NULL)) VIRTUAL
        ");

        // New unique constraint: only one active ticket per user + entertainment
        DB::statement('
            ALTER TABLE ppv_tickets
            ADD UNIQUE KEY unique_active_ticket (user_id, entertainment_id, active_marker)
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ppv_tickets DROP INDEX IF EXISTS unique_active_ticket');
        DB::statement('ALTER TABLE ppv_tickets DROP COLUMN IF EXISTS active_marker');

        // Restore the original (broken) constraint — only do this if you truly need rollback
        DB::statement('
            ALTER TABLE ppv_tickets
            ADD UNIQUE KEY unique_active_ticket (user_id, entertainment_id, status)
        ');
    }
};
