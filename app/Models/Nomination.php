<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nomination extends Model
{
    protected $table = 'title_nominations';
    protected $primaryKey = 'nomination_id';
    public $timestamps = false;

    protected $fillable = [
        'title_id',
        'user_id',
        'nominated_date',
    ];

    protected $casts = [
        'nominated_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }
}
