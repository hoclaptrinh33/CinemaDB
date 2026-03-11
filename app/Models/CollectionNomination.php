<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionNomination extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'collection_id',
        'user_id',
        'nominated_date',
    ];

    protected $casts = [
        'nominated_date' => 'date',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id', 'collection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
