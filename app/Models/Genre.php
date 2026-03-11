<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'genre_id';

    protected $fillable = ['tmdb_id', 'genre_name', 'genre_name_vi'];

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(
            Title::class,
            'title_genres',
            'genre_id',
            'title_id',
            'genre_id',
            'title_id'
        );
    }
}
