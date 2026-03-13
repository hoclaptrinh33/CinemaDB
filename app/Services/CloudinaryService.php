<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CloudinaryService
{
    private ?Cloudinary $client = null;

    /**
     * Upload a file to Cloudinary and return the secure URL.
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $result = $this->getClient()->uploadApi()->upload($file->getRealPath(), [
            'folder'        => $folder,
            'resource_type' => 'image',
        ]);

        return $result['secure_url'];
    }

    /**
     * Delete an image from Cloudinary by its stored URL.
     * Silently ignores non-Cloudinary URLs (e.g. old local paths or TMDB URLs).
     */
    public function delete(string $url): void
    {
        $publicId = $this->extractPublicId($url);

        if (! $publicId) {
            return;
        }

        if (! $this->hasConfiguration()) {
            return;
        }

        $this->getClient()->uploadApi()->destroy($publicId);
    }

    private function getClient(): Cloudinary
    {
        if ($this->client) {
            return $this->client;
        }

        // Dùng config() thay vì env() để hoạt động đúng khi production
        // chạy php artisan config:cache (env() sẽ trả về null sau khi cache)
        $url = config('cloudinary.url');

        if (blank($url)) {
            throw new RuntimeException('Cloudinary is not configured.');
        }

        return $this->client = new Cloudinary($url);
    }

    private function hasConfiguration(): bool
    {
        // Dùng config() thay vì env() để hoạt động đúng khi production dùng config:cache
        return filled(config('cloudinary.url'));
    }

    /**
     * Extract the Cloudinary public_id from a secure URL.
     * e.g. https://res.cloudinary.com/<cloud>/image/upload/v123/folder/file.jpg → folder/file
     */
    private function extractPublicId(string $url): ?string
    {
        if (preg_match('~/image/upload/(?:v\d+/)?(.+)\.[a-zA-Z0-9]+$~', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
