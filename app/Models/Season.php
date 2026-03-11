<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'season_id';

    protected $fillable = [
        'series_id',
        'season_number',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id', 'series_id');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'season_id', 'season_id')
            ->orderBy('episode_number');
    }
}
