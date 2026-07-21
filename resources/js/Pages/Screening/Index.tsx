import { Fragment, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    X, CheckCircle2, XCircle, ClipboardCheck, Clock, ChevronDown, type LucideIcon,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import { PageProps } from '@/types';

function initials(name: string) {
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}

interface EligItem { label: string; ok: boolean; note: string }
interface Row {
    id: number;
    name: string;
    age: number | null;
    program: string | null;
    program_id: number | null;
    level: string | null;
    education: string | null;
    class_session: string | null;
    status: string;
    eligibility: EligItem[];
    eligible: boolean;
    disqualify_reason: string | null;
}
interface Paginated {
    data: Row[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null; to: number | null; total: number;
}

export default function ScreeningIndex({
    applicants, tab, counts,
}: {
    applicants: Paginated;
    tab: string;
    counts: { pending: number; enrolled: number; disqualified: number };
}) {
    const { auth } = usePage<PageProps>().props;
    const canScreen = auth.can['screen'];
    const [expanded, setExpanded] = useState<number | null>(null);
    const [dq, setDq] = useState<Row | null>(null);

    const go = (t: string) =>
        router.get('/screening', { tab: t }, { preserveScroll: true, preserveState: true, replace: true });

    // Mark the applicant Enrolled. (Paying then advances them to In training.)
    const qualify = (a: Row) => router.put(`/screening/${a.id}/qualify`, {}, { preserveScroll: true });

    const tabs: { key: string; label: string; count: number; icon: LucideIcon; tone: string }[] = [
        { key: 'pending', label: 'Awaiting screening', count: counts.pending, icon: Clock, tone: 'amber' },
        { key: 'enrolled', label: 'Enrolled', count: counts.enrolled, icon: CheckCircle2, tone: 'emerald' },
        { key: 'disqualified', label: 'Disqualified', count: counts.disqualified, icon: XCircle, tone: 'rose' },
    ];
    const TONE: Record<string, { icon: string; ring: string }> = {
        amber: { icon: 'bg-amber-50 text-amber-600', ring: 'border-amber-300 ring-amber-100' },
        emerald: { icon: 'bg-emerald-50 text-emerald-600', ring: 'border-emerald-300 ring-emerald-100' },
        rose: { icon: 'bg-rose-50 text-rose-600', ring: 'border-rose-300 ring-rose-100' },
    };

    return (
        <AppShell title="Screening & eligibility">
            <Head title="Screening" />

            <p className="mb-4 text-sm text-slate-500">Review newly-registered applicants against the eligibility checklist, then Enroll or Disqualify.</p>

            {/* Stat-card tabs */}
            <div className="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                {tabs.map((t) => {
                    const Icon = t.icon;
                    const active = tab === t.key;
                    const tn = TONE[t.tone];
                    return (
                        <button
                            key={t.key}
                            onClick={() => go(t.key)}
                            className={`flex items-center gap-3 rounded-xl border bg-white p-4 text-left shadow-sm transition ${
                                active ? `${tn.ring} ring-2` : 'border-slate-200 hover:border-slate-300'
                            }`}
                        >
                            <span className={`flex h-10 w-10 items-center justify-center rounded-lg ${tn.icon}`}><Icon className="h-5 w-5" /></span>
                            <div>
                                <div className="text-2xl font-semibold text-slate-800">{t.count}</div>
                                <div className="text-xs font-medium text-slate-500">{t.label}</div>
                            </div>
                        </button>
                    );
                })}
            </div>

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Applicant</th>
                                <th className="px-4 py-3">Program</th>
                                <th className="px-4 py-3">Education</th>
                                <th className="px-4 py-3">Eligibility</th>
                                <th className="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {applicants.data.map((a) => {
                                const pass = a.eligibility.filter((e) => e.ok).length;
                                const total = a.eligibility.length;
                                const open = expanded === a.id;
                                return (
                                <Fragment key={a.id}>
                                    <tr className="hover:bg-slate-50">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{initials(a.name)}</span>
                                                <div>
                                                    <Link href={`/applicants/${a.id}`} className="font-medium text-slate-800 hover:text-brand-600">
                                                        {a.name}
                                                    </Link>
                                                    <div className="text-xs text-slate-400">
                                                        {a.age ? `${a.age} yrs` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-slate-600">
                                            {a.program ?? '—'} <span className="text-slate-400">{a.level}</span>
                                        </td>
                                        <td className="px-4 py-3 text-slate-600">{a.education ?? '—'}</td>
                                        <td className="px-4 py-3">
                                            <button
                                                onClick={() => setExpanded(open ? null : a.id)}
                                                className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ${
                                                    a.eligible ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'
                                                }`}
                                            >
                                                {a.eligible ? <CheckCircle2 className="h-3.5 w-3.5" /> : <ClipboardCheck className="h-3.5 w-3.5" />}
                                                {pass}/{total} checks
                                                <ChevronDown className={`h-3.5 w-3.5 transition ${open ? 'rotate-180' : ''}`} />
                                            </button>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center justify-end gap-2">
                                                {a.status === 'Disqualified' && a.disqualify_reason && (
                                                    <span className="mr-1 max-w-[160px] truncate text-xs text-rose-500" title={a.disqualify_reason}>
                                                        {a.disqualify_reason}
                                                    </span>
                                                )}
                                                {a.status !== 'Enrolled' ? (
                                                    canScreen ? (
                                                        <>
                                                            <button onClick={() => qualify(a)} className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                                                                <CheckCircle2 className="h-3.5 w-3.5" /> Enroll
                                                            </button>
                                                            {a.status !== 'Disqualified' && (
                                                                <button onClick={() => setDq(a)} className="inline-flex items-center gap-1 rounded-md border border-rose-200 px-2.5 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                                                    <XCircle className="h-3.5 w-3.5" /> Disqualify
                                                                </button>
                                                            )}
                                                        </>
                                                    ) : <span className="text-xs text-slate-400">View only</span>
                                                ) : (
                                                    <StatusBadge status="Enrolled" />
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                    {open && (
                                        <tr className="bg-slate-50/60">
                                            <td colSpan={5} className="px-4 pb-4 pt-1">
                                                <div className="rounded-lg border border-slate-200 bg-white p-4">
                                                    <div className="mb-3 flex items-center justify-between">
                                                        <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Eligibility checklist</span>
                                                        <span className={`text-xs font-medium ${a.eligible ? 'text-emerald-600' : 'text-amber-600'}`}>{pass} of {total} passing</span>
                                                    </div>
                                                    <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                                                        {a.eligibility.map((e) => (
                                                            <div key={e.label} className={`flex items-start gap-2 rounded-lg border px-3 py-2 text-sm ${e.ok ? 'border-emerald-100 bg-emerald-50/40' : 'border-amber-100 bg-amber-50/40'}`}>
                                                                {e.ok
                                                                    ? <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                                                                    : <XCircle className="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />}
                                                                <div>
                                                                    <div className="text-slate-700">{e.label}</div>
                                                                    <div className="text-xs text-slate-400">{e.note}</div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </Fragment>
                                );
                            })}
                            {applicants.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-12 text-center">
                                        <ClipboardCheck className="mx-auto h-8 w-8 text-slate-300" />
                                        <p className="mt-2 text-sm text-slate-400">Nothing in this queue.</p>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination links={applicants.links} from={applicants.from} to={applicants.to} total={applicants.total} />

            {dq && <DisqualifyModal applicant={dq} onClose={() => setDq(null)} />}
        </AppShell>
    );
}

function DisqualifyModal({ applicant, onClose }: { applicant: Row; onClose: () => void }) {
    const { data, setData, put, processing, errors } = useForm({ reason: '' });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/screening/${applicant.id}/disqualify`, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 className="text-base font-semibold text-slate-800">Disqualify applicant</h3>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-4 px-5 py-4">
                    <p className="text-sm text-slate-500">
                        Provide a reason for disqualifying <span className="font-medium text-slate-700">{applicant.name}</span>.
                    </p>
                    <div>
                        <textarea className="input" rows={3} value={data.reason} onChange={(e) => setData('reason', e.target.value)} placeholder="e.g. Below minimum age requirement" autoFocus />
                        {errors.reason && <span className="mt-1 block text-xs text-rose-600">{errors.reason}</span>}
                    </div>
                    <div className="flex justify-end gap-2">
                        <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                        <button type="submit" disabled={processing} className="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50">
                            Disqualify
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
