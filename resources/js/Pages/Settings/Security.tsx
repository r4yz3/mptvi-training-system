import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft, ShieldCheck, ShieldAlert, CheckCircle2, AlertTriangle, XCircle, Info,
    KeyRound, Activity, LogIn, LogOut, Ban, Smartphone,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Check { key: string; label: string; status: 'ok' | 'warn' | 'fail' | 'info'; detail: string; advice: string | null }
interface TwoFactor { enabled: number; total: number; admins: number; admins_on: number; status: string; advice: string }
interface Posture { overall: 'healthy' | 'attention' | 'critical'; checks: Check[]; twofactor: TwoFactor; failed_24h: number }
interface Event { id: number; type: string; email: string | null; user: string | null; ip: string | null; at: string; at_full: string }

const OVERALL: Record<Posture['overall'], { label: string; cls: string; Icon: typeof ShieldCheck }> = {
    healthy: { label: 'Well protected', cls: 'bg-emerald-50 text-emerald-700 ring-emerald-200/70', Icon: ShieldCheck },
    attention: { label: 'Needs attention', cls: 'bg-amber-50 text-amber-700 ring-amber-200/70', Icon: ShieldAlert },
    critical: { label: 'Action needed', cls: 'bg-rose-50 text-rose-700 ring-rose-200/70', Icon: ShieldAlert },
};

const STATUS = {
    ok: { Icon: CheckCircle2, cls: 'text-emerald-600' },
    warn: { Icon: AlertTriangle, cls: 'text-amber-600' },
    fail: { Icon: XCircle, cls: 'text-rose-600' },
    info: { Icon: Info, cls: 'text-slate-400' },
};

const EVENT: Record<string, { label: string; cls: string; Icon: typeof LogIn }> = {
    login_success: { label: 'Signed in', cls: 'bg-emerald-50 text-emerald-700', Icon: LogIn },
    login_failed: { label: 'Failed login', cls: 'bg-rose-50 text-rose-700', Icon: XCircle },
    lockout: { label: 'Locked out', cls: 'bg-rose-100 text-rose-800', Icon: Ban },
    logout: { label: 'Signed out', cls: 'bg-slate-100 text-slate-600', Icon: LogOut },
    '2fa_success': { label: '2FA passed', cls: 'bg-emerald-50 text-emerald-700', Icon: Smartphone },
    '2fa_failed': { label: '2FA failed', cls: 'bg-rose-50 text-rose-700', Icon: Smartphone },
    '2fa_enabled': { label: '2FA enabled', cls: 'bg-brand-50 text-brand-700', Icon: KeyRound },
    '2fa_disabled': { label: '2FA disabled', cls: 'bg-amber-50 text-amber-700', Icon: KeyRound },
};

export default function Security({ posture, events }: { posture: Posture; events: Event[] }) {
    const overall = OVERALL[posture.overall];
    const OverallIcon = overall.Icon;
    const tf = posture.twofactor;

    return (
        <AppShell title="Security">
            <Head title="Security" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            {/* Header */}
            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex items-start gap-3.5">
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-sm">
                        <ShieldCheck className="h-6 w-6" />
                    </div>
                    <div>
                        <h2 className="text-xl font-semibold text-slate-800">Security</h2>
                        <p className="mt-0.5 max-w-xl text-sm text-slate-500">
                            How locked-down the system is, who has two-factor on, and recent sign-in activity.
                        </p>
                    </div>
                </div>
                <span className={`inline-flex items-center gap-1.5 self-start rounded-full px-3 py-1.5 text-xs font-medium ring-1 ${overall.cls}`}>
                    <OverallIcon className="h-3.5 w-3.5" /> {overall.label}
                </span>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Posture checklist */}
                <div className="lg:col-span-2">
                    <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Security checklist</h3>
                    <div className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm">
                        {posture.checks.map((c) => {
                            const s = STATUS[c.status];
                            const Icon = s.Icon;
                            return (
                                <div key={c.key} className="flex items-start gap-3 px-4 py-3">
                                    <Icon className={`mt-0.5 h-5 w-5 shrink-0 ${s.cls}`} />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <span className="text-sm font-medium text-slate-800">{c.label}</span>
                                            <span className="text-xs text-slate-500">{c.detail}</span>
                                        </div>
                                        {c.advice && <p className="mt-1 text-xs text-amber-700">{c.advice}</p>}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* 2FA adoption */}
                <div>
                    <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Two-factor adoption</h3>
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-end gap-4">
                            <div>
                                <div className="text-3xl font-semibold leading-none text-slate-800">{tf.enabled}<span className="text-lg text-slate-300">/{tf.total}</span></div>
                                <div className="mt-1 text-xs text-slate-500">staff with 2FA on</div>
                            </div>
                            <div className="ml-auto text-right">
                                <div className={`text-2xl font-semibold leading-none ${tf.admins_on < tf.admins ? 'text-amber-600' : 'text-emerald-600'}`}>
                                    {tf.admins_on}<span className="text-base text-slate-300">/{tf.admins}</span>
                                </div>
                                <div className="mt-1 text-xs text-slate-500">admins protected</div>
                            </div>
                        </div>
                        <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                            <div className="h-full rounded-full bg-brand-600 transition-all" style={{ width: `${tf.total ? (tf.enabled / tf.total) * 100 : 0}%` }} />
                        </div>
                        <p className="mt-3 flex items-start gap-2 text-xs text-slate-500">
                            <KeyRound className="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-500" /> {tf.advice}
                        </p>
                        <Link href="/profile" className="mt-3 inline-block text-xs font-medium text-brand-600 hover:text-brand-700">
                            Set up 2FA on your account →
                        </Link>
                    </div>

                    <div className="mt-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <AlertTriangle className="h-4 w-4 text-slate-400" /> Failed logins (24h)
                        </div>
                        <div className={`mt-1 text-3xl font-semibold ${posture.failed_24h > 10 ? 'text-rose-600' : 'text-slate-800'}`}>
                            {posture.failed_24h}
                        </div>
                        <p className="mt-1 text-xs text-slate-500">
                            {posture.failed_24h > 10 ? 'Unusually high — review the activity log below.' : 'Failed and locked-out attempts in the last day.'}
                        </p>
                    </div>
                </div>
            </div>

            {/* Activity log */}
            <div className="mt-6">
                <h3 className="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <Activity className="h-3.5 w-3.5" /> Recent sign-in activity
                </h3>
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="min-w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-400">
                            <tr>
                                <th className="px-4 py-2.5">Event</th>
                                <th className="px-4 py-2.5">Account</th>
                                <th className="hidden px-4 py-2.5 sm:table-cell">IP address</th>
                                <th className="px-4 py-2.5">When</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {events.length === 0 && (
                                <tr><td colSpan={4} className="px-4 py-6 text-center text-sm text-slate-400">No activity recorded yet.</td></tr>
                            )}
                            {events.map((e) => {
                                const meta = EVENT[e.type] ?? { label: e.type, cls: 'bg-slate-100 text-slate-600', Icon: Info };
                                const Icon = meta.Icon;
                                return (
                                    <tr key={e.id} className="hover:bg-slate-50/60">
                                        <td className="px-4 py-2.5">
                                            <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${meta.cls}`}>
                                                <Icon className="h-3.5 w-3.5" /> {meta.label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-2.5 text-slate-700">{e.user ?? e.email ?? '—'}</td>
                                        <td className="hidden px-4 py-2.5 font-mono text-xs text-slate-500 sm:table-cell">{e.ip ?? '—'}</td>
                                        <td className="px-4 py-2.5 text-slate-500" title={e.at_full}>{e.at}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppShell>
    );
}
