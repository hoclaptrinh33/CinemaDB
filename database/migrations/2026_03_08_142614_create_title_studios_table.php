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
        Schema::create('title_studios', function (Blueprint $table) {
            $table->unsignedInteger('title_id');
            $table->unsignedInteger('studio_id');
            $table->primary(['title_id', 'studio_id']);
            $table->foreign('title_id')->references('title_id')->on('titles')->cascadeOnDelete();
            $table->foreign('studio_id')->references('studio_id')->on('studios')->cascadeOnDelete();
            $table->index('title_id', 'idx_title_studio_title');
            $table->index('studio_id', 'idx_title_studio_studio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_studios');
    }
};
