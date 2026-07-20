<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use LogsActivity;

    /** Normal TESDA training — runs for months, collects a fee. */
    public const SCHOOL_BASED = 'school_based';

    /** Free soft-skills training — no payment. */
    public const COMMUNITY_BASED = 'community_based';

    public function activityLabel(): string
    {
        return "program {$this->title}";
    }

    protected $fillable = [
        'title', 'qualification', 'training_type', 'level', 'hours', 'fee', 'slots', 'active',
    ];

    /** Free soft-skills community offering (no payment). */
    public function isCommunityBased(): bool
    {
        return $this->training_type === self::COMMUNITY_BASED;
    }

    /** Human label for the training type. */
    public function trainingTypeLabel(): string
    {
        return $this->isCommunityBased() ? 'Community-Based' : 'School-Based';
    }

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'fee' => 'integer',
            'slots' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /** TESDA Units of Competency, ordered Basic → Common → Core then by sort. */
    public function competencyUnits(): HasMany
    {
        return $this->hasMany(CompetencyUnit::class)
            ->orderByRaw("CASE type WHEN 'Basic' THEN 0 WHEN 'Common' THEN 1 WHEN 'Core' THEN 2 ELSE 3 END")
            ->orderBy('sort')->orderBy('id');
    }
}
