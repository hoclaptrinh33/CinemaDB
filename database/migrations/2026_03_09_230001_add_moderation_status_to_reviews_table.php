<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->enum('moderation_status', ['VISIBLE', 'HIDDEN'])
                ->default('VISIBLE')
                ->after('has_spoilers');

            $table->index('moderation_status', 'idx_reviews_moderation_status');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_moderation_status');
            $table->dropColumn('moderation_status');
        });
    }
};
