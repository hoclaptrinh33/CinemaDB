<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->string('title_name_vi', 300)->nullable()->after('title_name');
            $table->text('description_vi')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->dropColumn(['title_name_vi', 'description_vi']);
        });
    }
};
