<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    /** Emojis allowed as quick reactions. */
    public const REACTIONS = ['👍', '❤️', '😂', '🎉', '😮', '🙏'];

    public function index(Request $request, ?User $user = null): Response
    {
        $me = $request->user();

        // Conversation list — every other staff member, with last message + unread count.
        $conversations = User::query()
            ->where('id', '!=', $me->id)
            ->orderBy('name')
            ->get()
            ->map(function (User $other) use ($me) {
                $last = Message::query()
                    ->where(fn ($q) => $q->where('sender_id', $me->id)->where('recipient_id', $other->id))
                    ->orWhere(fn ($q) => $q->where('sender_id', $other->id)->where('recipient_id', $me->id))
                    ->latest('id')->first();

                return [
                    'id' => $other->id,
                    'name' => $other->name,
                    'role' => config('rbac.roles')[$other->getRoleNames()->first()] ?? null,
                    'last' => $last ? ($last->body ?: ($last->attachment_path ? '📎 Attachment' : null)) : null,
                    'last_at' => $last?->created_at?->diffForHumans(),
                    'unread' => Message::where('sender_id', $other->id)->where('recipient_id', $me->id)->whereNull('read_at')->count(),
                ];
            });

        $thread = null;
        if ($user && $user->id !== $me->id) {
            // Mark their messages to me as read.
            Message::where('sender_id', $user->id)->where('recipient_id', $me->id)->whereNull('read_at')->update(['read_at' => now()]);

            $messages = Message::query()
                ->with('reactions')
                ->where(fn ($q) => $q->where('sender_id', $me->id)->where('recipient_id', $user->id))
                ->orWhere(fn ($q) => $q->where('sender_id', $user->id)->where('recipient_id', $me->id))
                ->orderBy('id')
                ->get();

            $thread = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => config('rbac.roles')[$user->getRoleNames()->first()] ?? null,
                ],
                'messages' => $messages->map(fn (Message $m) => [
                    'id' => $m->id,
                    'body' => $m->body,
                    'mine' => $m->sender_id === $me->id,
                    'at' => $m->created_at?->format('M j, g:i A'),
                    'read' => $m->read_at !== null,
                    'attachment' => $m->attachment_path ? [
                        'url' => route('messages.attachment', $m->id),
                        'name' => $m->attachment_name,
                        'is_image' => str_starts_with((string) $m->attachment_mime, 'image/'),
                    ] : null,
                    'reactions' => $this->groupReactions($m, $me->id),
                ]),
            ];
        }

        return Inertia::render('Messages/Index', [
            'conversations' => $conversations,
            'thread' => $thread,
            'reactionChoices' => self::REACTIONS,
        ]);
    }

    public function send(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        if (empty($data['body']) && ! $request->hasFile('attachment')) {
            return back()->withErrors(['body' => 'Type a message or attach a file.']);
        }

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            \App\Support\ImageOptimizer::uploaded($file, 1600, 80); // no-op for PDFs/docs
            $attachment = [
                'attachment_path' => $file->store('message-attachments', 'local'),
                'attachment_name' => $file->getClientOriginalName(),
                'attachment_mime' => $file->getMimeType(),
            ];
        }

        Message::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $user->id,
            'body' => $data['body'] ?? '',
            ...($attachment ?? []),
        ]);

        return back();
    }

    /** Toggle an emoji reaction on a message I'm a participant in. */
    public function react(Request $request, Message $message): RedirectResponse
    {
        $me = $request->user();
        abort_unless($message->involves($me->id), 403);

        $data = $request->validate(['emoji' => ['required', Rule::in(self::REACTIONS)]]);

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $me->id)->where('emoji', $data['emoji'])->first();

        if ($existing) {
            $existing->delete();
        } else {
            MessageReaction::create(['message_id' => $message->id, 'user_id' => $me->id, 'emoji' => $data['emoji']]);
        }

        return back();
    }

    /** Stream an attachment from the private disk — only to conversation participants. */
    public function attachment(Request $request, Message $message): StreamedResponse
    {
        abort_unless($message->involves($request->user()->id), 403);
        abort_unless($message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name);
    }

    /** [{emoji, count, mine}] for a message. */
    private function groupReactions(Message $message, int $meId): array
    {
        return $message->reactions
            ->groupBy('emoji')
            ->map(fn ($group, $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
                'mine' => $group->contains('user_id', $meId),
            ])
            ->values()->all();
    }
}
