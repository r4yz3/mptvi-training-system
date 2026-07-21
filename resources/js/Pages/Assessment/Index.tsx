import { Head, Link } from '@inertiajs/react';
import { ClipboardList, GraduationCap, BadgeCheck, ChevronRight, ChevronRight as Arrow } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import StatusBadge from '@/Components/StatusBadge';

interface Grades { total: number; graded: number; gwa: number | null; remark: string }
interface Row {
    id: number; name: string; program: string | null; level: string | null;
    status: string; result: string | null; grades: Grades;
}

const STAGES = [
    { key: 'In training', label: 'In training', icon: GraduationCap, tile: 'bg-indigo-50 text-indigo-600' },
    { key: 'For assessment', label: 'For assessment', icon: ClipboardList, tile: 'bg-amber-50 text-amber-600' },
    { key: 'Certified', label: 'Competent', icon: BadgeCheck, tile: 'bg-emerald-50 text-emerald-600' },
];

function initials(name: string) {
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}

function resultBadge(result: string | null) {
    if (result === 'Competent') return 'bg-emerald-50 text-emerald-700';
    if (result === 'Not Yet Competent') return 'bg-amber-50 text-amber-700';
    return 'bg-slate-100 text-slate-400';
}

export default function AssessmentIndex({ applicants }: { applicants: Row[] }) {
    const competentCount = applicants.filter((a) => a.result === 'Competent').length;

    return (
        <AppShell title="Assessment">
            <Head title="Assessment" />

            {/* Summary */}
            <div className="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                {STAGES.map((s, i) => (
                    <div key={s.key} className="relative flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <span className={`flex h-10 w-10 items-center justify-center rounded-xl ${s.tile}`}><s.icon className="h-5 w-5" /></span>
                        <div>
                            <div className="text-2xl font-semibold leading-none text-slate-800">
                                {s.key === 'Certified' ? competentCount : applicants.filter((a) => a.status === s.key).length}
                            </div>
                            <div className="mt-1 text-xs font-medium text-slate-400">{s.label}</div>
                        </div>
                        {i < STAGES.length - 1 && (
                            <ChevronRight className="absolute -right-2.5 top-1/2 hidden h-5 w-5 -translate-y-1/2 text-slate-300 sm:block" />
                        )}
                    </div>
                ))}
            </div>

            <p className="mb-3 text-xs text-slate-400">
                Open a trainee to set their assessment result (Competent / Not Yet Competent) — admin &amp; registrar only.
            </p>

            {/* Roster */}
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Trainee</th>
                                <th className="px-4 py-3">Program</th>
                                <th className="px-4 py-3">Stage</th>
                                <th className="px-4 py-3">GWA</th>
                                <th className="px-4 py-3">Assessment result</th>
                                <th className="px-4 py-3 text-right" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {applicants.map((a) => (
                                <tr key={a.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{initials(a.name)}</span>
                                            <span className="font-medium text-slate-800">{a.name}</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{a.program ?? '—'}{a.level ? ` (${a.level})` : ''}</td>
                                    <td className="px-4 py-3"><StatusBadge status={a.status} /></td>
                                    <td className="px-4 py-3 text-slate-500">
                                        {a.grades.total > 0
                                            ? <span className={a.grades.remark === 'Passed' ? 'font-medium text-emerald-600' : a.grades.remark === 'Failed' ? 'font-medium text-rose-600' : ''}>
                                                {a.grades.gwa !== null ? a.grades.gwa.toFixed(2) : '—'} <span className="text-slate-400">({a.grades.graded}/{a.grades.total})</span>
                                            </span>
                                            : <span className="text-slate-300">— no subjects —</span>}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${resultBadge(a.result)}`}>{a.result ?? 'Not yet assessed'}</span>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Link href={`/applicants/${a.id}`} className="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:underline">
                                            Open <Arrow className="h-3.5 w-3.5" />
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {applicants.length === 0 && (
                                <tr><td colSpan={6} className="px-4 py-14 text-center text-sm text-slate-400">
                                    <ClipboardList className="mx-auto mb-2 h-8 w-8 text-slate-300" />
                                    No trainees in the assessment phase yet.
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppShell>
    );
}
