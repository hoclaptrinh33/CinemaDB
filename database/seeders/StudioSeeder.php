<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudioSeeder extends Seeder
{
    public function run(): void
    {
        // country_id references: US=1, GB=2, FR=3, DE=4, IT=5, JP=7, KR=8, AU=11
        $studios = [
            [
                'studio_name'  => 'Warner Bros. Pictures',
                'country_id'   => 1, // US
                'founded_year' => 1923,
                'website_url'  => 'https://www.warnerbros.com',
            ],
            [
                'studio_name'  => 'Universal Pictures',
                'country_id'   => 1, // US
                'founded_year' => 1912,
                'website_url'  => 'https://www.universalpictures.com',
            ],
            [
                'studio_name'  => 'Paramount Pictures',
                'country_id'   => 1, // US
                'founded_year' => 1912,
                'website_url'  => 'https://www.paramount.com',
            ],
            [
                'studio_name'  => 'Walt Disney Pictures',
                'country_id'   => 1, // US
                'founded_year' => 1923,
                'website_url'  => 'https://www.disney.com',
            ],
            [
                'studio_name'  => 'Sony Pictures Entertainment',
                'country_id'   => 1, // US
                'founded_year' => 1987,
                'website_url'  => 'https://www.sonypictures.com',
            ],
            [
                'studio_name'  => '20th Century Studios',
                'country_id'   => 1, // US
                'founded_year' => 1935,
                'website_url'  => 'https://www.20thcenturystudios.com',
            ],
            [
                'studio_name'  => 'Working Title Films',
                'country_id'   => 2, // GB
                'founded_year' => 1983,
                'website_url'  => 'https://www.workingtitlefilms.com',
            ],
            [
                'studio_name'  => 'Toho Co., Ltd.',
                'country_id'   => 7, // JP
                'founded_year' => 1932,
                'website_url'  => 'https://www.toho.co.jp',
            ],
            [
                'studio_name'  => 'CJ ENM',
                'country_id'   => 8, // KR
                'founded_year' => 1994,
                'website_url'  => 'https://www.cjenm.com',
            ],
            [
                'studio_name'  => 'Pathé',
                'country_id'   => 3, // FR
                'founded_year' => 1896,
                'website_url'  => 'https://www.pathe.com',
            ],
        ];

        DB::table('studios')->insert($studios);
    }
}
