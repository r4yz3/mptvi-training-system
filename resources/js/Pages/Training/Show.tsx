import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, GraduationCap, Award, X } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import StatusBadge from '@/Components/StatusBadge';
import TraineeStatusBadge from '@/Components/TraineeStatusBadge';

interface GradeSummary { scores: Record<string, number>; final: number | null; remark: string }
interface GradeComponent { key: string; label: string; weight: number }
interface RosterRow {
    id: number; name: string; status: string; trainee_status: string | null; rate: number; today: string | null;
    grade: GradeSummary;
}
interface Props {
    batch: { id: number; code: string; program: string | null; start_date: string | null; end_date: string | null };
    roster: RosterRow[];
    date: string;
    canMark: boolean;
    statuses: string[];
    canSetStatus: boolean;
    traineeStatuses: string[];
    canGrade: boolean;
    gradeComponents: GradeComponent[];
    passingGrade: number;
}

const REMARK_STYLE: Record<string, string> = {
    Passed: 'bg-emerald-50 text-emerald-700',
    Failed: 'bg-rose-50 text-rose-700',
    Incomplete: 'bg-slate-100 text-slate-500',
};

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

export default function TrainingShow({ batch, roster, date, canMark, statuses, canSetStatus, traineeStatuses, canGrade, gradeComponents, passingGrade }: Props) {
    const [grading, setGrading] = useState<RosterRow | null>(null);
    const setDate = (d: string) =>
        router.get(`/training/${batch.id}`, { date: d }, { preserveState: true, preserveScroll: true, replace: true });
    const mark = (applicantId: number, status: string) =>
        router.post(`/training/${applicantId}/attendance`, { date, status }, { preserveScroll: true });
    const setTrainee = (applicantId: number, value: string) =>
        router.put(`/applicants/${applicantId}/trainee-status`, { trainee_status: value }, { preserveScroll: true });

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
                                <th className="px-4 py-3">Training status</th>
                                <th className="px-4 py-3">Overall attendance</th>
                                <th className="px-4 py-3">Final grade</th>
                                <th className="px-4 py-3">Mark for {date}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {roster.map((r) => (
                                <tr key={r.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{initials(r.name)}</span>
                                            <div className="font-medium text-slate-800">{r.name}</div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3"><StatusBadge status={r.status} /></td>
                                    <td className="px-4 py-3">
                                        {canSetStatus ? (
                                            <select
                                                value={r.trainee_status ?? ''}
                                                onChange={(e) => setTrainee(r.id, e.target.value)}
                                                className="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 focus:border-brand-400 focus:ring-brand-400"
                                            >
                                                <option value="">Not set</option>
                                                {traineeStatuses.map((s) => <option key={s} value={s}>{s}</option>)}
                                            </select>
                                        ) : (
                                            r.trainee_status ? <TraineeStatusBadge status={r.trainee_status} /> : <span className="text-xs text-slate-400">—</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <div className="h-2 w-20 overflow-hidden rounded-full bg-slate-100">
                                                <div className={`h-full rounded-full ${r.rate >= 80 ? 'bg-emerald-500' : r.rate >= 50 ? 'bg-amber-500' : 'bg-rose-400'}`} style={{ width: `${r.rate}%` }} />
                                            </div>
                                            <span className={`text-xs font-semibold ${r.rate >= 80 ? 'text-emerald-600' : r.rate >= 50 ? 'text-amber-600' : 'text-slate-400'}`}>{r.rate}%</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            {r.grade.final !== null
                                                ? <span className={`text-sm font-semibold ${r.grade.remark === 'Passed' ? 'text-emerald-600' : 'text-rose-600'}`}>{r.grade.final}</span>
                                                : <span className="text-xs text-slate-400">—</span>}
                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium ${REMARK_STYLE[r.grade.remark]}`}>{r.grade.remark}</span>
                                            {canGrade && (
                                                <button
                                                    onClick={() => setGrading(r)}
                                                    className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600 hover:border-brand-200 hover:text-brand-700"
                                                >
                                                    <Award className="h-3.5 w-3.5" /> Grades
                                                </button>
                                            )}
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
                                <tr><td colSpan={6} className="px-4 py-12 text-center text-sm text-slate-400">No trainees enrolled in this batch yet.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {grading && (
                <GradeModal
                    trainee={grading}
                    components={gradeComponents}
                    passing={passingGrade}
                    onClose={() => setGrading(null)}
                />
            )}
        </AppShell>
    );
}

/** Score entry per configured component; final grade previews live as you type. */
function GradeModal({ trainee, components, passing, onClose }: {
    trainee: RosterRow; components: GradeComponent[]; passing: number; onClose: () => void;
}) {
    const { data, setData, put, processing, errors } = useForm({
        scores: Object.fromEntries(components.map((c) => [c.key, trainee.grade.scores[c.key] ?? ''])) as Record<string, number | ''>,
    });

    const entries = components.map((c) => ({ ...c, score: data.scores[c.key] }));
    const complete = entries.every((e) => e.score !== '' && e.score !== null);
    const weightTotal = entries.reduce((s, e) => s + e.weight, 0);
    const final = complete && weightTotal > 0
        ? Math.round(entries.reduce((s, e) => s + Number(e.score) * e.weight, 0) / weightTotal * 10) / 10
        : null;
    const remark = final === null ? 'Incomplete' : final >= passing ? 'Passed' : 'Failed';

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/training/${trainee.id}/grades`, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-4">
            <div className="max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white pb-[env(safe-area-inset-bottom)] shadow-xl sm:max-h-[calc(100vh-3rem)] sm:rounded-xl sm:pb-0">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600"><Award className="h-5 w-5" /></span>
                        <div>
                            <h3 className="text-base font-semibold text-slate-800">Grades — {trainee.name}</h3>
                            <p className="text-xs text-slate-500">Score each component 0–100 · passing grade {passing}</p>
                        </div>
                    </div>
                    <button type="button" onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-3 px-5 py-4">
                    {components.map((c) => (
                        <label key={c.key} className="flex items-center justify-between gap-3">
                            <span className="text-sm text-slate-600">{c.label} <span className="text-xs text-slate-400">({c.weight}%)</span></span>
                            <input
                                type="number" inputMode="decimal" min={0} max={100} step="0.1"
                                className="input w-24 text-center"
                                value={data.scores[c.key]}
                                onChange={(e) => setData('scores', { ...data.scores, [c.key]: e.target.value === '' ? '' : Number(e.target.value) })}
                            />
                        </label>
                    ))}
                    {Object.values(errors).length > 0 && <p className="text-xs text-rose-600">{Object.values(errors)[0]}</p>}

                    <div className="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2.5">
                        <span className="text-sm font-medium text-slate-600">Final grade</span>
                        <span className="flex items-center gap-2">
                            <span className={`text-lg font-bold ${final === null ? 'text-slate-400' : remark === 'Passed' ? 'text-emerald-600' : 'text-rose-600'}`}>{final ?? '—'}</span>
                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${REMARK_STYLE[remark]}`}>{remark}</span>
                        </span>
                    </div>

                    <div className="flex gap-2 border-t border-slate-100 pt-3 sm:justify-end">
                        <button type="button" onClick={onClose} className="btn-ghost justify-center max-sm:flex-1">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary py-2.5 max-sm:flex-[2] sm:py-2">Save grades</button>
                    </div>
                </form>
            </div>
        </div>
    );
}
