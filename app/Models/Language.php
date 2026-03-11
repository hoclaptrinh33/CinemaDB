<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'language_id';

    protected $fillable = ['iso_code', 'language_name'];

    // ── Relationships ─────────────────────────────────────────────────────

    public function titles(): HasMany
    {
        return $this->hasMany(Title::class, 'original_language_id', 'language_id');
    }
}
