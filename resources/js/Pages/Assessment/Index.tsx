import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Award, ClipboardList, GraduationCap, BadgeCheck, Printer, X, ChevronRight } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import StatusBadge from '@/Components/StatusBadge';

interface Row {
    id: number; name: string; uli: string | null; program: string | null; level: string | null;
    status: string; rate: number; cert_number: string | null; last_result: string | null;
}

const STAGES = [
    { key: 'In training', label: 'In training', icon: GraduationCap, tile: 'bg-indigo-50 text-indigo-600' },
    { key: 'For assessment', label: 'For assessment', icon: ClipboardList, tile: 'bg-amber-50 text-amber-600' },
    { key: 'Certified', label: 'Certified', icon: BadgeCheck, tile: 'bg-emerald-50 text-emerald-600' },
];

function initials(name: string) {
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}

export default function AssessmentIndex({ applicants, canAssess, defaultAssessor }: { applicants: Row[]; canAssess: boolean; defaultAssessor: string }) {
    const [recording, setRecording] = useState<Row | null>(null);
    const count = (key: string) => applicants.filter((a) => a.status === key).length;

    return (
        <AppShell title="Assessment & certifications">
            <Head title="Assessment" />

            {/* Pipeline summary */}
            <div className="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                {STAGES.map((s, i) => (
                    <div key={s.key} className="relative flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <span className={`flex h-10 w-10 items-center justify-center rounded-xl ${s.tile}`}><s.icon className="h-5 w-5" /></span>
                        <div>
                            <div className="text-2xl font-semibold leading-none text-slate-800">{count(s.key)}</div>
                            <div className="mt-1 text-xs font-medium text-slate-400">{s.label}</div>
                        </div>
                        {i < STAGES.length - 1 && (
                            <ChevronRight className="absolute -right-2.5 top-1/2 hidden h-5 w-5 -translate-y-1/2 text-slate-300 sm:block" />
                        )}
                    </div>
                ))}
            </div>

            {/* Roster */}
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Trainee</th>
                                <th className="px-4 py-3">Program</th>
                                <th className="px-4 py-3">Attendance</th>
                                <th className="px-4 py-3">Stage</th>
                                <th className="px-4 py-3">Certificate</th>
                                <th className="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {applicants.map((a) => (
                                <tr key={a.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{initials(a.name)}</span>
                                            <div>
                                                <Link href={`/applicants/${a.id}`} className="font-medium text-slate-800 hover:text-brand-600">{a.name}</Link>
                                                <div className="font-mono text-xs text-slate-400">{a.uli ?? '—'}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{a.program ?? '—'} {a.level && <span className="text-slate-400">{a.level}</span>}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <div className="h-2 w-16 overflow-hidden rounded-full bg-slate-100">
                                                <div className={`h-full rounded-full ${a.rate >= 80 ? 'bg-emerald-500' : a.rate >= 50 ? 'bg-amber-500' : 'bg-rose-400'}`} style={{ width: `${a.rate}%` }} />
                                            </div>
                                            <span className={`text-xs font-semibold ${a.rate >= 80 ? 'text-emerald-600' : a.rate >= 50 ? 'text-amber-600' : 'text-slate-400'}`}>{a.rate}%</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3"><StatusBadge status={a.status} /></td>
                                    <td className="px-4 py-3">
                                        {a.cert_number
                                            ? <span className="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 font-mono text-xs font-medium text-emerald-700"><BadgeCheck className="h-3.5 w-3.5" />{a.cert_number}</span>
                                            : <span className="text-xs text-slate-300">—</span>}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            {canAssess && a.status === 'In training' && (
                                                <button onClick={() => router.put(`/assessment/${a.id}/for-assessment`, {}, { preserveScroll: true })}
                                                    className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                                    <ClipboardList className="h-3.5 w-3.5" /> Endorse
                                                </button>
                                            )}
                                            {canAssess && a.status === 'For assessment' && (
                                                <button onClick={() => setRecording(a)}
                                                    className="inline-flex items-center gap-1 rounded-md bg-brand-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-brand-700">
                                                    <Award className="h-3.5 w-3.5" /> Record result
                                                </button>
                                            )}
                                            {a.status === 'Certified' && (
                                                <a href={`/assessment/${a.id}/certificate`} target="_blank" rel="noopener"
                                                    className="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                                                    <Printer className="h-3.5 w-3.5" /> Print certificate
                                                </a>
                                            )}
                                            {!canAssess && a.status !== 'Certified' && (
                                                <span className="text-xs text-slate-300">—</span>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {applicants.length === 0 && (
                                <tr><td colSpan={6} className="px-4 py-14 text-center text-sm text-slate-400">
                                    <Award className="mx-auto mb-2 h-8 w-8 text-slate-300" />
                                    No trainees in the assessment pipeline yet.
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {recording && <ResultModal applicant={recording} defaultAssessor={defaultAssessor} onClose={() => setRecording(null)} />}
        </AppShell>
    );
}

function ResultModal({ applicant, defaultAssessor, onClose }: { applicant: Row; defaultAssessor: string; onClose: () => void }) {
    const { data, setData, post, processing, errors } = useForm({
        result: 'Competent', assessed_at: new Date().toISOString().slice(0, 10), assessor: defaultAssessor ?? '', remarks: '',
    });
    const competent = data.result === 'Competent';
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/assessment/${applicant.id}/result`, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div className="flex items-center gap-2.5">
                        <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Award className="h-5 w-5" /></span>
                        <div>
                            <h3 className="text-base font-semibold text-slate-800">Record assessment</h3>
                            <p className="text-xs text-slate-500">{applicant.name} · {applicant.program}</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-4 px-5 py-4">
                    <div>
                        <span className="mb-1.5 block text-xs font-medium text-slate-600">Result</span>
                        <div className="grid grid-cols-2 gap-2">
                            <button type="button" onClick={() => setData('result', 'Competent')}
                                className={`rounded-lg border px-3 py-2 text-sm font-medium transition ${competent ? 'border-emerald-500 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-500' : 'border-slate-200 text-slate-600 hover:bg-slate-50'}`}>
                                Competent
                            </button>
                            <button type="button" onClick={() => setData('result', 'Not Yet Competent')}
                                className={`rounded-lg border px-3 py-2 text-sm font-medium transition ${!competent ? 'border-amber-500 bg-amber-50 text-amber-700 ring-1 ring-amber-500' : 'border-slate-200 text-slate-600 hover:bg-slate-50'}`}>
                                Not Yet Competent
                            </button>
                        </div>
                        <p className={`mt-2 flex items-start gap-1.5 rounded-md px-2.5 py-1.5 text-xs ${competent ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-500'}`}>
                            <BadgeCheck className="mt-px h-3.5 w-3.5 shrink-0" />
                            {competent
                                ? 'Issues a certificate number and certifies the trainee.'
                                : 'Returns the trainee to the For-assessment stage for re-evaluation.'}
                        </p>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Date assessed</span>
                            <input type="date" className="input" value={data.assessed_at} onChange={(e) => setData('assessed_at', e.target.value)} />
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Assessor</span>
                            <input className="input" placeholder="Name" value={data.assessor} onChange={(e) => setData('assessor', e.target.value)} />
                        </label>
                    </div>
                    <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Remarks</span>
                        <input className="input" placeholder="Optional" value={data.remarks} onChange={(e) => setData('remarks', e.target.value)} />
                    </label>
                    {errors.result && <span className="block text-xs text-rose-600">{errors.result}</span>}
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary">Save result</button>
                    </div>
                </form>
            </div>
        </div>
    );
}
