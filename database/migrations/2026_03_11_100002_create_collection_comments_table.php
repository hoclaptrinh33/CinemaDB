<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_comments', function (Blueprint $table) {
            $table->bigIncrements('collection_comment_id');
            $table->unsignedInteger('collection_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('content');
            $table->enum('content_type', ['text', 'gif'])->default('text');
            $table->string('gif_url')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->foreign('collection_id')
                ->references('collection_id')->on('collections')->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')
                ->references('collection_comment_id')->on('collection_comments')->onDelete('cascade');

            $table->index('collection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_comments');
    }
};
