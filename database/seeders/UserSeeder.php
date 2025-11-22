<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * This seeder is supposed to run only once.
         */
        if (User::whereEmail('superadmin@example.com')->exists()) {
            return;
        }

        $projects = Project::all();

        Admin::factory()->create([
            'id'        => 1,
            'firstname' => 'Test',
            'lastname'  => 'Superadmin',
            'email'     => 'superadmin@example.com',
        ]);

        Admin::factory()->create([
            'firstname' => 'Test',
            'lastname'  => 'Admin',
            'email'     => 'admin@example.com',
        ])->projects()->attach($projects);

        Admin::factory()->inProject($projects[0])->create([
            'firstname' => '1-Project',
            'lastname'  => 'Admin',
            'email'     => 'admin.x1@example.com',
        ]);

        Admin::factory()->create([
            'firstname' => '2-Projects',
            'lastname'  => 'Admin',
            'email'     => 'admin.x2@example.com',
        ])->projects()->attach($projects->slice(1, 2));

        Member::factory()->inProject($projects[0])->create([
            'firstname' => 'Test',
            'lastname'  => 'Member',
            'email'     => 'member@example.com',
            'family_id' => 1,
        ]);

        // Example orphan User (data inconsistency: no family, no project)
        Member::factory()->create([
            'firstname' => 'Test',
            'lastname'  => 'Orphan',
            'email'     => 'orphan@example.com',
            'family_id' => null,
        ]);

        Member::factory()->inProject($projects[0])->create([
            'firstname' => 'A Regular User',
            'lastname'  => 'With a Very Long Name',
            'email'     => 'longname@example.com',
            'family_id' => 1,
        ]);

        // Example inactive User (@see DatabaseSeeder for further setup of this User)
        Member::factory()->inProject($projects[0])->create([
            'firstname' => 'Test',
            'lastname'  => 'Inactive',
            'email'     => 'inactive@example.com',
            'family_id' => 1,
        ])->leaveProject();
    }
}
