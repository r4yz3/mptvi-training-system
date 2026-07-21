<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A trainee's numeric grade (1.00–5.00) for one subject. */
class SubjectGrade extends Model
{
    protected $guarded = ['id'];

    /** Lowest passing grade (Philippine college convention: 3.00 = 75%). */
    public const PASSING = 3.00;

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:2',
            'graded_at' => 'date:Y-m-d',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
