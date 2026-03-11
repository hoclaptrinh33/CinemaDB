<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Intentionally empty.
        // Fresh databases get these columns from create_badges_table.
    }

    public function down(): void
    {
        // Intentionally empty.
    }
};
