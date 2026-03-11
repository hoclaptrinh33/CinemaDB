<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->unsignedInteger('nomination_count')->default(0)->after('cover_title_id');
            $table->boolean('is_published')->default(false)->after('nomination_count');
            $table->timestamp('published_at')->nullable()->after('is_published');
            $table->unsignedInteger('source_collection_id')->nullable()->after('published_at');
            $table->string('original_author_name', 100)->nullable()->after('source_collection_id');

            $table->foreign('source_collection_id')
                ->references('collection_id')
                ->on('collections')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['source_collection_id']);
            $table->dropColumn([
                'nomination_count',
                'is_published',
                'published_at',
                'source_collection_id',
                'original_author_name',
            ]);
        });
    }
};
