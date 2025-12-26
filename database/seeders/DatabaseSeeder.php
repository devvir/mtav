<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProjectSeeder::class,
            UnitTypeSeeder::class,
            UnitSeeder::class,
            FamilySeeder::class,
            UserSeeder::class,
            MemberSeeder::class,
            MediaSeeder::class,
            EventSeeder::class,
            LogSeeder::class,
        ]);
    }
}
