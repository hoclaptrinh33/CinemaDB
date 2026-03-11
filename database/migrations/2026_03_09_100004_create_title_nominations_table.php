<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('title_nominations', function (Blueprint $table) {
            $table->unsignedBigInteger('nomination_id')->autoIncrement()->primary();
            $table->unsignedInteger('title_id');
            $table->unsignedBigInteger('user_id');
            $table->date('nominated_date'); // UTC date for daily limit logic

            $table->unique(['user_id', 'title_id', 'nominated_date'], 'uq_nomination_per_day');

            $table->foreign('title_id')->references('title_id')->on('titles')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index('title_id');
            $table->index(['user_id', 'nominated_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('title_nominations');
    }
};
