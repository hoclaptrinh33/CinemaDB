<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmdbImportLog extends Model
{
    protected $fillable = ['tmdb_id', 'type', 'status', 'title_name', 'error_message'];
}
