// Copilot - Pending review
<?php

use App\Models\Log;
use App\Models\Project;
use App\Models\User;

describe('Log Model Relations', function () {
    it('belongs to a creator (User)', function () {
        $log = Log::find(1); // Log #1 from universe

        expect($log->creator)->toBeInstanceOf(User::class);
    });

    it('belongs to a project', function () {
        $log = Log::find(1); // Log #1 from universe

        expect($log->project)->toBeInstanceOf(Project::class);
    });
});

describe('Log Model Scopes', function () {
    it('searches logs by event', function () {
        $log = Log::find(1); // Log #1 with 'Member logged in' event

        $results = Log::search($log->event)->get();

        expect($results->pluck('id'))->toContain($log->id);
    });
});
