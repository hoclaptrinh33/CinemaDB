<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('title_media', function (Blueprint $table) {
            $table->bigIncrements('media_id');
            $table->unsignedInteger('title_id');
            $table->enum('media_type', ['image', 'backdrop', 'trailer']);
            $table->string('url', 1024);
            $table->string('thumbnail_url', 1024)->nullable();
            $table->string('title', 255)->nullable(); // e.g. trailer name
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('title_id')->references('title_id')->on('titles')->cascadeOnDelete();
            $table->index(['title_id', 'media_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('title_media');
    }
};
