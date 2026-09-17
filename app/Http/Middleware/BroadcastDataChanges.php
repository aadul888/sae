<?php

namespace App\Http\Middleware;

use App\Services\RealtimeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BroadcastDataChanges
{
    /**
     * Handle an incoming request and broadcast event on successful state mutation.
     *
     * ponytail: automatic broadcast on HTTP mutation. Upgrade to granular model observers if specific payload diffs are needed per entity.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $path = $request->path();
            $statusCode = $response->getStatusCode();

            // Hanya mutasi sukses (< 400) dan di luar auth / streaming
            if (
                $statusCode < 400 &&
                !str_contains($path, 'login') &&
                !str_contains($path, 'logout') &&
                !str_contains($path, 'realtime')
            ) {
                try {
                    RealtimeService::trigger('data.changed', [
                        'path'   => $path,
                        'method' => $request->method(),
                    ]);
                } catch (\Throwable) {
                    // Jangan gagalkan response utama jika caching bermasalah
                }
            }
        }

        return $response;
    }
}
