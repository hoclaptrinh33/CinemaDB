<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'activity_type',
        'subject_id',
        'subject_type',
        'title_id',
        'collection_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id', 'collection_id');
    }
}
