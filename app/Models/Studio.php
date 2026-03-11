<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Studio extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'studio_id';

    protected $fillable = [
        'studio_name',
        'country_id',
        'founded_year',
        'website_url',
        'logo_path',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'country_id');
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(
            Title::class,
            'title_studios',
            'studio_id',
            'title_id',
            'studio_id',
            'title_id'
        );
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return str_starts_with($this->logo_path, 'http') ? $this->logo_path : Storage::url($this->logo_path);
    }
}
