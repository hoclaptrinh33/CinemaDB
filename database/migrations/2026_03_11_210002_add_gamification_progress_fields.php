<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('current_streak_days')->default(0)->after('reputation');
            $table->unsignedSmallInteger('longest_streak_days')->default(0)->after('current_streak_days');
            $table->date('last_seen_on')->nullable()->after('longest_streak_days');
            $table->date('last_daily_rewarded_on')->nullable()->after('last_seen_on');
        });

        Schema::table('user_badges', function (Blueprint $table) {
            $table->unsignedInteger('earned_reputation_bonus')->default(0)->after('earned_at');
            $table->unsignedInteger('earned_snapshot_value')->nullable()->after('earned_reputation_bonus');
        });
    }

    public function down(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->dropColumn(['earned_reputation_bonus', 'earned_snapshot_value']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'current_streak_days',
                'longest_streak_days',
                'last_seen_on',
                'last_daily_rewarded_on',
            ]);
        });
    }
};
