<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DiscoverTmdbTitlesJob;
use App\Jobs\ImportTmdbTitleJob;
use App\Models\TmdbImportLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TmdbImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/TmdbImport');
    }

    public function logs(): JsonResponse
    {
        $total = TmdbImportLog::count();

        // Ưu tiên hiển thị jobs đang chờ/xử lý trước, sau đó mới hiện done/failed gần nhất
        $active = TmdbImportLog::whereIn('status', ['pending', 'processing'])
            ->latest('id')
            ->limit(200)
            ->get(['id', 'tmdb_id', 'type', 'status', 'title_name', 'error_message', 'created_at', 'updated_at']);

        $remaining = max(0, 200 - $active->count());
        $finished = TmdbImportLog::whereIn('status', ['done', 'failed'])
            ->latest('id')
            ->limit($remaining)
            ->get(['id', 'tmdb_id', 'type', 'status', 'title_name', 'error_message', 'created_at', 'updated_at']);

        $logs = $active->concat($finished)->sortByDesc('id')->values();

        return response()->json([
            'logs'  => $logs,
            'total' => $total,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode'  => ['required', 'in:single,discover'],
            'type'  => ['required', 'in:movie,tv,both'],
            'id'    => ['required_if:mode,single', 'nullable', 'integer', 'min:1'],
            'pages' => ['required_if:mode,discover', 'nullable', 'integer', 'min:1', 'max:10'],
        ]);

        if ($validated['mode'] === 'single') {
            $tmdbId = (int) $validated['id'];
            $type   = $validated['type'];

            if ($type === 'both') {
                $logMovie = TmdbImportLog::create(['tmdb_id' => $tmdbId, 'type' => 'movie']);
                $logTv    = TmdbImportLog::create(['tmdb_id' => $tmdbId, 'type' => 'tv']);
                ImportTmdbTitleJob::dispatch($tmdbId, 'movie', $logMovie->id);
                ImportTmdbTitleJob::dispatch($tmdbId, 'tv', $logTv->id);
                $msg = "Đã đẩy vào queue: TMDB #{$tmdbId} (movie + tv).";
            } else {
                $log = TmdbImportLog::create(['tmdb_id' => $tmdbId, 'type' => $type]);
                ImportTmdbTitleJob::dispatch($tmdbId, $type, $log->id);
                $msg = "Đã đẩy vào queue: TMDB #{$tmdbId} ({$type}).";
            }
        } else {
            $pages = (int) ($validated['pages'] ?? 3);
            $type  = $validated['type'];

            if ($type === 'both') {
                DiscoverTmdbTitlesJob::dispatch(pages: $pages, types: ['movie', 'tv']);
                $msg = "Đang khám phá {$pages} trang movie + tv (tối đa ~" . ($pages * 40) . " titles) — chỉ import titles chưa có trong DB.";
            } else {
                DiscoverTmdbTitlesJob::dispatch(pages: $pages, types: [$type]);
                $msg = "Đang khám phá {$pages} trang {$type} (tối đa ~" . ($pages * 20) . " titles) — chỉ import titles chưa có trong DB.";
            }
        }

        return back()->with('success', $msg);
    }
}
