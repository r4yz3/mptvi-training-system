<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A Major or Minor subject (with credit units) belonging to a program. */
class Subject extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    /** Major subjects (core/specialization) weigh the same per unit as Minor;
     *  the split is shown separately and Major carries more units. */
    public const CATEGORIES = ['Major', 'Minor'];

    protected function casts(): array
    {
        return ['units' => 'integer', 'sort' => 'integer'];
    }

    public function activityLabel(): string
    {
        return "subject “{$this->title}”";
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(SubjectGrade::class);
    }
}
