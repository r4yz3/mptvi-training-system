import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Download, FileSpreadsheet, Users, BadgeCheck, UserCheck, Banknote, Filter,
    BarChart3, GraduationCap, Activity, Database, ChevronDown,
} from 'lucide-react';
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
    Registered: 'bg-slate-400', Enrolled: 'bg-sky-500',
    'In training': 'bg-indigo-500', 'For assessment': 'bg-amber-500', Certified: 'bg-brand-600', Disqualified: 'bg-rose-500',
};

export default function ReportsIndex({ byStatus, byProgram, totals, programs, statuses, sessions, barangays, schoolYears, methods, paymentTypes, canFinance }: Props) {
    // Applicants export filters + output options (format/columns)
    const [af, setAf] = useState<Record<string, string>>({ format: 'csv', columns: 'summary' });
    // Payments export filters + format
    const [pf, setPf] = useState<Record<string, string>>({ format: 'csv' });
    const fmtWord = (f?: string) => (f === 'xlsx' ? 'Excel' : 'CSV');

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
    const sortedProg = [...byProgram].sort((a, b) => b.applicants - a.applicants);

    return (
        <AppShell title="Reports">
            <Head title="Reports" />

            {/* Header */}
            <div className="mb-6 flex items-center gap-3.5">
                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-sm">
                    <BarChart3 className="h-6 w-6" />
                </div>
                <div>
                    <h2 className="text-xl font-semibold text-slate-800">Reports</h2>
                    <p className="mt-0.5 text-sm text-slate-500">At-a-glance counts, and filtered CSV exports for applicants and payments.</p>
                </div>
            </div>

            {/* Overview stats */}
            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Stat icon={<Users className="h-5 w-5" />} label="Total applicants" value={totals.applicants} tint="bg-brand-50 text-brand-600" accent="bg-brand-500" />
                <Stat icon={<UserCheck className="h-5 w-5" />} label="Active" value={totals.active} tint="bg-emerald-50 text-emerald-600" accent="bg-emerald-500" />
                <Stat icon={<BadgeCheck className="h-5 w-5" />} label="Certified" value={totals.certified} tint="bg-amber-50 text-amber-600" accent="bg-amber-500" />
            </div>

            {/* Breakdown charts */}
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <ChartCard icon={<Activity className="h-4 w-4" />} title="By pipeline status" badge={`${totals.applicants} total`}>
                    <div className="space-y-3">
                        {statuses.map((s) => {
                            const v = byStatus[s] ?? 0;
                            return (
                                <div key={s} className="flex items-center gap-3">
                                    <div className="flex w-28 shrink-0 items-center gap-2 text-xs text-slate-600">
                                        <span className={`h-2.5 w-2.5 shrink-0 rounded-full ${STATUS_COLOR[s] ?? 'bg-slate-400'}`} />
                                        <span className="truncate">{s}</span>
                                    </div>
                                    <div className="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                        <div className={`h-full rounded-full ${STATUS_COLOR[s] ?? 'bg-slate-400'}`} style={{ width: `${(v / totalStatus) * 100}%` }} />
                                    </div>
                                    <span className="w-6 shrink-0 text-right text-xs font-semibold tabular-nums text-slate-700">{v}</span>
                                </div>
                            );
                        })}
                    </div>
                </ChartCard>

                <ChartCard icon={<GraduationCap className="h-4 w-4" />} title="By program" badge={`${byProgram.length} programs`}>
                    <div className="space-y-3">
                        {sortedProg.map((p) => (
                            <div key={p.program} className="flex items-center gap-3">
                                <span className="w-40 shrink-0 truncate text-xs text-slate-600" title={p.program}>{p.program}</span>
                                <div className="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div className="h-full rounded-full bg-brand-500" style={{ width: `${(p.applicants / maxProg) * 100}%` }} />
                                </div>
                                <span className="w-6 shrink-0 text-right text-xs font-semibold tabular-nums text-slate-700">{p.applicants}</span>
                            </div>
                        ))}
                        {byProgram.length === 0 && <p className="text-sm text-slate-400">No programs yet.</p>}
                    </div>
                </ChartCard>
            </div>

            {/* Data exports section */}
            <div className="mb-4 mt-9 flex items-center gap-3">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                    <Database className="h-5 w-5" />
                </span>
                <div className="min-w-0">
                    <h3 className="text-sm font-semibold text-slate-800">Data exports</h3>
                    <p className="truncate text-xs text-slate-400">Set filters, then download a CSV. Leave a filter on “All” to include everything.</p>
                </div>
            </div>

            <div className="space-y-5">
                {/* Applicants export */}
                <ExportPanel
                    icon={<FileSpreadsheet className="h-5 w-5" />}
                    title="Applicants"
                    desc="One row per learner. Choose Summary (key fields) or Complete (every LPF field)."
                    actionLabel={`${canApprove ? 'Download' : 'Request'} ${fmtWord(af.format)}`}
                    onDownload={() => go('reports_applicants_csv', '/reports/applicants.csv', af)}
                    controls={
                        <>
                            <MiniSelect label="Columns" value={af.columns ?? 'summary'} onChange={(v) => setAf({ ...af, columns: v })}
                                options={[{ value: 'summary', label: 'Summary' }, { value: 'full', label: 'Complete' }]} />
                            <MiniSelect label="Format" value={af.format ?? 'csv'} onChange={(v) => setAf({ ...af, format: v })}
                                options={[{ value: 'csv', label: 'CSV' }, { value: 'xlsx', label: 'Excel' }]} />
                        </>
                    }
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
                        icon={<Banknote className="h-5 w-5" />}
                        title="Payments"
                        desc="Payment ledger — amounts, categories, methods, OR numbers and cashier."
                        actionLabel={`${canApprove ? 'Download' : 'Request'} ${fmtWord(pf.format)}`}
                        onDownload={() => go('reports_payments_csv', '/reports/payments.csv', pf)}
                        controls={
                            <MiniSelect label="Format" value={pf.format ?? 'csv'} onChange={(v) => setPf({ ...pf, format: v })}
                                options={[{ value: 'csv', label: 'CSV' }, { value: 'xlsx', label: 'Excel' }]} />
                        }
                    >
                        <Sel label="Program" value={pf.program} onChange={(v) => setPf({ ...pf, program: v })} options={programs.map((p) => ({ value: String(p.id), label: p.title }))} />
                        <Sel label="Method" value={pf.method} onChange={(v) => setPf({ ...pf, method: v })} options={methods} />
                        <Sel label="Type" value={pf.type} onChange={(v) => setPf({ ...pf, type: v })} options={paymentTypes} />
                        <Sel label="Status" value={pf.status} onChange={(v) => setPf({ ...pf, status: v })} options={[{ value: 'valid', label: 'Valid' }, { value: 'void', label: 'Voided' }]} />
                        <Dat label="Paid from" value={pf.paid_from} onChange={(v) => setPf({ ...pf, paid_from: v })} />
                        <Dat label="Paid to" value={pf.paid_to} onChange={(v) => setPf({ ...pf, paid_to: v })} />
                    </ExportPanel>
                )}
            </div>
        </AppShell>
    );
}

function ChartCard({ icon, title, badge, children }: { icon: React.ReactNode; title: string; badge?: string; children: React.ReactNode }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4 flex items-center justify-between">
                <h3 className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <span className="text-slate-400">{icon}</span> {title}
                </h3>
                {badge && <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">{badge}</span>}
            </div>
            {children}
        </div>
    );
}

function ExportPanel({ icon, title, desc, children, onDownload, actionLabel = 'Download CSV', controls }: { icon: React.ReactNode; title: string; desc: string; children: React.ReactNode; onDownload: () => void; actionLabel?: string; controls?: React.ReactNode }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4 flex items-start gap-3">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">{icon}</span>
                <div className="min-w-0">
                    <h3 className="font-semibold text-slate-800">{title}</h3>
                    <p className="text-xs text-slate-500">{desc}</p>
                </div>
            </div>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">{children}</div>
            <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-3 border-t border-slate-100 pt-4">
                <span className="flex items-center gap-1.5 text-xs text-slate-400">
                    <Filter className="h-3.5 w-3.5 shrink-0" /> “All” includes everything.
                </span>
                <div className="ml-auto flex flex-wrap items-center gap-3">
                    {controls}
                    <button onClick={onDownload} className="btn-primary"><Download className="h-4 w-4" /> {actionLabel}</button>
                </div>
            </div>
        </div>
    );
}

function MiniSelect({ label, value, onChange, options }: { label: string; value: string; onChange: (v: string) => void; options: { value: string; label: string }[] }) {
    return (
        <label className="flex items-center gap-1.5 text-xs font-medium text-slate-500">
            {label}
            <div className="relative">
                <select
                    className="appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-7 text-xs font-medium text-slate-700 focus:border-brand-400 focus:ring-brand-400"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                >
                    {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
                <ChevronDown className="pointer-events-none absolute right-1.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
            </div>
        </label>
    );
}

type Opt = string | { value: string; label: string };
function Sel({ label, value, onChange, options }: { label: string; value?: string; onChange: (v: string) => void; options: Opt[] }) {
    const active = !!value;
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-slate-600">{label}</span>
            <select className={`input ${active ? 'border-brand-300 bg-brand-50/40 font-medium text-brand-800' : ''}`} value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
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
    const active = !!value;
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-slate-600">{label}</span>
            <input type="date" className={`input ${active ? 'border-brand-300 bg-brand-50/40 font-medium text-brand-800' : ''}`} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />
        </label>
    );
}

function Stat({ icon, label, value, tint, accent }: { icon: React.ReactNode; label: string; value: number; tint: string; accent: string }) {
    return (
        <div className="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
            <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ${tint}`}>{icon}</div>
            <div className="min-w-0 flex-1">
                <div className="truncate text-sm text-slate-500">{label}</div>
                <div className="text-2xl font-semibold tracking-tight text-slate-800">{value}</div>
            </div>
            <span className={`h-10 w-1 rounded-full ${accent} opacity-70 transition-opacity group-hover:opacity-100`} />
        </div>
    );
}
