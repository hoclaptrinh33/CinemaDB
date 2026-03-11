<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_comment_likes', function (Blueprint $table) {
            $table->unsignedBigInteger('collection_comment_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->primary(['collection_comment_id', 'user_id']);
            $table->foreign('collection_comment_id')
                ->references('collection_comment_id')->on('collection_comments')->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_comment_likes');
    }
};
