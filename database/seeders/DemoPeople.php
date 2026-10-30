<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\Project;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * People for the demo, as a Uruguayan cooperative has them: their names, an address at
 * example.com (mail never reaches it), often a phone, sometimes their ID number. Families
 * of one to three of them, named after their surnames.
 */
class DemoPeople
{
    private const WOMEN = [
        'Lucía', 'Martina', 'Sofía', 'Valentina', 'Camila', 'Florencia', 'Carolina', 'Natalia',
        'Ana', 'Laura', 'Mariana', 'Gabriela', 'Paula', 'Victoria', 'Agustina', 'Romina',
        'Silvana', 'Claudia', 'Patricia', 'Graciela', 'Cecilia', 'Andrea', 'Lorena', 'Verónica',
        'Rosario', 'Inés', 'Magdalena', 'Josefina', 'Belén', 'Noelia',
    ];

    private const MEN = [
        'Juan', 'Martín', 'Diego', 'Pablo', 'Santiago', 'Nicolás', 'Federico', 'Gonzalo',
        'Matías', 'Sebastián', 'Rodrigo', 'Gustavo', 'Marcelo', 'Fernando', 'Leonardo', 'Bruno',
        'Facundo', 'Joaquín', 'Ignacio', 'Álvaro', 'Gastón', 'Mauricio', 'Washington', 'Néstor',
        'Ricardo', 'Hugo', 'Emiliano', 'Agustín', 'Germán', 'Felipe',
    ];

    private const SURNAMES = [
        'Rodríguez', 'González', 'Fernández', 'García', 'López', 'Martínez', 'Pérez', 'Sosa',
        'Silva', 'Pereira', 'Álvarez', 'Gómez', 'Díaz', 'Ramos', 'Suárez', 'Acosta', 'Benítez',
        'Méndez', 'Castro', 'Núñez', 'Cabrera', 'Olivera', 'De León', 'Techera', 'Bentancor',
        'Píriz', 'Cardozo', 'Viera', 'Ferreira', 'Machado', 'Correa', 'Da Silva', 'Morales',
        'Rosa', 'Vázquez', 'Sánchez', 'Romero', 'Hernández', 'Medina', 'Barrios', 'Ríos',
        'Figueroa', 'Larrosa', 'Clavijo', 'Rocha', 'Sena', 'Ocampo', 'Duarte', 'Montero', 'Varela',
    ];

    /** @var array<string, true> */
    private array $emails = [];

    /** @var array<string, true> "project:name" */
    private array $families = [];

    private ?string $password = null;

    public function person(string $lastname, array $attributes = []): User
    {
        $firstname = $this->pick(mt_rand(0, 1) ? self::WOMEN : self::MEN);

        return User::factory()->create([
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'email'     => $this->email($firstname, $lastname),
            'phone'     => mt_rand(1, 10) <= 7 ? sprintf('09%d %03d %03d', mt_rand(1, 9), mt_rand(0, 999), mt_rand(0, 999)) : null,
            'legal_id'  => mt_rand(1, 10) <= 4 ? $this->legalId() : null,
            // Hashed once: a hundred accounts, each hashed anew, take a while on a server
            'password' => $this->password ??= Hash::make(config('app.demo_password') ?? 'password'),
            ...$attributes,
        ]);
    }

    /**
     * A family of the type, in the project: the person given and one more, or one to
     * three new people (a couple, perhaps a grown child)
     */
    public function family(Project $project, UnitType $type, ?User $known = null): Family
    {
        do {
            [$first, $second] = [$known?->lastname ?? $this->pick(self::SURNAMES), $this->pick(self::SURNAMES)];
        } while ($first === $second || isset($this->families["{$project->id}:{$first} {$second}"]));
        $this->families["{$project->id}:{$first} {$second}"] = true;

        $family = Family::factory()->create(['project_id' => $project->id, 'unit_type_id' => $type->id, 'name' => "{$first} {$second}"]);

        $size = mt_rand(1, 10);
        $people = $known ? [$known, $this->person($second)] : [$this->person($first)];
        if (! $known && $size > 2) {
            $people[] = $this->person($second);
        }
        if ($size > 8) {
            $people[] = $this->person("{$first} {$second}");
        }
        foreach ($people as $person) {
            $project->memberships()->updateOrCreate(['user_id' => $person->id], ['family_id' => $family->id]);
        }

        return $family;
    }

    /**
     * @template T
     *
     * @param array<T> $from
     *
     * @return T
     */
    public function pick(array $from): mixed
    {
        return $from[mt_rand(0, count($from) - 1)];
    }

    private function email(string $firstname, string $lastname): string
    {
        $base = Str::slug($firstname, '') . '.' . Str::slug($lastname, '');
        $email = "{$base}@example.com";
        for ($n = 2; isset($this->emails[$email]); $n++) {
            $email = "{$base}{$n}@example.com";
        }
        $this->emails[$email] = true;

        return $email;
    }

    /** A Uruguayan ID number, its check digit right: "4.512.338-2" */
    private function legalId(): string
    {
        $digits = (string) mt_rand(1000000, 6999999);
        $sum = 0;
        foreach (str_split($digits) as $index => $digit) {
            $sum += (int) $digit * [2, 9, 8, 7, 6, 3, 4][$index];
        }

        return sprintf('%s.%s.%s-%d', $digits[0], substr($digits, 1, 3), substr($digits, 4, 3), (10 - $sum % 10) % 10);
    }
}
