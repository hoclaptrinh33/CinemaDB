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
        Schema::create('reviews', function (Blueprint $table) {
            $table->increments('review_id');
            $table->unsignedInteger('title_id');
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('rating')->unsigned()->nullable();
            $table->text('review_text')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('helpful_votes')->default(0);
            $table->boolean('has_spoilers')->default(false);
            $table->integer('reputation_earned')->default(0);
            $table->unique(['title_id', 'user_id'], 'uq_user_title');
            $table->foreign('title_id')->references('title_id')->on('titles')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('title_id', 'idx_reviews_title');
            $table->index('user_id', 'idx_reviews_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
