<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CollectionComment extends Model
{
    protected $primaryKey = 'collection_comment_id';

    public function getRouteKeyName(): string
    {
        return 'collection_comment_id';
    }

    protected $appends = ['comment_id'];

    public function getCommentIdAttribute(): int
    {
        return $this->collection_comment_id;
    }

    protected $fillable = [
        'collection_id',
        'user_id',
        'parent_id',
        'content',
        'content_type',
        'gif_url',
        'is_hidden',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id', 'collection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'collection_comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'collection_comment_id');
    }

    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'collection_comment_likes',
            'collection_comment_id',
            'user_id'
        )->withTimestamps();
    }
}
