<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            FamilySeeder::class,
            ProjectSeeder::class,
        ]);

        User::firstWhere('email', 'user@example.com')
            ->joinProject(Project::first());

        // Example inactive User (switch from Project #1 to #2)
        User::firstWhere('email', 'inactive@example.com')
            ->joinProject(Project::first())
            ->switchProject(Project::find(2));
    }
}
