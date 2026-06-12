<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    public function activityLabel(): string
    {
        return "payment #{$this->getKey()} (applicant #{$this->applicant_id})";
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function scopeValid(Builder $q): Builder
    {
        return $q->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
