<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TenorService
{
    private string $baseUrl;
    private string $apiKey;
    private int $limit;

    public function __construct()
    {
        $this->baseUrl = config('tenor.base_url');
        $this->apiKey  = config('tenor.api_key');
        $this->limit   = config('tenor.limit', 20);
    }

    /**
     * Search for GIFs via Klipy (Tenor v2-compatible API).
     *
     * @return array{results: array, next: string}
     */
    public function search(string $query, int $limit = 0, string $pos = ''): array
    {
        if (!$this->apiKey) {
            return ['results' => [], 'next' => ''];
        }

        try {
            $params = [
                'q'      => $query,
                'key'    => $this->apiKey,
                'limit'  => $limit ?: $this->limit,
                'media_filter' => 'gif,tinygif',
            ];

            if ($pos) {
                $params['pos'] = $pos;
            }

            $response = Http::timeout(5)->get("{$this->baseUrl}/search", $params);

            if ($response->failed()) {
                return ['results' => [], 'next' => ''];
            }

            $data = $response->json();

            // Map to a simple format safe to expose to frontend
            $results = array_map(fn($item) => [
                'id'          => $item['id'],
                'title'       => $item['title'] ?? '',
                'url'         => $item['media_formats']['gif']['url'] ?? '',
                'preview_url' => $item['media_formats']['tinygif']['url'] ?? '',
                'dims'        => $item['media_formats']['gif']['dims'] ?? [0, 0],
            ], $data['results'] ?? []);

            return [
                'results' => $results,
                'next'    => $data['next'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::warning('Klipy search failed: ' . $e->getMessage());
            return ['results' => [], 'next' => ''];
        }
    }

    /**
     * Fetch featured/trending GIFs.
     */
    public function featured(int $limit = 0): array
    {
        if (!$this->apiKey) {
            return ['results' => [], 'next' => ''];
        }

        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/featured", [
                'key'          => $this->apiKey,
                'limit'        => $limit ?: $this->limit,
                'media_filter' => 'gif,tinygif',
            ]);

            if ($response->failed()) {
                return ['results' => [], 'next' => ''];
            }

            $data = $response->json();

            $results = array_map(fn($item) => [
                'id'          => $item['id'],
                'title'       => $item['title'] ?? '',
                'url'         => $item['media_formats']['gif']['url'] ?? '',
                'preview_url' => $item['media_formats']['tinygif']['url'] ?? '',
                'dims'        => $item['media_formats']['gif']['dims'] ?? [0, 0],
            ], $data['results'] ?? []);

            return [
                'results' => $results,
                'next'    => $data['next'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::warning('Klipy featured failed: ' . $e->getMessage());
            return ['results' => [], 'next' => ''];
        }
    }
}
