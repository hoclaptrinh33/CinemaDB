<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates all DB objects: Views, Triggers, Functions, and Stored Procedures.
     * Each compound statement is executed individually to avoid DELIMITER issues with PDO.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return; // MySQL-specific objects (triggers, procedures, views) — skip on other drivers
        }

        // ── Drop existing objects (safe for migrate:fresh re-runs) ───────────
        DB::unprepared('DROP PROCEDURE IF EXISTS prc_add_review');
        DB::unprepared('DROP PROCEDURE IF EXISTS prc_add_title');
        DB::unprepared('DROP FUNCTION  IF EXISTS fn_get_total_reviews');
        DB::unprepared('DROP TRIGGER   IF EXISTS trg_audit_title_delete');
        DB::unprepared('DROP TRIGGER   IF EXISTS trg_audit_title_update');
        DB::unprepared('DROP TRIGGER   IF EXISTS trg_audit_title_insert');
        DB::unprepared('DROP TRIGGER   IF EXISTS trg_update_title_rating_update');
        DB::unprepared('DROP TRIGGER   IF EXISTS trg_update_title_rating_delete');
        DB::unprepared('DROP TRIGGER   IF EXISTS trg_update_title_rating_insert');
        DB::unprepared('DROP VIEW      IF EXISTS vw_user_reviews');
        DB::unprepared('DROP VIEW      IF EXISTS vw_title_details');

        // ── Views ────────────────────────────────────────────────────────────
        DB::unprepared(file_get_contents(database_path('sql/views.sql')));

        // ── Triggers (executed one-by-one) ───────────────────────────────────
        $this->executeSqlFile(database_path('sql/triggers.sql'));

        // ── Functions & Procedures (executed one-by-one) ─────────────────────
        $this->executeSqlFile(database_path('sql/procedures.sql'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Drop procedures
        DB::unprepared('DROP PROCEDURE IF EXISTS prc_add_review');
        DB::unprepared('DROP PROCEDURE IF EXISTS prc_add_title');

        // Drop functions
        DB::unprepared('DROP FUNCTION IF EXISTS fn_get_total_reviews');

        // Drop triggers
        DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_title_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_title_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_title_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_update_title_rating_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_update_title_rating_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_update_title_rating_insert');

        // Drop views
        DB::unprepared('DROP VIEW IF EXISTS vw_user_reviews');
        DB::unprepared('DROP VIEW IF EXISTS vw_title_details');
    }

    /**
     * Parse and execute a SQL file containing multiple CREATE TRIGGER /
     * FUNCTION / PROCEDURE statements (each ending with "END;").
     *
     * Tracks BEGIN/END depth to handle nested blocks correctly.
     */
    private function executeSqlFile(string $path): void
    {
        $sql    = file_get_contents($path);
        $lines  = explode("\n", $sql);
        $buffer = '';
        $depth  = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip comment/blank lines between top-level statements
            if ($depth === 0 && $buffer === '' && ($trimmed === '' || str_starts_with($trimmed, '--'))) {
                continue;
            }

            $buffer .= $line . "\n";

            // Track nesting depth
            if (preg_match('/\bBEGIN\b/i', $trimmed)) {
                $depth++;
            }
            if (preg_match('/^END;$/i', $trimmed)) {
                $depth--;
                if ($depth === 0) {
                    DB::unprepared(trim($buffer));
                    $buffer = '';
                }
            }
        }

        // Execute any remaining simple statement (one-liner without END)
        if (trim($buffer) !== '') {
            DB::unprepared(trim($buffer));
        }
    }
};
