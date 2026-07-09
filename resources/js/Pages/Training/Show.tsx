import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, GraduationCap, ListChecks, X, Printer, FileText, CheckCircle2, Play } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import StatusBadge from '@/Components/StatusBadge';
import TraineeStatusBadge from '@/Components/TraineeStatusBadge';

interface UnitResult {
    unit_id: number; code: string | null; title: string; type: string;
    result: string | null; rated_at: string | null; remarks: string | null;
}
interface CompetencySummary { units: UnitResult[]; total: number; competent: number; complete: boolean }
interface Unit { id: number; code: string | null; title: string; type: string }
interface RosterRow {
    id: number; name: string; status: string; trainee_status: string | null;
    competency: CompetencySummary;
}
interface Props {
    batch: { id: number; code: string; program: string | null; program_id: number | null; start_date: string | null; end_date: string | null };
    roster: RosterRow[];
    canSetStatus: boolean;
    traineeStatuses: string[];
    canGrade: boolean;
    canStartTraining: boolean;
    units: Unit[];
    results: string[];
}

const TYPE_STYLE: Record<string, string> = {
    Basic: 'bg-sky-50 text-sky-700',
    Common: 'bg-violet-50 text-violet-700',
    Core: 'bg-emerald-50 text-emerald-700',
};

function initials(name: string) {
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}

export default function TrainingShow({ batch, roster, canSetStatus, traineeStatuses, canGrade, canStartTraining, units, results }: Props) {
    const [rating, setRating] = useState<RosterRow | null>(null);
    const setTrainee = (applicantId: number, value: string) =>
        router.put(`/applicants/${applicantId}/trainee-status`, { trainee_status: value }, { preserveScroll: true });
    const startTraining = (applicantId: number) =>
        router.post(`/training/${applicantId}/start`, {}, { preserveScroll: true });

    const today = new Date().toISOString().slice(0, 10);
    const noUnits = units.length === 0;
    const showAction = canStartTraining && roster.some((r) => r.status === 'Paid');

    return (
        <AppShell title={`Training — Batch ${batch.code}`}>
            <Head title={`Training ${batch.code}`} />

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
                    {canGrade && roster.length > 0 && !noUnits && (
                        <a href={`/training/${batch.id}/class-record`} target="_blank" rel="noopener noreferrer" className="btn-ghost self-start">
                            <Printer className="h-4 w-4" /> Achievement chart
                        </a>
                    )}
                </div>
            </div>

            {/* No competency units defined for this program */}
            {canGrade && noUnits && (
                <div className="mt-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <ListChecks className="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                        No Units of Competency are defined for this qualification yet, so trainees can’t be rated.
                        Set them up in <Link href="/settings/competencies" className="font-semibold underline">Settings → Competency Standards</Link>.
                    </p>
                </div>
            )}

            {/* Roster */}
            <div className="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Trainee</th>
                                <th className="px-4 py-3">Pipeline</th>
                                <th className="px-4 py-3">Training status</th>
                                <th className="px-4 py-3">Competency</th>
                                {showAction && <th className="px-4 py-3" />}
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
                                        <CompetencyCell row={r} />
                                        <div className="mt-1.5 flex items-center gap-1.5">
                                            {canGrade && r.competency.total > 0 && (
                                                <button
                                                    onClick={() => setRating(r)}
                                                    className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600 hover:border-brand-200 hover:text-brand-700"
                                                >
                                                    <ListChecks className="h-3.5 w-3.5" /> Rate
                                                </button>
                                            )}
                                            {canGrade && r.competency.total > 0 && (
                                                <a
                                                    href={`/training/${r.id}/report-card`} target="_blank" rel="noopener noreferrer"
                                                    title="Print Competency Achievement Record"
                                                    className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-slate-500 hover:border-brand-200 hover:text-brand-700"
                                                >
                                                    <FileText className="h-3.5 w-3.5" /> Record
                                                </a>
                                            )}
                                        </div>
                                    </td>
                                    {showAction && (
                                        <td className="px-4 py-3 text-right">
                                            {r.status === 'Paid' && (
                                                <button
                                                    onClick={() => startTraining(r.id)}
                                                    className="inline-flex items-center gap-1 rounded-md bg-brand-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700"
                                                    title="Move this Paid learner into training"
                                                >
                                                    <Play className="h-3.5 w-3.5" /> Start training
                                                </button>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            ))}
                            {roster.length === 0 && (
                                <tr><td colSpan={showAction ? 5 : 4} className="px-4 py-12 text-center text-sm text-slate-400">No trainees enrolled in this batch yet.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {rating && (
                <CompetencyModal
                    trainee={rating}
                    units={units}
                    results={results}
                    today={today}
                    onClose={() => setRating(null)}
                />
            )}
        </AppShell>
    );
}

/** Compact "X/Y competent" progress with a Complete badge. */
function CompetencyCell({ row }: { row: RosterRow }) {
    const { total, competent, complete } = row.competency;
    if (total === 0) return <span className="text-xs text-slate-400">No units</span>;
    const pct = Math.round((competent / total) * 100);

    return (
        <div className="flex items-center gap-2">
            <div className="h-2 w-20 overflow-hidden rounded-full bg-slate-100">
                <div className={`h-full rounded-full ${complete ? 'bg-emerald-500' : 'bg-brand-500'}`} style={{ width: `${pct}%` }} />
            </div>
            <span className="text-xs font-semibold text-slate-600">{competent}/{total}</span>
            {complete && (
                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">
                    <CheckCircle2 className="h-3 w-3" /> Complete
                </span>
            )}
        </div>
    );
}

/** Rate each Unit of Competency Competent / Not Yet Competent for one trainee. */
function CompetencyModal({ trainee, units, results, today, onClose }: {
    trainee: RosterRow; units: Unit[]; results: string[]; today: string; onClose: () => void;
}) {
    const initial = Object.fromEntries(
        trainee.competency.units.map((u) => [u.unit_id, u.result]),
    ) as Record<number, string | null>;

    const { data, setData, put, processing } = useForm({
        ratings: units.map((u) => ({ unit_id: u.id, result: initial[u.id] ?? null })) as { unit_id: number; result: string | null }[],
        rated_at: today,
    });

    const setResult = (unitId: number, result: string | null) =>
        setData('ratings', data.ratings.map((r) => (r.unit_id === unitId ? { ...r, result } : r)));
    const resultOf = (unitId: number) => data.ratings.find((r) => r.unit_id === unitId)?.result ?? null;

    const competent = data.ratings.filter((r) => r.result === 'Competent').length;
    const groups = ['Basic', 'Common', 'Core'].filter((t) => units.some((u) => u.type === t));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/training/${trainee.id}/competency`, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-4">
            <div className="max-h-[92dvh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white pb-[env(safe-area-inset-bottom)] shadow-xl sm:max-h-[calc(100vh-3rem)] sm:rounded-xl sm:pb-0">
                <div className="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                    <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600"><ListChecks className="h-5 w-5" /></span>
                        <div>
                            <h3 className="text-base font-semibold text-slate-800">Competencies — {trainee.name}</h3>
                            <p className="text-xs text-slate-500">{competent}/{units.length} Competent · rate each Unit of Competency</p>
                        </div>
                    </div>
                    <button type="button" onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>

                <form onSubmit={submit} className="px-5 py-4">
                    {groups.map((type) => (
                        <div key={type} className="mb-4">
                            <span className={`mb-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${TYPE_STYLE[type] ?? 'bg-slate-100 text-slate-600'}`}>{type}</span>
                            <div className="space-y-2">
                                {units.filter((u) => u.type === type).map((u) => {
                                    const val = resultOf(u.id);
                                    return (
                                        <div key={u.id} className="flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50/50 px-3 py-2">
                                            <div className="min-w-0">
                                                {u.code && <span className="mr-1 font-mono text-[10px] text-slate-400">{u.code}</span>}
                                                <span className="text-sm text-slate-700">{u.title}</span>
                                            </div>
                                            <div className="flex shrink-0 items-center gap-1">
                                                {results.map((res) => {
                                                    const active = val === res;
                                                    const isC = res === 'Competent';
                                                    return (
                                                        <button
                                                            key={res} type="button"
                                                            onClick={() => setResult(u.id, active ? null : res)}
                                                            className={`rounded-md px-2 py-1 text-xs font-medium transition ${active
                                                                ? isC ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white'
                                                                : 'bg-white text-slate-500 ring-1 ring-slate-200 hover:bg-slate-50'}`}
                                                            title={res}
                                                        >
                                                            {isC ? 'Competent' : 'Not yet'}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    ))}

                    <label className="mb-1 mt-2 flex items-center justify-between gap-3 text-sm">
                        <span className="text-slate-600">Date rated</span>
                        <input type="date" className="input w-auto" value={data.rated_at} onChange={(e) => setData('rated_at', e.target.value)} />
                    </label>

                    <div className="mt-4 flex gap-2 border-t border-slate-100 pt-3 sm:justify-end">
                        <button type="button" onClick={onClose} className="btn-ghost justify-center max-sm:flex-1">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary py-2.5 max-sm:flex-[2] sm:py-2">Save competencies</button>
                    </div>
                </form>
            </div>
        </div>
    );
}
