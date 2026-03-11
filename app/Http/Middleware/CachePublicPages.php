<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicPages
{
    /**
     * Set Cache-Control headers for public (non-authenticated) GET/HEAD responses.
     * This allows browsers and CDN edge nodes to serve cached copies.
     */
    public function handle(Request $request, Closure $next, int $maxAge = 300): Response
    {
        $response = $next($request);

        // Only cache successful GET/HEAD responses for guests
        if (
            $request->isMethodCacheable()
            && $response->isSuccessful()
            && ! $request->user()
        ) {
            $response->headers->set(
                'Cache-Control',
                "public, max-age={$maxAge}, stale-while-revalidate=60"
            );
        }

        return $response;
    }
}
