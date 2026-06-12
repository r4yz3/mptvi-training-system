import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, DatabaseBackup, Download, Trash2, Lock, Unlock, Clock, HardDrive, ShieldAlert, RefreshCw, CalendarClock, CheckCircle2 } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Backup { name: string; size: string; bytes: number; created: string; encrypted: boolean }
interface Stats { count: number; total: string; last: string | null; encrypted: boolean; schedule: string }
interface ScheduleCfg { time: string; enabled: boolean }

export default function Backups({ backups, stats, schedule }: { backups: Backup[]; stats: Stats; schedule: ScheduleCfg }) {
    const { post, processing } = useForm();
    const [confirming, setConfirming] = useState<string | null>(null);

    const sched = useForm({ time: schedule.time, enabled: schedule.enabled });
    const saveSchedule = (e: React.FormEvent) => { e.preventDefault(); sched.put('/settings/backups/schedule', { preserveScroll: true }); };

    const run = () => post('/settings/backups/run', { preserveScroll: true });
    const del = (name: string) => router.delete(`/settings/backups/${name}`, { preserveScroll: true, onFinish: () => setConfirming(null) });

    const tiles = [
        { label: 'Backups', value: String(stats.count), icon: DatabaseBackup, tint: 'bg-brand-50 text-brand-600' },
        { label: 'Total size', value: stats.total, icon: HardDrive, tint: 'bg-slate-100 text-slate-500' },
        { label: 'Last backup', value: stats.last ?? 'Never', icon: Clock, tint: 'bg-sky-50 text-sky-600' },
        { label: 'Schedule', value: stats.schedule, icon: RefreshCw, tint: 'bg-emerald-50 text-emerald-600' },
    ];

    return (
        <AppShell title="Backups">
            <Head title="Backups" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-wrap items-stretch gap-2.5">
                    {tiles.map((t) => (
                        <span key={t.label} className="inline-flex items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <span className={`flex h-8 w-8 items-center justify-center rounded-lg ${t.tint}`}><t.icon className="h-4 w-4" /></span>
                            <span className="flex flex-col">
                                <span className="text-sm font-semibold leading-tight text-slate-800">{t.value}</span>
                                <span className="text-[11px] font-medium text-slate-400">{t.label}</span>
                            </span>
                        </span>
                    ))}
                </div>
                <button onClick={run} disabled={processing} className="btn-primary">
                    <DatabaseBackup className="h-4 w-4" /> {processing ? 'Backing up…' : 'Back up now'}
                </button>
            </div>

            {/* Encryption status */}
            {stats.encrypted ? (
                <div className="mb-4 flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-2.5 text-sm text-emerald-800">
                    <Lock className="h-4 w-4 shrink-0" /> Backups are <b>encrypted</b> (AES-256). Includes the database, uploaded files and config.
                </div>
            ) : (
                <div className="mb-4 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-2.5 text-sm text-amber-800">
                    <ShieldAlert className="mt-0.5 h-4 w-4 shrink-0" />
                    <span>Backups are <b>not encrypted</b> — they contain personal data. Set <code className="rounded bg-amber-100 px-1">BACKUP_PASSWORD</code> in the server <code className="rounded bg-amber-100 px-1">.env</code> to enable AES-256 encryption.</span>
                </div>
            )}

            {/* Schedule editor */}
            <form onSubmit={saveSchedule} className="mb-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <header className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><CalendarClock className="h-4 w-4" /></span>
                    <div>
                        <h3 className="text-sm font-semibold text-slate-800">Automatic backup schedule</h3>
                        <p className="text-xs text-slate-500">When the daily backup runs (server time).</p>
                    </div>
                </header>
                <div className="flex flex-wrap items-end gap-4 px-5 py-4">
                    <label className="block">
                        <span className="mb-1 block text-xs font-medium text-slate-600">Daily at</span>
                        <input type="time" className="input w-36" value={sched.data.time} onChange={(e) => sched.setData('time', e.target.value)} disabled={!sched.data.enabled} />
                    </label>
                    <label className="mb-2 inline-flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" className="rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={sched.data.enabled} onChange={(e) => sched.setData('enabled', e.target.checked)} />
                        Enable daily backup
                    </label>
                    <div className="ml-auto flex items-center gap-3">
                        {sched.recentlySuccessful && <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>}
                        <button type="submit" disabled={sched.processing} className="btn-primary">Save schedule</button>
                    </div>
                </div>
            </form>

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th className="px-4 py-3">Backup</th>
                            <th className="px-4 py-3">Created</th>
                            <th className="px-4 py-3">Size</th>
                            <th className="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {backups.map((b) => (
                            <tr key={b.name} className="hover:bg-slate-50">
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-2">
                                        {b.encrypted ? <Lock className="h-3.5 w-3.5 text-emerald-500" /> : <Unlock className="h-3.5 w-3.5 text-amber-500" />}
                                        <span className="font-mono text-xs text-slate-600">{b.name}</span>
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-slate-600">{b.created}</td>
                                <td className="px-4 py-3 text-slate-500">{b.size}</td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-1">
                                        <a href={`/settings/backups/${b.name}/download`} className="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-brand-600" title="Download">
                                            <Download className="h-4 w-4" />
                                        </a>
                                        {confirming === b.name ? (
                                            <span className="inline-flex items-center gap-1">
                                                <button onClick={() => del(b.name)} className="rounded-md bg-rose-600 px-2 py-1 text-xs font-medium text-white hover:bg-rose-700">Delete</button>
                                                <button onClick={() => setConfirming(null)} className="rounded-md px-2 py-1 text-xs text-slate-500 hover:bg-slate-100">Cancel</button>
                                            </span>
                                        ) : (
                                            <button onClick={() => setConfirming(b.name)} className="rounded-md p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="Delete">
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {backups.length === 0 && (
                            <tr><td colSpan={4} className="px-4 py-12 text-center text-sm text-slate-400">
                                <DatabaseBackup className="mx-auto mb-2 h-7 w-7 text-slate-300" />
                                No backups yet. Click “Back up now” or wait for the daily run.
                            </td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            <p className="mt-3 text-xs text-slate-400">
                Backups are kept on the server (14 daily + 8 weekly, older ones pruned automatically) and include the database, all uploaded files and the configuration.
            </p>
        </AppShell>
    );
}
