import { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Search, IdCard, CheckCircle2, Clock, Users, Printer, Layers } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import Pagination from '@/Components/Pagination';

interface Row { id: number; name: string; program: string | null; status: string; issued: string | null }
interface Paginated { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number }
interface Stats { total: number; issued: number; pending: number }
interface ProgramOpt { id: number; title: string }

function initials(name: string) {
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}
function fmtDate(d: string | null) {
    if (!d) return null;
    const dt = new Date(d + 'T00:00:00');
    return isNaN(dt.getTime()) ? d : dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

export default function IdIndex({ applicants, filters, programs, canIssue, stats }: { applicants: Paginated; filters: { search?: string; program?: string }; programs: ProgramOpt[]; canIssue: boolean; stats: Stats }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [selected, setSelected] = useState<Set<number>>(new Set()); // persists across pages (Pagination preserves state)

    const go = (patch: Record<string, string>) =>
        router.get('/idsystem', { search, program: filters.program ?? '', ...patch }, { preserveState: true, preserveScroll: true, replace: true });
    const submit = (e: FormEvent) => { e.preventDefault(); go({ search }); };

    const toggle = (id: number) => setSelected((s) => { const n = new Set(s); n.has(id) ? n.delete(id) : n.add(id); return n; });
    const pageIds = applicants.data.map((a) => a.id);
    const allOnPage = pageIds.length > 0 && pageIds.every((id) => selected.has(id));
    const togglePage = () => setSelected((s) => {
        const n = new Set(s);
        allOnPage ? pageIds.forEach((id) => n.delete(id)) : pageIds.forEach((id) => n.add(id));
        return n;
    });

    const printSelected = () => selected.size && window.open(`/idsystem/sheet?ids=${[...selected].join(',')}`, '_blank', 'noopener');
    const printProgram = () => filters.program && window.open(`/idsystem/sheet?program=${filters.program}`, '_blank', 'noopener');

    const tiles = [
        { label: 'Learners', value: stats.total, icon: Users, tile: 'bg-brand-50 text-brand-600' },
        { label: 'ID issued', value: stats.issued, icon: CheckCircle2, tile: 'bg-emerald-50 text-emerald-600' },
        { label: 'Pending', value: stats.pending, icon: Clock, tile: 'bg-amber-50 text-amber-600' },
    ];

    return (
        <AppShell title="ID system">
            <Head title="ID system" />

            {/* Summary */}
            <div className="mb-5 flex flex-wrap items-center gap-2.5">
                {tiles.map((t) => (
                    <span key={t.label} className="inline-flex items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <span className={`flex h-8 w-8 items-center justify-center rounded-lg ${t.tile}`}><t.icon className="h-4 w-4" /></span>
                        <span className="text-lg font-semibold leading-none text-slate-800">{t.value}</span>
                        <span className="text-xs font-medium text-slate-400">{t.label}</span>
                    </span>
                ))}
            </div>

            {/* Filters + bulk print */}
            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-wrap items-center gap-2">
                    <form onSubmit={submit} className="relative max-w-xs flex-1">
                        <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <input className="input pl-9" placeholder="Search learner…" value={search} onChange={(e) => setSearch(e.target.value)} />
                    </form>
                    <select
                        className="input w-auto"
                        value={filters.program ?? ''}
                        onChange={(e) => go({ program: e.target.value })}
                    >
                        <option value="">All programs</option>
                        {programs.map((p) => <option key={p.id} value={String(p.id)}>{p.title}</option>)}
                    </select>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    {filters.program && (
                        <button onClick={printProgram} className="btn-ghost">
                            <Layers className="h-4 w-4" /> Print program IDs ({applicants.total})
                        </button>
                    )}
                    <button onClick={printSelected} disabled={selected.size === 0} className="btn-primary disabled:opacity-40">
                        <Printer className="h-4 w-4" /> Print selected ({selected.size})
                    </button>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="w-10 px-4 py-3">
                                    <input type="checkbox" className="rounded border-slate-300 text-brand-600 focus:ring-brand-400" checked={allOnPage} onChange={togglePage} title="Select all on this page" />
                                </th>
                                <th className="px-4 py-3">Learner</th>
                                <th className="px-4 py-3">Program</th>
                                <th className="px-4 py-3">ID status</th>
                                <th className="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {applicants.data.map((a) => (
                                <tr key={a.id} className={`hover:bg-slate-50 ${selected.has(a.id) ? 'bg-brand-50/40' : ''}`}>
                                    <td className="px-4 py-3">
                                        <input type="checkbox" className="rounded border-slate-300 text-brand-600 focus:ring-brand-400" checked={selected.has(a.id)} onChange={() => toggle(a.id)} />
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{initials(a.name)}</span>
                                            <div className="font-medium text-slate-800">{a.name}</div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{a.program ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        {a.issued
                                            ? <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"><CheckCircle2 className="h-3.5 w-3.5" /> Issued {fmtDate(a.issued)}</span>
                                            : <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500"><Clock className="h-3.5 w-3.5" /> Not issued</span>}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Link href={`/idsystem/${a.id}`} className="inline-flex items-center gap-1 rounded-md bg-brand-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-brand-700">
                                            <IdCard className="h-3.5 w-3.5" /> {canIssue ? 'Issue / print' : 'View card'}
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {applicants.data.length === 0 && (
                                <tr><td colSpan={5} className="px-4 py-14 text-center text-sm text-slate-400">
                                    <IdCard className="mx-auto mb-2 h-8 w-8 text-slate-300" />
                                    {filters.search || filters.program ? 'No learners match your filters.' : 'No learners yet.'}
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
            <Pagination links={applicants.links} from={applicants.from} to={applicants.to} total={applicants.total} />
        </AppShell>
    );
}
