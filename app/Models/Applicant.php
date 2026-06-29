<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Applicant extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    public function activityLabel(): string
    {
        return "applicant {$this->display_name}";
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'voter' => 'boolean',
            'privacy_consent' => 'boolean',
            'classifications' => 'array',
            'custom_data' => 'array',
            'education_history' => 'array',
            'birthdate' => 'date:Y-m-d',
            'registered_at' => 'date:Y-m-d',
            'date_accomplished' => 'date:Y-m-d',
            'date_received' => 'date:Y-m-d',
            'certified_at' => 'date:Y-m-d',
            'id_issued_at' => 'date:Y-m-d',
            'age' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class)->latest('id');
    }

    /** % of attendance records marked Present or Late. */
    public function attendanceRate(): int
    {
        $total = $this->attendances()->count();
        if ($total === 0) {
            return 0;
        }
        $present = $this->attendances()->whereIn('status', ['Present', 'Late'])->count();

        return (int) round($present / $total * 100);
    }

    /** Program misc fee in whole pesos. */
    public function fee(): int
    {
        return (int) ($this->program?->fee ?? 0);
    }

    /** Sum of non-voided TRAINING-FEE payments (drives the program-fee balance). */
    public function paidTotal(): int
    {
        return (int) $this->payments()->valid()
            ->where('category', config('lpf.training_fee_category'))
            ->sum('amount');
    }

    /** Sum of non-voided payments for everything other than the training fee. */
    public function otherCollected(): int
    {
        return (int) $this->payments()->valid()
            ->where('category', '!=', config('lpf.training_fee_category'))
            ->sum('amount');
    }

    public function balance(): int
    {
        return max(0, $this->fee() - $this->paidTotal());
    }

    /** Unpaid | Partial | Paid | Free (no fee). */
    public function payStatus(): string
    {
        if ($this->fee() === 0) {
            return 'Free';
        }
        $paid = $this->paidTotal();
        if ($paid <= 0) {
            return 'Unpaid';
        }

        return $paid >= $this->fee() ? 'Paid' : 'Partial';
    }

    /**
     * Have all document requirements been addressed? Note-only: a requirement
     * counts as settled once staff mark it Submitted or Not applicable (the
     * latter covers applicants who can't provide that exact document).
     */
    public function documentsComplete(): bool
    {
        $settled = $this->documents()
            ->whereIn('status', ['Submitted', 'Not applicable'])
            ->pluck('requirement_key')->all();

        foreach (config('requirements') as $req) {
            if (! in_array($req['key'], $settled, true)) {
                return false;
            }
        }

        return true;
    }

    /** "Dela Cruz, Juan Santos Jr." — formal order */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(preg_replace('/\s+/', ' ',
                "{$this->last_name}, {$this->first_name} {$this->middle_name} {$this->ext_name}")),
        );
    }

    /** "Juan Dela Cruz" — display order */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(preg_replace('/\s+/', ' ',
                "{$this->first_name} {$this->middle_name} {$this->last_name} {$this->ext_name}")),
        );
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
        );
    }

    /**
     * Eligibility checklist used by Screening (P3). Each item: label, ok, note.
     * Document verification is folded in once P4 lands.
     */
    public function eligibility(): array
    {
        return [
            [
                'label' => 'Age within training range (' . config('academic.age_min', 15) . '–' . config('academic.age_max', 60) . ')',
                'ok' => $this->age !== null && $this->age >= (int) config('academic.age_min', 15) && $this->age <= (int) config('academic.age_max', 60),
                'note' => $this->age !== null ? "Age {$this->age}" : 'No birthdate on record',
            ],
            [
                'label' => 'Educational attainment on record',
                'ok' => ! empty($this->education),
                'note' => $this->education ?: 'Missing',
            ],
            [
                'label' => 'Contact number on record',
                'ok' => ! empty($this->contact),
                'note' => $this->contact ?: 'Missing',
            ],
            [
                'label' => 'Course / qualification selected',
                'ok' => $this->program_id !== null,
                'note' => $this->program?->title ?: 'None',
            ],
            [
                'label' => 'Privacy consent given',
                'ok' => (bool) $this->privacy_consent,
                'note' => $this->privacy_consent ? 'Consented' : 'Not given',
            ],
            [
                'label' => 'All documentary requirements noted',
                'ok' => $this->documentsComplete(),
                'note' => $this->documentsComplete() ? 'Complete' : 'Pending documents',
            ],
        ];
    }

    public function isEligible(): bool
    {
        foreach ($this->eligibility() as $item) {
            if (! $item['ok']) {
                return false;
            }
        }

        return true;
    }
}
