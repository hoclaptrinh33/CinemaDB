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
        Schema::create('review_helpful_votes', function (Blueprint $table) {
            $table->unsignedInteger('review_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('voted_at')->useCurrent();
            $table->primary(['review_id', 'user_id']);
            $table->foreign('review_id')->references('review_id')->on('reviews')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_helpful_votes');
    }
};
