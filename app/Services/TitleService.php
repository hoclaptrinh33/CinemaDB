<?php

namespace App\Services;

use App\Models\Title;

class TitleService
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function create(array $data): Title
    {
        $data = $this->handleMediaUploads($data);

        return Title::create($data);
    }

    public function update(Title $title, array $data): Title
    {
        $data = $this->handleMediaUploads($data, $title);
        $title->update($data);

        return $title->fresh();
    }

    public function delete(Title $title): void
    {
        if ($title->poster_path) {
            $this->cloudinary->delete($title->poster_path);
        }

        if ($title->backdrop_path) {
            $this->cloudinary->delete($title->backdrop_path);
        }

        $title->delete();
    }

    public function syncStudios(Title $title, array $studioIds): void
    {
        $title->studios()->sync($studioIds);
    }

    public function syncCast(Title $title, array $cast): void
    {
        // $cast = [['person_id' => 1, 'role_id' => 2, 'character_name' => 'Neo', 'cast_order' => 1], ...]
        $syncData = [];
        foreach ($cast as $entry) {
            $syncData[$entry['person_id']] = [
                'role_id'        => $entry['role_id'],
                'character_name' => $entry['character_name'] ?? null,
                'cast_order'     => $entry['cast_order'] ?? null,
            ];
        }

        $title->persons()->sync($syncData);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function handleMediaUploads(array $data, ?Title $existing = null): array
    {
        if (isset($data['poster_image'])) {
            if ($existing?->poster_path) {
                $this->cloudinary->delete($existing->poster_path);
            }
            $data['poster_path'] = $this->cloudinary->upload($data['poster_image'], 'movie-database/titles/posters');
            unset($data['poster_image']);
        }

        if (isset($data['backdrop_image'])) {
            if ($existing?->backdrop_path) {
                $this->cloudinary->delete($existing->backdrop_path);
            }
            $data['backdrop_path'] = $this->cloudinary->upload($data['backdrop_image'], 'movie-database/titles/backdrops');
            unset($data['backdrop_image']);
        }

        return $data;
    }
}
