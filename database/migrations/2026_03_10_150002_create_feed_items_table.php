<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id');
            $table->string('activity_type', 60); // review_created | comment_created | collection_created | collection_title_added | nomination_created | badge_earned
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type', 60)->nullable(); // review | comment | collection | nomination
            $table->unsignedInteger('title_id')->nullable();
            $table->unsignedInteger('collection_id')->nullable();
            $table->json('metadata')->nullable(); // denormalised render data (title_name, slug, poster_path, etc.)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_user_id', 'created_at']);
            $table->index('created_at');

            $table->foreign('actor_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_items');
    }
};
