import { useEffect, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Banknote, Receipt, Wallet, TrendingUp, X, Ban, FileSpreadsheet, Printer, Plus, Tags, Search, FileText, CalendarClock } from 'lucide-react';
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
    id: number; applicant: string | null; amount: number; category: string; description: string | null;
    type: string; method: string;
    or_number: string | null; paid_at: string | null; cashier: string | null;
    voided: boolean; void_reason: string | null;
}
interface Learner { id: number; name: string; program: string | null; balance: number }
interface Aggregates {
    collected: number; fee_collected: number; other_collected: number; outstanding: number;
    by_program: { program: string; expected: number; collected: number; pct: number }[];
    by_category: { category: string; total: number; count: number }[];
}
interface Props {
    worklist: WorkItem[]; ledger: LedgerItem[]; learners: Learner[];
    canFinance: boolean; canRecord: boolean; canVoid: boolean;
    methods: string[]; types: string[]; categories: string[]; trainingFeeCategory: string; otherCategory: string;
    aggregates?: Aggregates;
    programs?: { id: number; title: string }[];
}

export default function CashierIndex(props: Props) {
    const { worklist, ledger, learners, canFinance, canRecord, canVoid, aggregates } = props;
    const [pay, setPay] = useState<{ learner?: Learner } | null>(null);
    const [voiding, setVoiding] = useState<LedgerItem | null>(null);
    const [reportOpen, setReportOpen] = useState(false);
    const [query, setQuery] = useState('');

    // Counter search: filters the worklist + ledger as the cashier types.
    const q = query.trim().toLowerCase();
    const shownWork = q
        ? worklist.filter((w) => w.name.toLowerCase().includes(q) || (w.program ?? '').toLowerCase().includes(q))
        : worklist;
    const shownLedger = q
        ? ledger.filter((p) => (p.applicant ?? '').toLowerCase().includes(q) || (p.or_number ?? '').toLowerCase().includes(q))
        : ledger;
    // Matches with no outstanding fee balance (paying for uniform/ID/etc.) still surface.
    const otherMatches = q && canRecord
        ? learners.filter((l) => l.name.toLowerCase().includes(q) && !shownWork.some((w) => w.id === l.id)).slice(0, 5)
        : [];

    // After a payment is recorded, pop its receipt in a new tab.
    const flash = usePage<PageProps>().props.flash as { receipt_id?: number | null };
    useEffect(() => {
        if (flash?.receipt_id) window.open(`/cashier/payments/${flash.receipt_id}/receipt`, '_blank', 'noopener');
    }, [flash?.receipt_id]);

    return (
        <AppShell title="Cashier">
            <Head title="Cashier" />

            <div className="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div className="relative max-w-sm flex-1">
                    <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <input
                        className="input pl-9"
                        placeholder="Search trainee to collect from…"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                    />
                </div>
                <div className="flex flex-wrap justify-end gap-2">
                    {canRecord && (
                        <button onClick={() => setPay({})} className="btn-primary hidden md:inline-flex">
                            <Plus className="h-4 w-4" /> Receive payment
                        </button>
                    )}
                    {(canRecord || canFinance) && (
                        <a href="/cashier/daily" target="_blank" rel="noopener noreferrer" className="btn-ghost">
                            <CalendarClock className="h-4 w-4" /> Daily report
                        </a>
                    )}
                    {canFinance && (
                        <button onClick={() => setReportOpen(true)} className="btn-ghost">
                            <FileSpreadsheet className="h-4 w-4" /> Report / Export
                        </button>
                    )}
                </div>
            </div>

            {/* Mobile: thumb-reachable floating action */}
            {canRecord && (
                <button
                    onClick={() => setPay({})}
                    className="fixed bottom-5 right-5 z-30 inline-flex items-center gap-2 rounded-full bg-brand-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/30 active:scale-95 md:hidden"
                >
                    <Plus className="h-5 w-5" /> Receive payment
                </button>
            )}

            {canFinance && aggregates && (
                <div className="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    <Stat icon={<Wallet className="h-5 w-5" />} label="Total collected" value={peso(aggregates.collected)} tone="emerald" />
                    <Stat icon={<Banknote className="h-5 w-5" />} label="Miscellaneous fees" value={peso(aggregates.fee_collected)} tone="brand" />
                    <Stat icon={<Tags className="h-5 w-5" />} label="Other collections" value={peso(aggregates.other_collected)} tone="violet" />
                    <Stat icon={<TrendingUp className="h-5 w-5" />} label="Fee outstanding" value={peso(aggregates.outstanding)} tone="amber" />
                </div>
            )}

            {canFinance && aggregates && aggregates.by_category.length > 0 && (
                <div className="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Collections by category</h3>
                    <div className="flex flex-wrap gap-2.5">
                        {aggregates.by_category.map((c) => (
                            <div key={c.category} className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <span className="text-sm font-medium text-slate-700">{c.category}</span>
                                <span className="text-sm font-semibold text-brand-700">{peso(c.total)}</span>
                                <span className="rounded-full bg-white px-1.5 py-0.5 text-[10px] text-slate-400">{c.count}</span>
                            </div>
                        ))}
                    </div>
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
                <h3 className="mb-2 text-sm font-semibold text-slate-700">To collect ({q ? `${shownWork.length} of ${worklist.length}` : worklist.length})</h3>

                {/* Mobile: card per learner, Collect in easy reach */}
                <div className="space-y-3 md:hidden">
                    {shownWork.map((w) => (
                        <div key={w.id} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex items-center gap-3">
                                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{initials(w.name)}</span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium text-slate-800">{w.name}</p>
                                    <p className="truncate text-xs text-slate-500">{w.program ?? '—'}</p>
                                </div>
                                <span className="inline-flex shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-sm font-semibold text-amber-700">{peso(w.balance)}</span>
                            </div>
                            <div className="mt-2.5 flex items-center gap-3 text-xs text-slate-500">
                                <span>Fee {peso(w.fee)}</span>
                                <span>·</span>
                                <span>Paid {peso(w.paid)}</span>
                                {w.pay_status === 'Partial' && <span className="rounded bg-sky-50 px-1.5 py-0.5 text-[10px] font-medium text-sky-600">Partial</span>}
                            </div>
                            <div className="mt-3 flex items-center gap-2">
                                {canRecord && (
                                    <button
                                        onClick={() => setPay({ learner: { id: w.id, name: w.name, program: w.program, balance: w.balance } })}
                                        className="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white active:scale-[0.98]"
                                    >
                                        <Banknote className="h-4 w-4" /> Collect {peso(w.balance)}
                                    </button>
                                )}
                                <a
                                    href={`/cashier/${w.id}/statement`} target="_blank" rel="noopener noreferrer"
                                    className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-600 active:bg-slate-50"
                                >
                                    <FileText className="h-4 w-4" /> Statement
                                </a>
                            </div>
                        </div>
                    ))}
                    {shownWork.length === 0 && (
                        <div className="rounded-xl border border-slate-200 bg-white px-4 py-10 text-center shadow-sm">
                            <Wallet className="mx-auto h-8 w-8 text-slate-300" />
                            <p className="mt-2 text-sm text-slate-400">{q ? `No one owing matches “${query}”.` : 'No outstanding balances — everyone’s paid up.'}</p>
                        </div>
                    )}
                </div>

                <div className="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
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
                                {shownWork.map((w) => (
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
                                            <div className="flex items-center justify-end gap-1.5">
                                                <a href={`/cashier/${w.id}/statement`} target="_blank" rel="noopener noreferrer" title="Statement of account" className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-50">
                                                    <FileText className="h-3.5 w-3.5" /> Statement
                                                </a>
                                                {canRecord && (
                                                    <button onClick={() => setPay({ learner: { id: w.id, name: w.name, program: w.program, balance: w.balance } })} className="inline-flex items-center gap-1 rounded-md bg-brand-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700">
                                                        <Banknote className="h-3.5 w-3.5" /> Collect
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {shownWork.length === 0 && (
                                    <tr><td colSpan={6} className="px-4 py-12 text-center">
                                        <Wallet className="mx-auto h-8 w-8 text-slate-300" />
                                        <p className="mt-2 text-sm text-slate-400">{q ? `No one owing matches “${query}”.` : 'No outstanding balances — everyone’s paid up.'}</p>
                                    </td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Searched trainees with no fee balance — still collectable (uniform, ID, etc.) */}
                {otherMatches.length > 0 && (
                    <div className="mt-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                        <p className="mb-2 px-1 text-xs font-medium uppercase tracking-wide text-slate-400">Fully paid / no fee balance</p>
                        <div className="divide-y divide-slate-50">
                            {otherMatches.map((l) => (
                                <div key={l.id} className="flex items-center justify-between gap-3 px-1 py-2">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{initials(l.name)}</span>
                                        <div className="min-w-0">
                                            <p className="truncate font-medium text-slate-800">{l.name}</p>
                                            <p className="truncate text-xs text-slate-500">{l.program ?? '—'}</p>
                                        </div>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-1.5">
                                        <a href={`/cashier/${l.id}/statement`} target="_blank" rel="noopener noreferrer" title="Statement of account" className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-50">
                                            <FileText className="h-3.5 w-3.5" /> Statement
                                        </a>
                                        <button
                                            onClick={() => setPay({ learner: l })}
                                            className="inline-flex items-center gap-1 rounded-md border border-brand-200 px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
                                        >
                                            <Banknote className="h-3.5 w-3.5" /> Receive payment
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Ledger */}
            <h3 className="mb-2 text-sm font-semibold text-slate-700">
                {canFinance ? 'Payments ledger' : 'My payments'}
            </h3>

            {/* Mobile: card per payment */}
            <div className="space-y-3 pb-20 md:hidden">
                {shownLedger.map((p) => (
                    <div key={p.id} className={`rounded-xl border border-slate-200 bg-white p-4 shadow-sm ${p.voided ? 'opacity-60' : ''}`}>
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="truncate font-medium text-slate-800">{p.applicant}</p>
                                <p className="mt-0.5 text-xs text-slate-500">
                                    {p.paid_at} · <span className="font-mono">{p.or_number ?? '—'}</span>
                                    {canFinance && p.cashier && <> · {p.cashier}</>}
                                </p>
                            </div>
                            <span className={`shrink-0 text-base font-semibold ${p.voided ? 'text-slate-400 line-through' : 'text-slate-800'}`}>{peso(p.amount)}</span>
                        </div>
                        <div className="mt-2 flex flex-wrap items-center gap-1.5">
                            <span className="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{p.category}</span>
                            <MethodBadge method={p.method} />
                            <span className="text-xs text-slate-400">{p.type}</span>
                            {p.description && <span className="w-full text-xs text-slate-400">{p.description}</span>}
                        </div>
                        <div className="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
                            <a
                                href={`/cashier/payments/${p.id}/receipt`} target="_blank" rel="noopener noreferrer"
                                className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 active:bg-slate-50"
                            >
                                <Printer className="h-3.5 w-3.5" /> Receipt
                            </a>
                            {p.voided ? (
                                <span className="flex-1 text-center text-xs font-medium text-rose-400" title={p.void_reason ?? ''}>VOID</span>
                            ) : canVoid ? (
                                <button
                                    onClick={() => setVoiding(p)}
                                    className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-rose-100 px-3 py-2 text-xs font-medium text-rose-500 active:bg-rose-50"
                                >
                                    <Ban className="h-3.5 w-3.5" /> Void
                                </button>
                            ) : null}
                        </div>
                    </div>
                ))}
                {shownLedger.length === 0 && (
                    <div className="rounded-xl border border-slate-200 bg-white px-4 py-10 text-center shadow-sm">
                        <Receipt className="mx-auto h-8 w-8 text-slate-300" />
                        <p className="mt-2 text-sm text-slate-400">{q ? `No payments match “${query}”.` : 'No payments recorded yet.'}</p>
                    </div>
                )}
            </div>

            <div className="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Date</th><th className="px-4 py-3">OR No.</th>
                                <th className="px-4 py-3">Learner</th><th className="px-4 py-3">Item</th><th className="px-4 py-3">Method</th>
                                <th className="px-4 py-3 text-right">Amount</th>
                                {canFinance && <th className="px-4 py-3">Cashier</th>}
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {shownLedger.map((p) => (
                                <tr key={p.id} className={`hover:bg-slate-50 ${p.voided ? 'opacity-60' : ''}`}>
                                    <td className="px-4 py-3 text-slate-600">{p.paid_at}</td>
                                    <td className="px-4 py-3 font-mono text-xs text-slate-500">{p.or_number ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2.5">
                                            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-500">{initials(p.applicant)}</span>
                                            <span className="text-slate-700">{p.applicant}</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{p.category}</span>
                                        {p.description && <span className="ml-1 text-xs text-slate-400">{p.description}</span>}
                                    </td>
                                    <td className="px-4 py-3"><MethodBadge method={p.method} /> <span className="ml-1 text-xs text-slate-400">{p.type}</span></td>
                                    <td className={`px-4 py-3 text-right font-semibold ${p.voided ? 'text-slate-400 line-through' : 'text-slate-800'}`}>{peso(p.amount)}</td>
                                    {canFinance && <td className="px-4 py-3 text-xs text-slate-500">{p.cashier}</td>}
                                    <td className="px-4 py-3">
                                        <div className="flex items-center justify-end gap-3">
                                            <a href={`/cashier/payments/${p.id}/receipt`} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-brand-600" title="Print receipt">
                                                <Printer className="h-3.5 w-3.5" /> Receipt
                                            </a>
                                            {p.voided ? (
                                                <span className="text-xs text-rose-400" title={p.void_reason ?? ''}>VOID</span>
                                            ) : canVoid ? (
                                                <button onClick={() => setVoiding(p)} className="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-rose-600">
                                                    <Ban className="h-3.5 w-3.5" /> Void
                                                </button>
                                            ) : null}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {shownLedger.length === 0 && (
                                <tr><td colSpan={canFinance ? 8 : 7} className="px-4 py-12 text-center">
                                    <Receipt className="mx-auto h-8 w-8 text-slate-300" />
                                    <p className="mt-2 text-sm text-slate-400">{q ? `No payments match “${query}”.` : 'No payments recorded yet.'}</p>
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {pay && (
                <PaymentModal
                    fixedLearner={pay.learner}
                    learners={learners}
                    methods={props.methods}
                    types={props.types}
                    categories={props.categories}
                    trainingFeeCategory={props.trainingFeeCategory}
                    otherCategory={props.otherCategory}
                    onClose={() => setPay(null)}
                />
            )}
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
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-4">
            <div className="max-h-[92dvh] w-full max-w-2xl overflow-y-auto rounded-t-2xl bg-white pb-[env(safe-area-inset-bottom)] shadow-xl sm:max-h-[calc(100vh-3rem)] sm:rounded-xl sm:pb-0">
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

                <div className="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
                    <button onClick={onClose} className="btn-ghost justify-center">Cancel</button>
                    <button onClick={csv} className="btn-ghost justify-center"><FileSpreadsheet className="h-4 w-4" /> {canApprove ? 'Export CSV' : 'Request CSV'}</button>
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
        emerald: 'bg-emerald-50 text-emerald-600', amber: 'bg-amber-50 text-amber-600',
        brand: 'bg-brand-50 text-brand-600', violet: 'bg-violet-50 text-violet-600',
    };
    return (
        <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:shadow sm:gap-4 sm:p-5">
            <div className={`hidden h-12 w-12 shrink-0 items-center justify-center rounded-xl sm:flex ${tones[tone]}`}>{icon}</div>
            <div className="min-w-0">
                <div className="truncate text-xs text-slate-500 sm:text-sm">{label}</div>
                <div className="truncate text-lg font-semibold text-slate-800 sm:text-2xl">{value}</div>
            </div>
        </div>
    );
}

function PaymentModal({
    fixedLearner, learners, methods, types, categories, trainingFeeCategory, otherCategory, onClose,
}: {
    fixedLearner?: Learner;
    learners: Learner[];
    methods: string[];
    types: string[];
    categories: string[];
    trainingFeeCategory: string;
    otherCategory: string;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        learner_id: fixedLearner ? String(fixedLearner.id) : '',
        category: trainingFeeCategory,
        description: '',
        amount: fixedLearner && fixedLearner.balance > 0 ? fixedLearner.balance : ('' as number | ''),
        type: 'Partial',
        method: 'Cash',
        paid_at: new Date().toISOString().slice(0, 10),
    });
    const [search, setSearch] = useState('');

    const learner = fixedLearner ?? learners.find((l) => String(l.id) === data.learner_id);
    const isFee = data.category === trainingFeeCategory;
    const isOther = data.category === otherCategory;

    const q = search.trim().toLowerCase();
    const matches = q === '' ? [] : learners
        .filter((l) => l.name.toLowerCase().includes(q) || (l.program ?? '').toLowerCase().includes(q))
        .slice(0, 8);

    // Picking a learner for a training-fee payment pre-fills the outstanding balance.
    const pickLearner = (id: string) => {
        const l = learners.find((x) => String(x.id) === id);
        setData((d) => ({ ...d, learner_id: id, amount: isFee && l && l.balance > 0 ? l.balance : d.amount }));
        setSearch('');
    };
    const pickCategory = (cat: string) => {
        setData((d) => ({ ...d, category: cat, amount: cat === trainingFeeCategory && learner && learner.balance > 0 ? learner.balance : d.amount }));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.learner_id) return;
        post(`/cashier/${data.learner_id}/payments`, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-4">
            <div className="max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white pb-[env(safe-area-inset-bottom)] shadow-xl sm:max-h-[calc(100vh-3rem)] sm:rounded-xl sm:pb-0">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600"><Banknote className="h-5 w-5" /></span>
                        <div>
                            <h3 className="text-base font-semibold text-slate-800">{fixedLearner ? 'Record payment' : 'Receive payment'}</h3>
                            <p className="text-xs text-slate-500">
                                {learner
                                    ? <>{learner.name}{isFee && learner.balance > 0 && <> · fee balance <span className="font-medium text-amber-600">{peso(learner.balance)}</span></>}</>
                                    : 'Select a learner and what they are paying for'}
                            </p>
                        </div>
                    </div>
                    <button type="button" onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-3 px-5 py-4">
                    {!fixedLearner && !learner && (
                        <div>
                            <span className="mb-1 block text-xs font-medium text-slate-600">Learner</span>
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                                <input
                                    className="input pl-9" placeholder="Type the trainee's name…"
                                    value={search} onChange={(e) => setSearch(e.target.value)} autoFocus
                                />
                            </div>
                            {matches.length > 0 && (
                                <div className="mt-1 max-h-52 divide-y divide-slate-50 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                                    {matches.map((l) => (
                                        <button key={l.id} type="button" onClick={() => pickLearner(String(l.id))}
                                            className="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-brand-50/60">
                                            <span className="min-w-0">
                                                <span className="block truncate font-medium text-slate-700">{l.name}</span>
                                                <span className="block truncate text-xs text-slate-400">{l.program ?? '—'}</span>
                                            </span>
                                            {l.balance > 0 && <span className="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">{peso(l.balance)}</span>}
                                        </button>
                                    ))}
                                </div>
                            )}
                            {q !== '' && matches.length === 0 && <p className="mt-1 text-xs text-slate-400">No learners match “{search}”.</p>}
                            {errors.learner_id && <span className="text-xs text-rose-600">{errors.learner_id}</span>}
                        </div>
                    )}
                    {!fixedLearner && learner && (
                        <div className="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <span className="min-w-0">
                                <span className="block truncate text-sm font-medium text-slate-700">{learner.name}</span>
                                <span className="block truncate text-xs text-slate-400">{learner.program ?? '—'}</span>
                            </span>
                            <button type="button" onClick={() => setData('learner_id', '')} className="shrink-0 text-xs font-medium text-brand-600 hover:underline">Change</button>
                        </div>
                    )}
                    <div className="grid grid-cols-2 gap-3">
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Paying for</span>
                            <select className="input" value={data.category} onChange={(e) => pickCategory(e.target.value)}>{categories.map((c) => <option key={c}>{c}</option>)}</select>
                            {errors.category && <span className="text-xs text-rose-600">{errors.category}</span>}
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Amount (₱)</span>
                            <input type="number" inputMode="decimal" min="0" className="input" value={data.amount} onChange={(e) => setData('amount', e.target.value === '' ? '' : Number(e.target.value))} />
                            {errors.amount && <span className="text-xs text-rose-600">{errors.amount}</span>}
                        </label>
                        <label className="col-span-2 block">
                            <span className="mb-1 block text-xs font-medium text-slate-600">
                                {isOther ? <>Specify <span className="text-rose-500">*</span></> : <>Description <span className="text-slate-400">(optional)</span></>}
                            </span>
                            <input className="input" value={data.description} onChange={(e) => setData('description', e.target.value)}
                                placeholder={isOther ? 'What is this collection for?' : isFee ? 'e.g. 2nd installment' : 'e.g. 1 set uniform, size M'} />
                            {errors.description && <span className="text-xs text-rose-600">{errors.description}</span>}
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Type</span>
                            <select className="input" value={data.type} onChange={(e) => setData('type', e.target.value)}>{types.map((t) => <option key={t}>{t}</option>)}</select>
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Method</span>
                            <select className="input" value={data.method} onChange={(e) => setData('method', e.target.value)}>{methods.map((m) => <option key={m}>{m}</option>)}</select>
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Payment date</span>
                            <input type="date" className="input" value={data.paid_at} onChange={(e) => setData('paid_at', e.target.value)} />
                        </label>
                    </div>
                    <p className="rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-slate-400">An OR number is assigned automatically. A receipt opens after recording — also reprintable from the ledger.</p>
                    <div className="flex gap-2 border-t border-slate-100 pt-3 sm:justify-end">
                        <button type="button" onClick={onClose} className="btn-ghost justify-center max-sm:flex-1">Cancel</button>
                        <button type="submit" disabled={processing || !data.learner_id} className="btn-primary py-2.5 max-sm:flex-[2] sm:py-2">Record payment</button>
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
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-4">
            <div className="w-full max-w-md rounded-t-2xl bg-white pb-[env(safe-area-inset-bottom)] shadow-xl sm:rounded-xl sm:pb-0">
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
