<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->smallIncrements('genre_id');
            $table->unsignedSmallInteger('tmdb_id')->unique();
            $table->string('genre_name', 100);
            $table->string('genre_name_vi', 100)->nullable();
        });

        Schema::create('title_genres', function (Blueprint $table) {
            $table->unsignedInteger('title_id');
            $table->unsignedSmallInteger('genre_id');
            $table->primary(['title_id', 'genre_id']);
            $table->foreign('title_id')->references('title_id')->on('titles')->cascadeOnDelete();
            $table->foreign('genre_id')->references('genre_id')->on('genres')->cascadeOnDelete();
            $table->index('genre_id');
        });

        // Pre-seed all known TMDB genres
        $genres = [
            // Movie genres
            ['tmdb_id' => 28,    'genre_name' => 'Action',           'genre_name_vi' => 'Hành động'],
            ['tmdb_id' => 12,    'genre_name' => 'Adventure',        'genre_name_vi' => 'Phiêu lưu'],
            ['tmdb_id' => 16,    'genre_name' => 'Animation',        'genre_name_vi' => 'Hoạt hình'],
            ['tmdb_id' => 35,    'genre_name' => 'Comedy',           'genre_name_vi' => 'Hài hước'],
            ['tmdb_id' => 80,    'genre_name' => 'Crime',            'genre_name_vi' => 'Tội phạm'],
            ['tmdb_id' => 99,    'genre_name' => 'Documentary',      'genre_name_vi' => 'Tài liệu'],
            ['tmdb_id' => 18,    'genre_name' => 'Drama',            'genre_name_vi' => 'Chính kịch'],
            ['tmdb_id' => 10751, 'genre_name' => 'Family',           'genre_name_vi' => 'Gia đình'],
            ['tmdb_id' => 14,    'genre_name' => 'Fantasy',          'genre_name_vi' => 'Kỳ ảo'],
            ['tmdb_id' => 36,    'genre_name' => 'History',          'genre_name_vi' => 'Lịch sử'],
            ['tmdb_id' => 27,    'genre_name' => 'Horror',           'genre_name_vi' => 'Kinh dị'],
            ['tmdb_id' => 10402, 'genre_name' => 'Music',            'genre_name_vi' => 'Âm nhạc'],
            ['tmdb_id' => 9648,  'genre_name' => 'Mystery',          'genre_name_vi' => 'Bí ẩn'],
            ['tmdb_id' => 10749, 'genre_name' => 'Romance',          'genre_name_vi' => 'Lãng mạn'],
            ['tmdb_id' => 878,   'genre_name' => 'Science Fiction',  'genre_name_vi' => 'Khoa học viễn tưởng'],
            ['tmdb_id' => 10770, 'genre_name' => 'TV Movie',         'genre_name_vi' => 'Phim TV'],
            ['tmdb_id' => 53,    'genre_name' => 'Thriller',         'genre_name_vi' => 'Giật gân'],
            ['tmdb_id' => 10752, 'genre_name' => 'War',              'genre_name_vi' => 'Chiến tranh'],
            ['tmdb_id' => 37,    'genre_name' => 'Western',          'genre_name_vi' => 'Cao bồi'],
            // TV-only genres
            ['tmdb_id' => 10759, 'genre_name' => 'Action & Adventure', 'genre_name_vi' => 'Hành động & Phiêu lưu'],
            ['tmdb_id' => 10762, 'genre_name' => 'Kids',             'genre_name_vi' => 'Thiếu nhi'],
            ['tmdb_id' => 10763, 'genre_name' => 'News',             'genre_name_vi' => 'Tin tức'],
            ['tmdb_id' => 10764, 'genre_name' => 'Reality',          'genre_name_vi' => 'Thực tế'],
            ['tmdb_id' => 10765, 'genre_name' => 'Sci-Fi & Fantasy', 'genre_name_vi' => 'Khoa học & Kỳ ảo'],
            ['tmdb_id' => 10766, 'genre_name' => 'Soap',             'genre_name_vi' => 'Phim dài tập'],
            ['tmdb_id' => 10767, 'genre_name' => 'Talk',             'genre_name_vi' => 'Talk show'],
            ['tmdb_id' => 10768, 'genre_name' => 'War & Politics',   'genre_name_vi' => 'Chiến tranh & Chính trị'],
        ];

        DB::table('genres')->insert($genres);
    }

    public function down(): void
    {
        Schema::dropIfExists('title_genres');
        Schema::dropIfExists('genres');
    }
};
