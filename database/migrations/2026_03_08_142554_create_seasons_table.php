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
        Schema::create('seasons', function (Blueprint $table) {
            $table->increments('season_id');
            $table->unsignedInteger('series_id');
            $table->integer('season_number');
            $table->foreign('series_id')->references('series_id')->on('series')->cascadeOnDelete();
            $table->index('series_id', 'idx_season_series');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
