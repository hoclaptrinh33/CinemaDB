<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('badge_id');
            $table->timestamp('earned_at')->useCurrent();

            $table->primary(['user_id', 'badge_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('badge_id')->references('badge_id')->on('badges')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
    }
};
