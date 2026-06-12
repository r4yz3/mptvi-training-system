<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    private array $types = ['General', 'Orientation', 'Assessment', 'Holiday', 'Deadline'];

    public function index(Request $request): Response
    {
        $today = now()->toDateString();

        $events = Event::query()->orderBy('date')->get()->map(fn (Event $e) => [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'type' => $e->type,
            'date' => $e->date->toDateString(),
            'time' => $e->time,
            'location' => $e->location,
        ]);

        return Inertia::render('Events/Index', [
            'upcoming' => $events->filter(fn ($e) => $e['date'] >= $today)->values(),
            'past' => $events->filter(fn ($e) => $e['date'] < $today)->sortByDesc('date')->values(),
            'types' => $this->types,
            'canManage' => $request->user()->can('event.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('event.manage'), 403);
        Event::create([...$this->validateEvent($request), 'created_by' => $request->user()->id]);

        return back()->with('success', 'Event added.');
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->can('event.manage'), 403);
        $event->update($this->validateEvent($request));

        return back()->with('success', 'Event updated.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->can('event.manage'), 403);
        $event->delete();

        return back()->with('success', 'Event deleted.');
    }

    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in($this->types)],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:160'],
        ]);
    }
}
