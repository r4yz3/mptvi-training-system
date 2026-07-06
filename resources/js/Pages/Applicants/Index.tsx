import { FormEvent, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search, UserCircle2, X, FileSpreadsheet, Printer, ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import { PageProps } from '@/types';

interface Row {
    id: number;
    name: string;
    sex: string | null;
    age: number | null;
    barangay: string;
    contact: string | null;
    education: string | null;
    program: string | null;
    level: string | null;
    status: string;
    active: boolean;
    photo_url: string | null;
    custom?: Record<string, string | number | boolean | null>;
}
interface CustomFieldDef { key: string; label: string; type: string; options: string[] | null }
interface Paginated {
    data: Row[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}
interface Options {
    statuses: string[];
    programs: { id: number; title: string }[];
    barangays: string[];
    school_years: string[];
    class_sessions: string[];
    listCustom: CustomFieldDef[];
    filterCustom: CustomFieldDef[];
    canExport: boolean;
}
interface Filters {
    search?: string;
    status?: string;
    program?: string;
    barangay?: string;
    active?: string;
    school_year?: string;
    sort?: string;
    dir?: string;
    custom?: Record<string, string>;
}

export default function ApplicantsIndex({
    applicants,
    filters,
    options,
}: {
    applicants: Paginated;
    filters: Filters;
    options: Options;
}) {
    const { auth } = usePage<PageProps>().props;
    const [form, setForm] = useState<Filters>(filters);
    const [customForm, setCustomForm] = useState<Record<string, string>>(filters.custom ?? {});
    const [reportOpen, setReportOpen] = useState(false);

    const apply = (next: Filters, nextCustom = customForm) => {
        const clean: Record<string, string> = Object.fromEntries(
            Object.entries(next).filter(([k, v]) => k !== 'custom' && v !== '' && v != null) as [string, string][],
        );
        Object.entries(nextCustom).forEach(([k, v]) => { if (v) clean[`cf_${k}`] = v; });
        router.get('/applicants', clean, { preserveState: true, preserveScroll: true, replace: true });
    };

    const onSearch = (e: FormEvent) => {
        e.preventDefault();
        apply(form);
    };

    const set = (key: keyof Filters, value: string) => {
        const next = { ...form, [key]: value };
        setForm(next);
        if (key !== 'search') apply(next);
    };

    const setCustom = (key: string, value: string) => {
        const next = { ...customForm, [key]: value };
        setCustomForm(next);
        apply(form, next);
    };

    const clearAll = () => {
        setForm({});
        setCustomForm({});
        router.get('/applicants', {}, { preserveScroll: true, replace: true });
    };

    // Click a column header to sort by it; click again to flip direction.
    const sortBy = (key: string) => {
        const dir = filters.sort === key && filters.dir !== 'desc' ? 'desc' : 'asc';
        const next = { ...form, sort: key, dir };
        setForm(next);
        apply(next);
    };

    const hasFilters = Object.entries(filters).some(([k, v]) => (k === 'custom' ? Object.keys(v ?? {}).length : v));
    const listCustom = options.listCustom ?? [];

    return (
        <AppShell title="Applicants">
            <Head title="Applicants" />

            {/* Toolbar */}
            <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <form onSubmit={onSearch} className="relative max-w-sm flex-1">
                    <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <input
                        className="input pl-9"
                        placeholder="Search name, contact…"
                        value={form.search ?? ''}
                        onChange={(e) => setForm({ ...form, search: e.target.value })}
                    />
                </form>
                <div className="flex flex-wrap gap-2">
                    {options.canExport && (
                        <button onClick={() => setReportOpen(true)} className="btn-ghost">
                            <FileSpreadsheet className="h-4 w-4" /> Report / Export
                        </button>
                    )}
                    {auth.can['applicant.create'] && (
                        <Link
                            href="/applicants/create"
                            className="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                        >
                            <Plus className="h-4 w-4" /> Register applicant
                        </Link>
                    )}
                </div>
            </div>

            {/* Filters */}
            <div className="mb-4 flex flex-wrap items-center gap-2">
                <select className="input w-auto" value={form.status ?? ''} onChange={(e) => set('status', e.target.value)}>
                    <option value="">All statuses</option>
                    {options.statuses.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
                <select className="input w-auto" value={form.program ?? ''} onChange={(e) => set('program', e.target.value)}>
                    <option value="">All programs</option>
                    {options.programs.map((p) => <option key={p.id} value={p.id}>{p.title}</option>)}
                </select>
                <select className="input w-auto" value={form.barangay ?? ''} onChange={(e) => set('barangay', e.target.value)}>
                    <option value="">All barangays</option>
                    {options.barangays.map((b) => <option key={b} value={b}>{b}</option>)}
                </select>
                <select className="input w-auto" value={form.school_year ?? ''} onChange={(e) => set('school_year', e.target.value)}>
                    <option value="">All school years</option>
                    {options.school_years.map((y) => <option key={y} value={y}>S.Y. {y}</option>)}
                </select>
                <select className="input w-auto" value={form.active ?? ''} onChange={(e) => set('active', e.target.value)}>
                    <option value="">Active &amp; inactive</option>
                    <option value="active">Active only</option>
                    <option value="inactive">Inactive only</option>
                </select>
                {(options.filterCustom ?? []).map((cf) => (
                    cf.type === 'select' || cf.type === 'checkbox' ? (
                        <select key={cf.key} className="input w-auto" value={customForm[cf.key] ?? ''} onChange={(e) => setCustom(cf.key, e.target.value)}>
                            <option value="">All {cf.label}</option>
                            {cf.type === 'checkbox'
                                ? [<option key="1" value="1">Yes</option>, <option key="0" value="0">No</option>]
                                : (cf.options ?? []).map((o) => <option key={o} value={o}>{o}</option>)}
                        </select>
                    ) : (
                        <input key={cf.key} className="input w-auto" placeholder={cf.label} value={customForm[cf.key] ?? ''} onChange={(e) => setCustom(cf.key, e.target.value)} />
                    )
                ))}
                {hasFilters && (
                    <button onClick={clearAll} className="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-sm text-slate-500 hover:text-rose-600">
                        <X className="h-4 w-4" /> Clear
                    </button>
                )}
            </div>

            {/* Table */}
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <Th label="Applicant" sortKey="name" sort={filters.sort} dir={filters.dir} onSort={sortBy} />
                                <Th label="Program" sortKey="program" sort={filters.sort} dir={filters.dir} onSort={sortBy} />
                                <Th label="Barangay" sortKey="barangay" sort={filters.sort} dir={filters.dir} onSort={sortBy} />
                                {listCustom.map((cf) => <th key={cf.key} className="px-4 py-3">{cf.label}</th>)}
                                <Th label="Status" sortKey="status" sort={filters.sort} dir={filters.dir} onSort={sortBy} />
                                <Th label="Active" sortKey="active" sort={filters.sort} dir={filters.dir} onSort={sortBy} />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {applicants.data.map((a) => (
                                <tr
                                    key={a.id}
                                    className="cursor-pointer hover:bg-slate-50"
                                    onClick={() => router.visit(`/applicants/${a.id}`)}
                                >
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            {a.photo_url ? (
                                                <img src={a.photo_url} alt="" className="h-9 w-9 rounded-full object-cover" />
                                            ) : (
                                                <UserCircle2 className="h-9 w-9 text-slate-300" />
                                            )}
                                            <div>
                                                <div className="font-medium text-slate-800">{a.name}</div>
                                                <div className="text-xs text-slate-400">
                                                    {[a.sex, a.age ? `${a.age} yrs` : null].filter(Boolean).join(' · ')}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">
                                        {a.program ? (
                                            <span>{a.program} <span className="text-slate-400">{a.level}</span></span>
                                        ) : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{a.barangay}</td>
                                    {listCustom.map((cf) => {
                                        const v = a.custom?.[cf.key];
                                        return <td key={cf.key} className="px-4 py-3 text-slate-600">{typeof v === 'boolean' ? (v ? 'Yes' : 'No') : (v == null || v === '' ? '—' : String(v))}</td>;
                                    })}
                                    <td className="px-4 py-3"><StatusBadge status={a.status} /></td>
                                    <td className="px-4 py-3">
                                        {a.active ? (
                                            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" /> Active
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                                <span className="h-1.5 w-1.5 rounded-full bg-slate-400" /> Inactive
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {applicants.data.length === 0 && (
                                <tr>
                                    <td colSpan={6 + listCustom.length} className="px-4 py-12 text-center text-sm text-slate-400">
                                        No applicants match your filters.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination links={applicants.links} from={applicants.from} to={applicants.to} total={applicants.total} />

            {reportOpen && (
                <ReportModal
                    options={options}
                    current={form}
                    customForm={customForm}
                    onClose={() => setReportOpen(false)}
                />
            )}
        </AppShell>
    );
}

function Th({ label, sortKey, sort, dir, onSort }: {
    label: string; sortKey: string; sort?: string; dir?: string; onSort: (k: string) => void;
}) {
    const active = sort === sortKey;
    return (
        <th className="px-4 py-3">
            <button
                type="button"
                onClick={() => onSort(sortKey)}
                className={`group inline-flex items-center gap-1 uppercase tracking-wide transition hover:text-slate-700 ${active ? 'text-slate-700' : ''}`}
            >
                {label}
                {active
                    ? (dir === 'desc' ? <ChevronDown className="h-3.5 w-3.5" /> : <ChevronUp className="h-3.5 w-3.5" />)
                    : <ChevronsUpDown className="h-3.5 w-3.5 text-slate-300 group-hover:text-slate-400" />}
            </button>
        </th>
    );
}

function ReportModal({
    options, current, customForm, onClose,
}: {
    options: Options;
    current: Filters;
    customForm: Record<string, string>;
    onClose: () => void;
}) {
    const [f, setF] = useState({
        status: current.status ?? '',
        program: current.program ?? '',
        school_year: current.school_year ?? '',
        class_session: '',
        barangay: current.barangay ?? '',
        active: current.active ?? '',
        registered_from: '',
        registered_to: '',
    });
    const set = (k: keyof typeof f, v: string) => setF((p) => ({ ...p, [k]: v }));

    const thisYear = () => {
        const y = new Date().getFullYear();
        setF((p) => ({ ...p, registered_from: `${y}-01-01`, registered_to: `${y}-12-31` }));
    };
    const thisMonth = () => {
        const d = new Date();
        const m = `${d.getMonth() + 1}`.padStart(2, '0');
        const last = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
        setF((p) => ({ ...p, registered_from: `${d.getFullYear()}-${m}-01`, registered_to: `${d.getFullYear()}-${m}-${last}` }));
    };
    const clearDates = () => setF((p) => ({ ...p, registered_from: '', registered_to: '' }));

    const canApprove = usePage<PageProps>().props.auth.can['download.approve'] ?? false;
    const params = () => {
        const p: Record<string, string> = {};
        if (current.search) p.search = current.search; // carried from on-screen search
        Object.entries(customForm).forEach(([k, v]) => { if (v) p[`cf_${k}`] = v as string; });
        Object.entries(f).forEach(([k, v]) => { if (v) p[k] = v as string; });
        return p;
    };
    const buildUrl = (path: string) => {
        const q = new URLSearchParams(params()).toString();
        return path + (q ? `?${q}` : '');
    };
    const request = (type: string) => router.post('/downloads', { type, params: params() }, { preserveScroll: true, onSuccess: onClose });
    const csv = () => { if (canApprove) { window.location.href = buildUrl('/applicants/export.csv'); onClose(); } else request('applicants_csv'); };
    const pdf = () => { if (canApprove) { window.open(buildUrl('/applicants/report'), '_blank', 'noopener'); onClose(); } else request('applicants_pdf'); };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600"><Printer className="h-5 w-5" /></span>
                        <div>
                            <h3 className="text-base font-semibold text-slate-800">Applicants report</h3>
                            <p className="text-xs text-slate-500">Filter the data, then generate a PDF or CSV</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>

                <div className="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2">
                    <Sel label="Status" value={f.status} onChange={(v) => set('status', v)} blank="All statuses" opts={options.statuses} />
                    <Sel label="Program" value={f.program} onChange={(v) => set('program', v)} blank="All programs" opts={options.programs.map((p) => ({ value: String(p.id), label: p.title }))} />
                    <Sel label="School year" value={f.school_year} onChange={(v) => set('school_year', v)} blank="All school years" opts={options.school_years} />
                    <Sel label="Class session" value={f.class_session} onChange={(v) => set('class_session', v)} blank="All sessions" opts={options.class_sessions} />
                    <Sel label="Barangay" value={f.barangay} onChange={(v) => set('barangay', v)} blank="All barangays" opts={options.barangays} />
                    <Sel label="Activity" value={f.active} onChange={(v) => set('active', v)} opts={[{ value: '', label: 'Active & inactive' }, { value: 'active', label: 'Active only' }, { value: 'inactive', label: 'Inactive only' }]} />
                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Registered from <span className="text-slate-400">(optional)</span></span>
                        <input type="date" className="input" value={f.registered_from} onChange={(e) => set('registered_from', e.target.value)} />
                    </label>
                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Registered to <span className="text-slate-400">(optional)</span></span>
                        <input type="date" className="input" value={f.registered_to} onChange={(e) => set('registered_to', e.target.value)} />
                    </label>
                    <div className="sm:col-span-2 flex flex-wrap items-center gap-2">
                        <span className="text-xs text-slate-400">Quick range:</span>
                        <button onClick={thisYear} className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">This year</button>
                        <button onClick={thisMonth} className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">This month</button>
                        <button onClick={clearDates} className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">Clear dates</button>
                    </div>
                    <p className="sm:col-span-2 text-xs text-slate-400">
                        Defaults match your current on-screen filters. The full filter summary is printed on the report and CSV.
                    </p>
                </div>

                <div className="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button onClick={onClose} className="btn-ghost">Cancel</button>
                    <button onClick={csv} className="btn-ghost"><FileSpreadsheet className="h-4 w-4" /> {canApprove ? 'Export CSV' : 'Request CSV'}</button>
                    <button onClick={pdf} className="btn-primary"><Printer className="h-4 w-4" /> {canApprove ? 'Generate PDF' : 'Request PDF'}</button>
                </div>
            </div>
        </div>
    );
}

function Sel({
    label, value, onChange, opts, blank,
}: {
    label: string; value: string; onChange: (v: string) => void;
    opts: (string | { value: string; label: string })[]; blank?: string;
}) {
    return (
        <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">{label}</span>
            <select className="input" value={value} onChange={(e) => onChange(e.target.value)}>
                {blank !== undefined && <option value="">{blank}</option>}
                {opts.map((o) => {
                    const opt = typeof o === 'string' ? { value: o, label: o } : o;
                    return <option key={opt.value} value={opt.value}>{opt.label}</option>;
                })}
            </select>
        </label>
    );
}
