<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('title_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('comment_id')->autoIncrement()->primary();
            $table->unsignedInteger('title_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('content');
            $table->enum('content_type', ['text', 'emoji', 'gif'])->default('text');
            $table->string('gif_url', 1024)->nullable(); // Tenor GIF URL
            $table->unsignedInteger('like_count')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->foreign('title_id')->references('title_id')->on('titles')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('parent_id')->references('comment_id')->on('title_comments')->cascadeOnDelete();

            $table->index('title_id');
            $table->index(['title_id', 'parent_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('title_comments');
    }
};
