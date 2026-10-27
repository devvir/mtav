<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Family;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use App\Services\Lottery\PreferencesService;
use App\Services\PlanService;
use App\Services\ProjectService;
use Illuminate\Database\Seeder;

/**
 * A demo platform for development: every account's password is "password".
 *
 *  - superadmin@example.com   platform superadmin
 *  - admin@example.com        admin of both projects (and in a family of the first)
 *  - member@example.com       member of a family of the first project
 */
class DatabaseSeeder extends Seeder
{
    public function run(ProjectService $projects, PlanService $plans, PreferencesService $preferences): void
    {
        $superadmin = User::factory()->superadmin()->create([
            'email' => 'superadmin@example.com', 'firstname' => 'Sofía', 'lastname' => 'Superadmin',
        ]);
        $admin = User::factory()->create(['email' => 'admin@example.com', 'firstname' => 'Andrés', 'lastname' => 'Admin']);
        $member = User::factory()->create(['email' => 'member@example.com', 'firstname' => 'María', 'lastname' => 'Member']);

        $acacias = $projects->create($superadmin, [
            'name'         => 'Cooperativa Las Acacias',
            'description'  => 'Forty dwellings in two blocks, built by mutual aid.',
            'organization' => 'FUCVAM',
        ]);
        $ceibos = $projects->create($superadmin, [
            'name'        => 'Cooperativa Los Ceibos',
            'description' => 'A smaller project, still loading its families.',
        ]);

        foreach ([$acacias, $ceibos] as $project) {
            $project->memberships()->create(['user_id' => $admin->id, 'is_admin' => true]);
        }

        $this->structure($acacias, $plans, ['2 bedrooms' => 6, '3 bedrooms' => 4], $admin, $member);
        $this->structure($ceibos, $plans, ['2 bedrooms' => 3]);

        foreach ($acacias->families as $family) {
            $preferences->rank($family, $family->unitType->units->shuffle()->take(3)->pluck('id')->all());
        }

        Event::factory()->count(3)->create(['project_id' => $acacias->id, 'creator_id' => $admin->id]);
        Event::factory()->create([
            'project_id' => $acacias->id, 'creator_id' => $admin->id, 'title' => 'Asamblea general', 'allows_rsvp' => true,
        ]);
        $acacias->lottery->update(['start_date' => now()->addMonth()]);
    }

    /**
     * Unit types with as many units as families; each family gets two people.
     *
     * @param array<string, int> $types  name => units (and families)
     */
    private function structure(Project $project, PlanService $plans, array $types, User ...$people): void
    {
        foreach ($types as $name => $count) {
            $type = UnitType::factory()->create(['project_id' => $project->id, 'name' => $name]);

            for ($i = 1; $i <= $count; $i++) {
                $unit = Unit::factory()->create([
                    'project_id' => $project->id, 'unit_type_id' => $type->id, 'identifier' => "{$type->id}-{$i}0{$i}",
                ]);
                $plans->place($unit);

                $family = Family::factory()->create(['project_id' => $project->id, 'unit_type_id' => $type->id]);

                foreach ([array_shift($people) ?? User::factory()->create(), User::factory()->create()] as $person) {
                    $project->memberships()->updateOrCreate(['user_id' => $person->id], ['family_id' => $family->id]);
                }
            }
        }
    }
}
