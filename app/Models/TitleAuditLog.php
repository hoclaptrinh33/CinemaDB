<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only log — never written to from Laravel directly.
 * Records are created by MySQL triggers (trg_audit_title_*).
 */
class TitleAuditLog extends Model
{
    // No created_at/updated_at columns; action_date is managed by MySQL DEFAULT
    public $timestamps = false;

    protected $table      = 'title_audit_log';
    protected $primaryKey = 'log_id';

    // Read-only: no fillable needed
    protected $guarded = ['*'];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }
}
