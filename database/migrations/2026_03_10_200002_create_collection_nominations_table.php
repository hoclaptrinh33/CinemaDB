<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_nominations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('collection_id');
            $table->unsignedBigInteger('user_id');
            $table->date('nominated_date');

            $table->foreign('collection_id')
                ->references('collection_id')
                ->on('collections')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->unique(['user_id', 'collection_id', 'nominated_date'], 'uq_coll_nom_per_day');
            $table->index(['user_id', 'nominated_date']);
            $table->index('collection_id');
        });

        // Triggers to maintain collections.nomination_count
        DB::unprepared('DROP TRIGGER IF EXISTS trg_collection_nom_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_collection_nom_delete');

        DB::unprepared('
            CREATE TRIGGER trg_collection_nom_insert
            AFTER INSERT ON collection_nominations
            FOR EACH ROW
            BEGIN
                UPDATE collections SET nomination_count = nomination_count + 1 WHERE collection_id = NEW.collection_id;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_collection_nom_delete
            AFTER DELETE ON collection_nominations
            FOR EACH ROW
            BEGIN
                UPDATE collections SET nomination_count = GREATEST(nomination_count - 1, 0) WHERE collection_id = OLD.collection_id;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_collection_nom_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_collection_nom_delete');
        Schema::dropIfExists('collection_nominations');
    }
};
