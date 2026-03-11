<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('titles', function (Blueprint $table) {
            $table->increments('title_id');
            $table->string('title_name', 300);
            $table->unsignedInteger('original_language_id')->nullable();
            $table->date('release_date')->nullable();
            $table->integer('runtime_mins')->nullable();
            $table->enum('title_type', ['MOVIE', 'SERIES', 'EPISODE']);
            $table->text('description')->nullable();

            // Denormalization — managed by triggers only
            $table->integer('rating_sum')->default(0);
            $table->integer('rating_count')->default(0);
            $table->decimal('avg_rating', 4, 2)->default(0.00);

            // Media
            $table->string('poster_path', 500)->nullable();
            $table->string('backdrop_path', 500)->nullable();
            $table->string('trailer_url', 500)->nullable();

            $table->enum('status', ['Rumored', 'Post Production', 'Released', 'Canceled'])->nullable();
            $table->bigInteger('budget')->nullable();
            $table->bigInteger('revenue')->nullable();
            $table->enum('visibility', ['PUBLIC', 'HIDDEN', 'COPYRIGHT_STRIKE', 'GEO_BLOCKED'])->default('PUBLIC');
            $table->string('moderation_reason', 500)->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->string('slug', 400)->unique()->nullable();

            $table->foreign('original_language_id')->references('language_id')->on('languages')->nullOnDelete();

            // Indexes
            $table->index('title_name', 'idx_titles_name');
            $table->index('release_date', 'idx_titles_release');
            $table->index('avg_rating', 'idx_titles_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titles');
    }
};
