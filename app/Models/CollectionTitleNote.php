<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionTitleNote extends Model
{
    protected $fillable = [
        'collection_id',
        'title_id',
        'user_id',
        'note',
        'watched_at',
    ];

    protected $casts = [
        'watched_at' => 'datetime',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id', 'collection_id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
