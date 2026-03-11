<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->increments('badge_id');
            $table->string('slug', 100)->unique();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->string('icon_path', 500)->nullable();
            $table->enum('tier', ['BRONZE', 'SILVER', 'GOLD', 'PLATINUM'])->default('BRONZE');
            $table->string('condition_type', 50)->default('manual');
            $table->unsignedInteger('condition_value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
