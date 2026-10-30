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

it('gives the lists to no one but admins', function () {
    expect($this->as($this->member, $this->project)->get(route('manage.export.people', $this->project)))->toBeDenied();
});
