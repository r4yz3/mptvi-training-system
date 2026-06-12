<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use LogsActivity;

    public function activityLabel(): string
    {
        return "program {$this->title}";
    }

    protected $fillable = [
        'title', 'qualification', 'level', 'hours', 'fee', 'slots', 'active',
    ];

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
}
