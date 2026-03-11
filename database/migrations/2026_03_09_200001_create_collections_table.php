<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->increments('collection_id');
            $table->unsignedBigInteger('user_id');
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->enum('visibility', ['PUBLIC', 'PRIVATE'])->default('PUBLIC');
            $table->unsignedInteger('cover_title_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('cover_title_id')->references('title_id')->on('titles')->nullOnDelete();

            $table->index('user_id');
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
