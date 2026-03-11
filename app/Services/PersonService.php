<?php

namespace App\Services;

use App\Models\Person;

class PersonService
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function create(array $data): Person
    {
        $data = $this->handlePhotoUpload($data);

        return Person::create($data);
    }

    public function update(Person $person, array $data): Person
    {
        $data = $this->handlePhotoUpload($data, $person);
        $person->update($data);

        return $person->fresh();
    }

    public function delete(Person $person): void
    {
        if ($person->profile_path) {
            $this->cloudinary->delete($person->profile_path);
        }

        $person->delete();
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function handlePhotoUpload(array $data, ?Person $existing = null): array
    {
        if (isset($data['profile_image'])) {
            if ($existing?->profile_path) {
                $this->cloudinary->delete($existing->profile_path);
            }

            $data['profile_path'] = $this->cloudinary->upload($data['profile_image'], 'movie-database/persons/profiles');
            unset($data['profile_image']);
        }

        return $data;
    }
}
