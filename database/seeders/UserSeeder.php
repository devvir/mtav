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

        $firstProject = Project::first();

        User::factory()->admin()->create([
            'id'        => 1,
            'firstname' => 'Test',
            'lastname'  => 'Superadmin',
            'email'     => 'superadmin@example.com',
        ]);

        User::factory()->admin()->create([
            'firstname' => 'Test',
            'lastname'  => 'Admin',
            'email'     => 'admin@example.com',
        ])->projects()->attach(Project::pluck('id'));

        User::factory()->admin()->create([
            'firstname' => '1-Project',
            'lastname'  => 'Admin',
            'email'     => 'admin.x1@example.com',
        ])->projects()->attach(Project::skip(0)->take(1)->pluck('id'));

        User::factory()->admin()->create([
            'firstname' => '2-Projects',
            'lastname'  => 'Admin',
            'email'     => 'admin.x2@example.com',
        ])->projects()->attach(Project::skip(1)->take(2)->pluck('id'));

        ($testMember = User::factory()->create([
            'firstname' => 'Test',
            'lastname'  => 'Member',
            'email'     => 'member@example.com',
        ])->asMember())->joinProject($testMember->family->project_id);

        // Example orphan User (data inconsistency: no family, no project)
        User::factory()->create([
            'firstname' => 'Test',
            'lastname'  => 'Orphan',
            'email'     => 'orphan@example.com',
            'family_id' => null,
        ]);

        User::factory()->create([
            'firstname' => 'A Regular User',
            'lastname'  => 'With a Very Long Name',
            'email'     => 'longname@example.com',
        ])->asMember()->joinProject($firstProject);

        // Example inactive User (@see DatabaseSeeder for further setup of this User)
        User::factory()->create([
            'firstname' => 'Test',
            'lastname'  => 'Inactive',
            'email'     => 'inactive@example.com',
        ])->asMember()->joinProject($firstProject)->leaveProject($firstProject);
    }
}
