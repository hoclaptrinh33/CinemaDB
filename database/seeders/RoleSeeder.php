<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_name' => 'Actor'],
            ['role_name' => 'Director'],
            ['role_name' => 'Writer'],
            ['role_name' => 'Producer'],
            ['role_name' => 'Cinematographer'],
            ['role_name' => 'Composer'],
            ['role_name' => 'Editor'],
        ];

        DB::table('roles')->insert($roles);
    }
}
