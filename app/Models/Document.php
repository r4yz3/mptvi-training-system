<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $guarded = ['id'];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /** Staff member who last recorded the note / status. */
    public function notedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'noted_by');
    }

    public function config(): ?array
    {
        return collect(config('requirements'))->firstWhere('key', $this->requirement_key);
    }
}
