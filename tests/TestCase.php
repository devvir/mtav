<?php

namespace Tests;

use App\Http\Middleware\RequireOwner;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

/**
 * Tests start from an empty schema (migrated once per run; each test runs inside a
 * rolled-back transaction that still fires after-commit callbacks) and build exactly
 * the data they need with the scenario builders in tests/Helpers/scenario.php.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The suite's deployment has its owner; the tests about owners start without one
        RequireOwner::$owned = true;
    }

    /**
     * Act as the given user (as the session guard would load them), optionally within
     * a project: like the app does, the project becomes the default {project} of URLs.
     */
    public function as(User $user, ?Project $project = null): static
    {
        $project && URL::defaults(['project' => $project->id]);

        return $this->actingAs(User::findOrFail($user->id));
    }
}
