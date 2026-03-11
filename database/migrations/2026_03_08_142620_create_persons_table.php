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
        Schema::create('persons', function (Blueprint $table) {
            $table->increments('person_id');
            $table->string('full_name', 200);
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->unsignedInteger('country_id')->nullable();
            $table->text('biography')->nullable();
            $table->string('profile_path', 500)->nullable();
            $table->foreign('country_id')->references('country_id')->on('countries')->nullOnDelete();
            $table->index('full_name', 'idx_persons_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
