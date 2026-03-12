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
        // Aggregate stats từ toàn bộ DB (không giới hạn 200)
        $stats = TmdbImportLog::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'done'       THEN 1 ELSE 0 END) as done_count,
            SUM(CASE WHEN status = 'failed'      THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN status = 'cancelled'   THEN 1 ELSE 0 END) as cancelled_count,
            SUM(CASE WHEN status IN ('pending','processing') THEN 1 ELSE 0 END) as pending_count
        ")->first();

        // Ưu tiên hiển thị jobs đang chờ/xử lý trước, sau đó mới hiện done/failed gần nhất
        $active = TmdbImportLog::whereIn('status', ['pending', 'processing'])
            ->latest('id')
            ->limit(200)
            ->get(['id', 'tmdb_id', 'type', 'status', 'title_name', 'error_message', 'created_at', 'updated_at']);

        $remaining = max(0, 200 - $active->count());
        $finished = TmdbImportLog::whereIn('status', ['done', 'failed', 'cancelled'])
            ->latest('id')
            ->limit($remaining)
            ->get(['id', 'tmdb_id', 'type', 'status', 'title_name', 'error_message', 'created_at', 'updated_at']);

        $logs = $active->concat($finished)->sortByDesc('id')->values();

        return response()->json([
            'logs'            => $logs,
            'total'           => (int) $stats->total,
            'done_count'      => (int) $stats->done_count,
            'failed_count'    => (int) $stats->failed_count,
            'pending_count'   => (int) $stats->pending_count,
            'cancelled_count' => (int) $stats->cancelled_count,
        ]);
    }

    /**
     * Hủy một job cụ thể (chỉ được hủy khi đang ở trạng thái pending)
     */
    public function cancelJob(TmdbImportLog $log): JsonResponse
    {
        if ($log->status !== 'pending') {
            return response()->json([
                'message' => 'Chỉ có thể hủy job đang chờ xử lý (pending).',
            ], 422);
        }

        $log->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Đã hủy job #' . $log->id]);
    }

    /**
     * Hủy toàn bộ job đang pending
     */
    public function cancelAll(): JsonResponse
    {
        $count = TmdbImportLog::where('status', 'pending')->update(['status' => 'cancelled']);

        return response()->json([
            'message' => "Đã hủy {$count} job đang chờ.",
            'count'   => $count,
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
