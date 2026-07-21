import { Head, Link, usePage } from '@inertiajs/react';
import {
    Users, ClipboardCheck, GraduationCap, Award, Wallet, UserPlus, CheckCircle2,
    UserX, Banknote, Receipt, ClipboardList, BarChart3, Plus, CalendarClock,
    type LucideIcon,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import { PageProps } from '@/types';

interface Card { label: string; value: number | string; tone: string }
interface PipelineStep { label: string; value: number }
interface Breakdown { label: string; items: { label: string; value: number }[] }

const TONE: Record<string, { badge: string; value: string; bar: string }> = {
    brand: { badge: 'bg-brand-50 text-brand-600', value: 'text-slate-800', bar: 'bg-brand-500' },
    emerald: { badge: 'bg-emerald-50 text-emerald-600', value: 'text-slate-800', bar: 'bg-emerald-500' },
    amber: { badge: 'bg-amber-50 text-amber-600', value: 'text-slate-800', bar: 'bg-amber-500' },
    indigo: { badge: 'bg-indigo-50 text-indigo-600', value: 'text-slate-800', bar: 'bg-indigo-500' },
    slate: { badge: 'bg-slate-100 text-slate-500', value: 'text-slate-700', bar: 'bg-slate-400' },
};

// Card-count → grid columns so a row is never left with a lone stranded card.
const COLS: Record<number, string> = {
    1: 'lg:grid-cols-2', 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3',
    4: 'lg:grid-cols-4', 5: 'lg:grid-cols-5',
};

function cardIcon(label: string): LucideIcon {
    const l = label.toLowerCase();
    if (l.includes('collected') || l.includes('collect')) return Banknote;
    if (l.includes('payments')) return Receipt;
    if (l.includes('paid')) return Wallet;
    if (l.includes('total applicant')) return Users;
    if (l.includes('registered') || l.includes('newly')) return UserPlus;
    if (l.includes('screening') || l.includes('awaiting')) return ClipboardCheck;
    if (l.includes('enrolled')) return CheckCircle2;
    if (l.includes('training')) return GraduationCap;
    if (l.includes('competent')) return Award;
    if (l.includes('assessment')) return ClipboardList;
    if (l.includes('certified')) return Award;
    if (l.includes('inactive')) return UserX;
    return BarChart3;
}

// Pipeline stage → accent colour (mirrors the StatusBadge palette).
const STAGE: Record<string, string> = {
    Registered: 'bg-slate-400', Enrolled: 'bg-sky-500',
    'In training': 'bg-indigo-500', 'For assessment': 'bg-amber-500', Certified: 'bg-brand-500',
};

export default function Dashboard({
    roleLabel, cards, pipeline, customBreakdowns = [],
}: {
    role: string | null; roleLabel: string | null; cards: Card[]; pipeline: PipelineStep[] | null;
    customBreakdowns?: Breakdown[];
}) {
    const { auth } = usePage<PageProps>().props;
    const now = new Date();
    const hour = now.getHours();
    const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
    const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });

    const can = auth.can;
    const actions = [
        can['applicant.create'] && { href: '/applicants/create', label: 'Register applicant', icon: Plus },
        can['screen'] && { href: '/screening', label: 'Screening queue', icon: ClipboardCheck },
        can['payment.record'] && { href: '/cashier', label: 'Record payment', icon: Banknote },
        can['attendance'] && { href: '/training', label: 'Training', icon: GraduationCap },
        can['assess'] && { href: '/assessment', label: 'Assessment', icon: ClipboardList },
    ].filter(Boolean) as { href: string; label: string; icon: LucideIcon }[];

    const pipeTotal = pipeline ? Math.max(1, pipeline.reduce((s, p) => s + p.value, 0)) : 1;
    const maxPipe = pipeline ? Math.max(1, ...pipeline.map((p) => p.value)) : 1;

    return (
        <AppShell title="Dashboard">
            <Head title="Dashboard" />

            {/* Hero */}
            <div className="overflow-hidden rounded-2xl bg-gradient-to-br from-brand-700 to-brand-600 p-6 text-white shadow-sm sm:p-7">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs text-white/70">
                            <CalendarClock className="h-4 w-4" /> {dateStr}
                        </div>
                        <h2 className="mt-1 text-2xl font-semibold">{greeting}, {auth.user.name.split(' ')[0]}.</h2>
                        <p className="mt-0.5 text-sm text-white/80">
                            Signed in as <span className="font-medium text-white">{roleLabel}</span> · Maximino Pellerin Sr. TVI
                        </p>
                    </div>
                    <div className="hidden h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-white/40 sm:flex">
                        <img src="/mptvi-logo.png" alt="MPTVI" className="h-12 w-12 object-contain" />
                    </div>
                </div>

                {actions.length > 0 && (
                    <div className="mt-5 flex flex-wrap gap-2">
                        {actions.map((a) => {
                            const Icon = a.icon;
                            return (
                                <Link key={a.href} href={a.href} className="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-sm font-medium text-white backdrop-blur transition hover:bg-white/25">
                                    <Icon className="h-4 w-4" /> {a.label}
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Stat cards — grid columns track the card count so the row is always balanced */}
            {cards.length > 0 && (
                <div className={`mt-6 grid grid-cols-2 gap-3 sm:gap-4 ${COLS[cards.length] ?? 'lg:grid-cols-4'}`}>
                    {cards.map((c) => {
                        const Icon = cardIcon(c.label);
                        const t = TONE[c.tone] ?? TONE.slate;
                        return (
                            <div key={c.label} className="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md sm:p-5">
                                <div className="flex items-start justify-between gap-2">
                                    <span className="min-w-0 truncate text-sm font-medium text-slate-500">{c.label}</span>
                                    <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${t.badge}`}>
                                        <Icon className="h-[17px] w-[17px]" />
                                    </span>
                                </div>
                                <div className={`mt-2 text-3xl font-semibold tracking-tight ${t.value}`}>{c.value}</div>
                                <div className={`mt-3 h-1 rounded-full ${t.bar} opacity-70 transition-opacity group-hover:opacity-100`} />
                            </div>
                        );
                    })}
                </div>
            )}

            <div className={`mt-6 grid grid-cols-1 gap-6 ${pipeline && customBreakdowns.length > 0 ? 'lg:grid-cols-3' : ''}`}>
                {/* Pipeline */}
                {pipeline && (
                    <div className={`rounded-xl border border-slate-200 bg-white p-6 shadow-sm ${customBreakdowns.length > 0 ? 'lg:col-span-2' : ''}`}>
                        <div className="mb-5 flex items-center justify-between">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Applicant pipeline</h3>
                            <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">{pipeTotal} in pipeline</span>
                        </div>
                        <div className="space-y-2.5">
                            {pipeline.map((p) => {
                                const pct = Math.round((p.value / pipeTotal) * 100);
                                return (
                                    <div key={p.label} className="flex items-center gap-3">
                                        <div className="flex w-32 shrink-0 items-center gap-2 text-sm text-slate-600">
                                            <span className={`h-2.5 w-2.5 shrink-0 rounded-full ${STAGE[p.label] ?? 'bg-slate-400'}`} />
                                            <span className="truncate">{p.label}</span>
                                        </div>
                                        <div className="h-6 flex-1 overflow-hidden rounded-md bg-slate-100/80">
                                            <div className={`flex h-full items-center justify-end rounded-md px-2 text-xs font-semibold text-white ${STAGE[p.label] ?? 'bg-slate-400'}`} style={{ width: `${Math.max(p.value > 0 ? 7 : 0, (p.value / maxPipe) * 100)}%` }}>
                                                {p.value > 0 ? p.value : ''}
                                            </div>
                                        </div>
                                        <div className="w-9 shrink-0 text-right text-xs tabular-nums text-slate-400">{pct}%</div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Custom breakdowns (side column beside the pipeline, or a full row on their own) */}
                {customBreakdowns.length > 0 && (
                    <div className={`space-y-6 ${pipeline ? '' : 'lg:grid lg:grid-cols-3 lg:gap-6 lg:space-y-0'}`}>
                        {customBreakdowns.map((b) => {
                            const max = Math.max(1, ...b.items.map((i) => i.value));
                            return (
                                <div key={b.label} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{b.label}</h3>
                                    <div className="space-y-2.5">
                                        {b.items.map((i) => (
                                            <div key={i.label}>
                                                <div className="mb-0.5 flex justify-between text-xs">
                                                    <span className="text-slate-600">{i.label}</span>
                                                    <span className="font-medium text-slate-700">{i.value}</span>
                                                </div>
                                                <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                    <div className="h-full rounded-full bg-brand-500" style={{ width: `${(i.value / max) * 100}%` }} />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppShell>
    );
}
