<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Delivery\GitHub;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * What each environment runs, and deploying: dev from any branch, production or sorteo from
 * main (after its approval on GitHub), any of them back to the release before, dev or staging
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
            'released'     => config('delivery.released'),
        ]);
    }

    public function dev(Request $request): RedirectResponse
    {
        try {
            $branches = $this->github->branches();
        } catch (HttpClientException $e) {
            throw $this->refused($e);
        }

        $data = $request->validate(['branch' => ['required', 'string', Rule::in($branches)]]);

        return $this->dispatch('dev', $data['branch'], [], __('flash.deploy_dev', ['branch' => $data['branch']]));
    }

    /** Production, or another environment released from main (sorteo): production unless one is named */
    public function production(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'environment' => ['nullable', 'string', Rule::in(config('delivery.released'))],
            'commit'      => ['nullable', 'string', 'regex:/^[0-9a-f]{7,40}$/'],
        ]);
        $environment = $data['environment'] ?? 'production';

        return $this->dispatch('production', 'main', ['environment' => $environment, 'commit' => $data['commit'] ?? ''], __('flash.deploy_production', ['environment' => $environment]));
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
        } catch (HttpClientException $e) {
            throw $this->refused($e);
        }

        return back()->with('success', $done);
    }

    /** GitHub said no, or did not answer: said on the page */
    private function refused(HttpClientException $e): ValidationException
    {
        report($e);

        return ValidationException::withMessages(['deploy' => $e instanceof RequestException
            ? __('flash.deploy_refused', ['status' => $e->response->status()])
            : __('flash.deploy_unreachable'),
        ]);
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
