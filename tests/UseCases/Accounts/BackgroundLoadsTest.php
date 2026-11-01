<?php

/**
 * The app loads pages in the background, to have them ready. One that lands just after a form
 * was answered takes nothing the person is about to be told: a form told nothing takes it as success.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
});

/** What a page says: its message, and what was refused */
function saidBy($response): array
{
    $page = $response->viewData('page');

    return [$page['flash'] ?? [], (array) $page['props']['errors']];
}

function loadInTheBackground($test): array
{
    $said = saidBy($test->withHeaders(['Purpose' => 'prefetch'])->get(route('projects.show', $test->project)));
    $test->flushHeaders();

    return $said;
}

it('keeps a message for the visit that shows it', function () {
    $this->as($this->admin, $this->project)->post(route('manage.unit_types.store'), ['name' => 'Studio'])->assertSessionHasNoErrors();

    expect(loadInTheBackground($this)[0])->toBe([]);

    [$flash] = saidBy($this->get(route('units.index', $this->project)));
    expect($flash)->toBe(['success' => __('flash.unit_type_created')]);

    expect(saidBy($this->get(route('units.index', $this->project)))[0])->toBe([]);
});

it('keeps a refusal for the visit that shows it, so that the form does not take it as success', function () {
    $this->as($this->admin, $this->project)->delete(route('manage.people.destroy', $this->admin));

    expect(loadInTheBackground($this)[1])->toBe([]);

    [, $errors] = saidBy($this->get(route('people.index', $this->project)));
    expect($errors)->toBe(['member' => __('validation.last_admin')]);
});
