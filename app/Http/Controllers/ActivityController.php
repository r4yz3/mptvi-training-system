<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $type = $request->input('type');
        $event = $request->input('event');

        $activities = Activity::query()
            ->when($type, fn ($q, $t) => $q->where('subject_type', $t))
            ->when($event, fn ($q, $e) => $q->where('event', $e))
            ->latest('id')
            ->paginate(25)->withQueryString()
            ->through(fn (Activity $a) => [
                'id' => $a->id,
                'user' => $a->user_name ?? 'System',
                'event' => $a->event,
                'subject_type' => $a->subject_type,
                'description' => $a->description,
                'at' => $a->created_at?->diffForHumans(),
                'at_full' => $a->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Activity/Index', [
            'activities' => $activities,
            'filters' => ['type' => $type, 'event' => $event],
            'subjectTypes' => Activity::query()->distinct()->orderBy('subject_type')->pluck('subject_type'),
            'stats' => [
                'total' => (int) Activity::count(),
                'today' => (int) Activity::whereDate('created_at', now()->toDateString())->count(),
                'created' => (int) Activity::where('event', 'created')->count(),
                'updated' => (int) Activity::where('event', 'updated')->count(),
            ],
        ]);
    }
}
