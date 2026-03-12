<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return; // MySQL-specific triggers — skip on other drivers
        }

        // Nomination count triggers
        DB::unprepared('DROP TRIGGER IF EXISTS trg_nomination_count_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_nomination_count_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_comment_like_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_comment_like_delete');

        DB::unprepared('
            CREATE TRIGGER trg_nomination_count_insert
            AFTER INSERT ON title_nominations
            FOR EACH ROW
            BEGIN
                UPDATE titles SET nomination_count = nomination_count + 1 WHERE title_id = NEW.title_id;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_nomination_count_delete
            AFTER DELETE ON title_nominations
            FOR EACH ROW
            BEGIN
                UPDATE titles SET nomination_count = GREATEST(nomination_count - 1, 0) WHERE title_id = OLD.title_id;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_comment_like_insert
            AFTER INSERT ON title_comment_likes
            FOR EACH ROW
            BEGIN
                UPDATE title_comments SET like_count = like_count + 1 WHERE comment_id = NEW.comment_id;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trg_comment_like_delete
            AFTER DELETE ON title_comment_likes
            FOR EACH ROW
            BEGIN
                UPDATE title_comments SET like_count = GREATEST(like_count - 1, 0) WHERE comment_id = OLD.comment_id;
            END
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trg_nomination_count_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_nomination_count_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_comment_like_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_comment_like_delete');
    }
};
