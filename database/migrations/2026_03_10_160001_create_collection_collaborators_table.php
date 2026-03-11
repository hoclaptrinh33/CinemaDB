<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_collaborators', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('collection_id');
            $table->unsignedBigInteger('user_id');        // the invited user
            $table->unsignedBigInteger('invited_by');     // the inviter (owner)
            $table->timestamp('accepted_at')->nullable(); // null = pending

            $table->timestamps();

            $table->unique(['collection_id', 'user_id']);

            $table->foreign('collection_id')
                ->references('collection_id')
                ->on('collections')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('invited_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_collaborators');
    }
};
