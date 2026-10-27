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
            'description'  => 'Cuarenta viviendas en dos bloques, construidas por ayuda mutua.',
            'organization' => 'FUCVAM',
        ]);
        $ceibos = $projects->create($superadmin, [
            'name'        => 'Cooperativa Los Ceibos',
            'description' => 'Un proyecto más chico, que todavía está sumando familias.',
        ]);

        foreach ([$acacias, $ceibos] as $project) {
            $project->memberships()->create(['user_id' => $admin->id, 'is_admin' => true]);
        }

        $this->structure($acacias, $plans, ['2 dormitorios' => 6, '3 dormitorios' => 4], $admin, $member);
        $this->structure($ceibos, $plans, ['2 dormitorios' => 3]);

        foreach ($acacias->families as $family) {
            $preferences->rank($family, $family->unitType->units->shuffle()->take(3)->pluck('id')->all());
        }

        foreach ([
            ['Asamblea general', 'Orden del día: avance de obra, cuotas y el sorteo.', 'Salón comunal, Av. Italia 3456', 9, true],
            ['Jornada de trabajo en obra', 'Turno de la mañana: encofrado del bloque B. Traer guantes.', 'Obra, Camino Carrasco 7120', 3, true],
            ['Comisión de finanzas', 'Revisión del balance trimestral.', 'Sede de FUCVAM, Bvar. Artigas 1424', 16, false],
            ['Visita al terreno', 'Recorrida con el arquitecto por los dos bloques.', 'Obra, Camino Carrasco 7120', -12, false],
        ] as [$title, $description, $location, $inDays, $rsvp]) {
            Event::factory()->create([
                'project_id' => $acacias->id, 'creator_id' => $admin->id, 'title' => $title, 'description' => $description,
                'location'   => $location, 'start_date' => now()->addDays($inDays)->setTime(10, 0), 'allows_rsvp' => $rsvp,
            ]);
        }
        $acacias->lottery->update(['start_date' => now()->addMonth()]);
    }

    /**
     * Unit types with as many units as families; each family gets two people.
     *
     * @param array<string, int> $types  name => units (and families)
     */
    private function structure(Project $project, PlanService $plans, array $types, User ...$people): void
    {
        foreach (array_keys($types) as $index => $name) {
            $type = UnitType::factory()->create(['project_id' => $project->id, 'name' => $name, 'description' => null]);
            $block = chr(ord('A') + $index);

            for ($i = 1; $i <= $types[$name]; $i++) {
                $unit = Unit::factory()->create([
                    'project_id' => $project->id, 'unit_type_id' => $type->id, 'identifier' => "{$block}-" . (100 + $i),
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
