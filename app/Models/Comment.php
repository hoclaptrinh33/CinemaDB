<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $table = 'title_comments';
    protected $primaryKey = 'comment_id';

    protected $fillable = [
        'title_id',
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

    // ── Relationships ─────────────────────────────────────────────────────

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id', 'comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id', 'comment_id')
            ->where('is_hidden', false)
            ->with('user:id,name,username')
            ->orderBy('comment_id');
    }

    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'title_comment_likes',
            'comment_id',
            'user_id'
        )->withTimestamps('created_at');
    }
}
