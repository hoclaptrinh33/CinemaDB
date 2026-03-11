<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Series extends Model
{
    public $timestamps   = false;
    public $incrementing = false;

    protected $primaryKey = 'series_id';
    protected $table      = 'series';

    protected $fillable = ['series_id'];

    // ── Relationships ─────────────────────────────────────────────────────

    /** The parent Title record (title_type = 'SERIES'). */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'series_id', 'title_id');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class, 'series_id', 'series_id')
            ->orderBy('season_number');
    }

    public function episodes(): HasManyThrough
    {
        return $this->hasManyThrough(
            Episode::class,
            Season::class,
            'series_id',
            'season_id',
            'series_id',
            'season_id'
        );
    }
}
