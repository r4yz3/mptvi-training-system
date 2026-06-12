<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class)->latest('id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(DocumentAudit::class)->latest('id');
    }

    public function config(): ?array
    {
        return collect(config('requirements'))->firstWhere('key', $this->requirement_key);
    }
}
