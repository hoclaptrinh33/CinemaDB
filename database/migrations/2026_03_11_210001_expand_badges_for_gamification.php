<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE badges MODIFY tier ENUM('WOOD','IRON','BRONZE','SILVER','GOLD','PLATINUM','DIAMOND') NOT NULL DEFAULT 'BRONZE'");

        Schema::table('badges', function (Blueprint $table) {
            $table->string('category', 50)->default('general')->after('tier');
            $table->string('badge_family', 100)->nullable()->after('category');
            $table->unsignedTinyInteger('badge_stage')->default(1)->after('badge_family');
            $table->string('rarity_tier', 20)->default('COMMON')->after('badge_stage');
            $table->string('frame_style', 50)->default('plain')->after('rarity_tier');
            $table->unsignedInteger('sort_order')->default(0)->after('frame_style');
            $table->unsignedInteger('earned_users_count')->default(0)->after('condition_value');
            $table->decimal('earned_users_percent', 6, 2)->default(0)->after('earned_users_count');
        });
    }

    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'badge_family',
                'badge_stage',
                'rarity_tier',
                'frame_style',
                'sort_order',
                'earned_users_count',
                'earned_users_percent',
            ]);
        });

        DB::statement("ALTER TABLE badges MODIFY tier ENUM('BRONZE','SILVER','GOLD','PLATINUM') NOT NULL DEFAULT 'BRONZE'");
    }
};
