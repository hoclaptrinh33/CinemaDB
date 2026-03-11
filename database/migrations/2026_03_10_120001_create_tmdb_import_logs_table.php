<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tmdb_import_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tmdb_id');
            $table->string('type', 10);   // movie | tv
            $table->string('status', 20)->default('pending'); // pending | processing | done | failed
            $table->string('title_name', 300)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tmdb_id', 'type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tmdb_import_logs');
    }
};
