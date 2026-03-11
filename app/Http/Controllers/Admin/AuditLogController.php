<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TitleAuditLog;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(): Response
    {
        $logs = TitleAuditLog::with(['title:title_id,title_name'])
            ->when(request('search'), function ($q, $v) {
                $q->where(function ($nested) use ($v) {
                    $nested->where('action_user', 'LIKE', "%{$v}%")
                        ->orWhere('old_title', 'LIKE', "%{$v}%")
                        ->orWhere('new_title', 'LIKE', "%{$v}%");
                });
            })
            ->when(request('action'), fn($q, $v) =>
            $q->where('action_type', $v))
            ->latest('log_id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn(TitleAuditLog $log) => [
                'id' => $log->log_id,
                'title_id' => $log->title_id,
                'title_name' => $log->title?->title_name ?? $log->new_title ?? $log->old_title,
                'action' => $log->action_type,
                'changed_at' => $log->action_date,
                'changed_by' => $log->action_user,
                'changes' => [
                    'old_title' => $log->old_title,
                    'new_title' => $log->new_title,
                ],
            ]);

        return Inertia::render('Admin/AuditLog/Index', [
            'logs'    => $logs,
            'filters' => request()->only(['search', 'action']),
        ]);
    }
}
