<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('follower_user_id');
            $table->unsignedBigInteger('followed_user_id');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('follower_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('followed_user_id')->references('id')->on('users')->cascadeOnDelete();

            // Prevent duplicate follows and self-follows via DB constraint
            $table->unique(['follower_user_id', 'followed_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_follows');
    }
};
