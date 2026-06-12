<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Batch extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    public function activityLabel(): string
    {
        return "batch {$this->code}";
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'capacity' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    /** Hours of class per day for a given session. */
    public static function hoursPerDay(string $session): int
    {
        return $session === 'Whole-day' ? 8 : 4;
    }

    /** Carbon dayOfWeek numbers (0=Sun..6=Sat) for a class-days pattern. */
    public static function classWeekdays(string $pattern): array
    {
        return match ($pattern) {
            'MWF' => [1, 3, 5],
            'Tue-Thu' => [2, 4],
            'Mon-Sat' => [1, 2, 3, 4, 5, 6],
            'Sat' => [6],
            default => [1, 2, 3, 4, 5], // Mon–Fri
        };
    }

    /**
     * End date = the class day on which cumulative hours reach the program's
     * total training hours, stepping over only the pattern's class weekdays.
     */
    public static function computeEndDate(?string $start, int $totalHours, string $session, string $pattern): ?string
    {
        if (! $start || $totalHours <= 0) {
            return null;
        }

        $perDay = self::hoursPerDay($session);
        $days = self::classWeekdays($pattern);
        $date = Carbon::parse($start);
        $accrued = 0;
        $guard = 0;

        while ($accrued < $totalHours && $guard < 4000) {
            if (in_array($date->dayOfWeek, $days, true)) {
                $accrued += $perDay;
                if ($accrued >= $totalHours) {
                    return $date->toDateString();
                }
            }
            $date->addDay();
            $guard++;
        }

        return $date->toDateString();
    }
}
