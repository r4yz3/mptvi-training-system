import { Head, router } from '@inertiajs/react';
import { Plus, Pencil, Trash2, History, Activity as ActivityIcon, CalendarClock, X } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import Pagination from '@/Components/Pagination';

interface Row { id: number; user: string; event: string; subject_type: string; description: string; at: string; at_full: string }
interface Paginated { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number }
interface Stats { total: number; today: number; created: number; updated: number }

const EVENT_STYLE: Record<string, { icon: React.ReactNode; ring: string; label: string }> = {
    created: { icon: <Plus className="h-4 w-4" />, ring: 'bg-emerald-50 text-emerald-600', label: 'Created' },
    updated: { icon: <Pencil className="h-4 w-4" />, ring: 'bg-amber-50 text-amber-600', label: 'Updated' },
    deleted: { icon: <Trash2 className="h-4 w-4" />, ring: 'bg-rose-50 text-rose-600', label: 'Deleted' },
};
function initials(name: string) {
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}
function dayLabel(full: string) {
    const d = new Date(full.replace(' ', 'T'));
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const that = new Date(d); that.setHours(0, 0, 0, 0);
    const diff = Math.round((today.getTime() - that.getTime()) / 86400000);
    if (diff === 0) return 'Today';
    if (diff === 1) return 'Yesterday';
    return d.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
}

export default function ActivityIndex({
    activities, filters, subjectTypes, stats,
}: {
    activities: Paginated; filters: { type?: string; event?: string }; subjectTypes: string[]; stats: Stats;
}) {
    const set = (k: string, v: string) => {
        const next = { ...filters, [k]: v };
        const clean = Object.fromEntries(Object.entries(next).filter(([, val]) => val));
        router.get('/activity', clean, { preserveState: true, preserveScroll: true, replace: true });
    };
    const hasFilter = !!(filters.type || filters.event);

    const tiles = [
        { label: 'Total events', value: stats.total, icon: ActivityIcon, tint: 'bg-brand-50 text-brand-600' },
        { label: 'Today', value: stats.today, icon: CalendarClock, tint: 'bg-sky-50 text-sky-600' },
        { label: 'Created', value: stats.created, icon: Plus, tint: 'bg-emerald-50 text-emerald-600' },
        { label: 'Updated', value: stats.updated, icon: Pencil, tint: 'bg-amber-50 text-amber-600' },
    ];

    // Group the current page by day.
    const groups: { day: string; rows: Row[] }[] = [];
    for (const a of activities.data) {
        const day = dayLabel(a.at_full);
        const last = groups[groups.length - 1];
        if (last && last.day === day) last.rows.push(a);
        else groups.push({ day, rows: [a] });
    }

    return (
        <AppShell title="Activity log">
            <Head title="Activity log" />

            <div className="mb-5 flex flex-wrap items-center gap-2.5">
                {tiles.map((t) => (
                    <span key={t.label} className="inline-flex items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <span className={`flex h-8 w-8 items-center justify-center rounded-lg ${t.tint}`}><t.icon className="h-4 w-4" /></span>
                        <span className="text-lg font-semibold leading-none text-slate-800">{t.value}</span>
                        <span className="text-xs font-medium text-slate-400">{t.label}</span>
                    </span>
                ))}
            </div>

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <select className="input w-auto" value={filters.type ?? ''} onChange={(e) => set('type', e.target.value)}>
                    <option value="">All records</option>
                    {subjectTypes.map((t) => <option key={t} value={t}>{t}</option>)}
                </select>
                <select className="input w-auto" value={filters.event ?? ''} onChange={(e) => set('event', e.target.value)}>
                    <option value="">All events</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
                {hasFilter && (
                    <button onClick={() => router.get('/activity', {}, { preserveScroll: true, replace: true })} className="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100">
                        <X className="h-3.5 w-3.5" /> Clear
                    </button>
                )}
            </div>

            <div className="space-y-5">
                {groups.map((g) => (
                    <div key={g.day}>
                        <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{g.day}</div>
                        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <ul className="divide-y divide-slate-100">
                                {g.rows.map((a) => {
                                    const ev = EVENT_STYLE[a.event] ?? { icon: <History className="h-4 w-4" />, ring: 'bg-slate-100 text-slate-400', label: a.event };
                                    return (
                                        <li key={a.id} className="flex items-center gap-3 px-4 py-3 hover:bg-slate-50">
                                            <div className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${ev.ring}`}>{ev.icon}</div>
                                            <div className="min-w-0 flex-1">
                                                <div className="text-sm text-slate-800">{a.description}</div>
                                                <div className="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-400">
                                                    <span className="inline-flex items-center gap-1">
                                                        <span className="flex h-4 w-4 items-center justify-center rounded-full bg-brand-100 text-[8px] font-semibold text-brand-700">{initials(a.user)}</span>
                                                        {a.user}
                                                    </span>
                                                    · <span className="rounded bg-slate-100 px-1.5 py-0.5">{a.subject_type}</span>
                                                    · <span className={`rounded px-1.5 py-0.5 font-medium ${ev.ring}`}>{ev.label}</span>
                                                </div>
                                            </div>
                                            <div className="shrink-0 text-right text-xs text-slate-400" title={a.at_full}>{a.at}</div>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    </div>
                ))}
                {activities.data.length === 0 && (
                    <div className="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-400">
                        <History className="mx-auto mb-2 h-7 w-7 text-slate-300" /> No activity recorded.
                    </div>
                )}
            </div>
            <Pagination links={activities.links} from={activities.from} to={activities.to} total={activities.total} />
        </AppShell>
    );
}
