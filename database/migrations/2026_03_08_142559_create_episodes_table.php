<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->unsignedInteger('episode_id')->primary();
            $table->unsignedInteger('season_id');
            $table->integer('episode_number');
            $table->date('air_date')->nullable();
            $table->foreign('episode_id')->references('title_id')->on('titles')->cascadeOnDelete();
            $table->foreign('season_id')->references('season_id')->on('seasons')->cascadeOnDelete();
            $table->index('season_id', 'idx_episode_season');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
