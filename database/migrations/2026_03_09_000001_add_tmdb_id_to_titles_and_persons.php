<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->integer('tmdb_id')->nullable()->index('idx_titles_tmdb_id')->after('title_id');
        });

        Schema::table('persons', function (Blueprint $table) {
            $table->integer('tmdb_id')->nullable()->index('idx_persons_tmdb_id')->after('person_id');
        });
    }

    public function down(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->dropIndex('idx_titles_tmdb_id');
            $table->dropColumn('tmdb_id');
        });

        Schema::table('persons', function (Blueprint $table) {
            $table->dropIndex('idx_persons_tmdb_id');
            $table->dropColumn('tmdb_id');
        });
    }
};
