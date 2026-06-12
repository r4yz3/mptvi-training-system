import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, GraduationCap } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import StatusBadge from '@/Components/StatusBadge';

interface RosterRow {
    id: number; name: string; uli: string | null; status: string; rate: number; today: string | null;
}
interface Props {
    batch: { id: number; code: string; program: string | null; start_date: string | null; end_date: string | null };
    roster: RosterRow[];
    date: string;
    canMark: boolean;
    statuses: string[];
}

const MARK_STYLE: Record<string, string> = {
    Present: 'bg-emerald-600 text-white', Late: 'bg-amber-500 text-white',
    Absent: 'bg-rose-600 text-white', Excused: 'bg-slate-500 text-white',
};
const CHIP: Record<string, string> = {
    Present: 'bg-emerald-50 text-emerald-700', Late: 'bg-amber-50 text-amber-700',
    Absent: 'bg-rose-50 text-rose-700', Excused: 'bg-slate-100 text-slate-600',
};
function initials(name: string) {
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}

export default function TrainingShow({ batch, roster, date, canMark, statuses }: Props) {
    const setDate = (d: string) =>
        router.get(`/training/${batch.id}`, { date: d }, { preserveState: true, preserveScroll: true, replace: true });
    const mark = (applicantId: number, status: string) =>
        router.post(`/training/${applicantId}/attendance`, { date, status }, { preserveScroll: true });

    const today = new Date().toISOString().slice(0, 10);
    const counts = statuses.reduce((a, s) => ({ ...a, [s]: roster.filter((r) => r.today === s).length }), {} as Record<string, number>);
    const notMarked = roster.filter((r) => !r.today).length;

    return (
        <AppShell title={`Attendance — Batch ${batch.code}`}>
            <Head title={`Attendance ${batch.code}`} />

            <Link href="/training" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to batches
            </Link>

            {/* Hero */}
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="h-1.5 bg-gradient-to-r from-brand-600 to-brand-400" />
                <div className="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex items-center gap-3.5">
                        <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><GraduationCap className="h-6 w-6" /></span>
                        <div>
                            <h2 className="font-semibold text-slate-800">{batch.program}</h2>
                            <div className="text-xs text-slate-500">Batch {batch.code} · {batch.start_date} → {batch.end_date}</div>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <CalendarDays className="h-4 w-4 text-slate-400" />
                        <input type="date" className="input w-auto" value={date} onChange={(e) => setDate(e.target.value)} />
                        {date !== today && (
                            <button onClick={() => setDate(today)} className="rounded-md border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Today</button>
                        )}
                    </div>
                </div>

                {/* Today summary */}
                <div className="flex flex-wrap gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 text-xs">
                    {statuses.map((s) => (
                        <span key={s} className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 font-medium ${CHIP[s]}`}>
                            {s} <span className="font-semibold">{counts[s] ?? 0}</span>
                        </span>
                    ))}
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-0.5 font-medium text-slate-400 ring-1 ring-slate-200">
                        Not marked <span className="font-semibold">{notMarked}</span>
                    </span>
                </div>
            </div>

            {/* Roster */}
            <div className="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Trainee</th>
                                <th className="px-4 py-3">Pipeline</th>
                                <th className="px-4 py-3">Overall attendance</th>
                                <th className="px-4 py-3">Mark for {date}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {roster.map((r) => (
                                <tr key={r.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{initials(r.name)}</span>
                                            <div>
                                                <div className="font-medium text-slate-800">{r.name}</div>
                                                <div className="font-mono text-xs text-slate-400">{r.uli}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3"><StatusBadge status={r.status} /></td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <div className="h-2 w-20 overflow-hidden rounded-full bg-slate-100">
                                                <div className={`h-full rounded-full ${r.rate >= 80 ? 'bg-emerald-500' : r.rate >= 50 ? 'bg-amber-500' : 'bg-rose-400'}`} style={{ width: `${r.rate}%` }} />
                                            </div>
                                            <span className={`text-xs font-semibold ${r.rate >= 80 ? 'text-emerald-600' : r.rate >= 50 ? 'text-amber-600' : 'text-slate-400'}`}>{r.rate}%</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {canMark ? (
                                            <div className="inline-flex flex-wrap gap-1 rounded-lg bg-slate-100 p-0.5">
                                                {statuses.map((s) => (
                                                    <button
                                                        key={s}
                                                        onClick={() => mark(r.id, s)}
                                                        className={`rounded-md px-2.5 py-1 text-xs font-medium transition ${r.today === s ? MARK_STYLE[s] : 'text-slate-500 hover:bg-white'}`}
                                                    >
                                                        {s}
                                                    </button>
                                                ))}
                                            </div>
                                        ) : (
                                            r.today
                                                ? <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${CHIP[r.today]}`}>{r.today}</span>
                                                : <span className="text-xs text-slate-400">Not marked</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {roster.length === 0 && (
                                <tr><td colSpan={4} className="px-4 py-12 text-center text-sm text-slate-400">No trainees enrolled in this batch yet.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppShell>
    );
}
