<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_titles', function (Blueprint $table) {
            $table->unsignedInteger('collection_id');
            $table->unsignedInteger('title_id');
            $table->timestamp('added_at')->useCurrent();
            $table->text('note')->nullable();

            $table->primary(['collection_id', 'title_id']);

            $table->foreign('collection_id')->references('collection_id')->on('collections')->cascadeOnDelete();
            $table->foreign('title_id')->references('title_id')->on('titles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_titles');
    }
};
