<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $table      = 'persons';
    protected $primaryKey = 'person_id';

    protected $appends = ['photo_url'];

    protected $fillable = [
        'tmdb_id',
        'full_name',
        'birth_date',
        'death_date',
        'gender',
        'country_id',
        'biography',
        'biography_vi',
        'profile_path',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
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
            'title_person_roles',
            'person_id',
            'title_id',
            'person_id',
            'title_id'
        )
            ->using(TitlePersonRole::class)
            ->withPivot(['role_id', 'character_name', 'cast_order']);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn($q, $v) =>
            $q->where('full_name', 'LIKE', "%{$v}%"))
            ->when($filters['country_id'] ?? null, fn($q, $v) =>
            $q->where('country_id', $v))
            ->when($filters['gender'] ?? null, fn($q, $v) =>
            $q->where('gender', $v));
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->profile_path) {
            return null;
        }
        return str_starts_with($this->profile_path, 'http')
            ? $this->profile_path
            : Storage::url($this->profile_path);
    }

    public function getProfileUrlAttribute(): string
    {
        return $this->getPhotoUrlAttribute() ?? asset('images/no-profile.jpg');
    }
}
