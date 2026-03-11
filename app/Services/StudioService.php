<?php

namespace App\Services;

use App\Models\Studio;

class StudioService
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function create(array $data): Studio
    {
        $data = $this->handleLogoUpload($data);

        return Studio::create($data);
    }

    public function update(Studio $studio, array $data): Studio
    {
        $data = $this->handleLogoUpload($data, $studio);
        $studio->update($data);

        return $studio->fresh();
    }

    public function delete(Studio $studio): void
    {
        if ($studio->logo_path) {
            $this->cloudinary->delete($studio->logo_path);
        }

        $studio->delete();
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function handleLogoUpload(array $data, ?Studio $existing = null): array
    {
        if (isset($data['logo_image'])) {
            if ($existing?->logo_path) {
                $this->cloudinary->delete($existing->logo_path);
            }

            $data['logo_path'] = $this->cloudinary->upload($data['logo_image'], 'movie-database/studios/logos');
            unset($data['logo_image']);
        }

        return $data;
    }
}
