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
use RuntimeException;

/**
 * A demo platform: every account's password is "password", or DEMO_PASSWORD where set
 * (always, on a public site: there it refuses to seed without one).
 *
 *  - superadmin@example.com   platform superadmin, the deployment's owner
 *  - admin@example.com        admin of both projects (and in a family of the first)
 *  - member@example.com       member of a family of the first project
 *
 * The first project also shares files of every kind (DemoMedia).
 */
class DatabaseSeeder extends Seeder
{
    public function run(ProjectService $projects, PlanService $plans, PreferencesService $preferences): void
    {
        if (app()->isProduction() && ! config('app.demo_password')) {
            throw new RuntimeException('Set DEMO_PASSWORD: a public site never seeds demo accounts with "password".');
        }

        $superadmin = User::factory()->superadmin()->create([
            'email' => 'superadmin@example.com', 'firstname' => 'Sofía', 'lastname' => 'Superadmin',
        ]);
        $superadmin->forceFill(['is_owner' => true])->save();
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

        $this->site($acacias);

        $media = app(DemoMedia::class);
        $media->share($acacias, $admin, $member, ...$acacias->members()->whereNotIn('users.id', [$admin->id, $member->id])->take(3)->get());
    }

    /**
     * The first project's site: its two blocks around a square with trees, the street and
     * its entrance, parking, a playground and the common hall, on irregular land
     */
    private function site(Project $project): void
    {
        // A real site: not a rectangle, the avenue along its south side, an inner street
        $plan = $project->plan;
        $plan->update([
            'width'   => 1000,
            'height'  => 720,
            'polygon' => [[40, 70], [610, 30], [960, 120], [945, 600], [520, 650], [55, 600]],
        ]);
        $rect = fn ($x, $y, $w, $h) => [[$x, $y], [$x + $w, $y], [$x + $w, $y + $h], [$x, $y + $h]];

        // Block A (2 bedrooms): a building of three floors, two units on each; block B (3
        // bedrooms): two floors, one unit on each
        foreach ($plan->items()->with('unit')->where('type', 'unit')->get() as $item) {
            [$block, $number] = explode('-', $item->unit->identifier);
            $n = (int) $number - 101;
            [$polygon, $floor] = $block === 'A'
                ? [$rect(110 + ($n % 2) * 96, 130, 92, 84), intdiv($n, 2)]
                : [$rect(660 + ($n % 2) * 104, 150, 100, 92), intdiv($n, 2)];
            $item->update(['polygon' => $polygon, 'floor' => $floor]);
        }

        foreach ([
            ['green', 'Plaza', [[360, 95], [560, 80], [585, 270], [372, 292]]],
            ['green', null, [[90, 410], [440, 395], [455, 575], [100, 565]]],
            ['green', null, [[790, 400], [930, 395], [925, 560], [800, 575]]],
            // Green on both sides of the street's north, and around the blocks
            ['green', null, [[70, 240], [340, 232], [345, 318], [72, 322]]],
            ['green', null, [[620, 262], [925, 252], [928, 318], [624, 322]]],
            ['green', null, [[70, 100], [100, 98], [104, 230], [74, 232]]],
            ['playground', 'Juegos', $rect(250, 440, 140, 92)],
            ['common', 'Salón comunal', $rect(810, 425, 100, 110)],
            ['parking', 'Estacionamiento', $rect(560, 405, 200, 120)],
            ['street', null, [[60, 330], [940, 330], [940, 372], [60, 372]]],
            ['street', null, $rect(478, 372, 44, 270)],
            // Pointing in from the avenue
            ['entrance', 'Entrada', [[500, 598], [530, 642], [470, 642]]],
            ['street', 'Av. Italia', [[20, 650], [980, 600], [985, 652], [25, 702]]],
        ] as [$type, $name, $polygon]) {
            $plan->items()->create(['type' => $type, 'name' => $name, 'floor' => 0, 'polygon' => $polygon]);
        }
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
