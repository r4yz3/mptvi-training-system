<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A scheduled extra fee: how much a given program charges for a given category
 * (e.g. School uniform) in a given school year.
 */
class FeeItem extends Model
{
    protected $fillable = ['program_id', 'school_year', 'category', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
