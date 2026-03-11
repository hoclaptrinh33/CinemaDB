<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TitlePersonRole extends Pivot
{
    public $incrementing = false;
    public $timestamps   = false;

    protected $table = 'title_person_roles';

    protected $fillable = [
        'title_id',
        'person_id',
        'role_id',
        'character_name',
        'cast_order',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'person_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }
}
