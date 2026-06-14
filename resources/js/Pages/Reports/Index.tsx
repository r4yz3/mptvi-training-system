import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Download, FileSpreadsheet, Users, BadgeCheck, UserCheck, Banknote, Filter } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import { PageProps } from '@/types';

interface Props {
    byStatus: Record<string, number>;
    byProgram: { program: string; applicants: number }[];
    totals: { applicants: number; active: number; certified: number };
    programs: { id: number; title: string }[];
    statuses: string[];
    sessions: string[];
    barangays: string[];
    schoolYears: string[];
    methods: string[];
    paymentTypes: string[];
    canFinance: boolean;
}

const STATUS_COLOR: Record<string, string> = {
    Registered: 'bg-slate-400', Qualified: 'bg-sky-500', Paid: 'bg-emerald-500',
    'In training': 'bg-indigo-500', 'For assessment': 'bg-amber-500', Certified: 'bg-brand-600', Disqualified: 'bg-rose-500',
};

export default function ReportsIndex({ byStatus, byProgram, totals, programs, statuses, sessions, barangays, schoolYears, methods, paymentTypes, canFinance }: Props) {
    // Applicants export filters
    const [af, setAf] = useState<Record<string, string>>({});
    // Payments export filters
    const [pf, setPf] = useState<Record<string, string>>({});

    const canApprove = usePage<PageProps>().props.auth.can['download.approve'] ?? false;
    const url = (base: string, f: Record<string, string>) => {
        const q = new URLSearchParams(Object.entries(f).filter(([, v]) => v));
        return `${base}${q.toString() ? '?' + q.toString() : ''}`;
    };
    const go = (type: string, base: string, f: Record<string, string>) => {
        const params = Object.fromEntries(Object.entries(f).filter(([, v]) => v));
        if (canApprove) window.location.href = url(base, f);
        else router.post('/downloads', { type, params }, { preserveScroll: true });
    };
    const maxProg = Math.max(1, ...byProgram.map((p) => p.applicants));
    const totalStatus = Math.max(1, ...statuses.map((s) => byStatus[s] ?? 0));

    return (
        <AppShell title="Reports">
            <Head title="Reports" />

            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Stat icon={<Users className="h-5 w-5" />} label="Total applicants" value={totals.applicants} tint="bg-brand-50 text-brand-600" />
                <Stat icon={<UserCheck className="h-5 w-5" />} label="Active" value={totals.active} tint="bg-emerald-50 text-emerald-600" />
                <Stat icon={<BadgeCheck className="h-5 w-5" />} label="Certified" value={totals.certified} tint="bg-amber-50 text-amber-600" />
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">By pipeline status</h3>
                    <div className="space-y-2.5">
                        {statuses.map((s) => (
                            <div key={s}>
                                <div className="mb-1 flex justify-between text-xs"><span className="text-slate-600">{s}</span><span className="font-medium text-slate-800">{byStatus[s] ?? 0}</span></div>
                                <div className="h-2 overflow-hidden rounded-full bg-slate-100"><div className={`h-full rounded-full ${STATUS_COLOR[s] ?? 'bg-slate-400'}`} style={{ width: `${((byStatus[s] ?? 0) / totalStatus) * 100}%` }} /></div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">By program</h3>
                    <div className="space-y-2.5">
                        {byProgram.map((p) => (
                            <div key={p.program}>
                                <div className="mb-1 flex justify-between text-xs"><span className="truncate text-slate-600">{p.program}</span><span className="text-slate-400">{p.applicants}</span></div>
                                <div className="h-2 overflow-hidden rounded-full bg-slate-100"><div className="h-full rounded-full bg-brand-500" style={{ width: `${(p.applicants / maxProg) * 100}%` }} /></div>
                            </div>
                        ))}
                        {byProgram.length === 0 && <p className="text-sm text-slate-400">No programs yet.</p>}
                    </div>
                </div>
            </div>

            {/* Applicants export */}
            <ExportPanel
                icon={<FileSpreadsheet className="h-4 w-4" />}
                title="Applicants export (CSV)"
                actionLabel={canApprove ? 'Download CSV' : 'Request CSV'}
                onDownload={() => go('reports_applicants_csv', '/reports/applicants.csv', af)}
            >
                <Sel label="Status" value={af.status} onChange={(v) => setAf({ ...af, status: v })} options={statuses} />
                <Sel label="Program" value={af.program} onChange={(v) => setAf({ ...af, program: v })} options={programs.map((p) => ({ value: String(p.id), label: p.title }))} />
                <Sel label="Barangay" value={af.barangay} onChange={(v) => setAf({ ...af, barangay: v })} options={barangays} />
                <Sel label="School year" value={af.school_year} onChange={(v) => setAf({ ...af, school_year: v })} options={schoolYears} />
                <Sel label="Class session" value={af.class_session} onChange={(v) => setAf({ ...af, class_session: v })} options={sessions} />
                <Sel label="Sex" value={af.sex} onChange={(v) => setAf({ ...af, sex: v })} options={['Male', 'Female']} />
                <Sel label="Active" value={af.active} onChange={(v) => setAf({ ...af, active: v })} options={[{ value: '1', label: 'Active' }, { value: '0', label: 'Inactive' }]} />
                <Dat label="Registered from" value={af.registered_from} onChange={(v) => setAf({ ...af, registered_from: v })} />
                <Dat label="Registered to" value={af.registered_to} onChange={(v) => setAf({ ...af, registered_to: v })} />
            </ExportPanel>

            {/* Payments export */}
            {canFinance && (
                <ExportPanel
                    icon={<Banknote className="h-4 w-4" />}
                    title="Payments export (CSV)"
                    actionLabel={canApprove ? 'Download CSV' : 'Request CSV'}
                    onDownload={() => go('reports_payments_csv', '/reports/payments.csv', pf)}
                >
                    <Sel label="Program" value={pf.program} onChange={(v) => setPf({ ...pf, program: v })} options={programs.map((p) => ({ value: String(p.id), label: p.title }))} />
                    <Sel label="Method" value={pf.method} onChange={(v) => setPf({ ...pf, method: v })} options={methods} />
                    <Sel label="Type" value={pf.type} onChange={(v) => setPf({ ...pf, type: v })} options={paymentTypes} />
                    <Sel label="Status" value={pf.status} onChange={(v) => setPf({ ...pf, status: v })} options={[{ value: 'valid', label: 'Valid' }, { value: 'void', label: 'Voided' }]} />
                    <Dat label="Paid from" value={pf.paid_from} onChange={(v) => setPf({ ...pf, paid_from: v })} />
                    <Dat label="Paid to" value={pf.paid_to} onChange={(v) => setPf({ ...pf, paid_to: v })} />
                </ExportPanel>
            )}
        </AppShell>
    );
}

function ExportPanel({ icon, title, children, onDownload, actionLabel = 'Download CSV' }: { icon: React.ReactNode; title: string; children: React.ReactNode; onDownload: () => void; actionLabel?: string }) {
    return (
        <div className="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">{icon} {title}</h3>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">{children}</div>
            <div className="mt-4 flex items-center gap-2 border-t border-slate-100 pt-4">
                <Filter className="h-3.5 w-3.5 text-slate-300" />
                <span className="text-xs text-slate-400">Leave a filter on “All” to include everything.</span>
                <button onClick={onDownload} className="btn-primary ml-auto"><Download className="h-4 w-4" /> {actionLabel}</button>
            </div>
        </div>
    );
}

type Opt = string | { value: string; label: string };
function Sel({ label, value, onChange, options }: { label: string; value?: string; onChange: (v: string) => void; options: Opt[] }) {
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-slate-600">{label}</span>
            <select className="input" value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
                <option value="">All</option>
                {options.map((o) => {
                    const v = typeof o === 'string' ? o : o.value;
                    const l = typeof o === 'string' ? o : o.label;
                    return <option key={v} value={v}>{l}</option>;
                })}
            </select>
        </label>
    );
}
function Dat({ label, value, onChange }: { label: string; value?: string; onChange: (v: string) => void }) {
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-slate-600">{label}</span>
            <input type="date" className="input" value={value ?? ''} onChange={(e) => onChange(e.target.value)} />
        </label>
    );
}

function Stat({ icon, label, value, tint }: { icon: React.ReactNode; label: string; value: number; tint: string }) {
    return (
        <div className="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className={`flex h-11 w-11 items-center justify-center rounded-lg ${tint}`}>{icon}</div>
            <div><div className="text-sm text-slate-500">{label}</div><div className="text-2xl font-semibold text-slate-800">{value}</div></div>
        </div>
    );
}
