<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_title_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('collection_id');
            $table->unsignedInteger('title_id');
            $table->unsignedBigInteger('user_id');
            $table->text('note')->nullable();
            $table->timestamp('watched_at')->nullable();
            $table->timestamps();

            $table->foreign('collection_id')
                ->references('collection_id')
                ->on('collections')
                ->cascadeOnDelete();
            $table->foreign('title_id')
                ->references('title_id')
                ->on('titles')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->unique(['collection_id', 'title_id', 'user_id'], 'uq_coll_title_note');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_title_notes');
    }
};
