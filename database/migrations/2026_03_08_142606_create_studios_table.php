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
        Schema::create('studios', function (Blueprint $table) {
            $table->increments('studio_id');
            $table->string('studio_name', 200);
            $table->unsignedInteger('country_id')->nullable();
            $table->integer('founded_year')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->foreign('country_id')->references('country_id')->on('countries')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studios');
    }
};
