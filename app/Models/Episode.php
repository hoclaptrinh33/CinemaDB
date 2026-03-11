<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    public $timestamps   = false;
    public $incrementing = false;

    protected $primaryKey = 'episode_id';

    protected $fillable = [
        'episode_id',
        'season_id',
        'episode_number',
        'air_date',
    ];

    protected $casts = [
        'air_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    /** The parent Title record (title_type = 'EPISODE'). */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'episode_id', 'title_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id', 'season_id');
    }
}
