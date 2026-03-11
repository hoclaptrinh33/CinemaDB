<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: lookup tables first, then dependent tables.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,    // 1. countries
            LanguageSeeder::class,   // 2. languages
            RoleSeeder::class,       // 3. roles (film roles)
            UserSeeder::class,       // 4. users (admin, mod, user)
            StudioSeeder::class,     // 5. studios (depends on countries)
            TitleSeeder::class,      // 6. titles + persons + relations + reviews
            BadgeSeeder::class,      // 7. gamification badges
        ]);
    }
}
