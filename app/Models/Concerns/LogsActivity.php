<?php

namespace App\Models\Concerns;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

/**
 * Lightweight audit: records create/update/delete of a model into `activities`,
 * attributed to the authenticated user. Models may define activityLabel() to
 * describe the subject; otherwise the model basename + id is used.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($m) => $m->writeActivity('created'));
        static::updated(fn ($m) => $m->writeActivity('updated'));
        static::deleted(fn ($m) => $m->writeActivity('deleted'));
    }

    public function activityLabel(): string
    {
        return class_basename($this) . " #{$this->getKey()}";
    }

    protected function writeActivity(string $event): void
    {
        // Skip noise: updates that only touch timestamps.
        if ($event === 'updated') {
            $dirty = array_diff(array_keys($this->getChanges()), ['updated_at', 'created_at']);
            if (empty($dirty)) {
                return;
            }
        }

        $user = Auth::user();
        $verb = ['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted'][$event];

        Activity::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'event' => $event,
            'subject_type' => class_basename($this),
            'subject_id' => $this->getKey(),
            'description' => "{$verb} " . $this->activityLabel(),
            'created_at' => now(),
        ]);
    }
}
