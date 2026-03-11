<?php

namespace App\Http\Middleware;

use App\Services\GamificationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RecordUserActivity
{
    public function __construct(private GamificationService $gamification) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $user     = $request->user();
            $cacheKey = 'user_activity_recorded:' . $user->id . ':' . now('UTC')->toDateString();

            // Only call recordDailyVisit once per user per day (avoids firstOrCreate on every request)
            if (! Cache::has($cacheKey)) {
                $this->gamification->recordDailyVisit($user);
                Cache::put($cacheKey, true, now('UTC')->endOfDay());
            }
        }

        return $next($request);
    }
}
