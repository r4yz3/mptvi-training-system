import { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Banknote, Receipt, Wallet, TrendingUp, X, Ban, FileSpreadsheet, Printer } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import { PageProps } from '@/types';

const peso = (n: number) => '₱' + n.toLocaleString();
function initials(name: string | null) {
    if (!name) return '—';
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}
const METHOD_STYLE: Record<string, string> = {
    Cash: 'bg-emerald-50 text-emerald-700',
    Check: 'bg-amber-50 text-amber-700',
    GCash: 'bg-sky-50 text-sky-700',
    Bank: 'bg-violet-50 text-violet-700',
};
function MethodBadge({ method }: { method: string }) {
    return <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${METHOD_STYLE[method] ?? 'bg-slate-100 text-slate-600'}`}>{method}</span>;
}

interface WorkItem {
    id: number; name: string; program: string | null;
    fee: number; paid: number; balance: number; pay_status: string; status: string;
}
interface LedgerItem {
    id: number; applicant: string | null; amount: number; type: string; method: string;
    or_number: string | null; paid_at: string | null; cashier: string | null;
    voided: boolean; void_reason: string | null;
}
interface Aggregates {
    collected: number; outstanding: number;
    by_program: { program: string; expected: number; collected: number; pct: number }[];
}
interface Props {
    worklist: WorkItem[]; ledger: LedgerItem[];
    canFinance: boolean; canRecord: boolean; canVoid: boolean;
    methods: string[]; types: string[]; aggregates?: Aggregates;
    programs?: { id: number; title: string }[];
}

export default function CashierIndex(props: Props) {
    const { worklist, ledger, canFinance, canRecord, canVoid, aggregates } = props;
    const [pay, setPay] = useState<WorkItem | null>(null);
    const [voiding, setVoiding] = useState<LedgerItem | null>(null);
    const [reportOpen, setReportOpen] = useState(false);

    return (
        <AppShell title="Cashier">
            <Head title="Cashier" />

            {canFinance && (
                <div className="mb-5 flex justify-end">
                    <button onClick={() => setReportOpen(true)} className="btn-ghost">
                        <FileSpreadsheet className="h-4 w-4" /> Report / Export
                    </button>
                </div>
            )}

            {canFinance && aggregates && (
                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Stat icon={<Wallet className="h-5 w-5" />} label="Total collected" value={peso(aggregates.collected)} tone="emerald" />
                    <Stat icon={<TrendingUp className="h-5 w-5" />} label="Outstanding" value={peso(aggregates.outstanding)} tone="amber" />
                    <Stat icon={<Receipt className="h-5 w-5" />} label="Payments on file" value={`${ledger.length}`} tone="brand" />
                </div>
            )}

            {canFinance && aggregates && aggregates.by_program.length > 0 && (
                <div className="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Collections by program</h3>
                    <div className="space-y-3.5">
                        {aggregates.by_program.map((b) => (
                            <div key={b.program}>
                                <div className="mb-1 flex items-center justify-between gap-3 text-xs">
                                    <span className="truncate text-slate-600">{b.program}</span>
                                    <span className="flex shrink-0 items-center gap-2">
                                        <span className="text-slate-400">{peso(b.collected)} / {peso(b.expected)}</span>
                                        <span className={`rounded-full px-1.5 py-0.5 font-semibold ${b.pct >= 100 ? 'bg-emerald-50 text-emerald-600' : b.pct >= 50 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500'}`}>{b.pct}%</span>
                                    </span>
                                </div>
                                <div className="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                    <div className={`h-full rounded-full ${b.pct >= 100 ? 'bg-emerald-500' : 'bg-brand-500'}`} style={{ width: `${Math.min(100, b.pct)}%` }} />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Worklist */}
            <div className="mb-6">
                <h3 className="mb-2 text-sm font-semibold text-slate-700">To collect ({worklist.length})</h3>
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="px-4 py-3">Learner</th><th className="px-4 py-3">Program</th>
                                    <th className="px-4 py-3 text-right">Fee</th><th className="px-4 py-3 text-right">Paid</th>
                                    <th className="px-4 py-3 text-right">Balance</th><th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {worklist.map((w) => (
                                    <tr key={w.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{initials(w.name)}</span>
                                                <span className="font-medium text-slate-800">{w.name}</span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-slate-600">{w.program ?? '—'}</td>
                                        <td className="px-4 py-3 text-right text-slate-600">{peso(w.fee)}</td>
                                        <td className="px-4 py-3 text-right text-slate-500">
                                            {peso(w.paid)}
                                            {w.pay_status === 'Partial' && <span className="ml-1 rounded bg-sky-50 px-1 py-0.5 text-[10px] font-medium text-sky-600">Partial</span>}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <span className="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-sm font-semibold text-amber-700">{peso(w.balance)}</span>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {canRecord && (
                                                <button onClick={() => setPay(w)} className="inline-flex items-center gap-1 rounded-md bg-brand-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700">
                                                    <Banknote className="h-3.5 w-3.5" /> Record
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {worklist.length === 0 && (
                                    <tr><td colSpan={6} className="px-4 py-12 text-center">
                                        <Wallet className="mx-auto h-8 w-8 text-slate-300" />
                                        <p className="mt-2 text-sm text-slate-400">No outstanding balances — everyone's paid up.</p>
                                    </td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Ledger */}
            <h3 className="mb-2 text-sm font-semibold text-slate-700">
                {canFinance ? 'Payments ledger' : 'My payments'}
            </h3>
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Date</th><th className="px-4 py-3">OR No.</th>
                                <th className="px-4 py-3">Learner</th><th className="px-4 py-3">Method</th>
                                <th className="px-4 py-3 text-right">Amount</th>
                                {canFinance && <th className="px-4 py-3">Cashier</th>}
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {ledger.map((p) => (
                                <tr key={p.id} className={`hover:bg-slate-50 ${p.voided ? 'opacity-60' : ''}`}>
                                    <td className="px-4 py-3 text-slate-600">{p.paid_at}</td>
                                    <td className="px-4 py-3 font-mono text-xs text-slate-500">{p.or_number ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2.5">
                                            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-500">{initials(p.applicant)}</span>
                                            <span className="text-slate-700">{p.applicant}</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3"><MethodBadge method={p.method} /> <span className="ml-1 text-xs text-slate-400">{p.type}</span></td>
                                    <td className={`px-4 py-3 text-right font-semibold ${p.voided ? 'text-slate-400 line-through' : 'text-slate-800'}`}>{peso(p.amount)}</td>
                                    {canFinance && <td className="px-4 py-3 text-xs text-slate-500">{p.cashier}</td>}
                                    <td className="px-4 py-3 text-right">
                                        {p.voided ? (
                                            <span className="text-xs text-rose-400" title={p.void_reason ?? ''}>VOID</span>
                                        ) : canVoid ? (
                                            <button onClick={() => setVoiding(p)} className="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-rose-600">
                                                <Ban className="h-3.5 w-3.5" /> Void
                                            </button>
                                        ) : null}
                                    </td>
                                </tr>
                            ))}
                            {ledger.length === 0 && (
                                <tr><td colSpan={canFinance ? 7 : 6} className="px-4 py-12 text-center">
                                    <Receipt className="mx-auto h-8 w-8 text-slate-300" />
                                    <p className="mt-2 text-sm text-slate-400">No payments recorded yet.</p>
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {pay && <RecordModal item={pay} methods={props.methods} types={props.types} onClose={() => setPay(null)} />}
            {voiding && <VoidModal item={voiding} onClose={() => setVoiding(null)} />}
            {reportOpen && <PaymentReportModal programs={props.programs ?? []} methods={props.methods} onClose={() => setReportOpen(false)} />}
        </AppShell>
    );
}

function PaymentReportModal({ programs, methods, onClose }: { programs: { id: number; title: string }[]; methods: string[]; onClose: () => void }) {
    const [f, setF] = useState({ program: '', method: '', status: '', paid_from: '', paid_to: '' });
    const set = (k: keyof typeof f, v: string) => setF((p) => ({ ...p, [k]: v }));

    const thisYear = () => { const y = new Date().getFullYear(); setF((p) => ({ ...p, paid_from: `${y}-01-01`, paid_to: `${y}-12-31` })); };
    const monthRange = (offset: number) => {
        const d = new Date();
        const t = new Date(d.getFullYear(), d.getMonth() + offset, 1);
        const m = `${t.getMonth() + 1}`.padStart(2, '0');
        const last = new Date(t.getFullYear(), t.getMonth() + 1, 0).getDate();
        setF((p) => ({ ...p, paid_from: `${t.getFullYear()}-${m}-01`, paid_to: `${t.getFullYear()}-${m}-${last}` }));
    };
    const clearDates = () => setF((p) => ({ ...p, paid_from: '', paid_to: '' }));

    const canApprove = usePage<PageProps>().props.auth.can['download.approve'] ?? false;
    const params = () => Object.fromEntries(Object.entries(f).filter(([, v]) => v));
    const url = (path: string) => {
        const p = new URLSearchParams();
        Object.entries(f).forEach(([k, v]) => { if (v) p.set(k, v); });
        const q = p.toString();
        return path + (q ? `?${q}` : '');
    };
    const request = (type: string) => router.post('/downloads', { type, params: params() }, { preserveScroll: true, onSuccess: onClose });
    const csv = () => { if (canApprove) { window.location.href = url('/cashier/export.csv'); onClose(); } else request('cashier_csv'); };
    const pdf = () => { if (canApprove) { window.open(url('/cashier/report'), '_blank', 'noopener'); onClose(); } else request('cashier_pdf'); };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600"><Receipt className="h-5 w-5" /></span>
                        <div>
                            <h3 className="text-base font-semibold text-slate-800">Payments report</h3>
                            <p className="text-xs text-slate-500">Filter the collections, then generate a PDF or CSV</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>

                <div className="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2">
                    <CSel label="Program" value={f.program} onChange={(v) => set('program', v)} blank="All programs" opts={programs.map((p) => ({ value: String(p.id), label: p.title }))} />
                    <CSel label="Method" value={f.method} onChange={(v) => set('method', v)} blank="All methods" opts={methods.map((m) => ({ value: m, label: m }))} />
                    <CSel label="Status" value={f.status} onChange={(v) => set('status', v)} opts={[{ value: '', label: 'All payments' }, { value: 'valid', label: 'Valid only' }, { value: 'voided', label: 'Voided only' }]} />
                    <div className="hidden sm:block" />
                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Paid from <span className="text-slate-400">(optional)</span></span>
                        <input type="date" className="input" value={f.paid_from} onChange={(e) => set('paid_from', e.target.value)} />
                    </label>
                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Paid to <span className="text-slate-400">(optional)</span></span>
                        <input type="date" className="input" value={f.paid_to} onChange={(e) => set('paid_to', e.target.value)} />
                    </label>
                    <div className="sm:col-span-2 flex flex-wrap items-center gap-2">
                        <span className="text-xs text-slate-400">Quick range:</span>
                        <button onClick={thisYear} className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">This year</button>
                        <button onClick={() => monthRange(0)} className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">This month</button>
                        <button onClick={() => monthRange(-1)} className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">Last month</button>
                        <button onClick={clearDates} className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">Clear dates</button>
                    </div>
                    <p className="sm:col-span-2 text-xs text-slate-400">Voided payments appear in the report but are excluded from the total collected.</p>
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

function CSel({ label, value, onChange, opts, blank }: { label: string; value: string; onChange: (v: string) => void; opts: { value: string; label: string }[]; blank?: string }) {
    return (
        <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">{label}</span>
            <select className="input" value={value} onChange={(e) => onChange(e.target.value)}>
                {blank !== undefined && <option value="">{blank}</option>}
                {opts.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
        </label>
    );
}

function Stat({ icon, label, value, tone }: { icon: React.ReactNode; label: string; value: string; tone: string }) {
    const tones: Record<string, string> = {
        emerald: 'bg-emerald-50 text-emerald-600', amber: 'bg-amber-50 text-amber-600', brand: 'bg-brand-50 text-brand-600',
    };
    return (
        <div className="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow">
            <div className={`flex h-12 w-12 items-center justify-center rounded-xl ${tones[tone]}`}>{icon}</div>
            <div>
                <div className="text-sm text-slate-500">{label}</div>
                <div className="text-2xl font-semibold text-slate-800">{value}</div>
            </div>
        </div>
    );
}

function RecordModal({ item, methods, types, onClose }: { item: WorkItem; methods: string[]; types: string[]; onClose: () => void }) {
    const { data, setData, post, processing, errors } = useForm({
        amount: item.balance, type: 'Partial', method: 'Cash', or_number: '',
        paid_at: new Date().toISOString().slice(0, 10),
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/cashier/${item.id}/payments`, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600"><Banknote className="h-5 w-5" /></span>
                        <div>
                            <h3 className="text-base font-semibold text-slate-800">Record payment</h3>
                            <p className="text-xs text-slate-500">{item.name} · balance <span className="font-medium text-amber-600">{peso(item.balance)}</span></p>
                        </div>
                    </div>
                    <button type="button" onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-3 px-5 py-4">
                    <div className="grid grid-cols-2 gap-3">
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Amount (₱)</span>
                            <input type="number" className="input" value={data.amount} onChange={(e) => setData('amount', Number(e.target.value))} autoFocus />
                            {errors.amount && <span className="text-xs text-rose-600">{errors.amount}</span>}
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Type</span>
                            <select className="input" value={data.type} onChange={(e) => setData('type', e.target.value)}>{types.map((t) => <option key={t}>{t}</option>)}</select>
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Method</span>
                            <select className="input" value={data.method} onChange={(e) => setData('method', e.target.value)}>{methods.map((m) => <option key={m}>{m}</option>)}</select>
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">OR number</span>
                            <input className="input" value={data.or_number} onChange={(e) => setData('or_number', e.target.value)} placeholder="OR-…" />
                        </label>
                        <label className="col-span-2 block"><span className="mb-1 block text-xs font-medium text-slate-600">Payment date</span>
                            <input type="date" className="input" value={data.paid_at} onChange={(e) => setData('paid_at', e.target.value)} />
                        </label>
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary">Record payment</button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function VoidModal({ item, onClose }: { item: LedgerItem; onClose: () => void }) {
    const { data, setData, put, processing, errors } = useForm({ reason: '' });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/cashier/payments/${item.id}/void`, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 className="text-base font-semibold text-slate-800">Void payment</h3>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-4 px-5 py-4">
                    <p className="text-sm text-slate-500">{item.applicant} · {peso(item.amount)} · {item.or_number}</p>
                    <textarea className="input" rows={3} value={data.reason} onChange={(e) => setData('reason', e.target.value)} placeholder="Reason for voiding (audit trail)" autoFocus />
                    {errors.reason && <span className="block text-xs text-rose-600">{errors.reason}</span>}
                    <div className="flex justify-end gap-2">
                        <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                        <button type="submit" disabled={processing} className="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50">Void payment</button>
                    </div>
                </form>
            </div>
        </div>
    );
}
