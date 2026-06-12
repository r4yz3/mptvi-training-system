import { FormEvent, useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Send, MessageSquare, ArrowLeft, Check, CheckCheck, Paperclip, Smile, X, FileText, SmilePlus } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Convo { id: number; name: string; role: string | null; last: string | null; last_at: string | null; unread: number }
interface Reaction { emoji: string; count: number; mine: boolean }
interface Attachment { url: string; name: string; is_image: boolean }
interface ThreadMsg { id: number; body: string; mine: boolean; at: string; read: boolean; attachment: Attachment | null; reactions: Reaction[] }
interface Thread { user: { id: number; name: string; role?: string | null }; messages: ThreadMsg[] }

const EMOJIS = ['😀', '😁', '😂', '🤣', '😊', '😍', '😉', '😎', '🤔', '😴', '😢', '😡', '👍', '👎', '🙏', '👏', '🙌', '💪', '🎉', '🔥', '❤️', '✅', '❌', '⭐', '💯', '📌'];

function initials(name: string) {
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}

export default function MessagesIndex({ conversations, thread, reactionChoices }: { conversations: Convo[]; thread: Thread | null; reactionChoices: string[] }) {
    const endRef = useRef<HTMLDivElement>(null);
    const totalUnread = conversations.reduce((s, c) => s + c.unread, 0);

    // Lightweight polling while a thread is open (Reverb websockets planned later).
    useEffect(() => {
        if (!thread) return;
        const t = setInterval(() => router.reload({ only: ['thread', 'conversations'] }), 5000);
        return () => clearInterval(t);
    }, [thread?.user.id]);

    useEffect(() => { endRef.current?.scrollIntoView(); }, [thread?.messages.length]);

    return (
        <AppShell title="Messages">
            <Head title="Messages" />

            <div className="flex h-[calc(100vh-9rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                {/* Conversation list */}
                <div className={`flex w-full flex-col border-r border-slate-100 sm:w-80 ${thread ? 'hidden sm:flex' : ''}`}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <span className="text-sm font-semibold text-slate-700">Conversations</span>
                        {totalUnread > 0 && <span className="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-semibold text-white">{totalUnread} new</span>}
                    </div>
                    <div className="flex-1 overflow-y-auto">
                        {conversations.map((c) => {
                            const active = thread?.user.id === c.id;
                            return (
                                <Link
                                    key={c.id}
                                    href={`/messages/${c.id}`}
                                    preserveScroll
                                    className={`relative flex items-center gap-3 border-b border-slate-50 px-4 py-3 transition hover:bg-slate-50 ${active ? 'bg-brand-50' : ''}`}
                                >
                                    {active && <span className="absolute inset-y-2 left-0 w-1 rounded-r bg-brand-600" />}
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-brand-500 text-sm font-semibold text-white">{initials(c.name)}</div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center justify-between gap-2">
                                            <span className={`truncate text-sm ${c.unread > 0 ? 'font-semibold text-slate-900' : 'font-medium text-slate-800'}`}>{c.name}</span>
                                            {c.last_at && <span className="shrink-0 text-[10px] text-slate-400">{c.last_at}</span>}
                                        </div>
                                        <div className="flex items-center justify-between gap-2">
                                            <span className={`truncate text-xs ${c.unread > 0 ? 'text-slate-600' : 'text-slate-400'}`}>{c.last ?? c.role ?? 'No messages yet'}</span>
                                            {c.unread > 0 && <span className="shrink-0 rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">{c.unread}</span>}
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                        {conversations.length === 0 && (
                            <div className="px-4 py-10 text-center text-sm text-slate-400">No staff to message yet.</div>
                        )}
                    </div>
                </div>

                {/* Thread */}
                <div className="flex flex-1 flex-col">
                    {thread ? <ThreadPane thread={thread} endRef={endRef} reactionChoices={reactionChoices} /> : (
                        <div className="flex flex-1 flex-col items-center justify-center text-slate-400">
                            <MessageSquare className="mb-2 h-10 w-10 text-slate-200" />
                            <p className="text-sm">Select a conversation to start messaging.</p>
                        </div>
                    )}
                </div>
            </div>
        </AppShell>
    );
}

function ThreadPane({ thread, endRef, reactionChoices }: { thread: Thread; endRef: React.RefObject<HTMLDivElement>; reactionChoices: string[] }) {
    const { data, setData, post, processing, reset } = useForm<{ body: string; attachment: File | null }>({ body: '', attachment: null });
    const fileRef = useRef<HTMLInputElement>(null);
    const [showEmoji, setShowEmoji] = useState(false);

    // Index of the last message I sent (for the single "Seen" indicator).
    const lastMineIdx = (() => { for (let i = thread.messages.length - 1; i >= 0; i--) if (thread.messages[i].mine) return i; return -1; })();

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (!data.body.trim() && !data.attachment) return;
        post(`/messages/${thread.user.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => { reset(); if (fileRef.current) fileRef.current.value = ''; },
        });
    };

    const react = (messageId: number, emoji: string) =>
        router.post(`/messages/react/${messageId}`, { emoji }, { preserveScroll: true, preserveState: true });

    return (
        <>
            <div className="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
                <Link href="/messages" className="text-slate-400 hover:text-brand-600 sm:hidden"><ArrowLeft className="h-4 w-4" /></Link>
                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-brand-500 text-sm font-semibold text-white">{initials(thread.user.name)}</div>
                <div className="leading-tight">
                    <div className="font-medium text-slate-800">{thread.user.name}</div>
                    {thread.user.role && <div className="text-xs text-slate-400">{thread.user.role}</div>}
                </div>
            </div>

            <div className="flex-1 space-y-1.5 overflow-y-auto bg-slate-50 px-4 py-4">
                {thread.messages.map((m, idx) => (
                    <Bubble key={m.id} m={m} reactionChoices={reactionChoices} onReact={react}
                        showSeen={m.mine && idx === lastMineIdx && m.read} />
                ))}
                {thread.messages.length === 0 && <p className="py-8 text-center text-sm text-slate-400">No messages yet. Say hello!</p>}
                <div ref={endRef} />
            </div>

            {/* Selected attachment preview */}
            {data.attachment && (
                <div className="flex items-center gap-2 border-t border-slate-100 bg-slate-50 px-4 py-2 text-xs">
                    <FileText className="h-4 w-4 text-brand-600" />
                    <span className="truncate text-slate-600">{data.attachment.name}</span>
                    <button onClick={() => { setData('attachment', null); if (fileRef.current) fileRef.current.value = ''; }} className="ml-auto rounded p-0.5 text-slate-400 hover:bg-slate-200"><X className="h-3.5 w-3.5" /></button>
                </div>
            )}

            <form onSubmit={submit} className="relative flex items-center gap-1.5 border-t border-slate-100 px-3 py-3">
                {/* Emoji picker */}
                {showEmoji && (
                    <>
                        <div className="fixed inset-0 z-10" onClick={() => setShowEmoji(false)} />
                        <div className="absolute bottom-14 left-3 z-20 grid w-64 grid-cols-8 gap-0.5 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                            {EMOJIS.map((e) => (
                                <button key={e} type="button" onClick={() => { setData('body', data.body + e); setShowEmoji(false); }}
                                    className="rounded p-1 text-lg hover:bg-slate-100">{e}</button>
                            ))}
                        </div>
                    </>
                )}
                <button type="button" onClick={() => setShowEmoji((v) => !v)} className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-brand-600"><Smile className="h-5 w-5" /></button>
                <button type="button" onClick={() => fileRef.current?.click()} className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-brand-600"><Paperclip className="h-5 w-5" /></button>
                <input ref={fileRef} type="file" className="hidden" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                    onChange={(e) => setData('attachment', e.target.files?.[0] ?? null)} />

                <input className="input flex-1 rounded-full" placeholder="Type a message…" value={data.body} onChange={(e) => setData('body', e.target.value)} />
                <button type="submit" disabled={processing || (!data.body.trim() && !data.attachment)} className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white transition hover:bg-brand-700 disabled:opacity-40"><Send className="h-4 w-4" /></button>
            </form>
        </>
    );
}

function Bubble({ m, reactionChoices, onReact, showSeen }: { m: ThreadMsg; reactionChoices: string[]; onReact: (id: number, emoji: string) => void; showSeen: boolean }) {
    const [menu, setMenu] = useState(false);
    return (
        <div className={`group flex flex-col ${m.mine ? 'items-end' : 'items-start'}`}>
            <div className={`flex items-center gap-1 ${m.mine ? 'flex-row-reverse' : ''}`}>
                <div className={`max-w-[78%] px-3.5 py-2 text-sm shadow-sm ${m.mine ? 'rounded-2xl rounded-br-sm bg-brand-600 text-white' : 'rounded-2xl rounded-bl-sm bg-white text-slate-700'}`}>
                    {m.attachment && (
                        m.attachment.is_image
                            ? <a href={m.attachment.url} target="_blank" rel="noopener"><img src={m.attachment.url} alt={m.attachment.name} className="mb-1 max-h-48 rounded-lg object-cover" /></a>
                            : <a href={m.attachment.url} target="_blank" rel="noopener" className={`mb-1 flex items-center gap-2 rounded-lg px-2 py-1.5 ${m.mine ? 'bg-white/15' : 'bg-slate-100'}`}>
                                <FileText className="h-4 w-4 shrink-0" /><span className="truncate text-xs underline">{m.attachment.name}</span>
                              </a>
                    )}
                    {m.body && <div className="whitespace-pre-wrap break-words">{m.body}</div>}
                    <div className={`mt-0.5 flex items-center justify-end gap-1 text-[10px] ${m.mine ? 'text-white/60' : 'text-slate-400'}`}>
                        {m.at}
                        {m.mine && (m.read ? <CheckCheck className="h-3 w-3" /> : <Check className="h-3 w-3" />)}
                    </div>
                </div>

                {/* React button */}
                <div className="relative">
                    <button onClick={() => setMenu((v) => !v)} className="flex h-7 w-7 items-center justify-center rounded-full text-slate-300 opacity-0 transition hover:bg-slate-100 hover:text-brand-600 group-hover:opacity-100">
                        <SmilePlus className="h-4 w-4" />
                    </button>
                    {menu && (
                        <>
                            <div className="fixed inset-0 z-10" onClick={() => setMenu(false)} />
                            <div className={`absolute bottom-8 z-20 flex gap-0.5 rounded-full border border-slate-200 bg-white p-1 shadow-lg ${m.mine ? 'right-0' : 'left-0'}`}>
                                {reactionChoices.map((e) => (
                                    <button key={e} onClick={() => { onReact(m.id, e); setMenu(false); }} className="rounded-full p-1 text-base hover:bg-slate-100">{e}</button>
                                ))}
                            </div>
                        </>
                    )}
                </div>
            </div>

            {/* Reaction chips */}
            {m.reactions.length > 0 && (
                <div className={`mt-0.5 flex flex-wrap gap-1 ${m.mine ? 'justify-end' : ''}`}>
                    {m.reactions.map((r) => (
                        <button key={r.emoji} onClick={() => onReact(m.id, r.emoji)}
                            className={`inline-flex items-center gap-0.5 rounded-full border px-1.5 py-0.5 text-[11px] ${r.mine ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-600'}`}>
                            <span>{r.emoji}</span><span className="font-medium">{r.count}</span>
                        </button>
                    ))}
                </div>
            )}

            {showSeen && <span className="mt-0.5 text-[10px] text-slate-400">Seen</span>}
        </div>
    );
}
