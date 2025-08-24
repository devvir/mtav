<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::count()) {
            return;
        }

        User::factory()->admin()->create([
            'id'         => 1,
            'firstname'  => 'Test',
            'lastname'   => 'Superadmin',
            'email'      => 'superadmin@example.com',
        ]);

        User::factory()->admin()->create([
            'firstname'  => 'Test',
            'lastname'   => 'Admin',
            'email'      => 'admin@example.com',
        ]);

        User::factory()->withFamily()->create([
            'firstname'  => 'Test',
            'lastname'   => 'User',
            'email'      => 'user@example.com',
        ]);

        // Example orphan User (data inconsistency: no family, no project)
        User::factory()->create([
            'firstname'  => 'Orphan',
            'lastname'   => 'User',
            'email'      => 'orphan@example.com',
        ]);

        // Example inactive User (@see DatabaseSeeder for further setup of this User)
        User::factory()->withFamily()->create([
            'firstname'  => 'Inactive',
            'lastname'   => 'User',
            'email'      => 'inactive@example.com',
        ]);
    }
}
