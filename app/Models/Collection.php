<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Collection extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
    use HasFactory, HasSlug;

    protected $primaryKey = 'collection_id';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'visibility',
        'cover_title_id',
        'cover_image_url',
        'nomination_count',
        'is_published',
        'published_at',
        'publish_headline',
        'publish_body',
        'source_collection_id',
        'original_author_name',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn($model) => $model->user_id . '-' . $model->name)
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'cover_title_id', 'title_id');
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'collection_titles', 'collection_id', 'title_id')
            ->withPivot('added_at', 'note')
            ->orderByPivot('added_at', 'desc');
    }

    // ── Collaborators ─────────────────────────────────────────────────────

    public function collaborators(): HasMany
    {
        return $this->hasMany(CollectionCollaborator::class, 'collection_id', 'collection_id');
    }

    public function acceptedCollaborators(): HasMany
    {
        return $this->hasMany(CollectionCollaborator::class, 'collection_id', 'collection_id')
            ->whereNotNull('accepted_at');
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(CollectionNomination::class, 'collection_id', 'collection_id');
    }

    public function titleNotes(): HasMany
    {
        return $this->hasMany(CollectionTitleNote::class, 'collection_id', 'collection_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CollectionComment::class, 'collection_id', 'collection_id');
    }

    public function isCollaborator(int $userId): bool
    {
        return $this->collaborators()
            ->where('user_id', $userId)
            ->whereNotNull('accepted_at')
            ->exists();
    }

    public function hasPendingInvite(int $userId): bool
    {
        return $this->collaborators()
            ->where('user_id', $userId)
            ->whereNull('accepted_at')
            ->exists();
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('visibility', 'PUBLIC');
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)->where('visibility', 'PUBLIC');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isCopy(): bool
    {
        return !is_null($this->source_collection_id);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image_url) {
            return $this->cover_image_url;
        }

        return $this->cover?->poster_url ?? asset('images/collection-default.jpg');
    }

    /**
     * Returns up to 4 poster URLs from loaded titles for auto-collage.
     * Returns [] when the titles relation is not loaded.
     */
    public function getAutoCoverUrlsAttribute(): array
    {
        if (! $this->relationLoaded('titles')) {
            return [];
        }

        return $this->titles
            ->take(4)
            ->pluck('poster_url')
            ->filter()
            ->values()
            ->all();
    }
}
