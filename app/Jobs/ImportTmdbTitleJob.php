<?php

namespace App\Jobs;

use App\Models\TmdbImportLog;
use App\Models\Title;
use App\Services\TmdbImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportTmdbTitleJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int   $tries   = 2;
    public int   $timeout = 300;
    public array $backoff  = [30, 120];

    public function __construct(
        public int    $tmdbId,
        public string $type,    // 'movie' | 'tv'
        public ?int   $logId = null,
    ) {}

    public function handle(TmdbImportService $importer): void
    {
        if ($this->logId) {
            TmdbImportLog::where('id', $this->logId)->update(['status' => 'processing']);
        }

        $this->type === 'tv'
            ? $importer->importTv($this->tmdbId)
            : $importer->importMovie($this->tmdbId);

        if ($this->logId) {
            $titleName = Title::where('tmdb_id', $this->tmdbId)->value('title_name');
            TmdbImportLog::where('id', $this->logId)->update([
                'status'     => 'done',
                'title_name' => $titleName,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        if ($this->logId) {
            TmdbImportLog::where('id', $this->logId)->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
