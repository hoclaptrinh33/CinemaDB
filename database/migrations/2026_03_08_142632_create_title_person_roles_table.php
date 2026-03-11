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
        Schema::create('title_person_roles', function (Blueprint $table) {
            $table->unsignedInteger('title_id');
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('role_id');
            $table->string('character_name', 200)->nullable();
            $table->integer('cast_order')->nullable();
            $table->primary(['title_id', 'person_id', 'role_id']);
            $table->foreign('title_id')->references('title_id')->on('titles')->cascadeOnDelete();
            $table->foreign('person_id')->references('person_id')->on('persons')->cascadeOnDelete();
            $table->foreign('role_id')->references('role_id')->on('roles');
            // Indexes
            $table->index('title_id', 'idx_tpr_title');
            $table->index('person_id', 'idx_tpr_person');
            $table->index('role_id', 'idx_tpr_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_person_roles');
    }
};
