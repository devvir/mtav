<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Family;
use App\Models\Project;
use App\Models\UnitType;
use App\Models\User;
use App\Services\ForumService;
use App\Services\InvitationService;
use App\Services\Lottery\PreferencesService;
use App\Services\LotteryService;
use App\Services\ProjectService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A demo platform, to try every feature quickly and to show it: every account's password
 * is "password", or DEMO_PASSWORD where set (always, on a public site: there it refuses to
 * seed without one).
 *
 *  - superadmin@example.com   platform superadmin, the deployment's owner
 *  - admin@example.com        admin of the three projects (in a family of Las Acacias)
 *  - member@example.com       in a family of Las Acacias, and in one of Los Ceibos
 *
 * Three projects, each at another stage:
 *  - Las Acacias: 32 units of four types (two blocks and four houses, drawn on its site),
 *    its families ranking them, its lottery three weeks away; events people answered,
 *    files of every kind (DemoMedia), a forum
 *  - Los Ceibos: 18 units of three types, its lottery run two weeks ago (by the solver,
 *    as any run): each family has its unit, and the run its record
 *  - Barrio Sur: just started by its admin, waiting for approval: a few families,
 *    invitations out, its units not yet placed on the plan
 */
class DatabaseSeeder extends Seeder
{
    public function __construct(
        private ProjectService $projects,
        private PreferencesService $preferences,
        private LotteryService $lottery,
        private ForumService $forum,
        private InvitationService $invitations,
        private DemoPeople $people,
        private DemoSites $sites,
    ) {
    }

    public function run(): void
    {
        if (app()->isProduction() && ! config('app.demo_password')) {
            throw new RuntimeException('Set DEMO_PASSWORD: a public site never seeds demo accounts with "password".');
        }

        // Nobody watches a seed: nothing is broadcast, and what would wait on the queue (a
        // lottery run, its reveal) is done here and now. The same demo every time.
        config(['broadcasting.default' => 'null', 'queue.default' => 'sync']);
        mt_srand(2026);

        $superadmin = $this->people->person('Rodríguez', ['email' => 'superadmin@example.com', 'firstname' => 'Sofía']);
        $superadmin->forceFill(['is_superadmin' => true, 'is_owner' => true])->save();
        $admin = $this->people->person('Silva', ['email' => 'admin@example.com', 'firstname' => 'Andrés']);
        $member = $this->people->person('Pereira', ['email' => 'member@example.com', 'firstname' => 'María', 'phone' => '099 123 456']);

        $this->acacias($superadmin, $admin, $member);
        $this->ceibos($superadmin, $admin, $member);
        $this->barrioSur($admin);

        // The seed is no one's doing: the activity log starts empty
        Activity::query()->delete();
    }

    private function acacias(User $superadmin, User $admin, User $member): void
    {
        $project = $this->at(now()->subMonths(8), fn () => $this->projects->create($superadmin, [
            'name'         => 'Cooperativa Las Acacias',
            'description'  => 'Treinta y dos viviendas por ayuda mutua: dos bloques y cuatro casas alrededor de una plaza, en Malvín Norte.',
            'organization' => 'FUCVAM',
        ]));
        $project->memberships()->create(['user_id' => $admin->id, 'is_admin' => true]);

        $types = $this->types($project, [
            '1 dormitorio'  => 'Unos 45 m², para una o dos personas.',
            '2 dormitorios' => 'Unos 60 m², con patio de servicio.',
            '3 dormitorios' => 'Dúplex de unos 75 m², con terraza.',
            '4 dormitorios' => 'Casas de unos 90 m², con jardín al fondo.',
        ]);
        $this->sites->acacias($project, $types);
        $this->families($project, $types, [1 => $member, 2 => $admin]);

        // Another admin from among its people: the project's committee
        $project->memberships()->whereNotIn('user_id', [$admin->id, $member->id])->first()->update(['is_admin' => true]);

        // Most families ranked their units already, some only their first few, a few none
        foreach ($project->families()->with('unitType.units')->get() as $index => $family) {
            $units = $family->unitType->units->shuffle()->pluck('id');
            $ranked = $family->members()->whereKey($member->id)->exists() ? $units : match ($index % 7) {
                3       => $units->take(2),
                5       => collect(),
                default => $units,
            };
            if ($ranked->isNotEmpty()) {
                $this->preferences->rank($family, $ranked->all());
            }
        }

        $this->events($project, $admin, [
            ['Asamblea general', 'Orden del día: avance de obra, cuotas y el sorteo de las viviendas.', 'Salón comunal, Av. Italia 3456', EventType::ONSITE, 9, 19, true],
            ['Jornada de trabajo en obra', 'Turno de la mañana: encofrado del bloque B. Traer guantes y agua.', 'Obra, Camino Carrasco 7120', EventType::ONSITE, 3, 8, true],
            ['Comisión de finanzas', 'Revisión del balance trimestral, abierta a quien quiera sumarse.', 'https://meet.jit.si/las-acacias-finanzas', EventType::ONLINE, 16, 20, false],
            ['Taller de presupuesto familiar', 'Cómo organizar las cuotas y el ahorro mientras dura la obra.', 'Salón comunal, Av. Italia 3456', EventType::ONSITE, 23, 18, true],
            ['Visita al terreno', 'Recorrida con el arquitecto por los dos bloques y las casas.', 'Obra, Camino Carrasco 7120', EventType::ONSITE, -12, 10, true],
            ['Asamblea extraordinaria', 'Se aprobó el reglamento interno y el calendario de jornadas.', 'Salón comunal, Av. Italia 3456', EventType::ONSITE, -30, 19, true],
        ]);
        $project->lottery->update([
            'start_date'  => now()->addWeeks(3)->setTime(19, 0),
            'description' => 'El sorteo de las 32 viviendas, en el salón comunal y transmitido para quien no pueda venir.',
        ]);

        $people = $project->members()->whereNotIn('users.id', [$admin->id, $member->id])->get();
        app(DemoMedia::class)->share($project, $admin, $member, ...$people->take(3));

        $this->topics($project, [
            ['Bienvenidas y bienvenidos al foro', $admin, 'Este es el lugar para las conversaciones del proyecto que no entran en una asamblea. Para lo urgente, sigan usando el teléfono de la comisión.', 40, [
                [$people[0], '¡Buenísimo! Así no se pierde todo en el grupo de WhatsApp.'],
                [$member, 'Gracias. ¿Las actas van a estar acá también?'],
                [$admin, 'Las actas quedan en Archivos; acá conversamos sobre ellas.'],
            ], true, false],
            ['¿Quién se suma al turno del sábado?', $people[1], 'Faltan dos personas para el encofrado del bloque B. Empezamos a las 8.', 5, [
                [$people[2], 'Yo voy, llevo la mezcladora chica.'],
                [$member, 'Cuenten conmigo, llego 8:30.'],
                [$people[3], 'Esta semana no puedo, el próximo sí.'],
                [$people[1], 'Listo, quedamos completos. ¡Gracias!'],
            ], false, false],
            ['Compra conjunta de aberturas', $people[4], 'Si compramos las ventanas juntos nos hacen un 15 % de descuento. ¿Lo llevamos a la asamblea?', 12, [
                [$admin, 'Sí, lo agrego al orden del día. Traé el presupuesto si podés.'],
                [$people[5], 'Ojo con los plazos de entrega, a la cooperativa de al lado le demoraron tres meses.'],
            ], false, false],
            ['Horario de la jornada del 12', $admin, 'La jornada del 12 se pasa a las 9 por la lluvia del viernes.', 20, [
                [$people[6], 'Perfecto, gracias por avisar.'],
            ], false, true],
        ]);

        $this->invite($project, $admin, [['Rosa', 'Medina', null], ['Hugo', 'Clavijo', $project->families()->first()]]);
    }

    private function ceibos(User $superadmin, User $admin, User $member): void
    {
        $project = $this->at(now()->subYear(), fn () => $this->projects->create($superadmin, [
            'name'         => 'Cooperativa Los Ceibos',
            'description'  => 'Dieciocho viviendas en dos bloques sobre un patio común, en Piedras Blancas. Ya sorteadas: cada familia sabe cuál le tocó.',
            'organization' => 'FUCVAM',
        ]));
        $project->memberships()->create(['user_id' => $admin->id, 'is_admin' => true]);

        $types = $this->types($project, [
            '2 dormitorios'            => 'Unos 60 m², en el bloque norte.',
            '3 dormitorios'            => 'Unos 75 m², en los pisos altos del bloque sur.',
            '2 dormitorios accesibles' => 'En planta baja, sin escalones, con baño adaptado.',
        ]);
        $this->sites->ceibos($project, $types);
        $this->families($project, $types, [1 => $member]);

        foreach ($project->families()->with('unitType.units')->get() as $family) {
            $this->preferences->rank($family, $family->unitType->units->shuffle()->pluck('id')->all());
        }

        $this->events($project, $admin, [
            ['Asamblea de cierre de obra', 'Últimos detalles antes de la entrega de llaves.', 'Salón de usos múltiples', EventType::ONSITE, 10, 19, true],
            ['Entrega de llaves', 'Cada familia recibe las llaves de su vivienda. Traer cédula.', 'Cooperativa Los Ceibos, Camino Maldonado 6200', EventType::ONSITE, 45, 10, true],
            ['Asamblea previa al sorteo', 'Se revisaron las preferencias de todas las familias.', 'Salón de usos múltiples', EventType::ONSITE, -21, 19, false],
        ]);

        // The lottery, run two weeks ago, as its admin would: the solver decides
        $when = now()->subDays(14)->setTime(19, 0);
        $project->lottery->update(['start_date' => $when, 'description' => 'El sorteo de las 18 viviendas, en el salón de usos múltiples.']);
        $this->at($when->copy()->addMinutes(10), fn () => $this->lottery->run($project, $admin));

        $people = $project->members()->whereNotIn('users.id', [$admin->id, $member->id])->get();
        $this->topics($project, [
            ['¡Ya sabemos cuál nos tocó!', $people[0], 'Felicitaciones a todas las familias. ¿Armamos una jornada para conocer las viviendas por dentro?', 13, [
                [$member, '¡Qué emoción! Nosotros quedamos en el bloque sur.'],
                [$people[1], 'Me sumo a la jornada, propongo un sábado de mañana.'],
                [$admin, 'La agendamos para la asamblea de cierre de obra.'],
            ], true, false],
            ['Mudanzas: coordinemos fechas', $people[2], 'Para no cruzarnos con los camiones, anotemos acá qué semana pensamos mudarnos.', 6, [
                [$people[3], 'Nosotros la primera semana después de la entrega.'],
                [$people[4], 'La segunda, si se puede.'],
            ], false, false],
        ]);
    }

    private function barrioSur(User $admin): void
    {
        // Started by its admin (not a superadmin): it waits for approval
        $project = $this->at(now()->subDays(10), fn () => $this->projects->create($admin, [
            'name'        => 'Cooperativa Barrio Sur',
            'description' => 'Un proyecto que recién empieza: diez viviendas cerca de la rambla, sumando familias.',
        ]));
        $this->at(now()->subDays(2), fn () => $this->projects->requestApproval($project, $admin));

        $types = $this->types($project, [
            '2 dormitorios' => null,
            '3 dormitorios' => null,
        ]);
        $this->sites->undrawn($project, $types[0], 'A', 6);
        $this->sites->undrawn($project, $types[1], 'B', 4);
        foreach ([0, 0, 1, 1] as $type) {
            $this->people->family($project, $types[$type]);
        }

        $this->invite($project, $admin, [
            ['Lorena', 'Varela', null],
            ['Germán', 'Ocampo', null],
            ['Inés', 'Duarte', $project->families()->first()],
        ]);
    }

    /**
     * @param array<string, ?string> $types  name => description
     *
     * @return list<UnitType>
     */
    private function types(Project $project, array $types): array
    {
        return array_map(
            fn ($name) => UnitType::factory()->create(['project_id' => $project->id, 'name' => $name, 'description' => $types[$name]]),
            array_keys($types),
        );
    }

    /**
     * As many families as each type has units; the people given each in a family of the
     * type at that index
     *
     * @param list<UnitType>   $types
     * @param array<int, User> $known  type index => person
     */
    private function families(Project $project, array $types, array $known = []): void
    {
        foreach ($types as $index => $type) {
            foreach (range(1, $type->units()->count()) as $n) {
                $this->people->family($project, $type, $n === 1 ? ($known[$index] ?? null) : null);
            }
        }
    }

    /**
     * Events, in days from now (past ones too), and the answers people gave
     *
     * @param array<array{0: string, 1: string, 2: string, 3: EventType, 4: int, 5: int, 6: bool}> $events
     */
    private function events(Project $project, User $creator, array $events): void
    {
        $people = $project->members()->pluck('users.id');
        foreach ($events as [$title, $description, $location, $type, $inDays, $hour, $rsvp]) {
            $event = Event::factory()->create([
                'project_id'  => $project->id, 'creator_id' => $creator->id, 'type' => $type, 'title' => $title,
                'description' => $description, 'location' => $location, 'allows_rsvp' => $rsvp, 'is_published' => true,
                'start_date'  => now()->addDays($inDays)->setTime($hour, 0), 'end_date' => now()->addDays($inDays)->setTime($hour + 2, 0),
            ]);
            if ($rsvp) {
                foreach ($people->filter(fn () => mt_rand(1, 10) <= 6) as $id) {
                    $event->rsvps()->attach($id, ['attending' => mt_rand(1, 10) <= 8]);
                }
            }
        }
    }

    /**
     * Forum topics, opened days ago, their replies over the hours after
     *
     * @param array<array{0: string, 1: User, 2: string, 3: int, 4: array<array{0: User, 1: string}>, 5: bool, 6: bool}> $topics
     */
    private function topics(Project $project, array $topics): void
    {
        foreach ($topics as [$title, $author, $body, $daysAgo, $replies, $pinned, $closed]) {
            $when = now()->subDays($daysAgo)->setTime(20, 15);
            $topic = $this->at($when, fn () => $this->forum->open($project, $author, $title, $body));
            foreach ($replies as $index => [$person, $text]) {
                $this->at($when->copy()->addHours(2 + $index * 5), fn () => $this->forum->reply($topic, $person, $text));
            }
            $topic->update(['is_pinned' => $pinned, 'is_closed' => $closed]);
        }
    }

    /** @param array<array{0: string, 1: string, 2: ?Family}> $people  firstname, lastname, family */
    private function invite(Project $project, User $admin, array $people): void
    {
        foreach ($people as [$firstname, $lastname, $family]) {
            $this->invitations->invite($project, $admin, [
                'email'     => Str::slug($firstname, '') . '.' . Str::slug($lastname, '') . '@example.com',
                'firstname' => $firstname,
                'lastname'  => $lastname,
            ], $family, false);
        }
    }

    /**
     * What happens, as if it happened then
     *
     * @template T
     *
     * @param callable(): T $do
     *
     * @return T
     */
    private function at(Carbon $when, callable $do): mixed
    {
        Carbon::setTestNow($when);
        try {
            return $do();
        } finally {
            Carbon::setTestNow();
        }
    }
}
