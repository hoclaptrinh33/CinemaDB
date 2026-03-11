<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionCollaborator extends Model
{
    protected $fillable = [
        'collection_id',
        'user_id',
        'invited_by',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id', 'collection_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->accepted_at === null;
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }
}
