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
        Schema::create('title_audit_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->unsignedInteger('title_id')->nullable();
            $table->enum('action_type', ['INSERT', 'UPDATE', 'DELETE'])->nullable();
            $table->string('old_title', 300)->nullable();
            $table->string('new_title', 300)->nullable();
            $table->timestamp('action_date')->useCurrent();
            $table->string('action_user', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_audit_log');
    }
};
