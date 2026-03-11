<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Title extends Model
{
    use HasFactory, HasSlug, Searchable, SoftDeletes;

    protected $primaryKey = 'title_id';

    protected $appends = ['poster_url', 'backdrop_url'];

    protected $fillable = [
        'tmdb_id',
        'title_name',
        'title_name_vi',
        'slug',
        'original_language_id',
        'release_date',
        'runtime_mins',
        'title_type',
        'description',
        'description_vi',
        'poster_path',
        'backdrop_path',
        'trailer_url',
        'status',
        'budget',
        'revenue',
        'visibility',
        'moderation_reason',
    ];

    protected $casts = [
        'release_date' => 'date',
        'hidden_at'    => 'datetime',
        'deleted_at'   => 'datetime',
        'avg_rating'   => 'decimal:2',
    ];

    // ── Slug ──────────────────────────────────────────────────────────────

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title_name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(400);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function toSearchableArray(): array
    {
        return [
            'title_id'             => $this->title_id,
            'title_name'           => $this->title_name,
            'title_name_vi'        => $this->title_name_vi,
            'description'          => $this->description,
            'description_vi'       => $this->description_vi,
            'title_type'           => $this->title_type,
            'release_year'         => $this->release_date?->year,
            'avg_rating'           => (float) ($this->avg_rating ?? 0),
            'rating_count'         => (int) ($this->rating_count ?? 0),
            'original_language_id' => $this->original_language_id,
            'visibility'           => $this->visibility,
            'slug'                 => $this->slug,
            'poster_url'           => $this->poster_url,
            'genres'               => $this->genres->pluck('genre_name')->all(),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->visibility === 'PUBLIC';
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(
            Genre::class,
            'title_genres',
            'title_id',
            'genre_id',
            'title_id',
            'genre_id'
        );
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'original_language_id', 'language_id');
    }

    public function studios(): BelongsToMany
    {
        return $this->belongsToMany(
            Studio::class,
            'title_studios',
            'title_id',
            'studio_id',
            'title_id',
            'studio_id'
        );
    }

    public function persons(): BelongsToMany
    {
        return $this->belongsToMany(
            Person::class,
            'title_person_roles',
            'title_id',
            'person_id',
            'title_id',
            'person_id'
        )
            ->using(TitlePersonRole::class)
            ->withPivot(['role_id', 'character_name', 'cast_order']);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'title_id', 'title_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TitleAuditLog::class, 'title_id', 'title_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'title_id', 'title_id');
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class, 'title_id', 'title_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(TitleMedia::class, 'title_id', 'title_id')
            ->orderBy('sort_order');
    }

    public function seriesDetail(): HasOne
    {
        return $this->hasOne(Series::class, 'series_id', 'title_id');
    }

    public function episodeDetail(): HasOne
    {
        return $this->hasOne(Episode::class, 'episode_id', 'title_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('visibility', 'PUBLIC');
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn($q, $v) =>
            $q->where('title_name', 'LIKE', "%{$v}%"))
            ->when($filters['type'] ?? null, fn($q, $v) =>
            $q->where('title_type', $v))
            ->when($filters['year'] ?? null, fn($q, $v) =>
            $q->whereYear('release_date', $v))
            ->when($filters['language_id'] ?? null, fn($q, $v) =>
            $q->where('original_language_id', $v))
            ->when($filters['genre_id'] ?? null, fn($q, $v) =>
            $q->whereHas('genres', fn($gq) => $gq->where('genres.genre_id', $v)))
            ->when($filters['sort'] ?? 'latest', function ($q, $v) {
                return match ($v) {
                    'rating' => $q->orderByDesc('avg_rating'),
                    'title'  => $q->orderBy('title_name'),
                    default  => $q->orderByDesc('release_date'),
                };
            });
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getPosterUrlAttribute(): string
    {
        if (!$this->poster_path) {
            return asset('images/no-poster.svg');
        }
        return str_starts_with($this->poster_path, 'http')
            ? $this->poster_path
            : Storage::url($this->poster_path);
    }

    public function getBackdropUrlAttribute(): string
    {
        if (!$this->backdrop_path) {
            return asset('images/no-backdrop.svg');
        }
        return str_starts_with($this->backdrop_path, 'http')
            ? $this->backdrop_path
            : Storage::url($this->backdrop_path);
    }
}
