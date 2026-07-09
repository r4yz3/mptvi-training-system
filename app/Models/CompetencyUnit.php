<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A TESDA Unit of Competency belonging to a program/qualification. */
class CompetencyUnit extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    /** Ordering weight for the three competency groups. */
    public const TYPES = ['Basic', 'Common', 'Core'];

    protected function casts(): array
    {
        return ['sort' => 'integer'];
    }

    public function activityLabel(): string
    {
        return "competency unit “{$this->title}”";
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(CompetencyResult::class);
    }
}
