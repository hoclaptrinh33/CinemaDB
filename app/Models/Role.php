<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Film role (Actor, Director, Writer, etc.) — NOT the user auth role.
 */
class Role extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'role_id';

    protected $fillable = ['role_name'];

    // ── Relationships ─────────────────────────────────────────────────────

    public function persons(): BelongsToMany
    {
        return $this->belongsToMany(
            Person::class,
            'title_person_roles',
            'role_id',
            'person_id',
            'role_id',
            'person_id'
        )->withPivot(['title_id', 'character_name', 'cast_order']);
    }
}
