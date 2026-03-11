<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleMedia extends Model
{
    protected $table = 'title_media';
    protected $primaryKey = 'media_id';
    public $timestamps = false;

    protected $fillable = [
        'title_id',
        'media_type',
        'url',
        'thumbnail_url',
        'title',
        'sort_order',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }
}
