<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // titles: add created_at / updated_at (they were entirely missing) + soft delete
        Schema::table('titles', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->after('slug');
            $table->timestamp('updated_at')->nullable()->after('created_at');
            $table->softDeletes()->after('updated_at');
        });

        // persons: timestamps were also missing + soft delete
        Schema::table('persons', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->after('profile_path');
            $table->timestamp('updated_at')->nullable()->after('created_at');
            $table->softDeletes()->after('updated_at');
        });

        // reviews: has created_at, no updated_at by design — add only soft delete
        Schema::table('reviews', function (Blueprint $table) {
            $table->softDeletes()->after('reputation_earned');
        });
    }

    public function down(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at', 'deleted_at']);
        });

        Schema::table('persons', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at', 'deleted_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
