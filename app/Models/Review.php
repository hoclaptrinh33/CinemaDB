<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory, SoftDeletes;
    // Only created_at — no updated_at in schema
    const UPDATED_AT = null;

    protected $table      = 'reviews';
    protected $primaryKey = 'review_id';

    protected $fillable = [
        'title_id',
        'user_id',
        'rating',
        'review_text',
        'has_spoilers',
        'moderation_status',
        'helpful_votes',
        'reputation_earned',
    ];

    protected $casts = [
        'has_spoilers'     => 'boolean',
        'moderation_status' => 'string',
        'created_at'       => 'datetime',
        'helpful_votes'    => 'integer',
        'reputation_earned' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** Users who marked this review as helpful. */
    public function helpfulVoters(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'review_helpful_votes',
            'review_id',
            'user_id',
            'review_id',
            'id'
        )->withPivot('voted_at');
    }
}
