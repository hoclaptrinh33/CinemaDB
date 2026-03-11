<?php

namespace App\Console\Commands;

use App\Services\TmdbImportService;
use App\Services\TmdbService;
use Illuminate\Console\Command;

class TmdbImportCommand extends Command
{
    protected $signature   = 'tmdb:import
                                {--type=movie : movie|tv}
                                {--pages=3 : number of discover pages to fetch}
                                {--id= : import a single TMDB id}';

    protected $description = 'Import titles from TMDB API';

    public function __construct(
        private readonly TmdbService $tmdb,
        private readonly TmdbImportService $importer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->importer->setOutput(
            fn(string $msg) => $this->info($msg),
            fn(string $msg) => $this->warn($msg),
        );

        $type = $this->option('type');

        if ($id = $this->option('id')) {
            $type === 'tv'
                ? $this->importer->importTv((int) $id)
                : $this->importer->importMovie((int) $id);

            return self::SUCCESS;
        }

        $pages = (int) $this->option('pages');

        for ($page = 1; $page <= $pages; $page++) {
            $results = $type === 'tv'
                ? $this->tmdb->discoverTv($page)
                : $this->tmdb->discoverMovies($page);

            foreach ($results['results'] ?? [] as $item) {
                $type === 'tv'
                    ? $this->importer->importTv($item['id'])
                    : $this->importer->importMovie($item['id']);
            }

            $this->info("Page {$page}/{$pages} done.");
        }

        return self::SUCCESS;
    }
}
