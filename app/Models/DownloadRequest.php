<?php

namespace App\Models;

use App\Support\ReportCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'reviewed_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Approved (or already downloaded once) and still within the link window. */
    public function isDownloadable(): bool
    {
        return in_array($this->status, ['approved', 'downloaded'], true) && ! $this->isExpired();
    }

    public function label(): string
    {
        return ReportCatalog::label($this->type);
    }
}
