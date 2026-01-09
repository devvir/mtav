<?php

// Copilot - Pending review
// TEMPORARY TEST MIDDLEWARE - Remove after testing

namespace App\Http\Middleware;

use App\Services\Broadcast\DataObjects\Message;
use App\Services\Broadcast\Enums\BroadcastMessage;
use App\Services\BroadcastService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test middleware to broadcast user navigation events.
 *
 * This is TEMPORARY and should be removed after testing.
 */
class BroadcastNavigationTest
{
    public function __construct(
        protected BroadcastService $broadcast,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Broadcast navigation to user's private channel
            $this->broadcast->toUser(
                $user->id,
                Message::make(
                    BroadcastMessage::USER_NAVIGATION,
                    [
                        'url'       => $request->fullUrl(),
                        'path'      => $request->path(),
                        'method'    => $request->method(),
                        'user_id'   => $user->id,
                        'user_name' => $user->fullname,
                    ],
                ),
            );

            // If user has a current project, also broadcast to project channel
            $projectId = $request->route('project')?->id ?? session('current_project_id');
            if ($projectId) {
                $this->broadcast->toProject(
                    $projectId,
                    Message::make(
                        BroadcastMessage::USER_NAVIGATION,
                        [
                            'url'        => $request->fullUrl(),
                            'path'       => $request->path(),
                            'method'     => $request->method(),
                            'user_id'    => $user->id,
                            'user_name'  => $user->fullname,
                            'project_id' => $projectId,
                        ],
                    ),
                );
            }
        }

        return $next($request);
    }
}
