<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Delivery\GitHub;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * What each environment runs, and deploying: dev from any branch, production from main
 * (after its approval on GitHub), any of them back to the release before, dev or staging
 * over from the demo data. GitHub runs each deploy; this only asks it to.
 */
class DeploymentController extends Controller
{
    public function __construct(private GitHub $github)
    {
    }

    public function index(): Response
    {
        return inertia('Platform/Deployments', [
            // GitHub answers in a few seconds: the page comes first
            'main'         => Inertia::defer(fn () => $this->read(fn () => $this->github->main())),
            'environments' => Inertia::defer(fn () => $this->read(fn () => $this->github->environments())),
            'branches'     => Inertia::defer(fn () => $this->read(fn () => $this->github->branches())),
            'runs'         => Inertia::defer(fn () => $this->read(fn () => $this->github->runs()->take(12)->values()->all())),
            'fresh'        => config('delivery.fresh'),
        ]);
    }

    public function dev(Request $request): RedirectResponse
    {
        $data = $request->validate(['branch' => ['required', 'string', Rule::in($this->github->branches())]]);

        return $this->dispatch('dev', $data['branch'], [], __('flash.deploy_dev', ['branch' => $data['branch']]));
    }

    public function production(Request $request): RedirectResponse
    {
        $data = $request->validate(['commit' => ['nullable', 'string', 'regex:/^[0-9a-f]{7,40}$/']]);

        return $this->dispatch('production', 'main', ['commit' => $data['commit'] ?? ''], __('flash.deploy_production'));
    }

    public function rollback(Request $request): RedirectResponse
    {
        $data = $request->validate(['environment' => ['required', Rule::in(array_keys(config('delivery.environments')))]]);

        return $this->dispatch('rollback', 'main', ['environment' => $data['environment']], __('flash.deploy_rollback', ['environment' => $data['environment']]));
    }

    public function fresh(Request $request): RedirectResponse
    {
        $data = $request->validate(['environment' => ['required', Rule::in(config('delivery.fresh'))]]);

        return $this->dispatch('fresh', 'main', ['environment' => $data['environment']], __('flash.deploy_fresh', ['environment' => $data['environment']]));
    }

    private function dispatch(string $kind, string $ref, array $inputs, string $done): RedirectResponse
    {
        try {
            $this->github->dispatch(GitHub::WORKFLOWS[$kind], $ref, $inputs);
        } catch (RequestException $e) {
            report($e);

            throw ValidationException::withMessages(['deploy' => __('flash.deploy_refused', ['status' => $e->response->status()])]);
        }

        return back()->with('success', $done);
    }

    /** What GitHub answered, or null when it did not (the page says so) */
    private function read(callable $ask): mixed
    {
        try {
            return $ask();
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
