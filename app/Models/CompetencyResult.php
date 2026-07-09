<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One trainee's rating on one Unit of Competency (Competent / Not Yet Competent). */
class CompetencyResult extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    public const COMPETENT = 'Competent';
    public const NOT_YET = 'Not Yet Competent';
    public const RESULTS = [self::COMPETENT, self::NOT_YET];

    protected function casts(): array
    {
        return ['rated_at' => 'date:Y-m-d'];
    }

    public function activityLabel(): string
    {
        return "competency result #{$this->getKey()} (applicant #{$this->applicant_id})";
    }

    public function isCompetent(): bool
    {
        return $this->result === self::COMPETENT;
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(CompetencyUnit::class, 'competency_unit_id');
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by');
    }
}
