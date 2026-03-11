<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('watchlists');
    }

    public function down(): void
    {
        Schema::create('watchlists', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('title_id');
            $table->enum('status', ['want_to_watch', 'watching', 'watched', 'dropped']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();

            $table->primary(['user_id', 'title_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('title_id')->references('title_id')->on('titles')->cascadeOnDelete();

            $table->index('user_id');
        });
    }
};
