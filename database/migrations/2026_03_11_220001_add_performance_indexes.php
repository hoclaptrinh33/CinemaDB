<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // feed_items: actor_user_id used in FeedController whereIn lookup
        Schema::table('feed_items', function (Blueprint $table) {
            $table->index('actor_user_id', 'feed_items_actor_user_id_index');
        });

        // collections: (user_id, visibility) used together in scope queries
        Schema::table('collections', function (Blueprint $table) {
            $table->index(['user_id', 'visibility'], 'collections_user_id_visibility_index');
        });

        // reviews: moderation_status filtered in almost every controller
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('moderation_status', 'reviews_moderation_status_index');
        });

        // user_follows: followed_user_id for reverse-direction follower counts
        Schema::table('user_follows', function (Blueprint $table) {
            $table->index('followed_user_id', 'user_follows_followed_user_id_index');
        });

        // collection_collaborators: (collection_id, accepted_at) used in whereHas checks
        Schema::table('collection_collaborators', function (Blueprint $table) {
            $table->index(['collection_id', 'accepted_at'], 'collection_collaborators_collection_accepted_index');
        });

        // user_activity_logs: (user_id, activity_date) for profile calendar and count queries
        Schema::table('user_activity_logs', function (Blueprint $table) {
            $table->index(['user_id', 'activity_date'], 'user_activity_logs_user_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('feed_items', function (Blueprint $table) {
            $table->dropIndex('feed_items_actor_user_id_index');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex('collections_user_id_visibility_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_moderation_status_index');
        });

        Schema::table('user_follows', function (Blueprint $table) {
            $table->dropIndex('user_follows_followed_user_id_index');
        });

        Schema::table('collection_collaborators', function (Blueprint $table) {
            $table->dropIndex('collection_collaborators_collection_accepted_index');
        });

        Schema::table('user_activity_logs', function (Blueprint $table) {
            $table->dropIndex('user_activity_logs_user_date_index');
        });
    }
};
