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
        $plan = $project->plan;
        $plan->update(['polygon' => [[30, 30], [770, 42], [762, 562], [38, 570]]]);
        $rect = fn ($x, $y, $w, $h) => [[$x, $y], [$x + $w, $y], [$x + $w, $y + $h], [$x, $y + $h]];

        // Block A (2 bedrooms) to the west, block B (3 bedrooms) to the east
        foreach ($plan->items()->with('unit')->where('type', 'unit')->get() as $item) {
            [$block, $number] = explode('-', $item->unit->identifier);
            $n = (int) $number - 101;
            $item->update(['polygon' => $block === 'A'
                ? $rect(70 + ($n % 3) * 78, 80 + intdiv($n, 3) * 74, 70, 64)
                : $rect(560 + ($n % 2) * 92, 80 + intdiv($n, 2) * 84, 84, 74)]);
        }

        foreach ([
            ['green', 'Plaza', [[310, 92], [505, 82], [522, 248], [318, 268]]],
            ['common', 'Salón comunal', $rect(330, 318, 150, 98)],
            ['parking', 'Estacionamiento', $rect(70, 318, 196, 120)],
            ['playground', 'Juegos', $rect(546, 300, 136, 84)],
            ['green', null, $rect(546, 400, 190, 70)],
            // Pointing in from the street
            ['entrance', 'Entrada', [[400, 446], [430, 492], [370, 492]]],
            ['street', 'Av. Italia', $rect(40, 494, 720, 56)],
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
