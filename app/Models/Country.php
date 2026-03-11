<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'country_id';

    protected $fillable = ['iso_code', 'country_name'];

    // ── Relationships ─────────────────────────────────────────────────────

    public function studios(): HasMany
    {
        return $this->hasMany(Studio::class, 'country_id', 'country_id');
    }

    public function persons(): HasMany
    {
        return $this->hasMany(Person::class, 'country_id', 'country_id');
    }
}
