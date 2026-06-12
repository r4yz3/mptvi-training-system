import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Pencil, Trash2, X, Calendar, MapPin, Clock, CalendarDays, History } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface EventItem {
    id: number; title: string; description: string | null; type: string;
    date: string; time: string | null; location: string | null;
}
interface Props { upcoming: EventItem[]; past: EventItem[]; types: string[]; canManage: boolean }

const TYPE_STYLE: Record<string, string> = {
    General: 'bg-slate-100 text-slate-600', Orientation: 'bg-brand-100 text-brand-700',
    Assessment: 'bg-amber-100 text-amber-700', Holiday: 'bg-rose-100 text-rose-700', Deadline: 'bg-indigo-100 text-indigo-700',
};
const TYPE_TILE: Record<string, string> = {
    General: 'bg-slate-50 text-slate-600', Orientation: 'bg-brand-50 text-brand-700',
    Assessment: 'bg-amber-50 text-amber-700', Holiday: 'bg-rose-50 text-rose-700', Deadline: 'bg-indigo-50 text-indigo-700',
};
const TYPE_BAR: Record<string, string> = {
    General: 'bg-slate-300', Orientation: 'bg-brand-500',
    Assessment: 'bg-amber-500', Holiday: 'bg-rose-500', Deadline: 'bg-indigo-500',
};

function relative(date: string) {
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const d = new Date(date + 'T00:00:00');
    const days = Math.round((d.getTime() - today.getTime()) / 86400000);
    if (days === 0) return 'Today';
    if (days === 1) return 'Tomorrow';
    if (days > 1 && days <= 14) return `in ${days} days`;
    return null;
}

export default function EventsIndex({ upcoming, past, types, canManage }: Props) {
    const [editing, setEditing] = useState<EventItem | 'new' | null>(null);
    const next = upcoming[0];

    const tiles = [
        { label: 'Upcoming', value: upcoming.length, icon: CalendarDays, tile: 'bg-brand-50 text-brand-600' },
        { label: 'Past', value: past.length, icon: History, tile: 'bg-slate-100 text-slate-500' },
    ];

    return (
        <AppShell title="Calendar & events">
            <Head title="Calendar & events" />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-wrap items-center gap-2.5">
                    {tiles.map((t) => (
                        <span key={t.label} className="inline-flex items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <span className={`flex h-8 w-8 items-center justify-center rounded-lg ${t.tile}`}><t.icon className="h-4 w-4" /></span>
                            <span className="text-lg font-semibold leading-none text-slate-800">{t.value}</span>
                            <span className="text-xs font-medium text-slate-400">{t.label}</span>
                        </span>
                    ))}
                    {next && (
                        <span className="inline-flex items-center gap-2 rounded-lg border border-brand-100 bg-brand-50/60 px-3 py-2 text-xs text-brand-700">
                            <span className="font-medium text-brand-500">Next up</span>
                            <span className="font-semibold">{next.title}</span>
                            <span className="text-brand-500">· {new Date(next.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</span>
                        </span>
                    )}
                </div>
                {canManage && <button onClick={() => setEditing('new')} className="btn-primary"><Plus className="h-4 w-4" /> Add event</button>}
            </div>

            {/* Type legend */}
            <div className="mb-4 flex flex-wrap gap-1.5">
                {types.map((t) => (
                    <span key={t} className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium ${TYPE_STYLE[t]}`}>
                        <span className={`h-1.5 w-1.5 rounded-full ${TYPE_BAR[t]}`} />{t}
                    </span>
                ))}
            </div>

            <Section title="Upcoming" events={upcoming} canManage={canManage} onEdit={setEditing} empty="No upcoming events." />
            {past.length > 0 && <div className="mt-8"><Section title="Past" events={past} canManage={canManage} onEdit={setEditing} muted /></div>}

            {editing && <EventModal event={editing === 'new' ? null : editing} types={types} onClose={() => setEditing(null)} />}
        </AppShell>
    );
}

function Section({ title, events, canManage, onEdit, empty, muted }: {
    title: string; events: EventItem[]; canManage: boolean;
    onEdit: (e: EventItem) => void; empty?: string; muted?: boolean;
}) {
    return (
        <div>
            <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{title}</h3>
            <div className={`space-y-2 ${muted ? 'opacity-70' : ''}`}>
                {events.map((e) => {
                    const d = new Date(e.date + 'T00:00:00');
                    const rel = !muted && relative(e.date);
                    return (
                        <div key={e.id} className="group flex items-center gap-4 overflow-hidden rounded-xl border border-slate-200 bg-white p-4 pl-0 shadow-sm transition hover:shadow">
                            <span className={`h-14 w-1 shrink-0 rounded-r ${TYPE_BAR[e.type]}`} />
                            <div className={`flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-lg ${TYPE_TILE[e.type]}`}>
                                <span className="text-[10px] font-medium uppercase">{d.toLocaleString('en', { month: 'short' })}</span>
                                <span className="text-xl font-bold leading-none">{d.getDate()}</span>
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium text-slate-800">{e.title}</span>
                                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${TYPE_STYLE[e.type]}`}>{e.type}</span>
                                    {rel && <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-600">{rel}</span>}
                                </div>
                                <div className="mt-0.5 flex flex-wrap gap-x-4 text-xs text-slate-500">
                                    <span className="inline-flex items-center gap-1"><Calendar className="h-3 w-3" /> {d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })}</span>
                                    {e.time && <span className="inline-flex items-center gap-1"><Clock className="h-3 w-3" /> {e.time}</span>}
                                    {e.location && <span className="inline-flex items-center gap-1"><MapPin className="h-3 w-3" /> {e.location}</span>}
                                </div>
                                {e.description && <p className="mt-1 text-xs text-slate-500">{e.description}</p>}
                            </div>
                            {canManage && (
                                <div className="flex gap-1 opacity-0 transition group-hover:opacity-100">
                                    <button onClick={() => onEdit(e)} className="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600"><Pencil className="h-4 w-4" /></button>
                                    <button onClick={() => { if (confirm('Delete this event?')) router.delete(`/events/${e.id}`, { preserveScroll: true }); }} className="rounded-md p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-4 w-4" /></button>
                                </div>
                            )}
                        </div>
                    );
                })}
                {events.length === 0 && empty && (
                    <div className="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center text-sm text-slate-400">
                        <Calendar className="mx-auto mb-2 h-7 w-7 text-slate-300" />{empty}
                    </div>
                )}
            </div>
        </div>
    );
}

function EventModal({ event, types, onClose }: { event: EventItem | null; types: string[]; onClose: () => void }) {
    const isEdit = !!event;
    const { data, setData, post, put, processing, errors } = useForm({
        title: event?.title ?? '', description: event?.description ?? '', type: event?.type ?? 'General',
        date: event?.date ?? new Date().toISOString().slice(0, 10), time: event?.time ?? '', location: event?.location ?? '',
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onClose };
        isEdit ? put(`/events/${event!.id}`, opts) : post('/events', opts);
    };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div className="flex items-center gap-2.5">
                        <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><CalendarDays className="h-5 w-5" /></span>
                        <h3 className="text-base font-semibold text-slate-800">{isEdit ? 'Edit event' : 'Add event'}</h3>
                    </div>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-3 px-5 py-4">
                    <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Title</span>
                        <input className="input" value={data.title} onChange={(e) => setData('title', e.target.value)} autoFocus />
                        {errors.title && <span className="text-xs text-rose-600">{errors.title}</span>}
                    </label>
                    <div className="grid grid-cols-2 gap-3">
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Type</span>
                            <select className="input" value={data.type} onChange={(e) => setData('type', e.target.value)}>{types.map((t) => <option key={t}>{t}</option>)}</select>
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Date</span>
                            <input type="date" className="input" value={data.date} onChange={(e) => setData('date', e.target.value)} />
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Time</span>
                            <input className="input" value={data.time} onChange={(e) => setData('time', e.target.value)} placeholder="8:00 AM" />
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Location</span>
                            <input className="input" value={data.location} onChange={(e) => setData('location', e.target.value)} />
                        </label>
                    </div>
                    <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Description</span>
                        <textarea className="input" rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                    </label>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary">{isEdit ? 'Save' : 'Add event'}</button>
                    </div>
                </form>
            </div>
        </div>
    );
}
