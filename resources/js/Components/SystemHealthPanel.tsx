import { useCallback, useEffect, useState } from 'react';
import {
    Activity, Cpu, MemoryStick, HardDrive, RefreshCw, CheckCircle2, AlertTriangle,
    XCircle, Lightbulb, Server, ArrowUpCircle,
} from 'lucide-react';

interface SystemInfo { app: string; env: string; php: string; laravel: string }

interface Cpu { model: string | null; cores: number | null; load_pct: number | null }
interface Ram { total_gb: number; free_gb: number; used_pct: number }
interface Disk { total_gb: number | null; free_gb: number | null; used_pct: number | null; media: string }
interface Check { key: string; label: string; status: 'ok' | 'warn' | 'fail'; detail: string }
interface Advice { component: string; severity: 'ok' | 'consider' | 'recommended'; title: string; detail: string }

interface Report {
    overall: 'healthy' | 'attention' | 'critical';
    resources: { os: string; host: string; cpu: Cpu; ram: Ram | null; disk: Disk };
    checks: Check[];
    advice: Advice[];
    priority: string;
}

const meterColor = (pct: number | null) =>
    pct === null ? 'bg-slate-300'
        : pct >= 85 ? 'bg-rose-500'
            : pct >= 70 ? 'bg-amber-500'
                : 'bg-emerald-500';

const OVERALL: Record<Report['overall'], { label: string; cls: string }> = {
    healthy: { label: 'Healthy', cls: 'bg-emerald-50 text-emerald-700 ring-emerald-200/70' },
    attention: { label: 'Needs attention', cls: 'bg-amber-50 text-amber-700 ring-amber-200/70' },
    critical: { label: 'Action needed', cls: 'bg-rose-50 text-rose-700 ring-rose-200/70' },
};

export default function SystemHealthPanel({ system }: { system: SystemInfo }) {
    const [data, setData] = useState<Report | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);

    const load = useCallback(() => {
        setLoading(true);
        setError(false);
        fetch('/settings/health', { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then((r) => (r.ok ? r.json() : Promise.reject()))
            .then((j: Report) => setData(j))
            .catch(() => setError(true))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => { load(); }, [load]);

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4 flex flex-wrap items-center gap-3">
                <Server className="h-4 w-4 text-slate-400" />
                <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-400">System health</h3>
                {data && (
                    <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ${OVERALL[data.overall].cls}`}>
                        <Activity className="h-3 w-3" /> {OVERALL[data.overall].label}
                    </span>
                )}
                <button
                    onClick={load}
                    disabled={loading}
                    className="ml-auto inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 disabled:opacity-50"
                >
                    <RefreshCw className={`h-3.5 w-3.5 ${loading ? 'animate-spin' : ''}`} /> {loading ? 'Checking…' : 'Re-check'}
                </button>
            </div>

            {loading && !data && <SkeletonMeters />}

            {error && (
                <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    Couldn't read system health. Try Re-check.
                </div>
            )}

            {data && (
                <>
                    {/* Resource meters */}
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <Meter
                            icon={Cpu}
                            title="Processor"
                            pct={data.resources.cpu.load_pct}
                            pctLabel={data.resources.cpu.load_pct === null ? 'load n/a' : `${data.resources.cpu.load_pct}% load`}
                            sub={`${data.resources.cpu.cores ?? '—'} cores`}
                            foot={data.resources.cpu.model ?? 'CPU'}
                        />
                        <Meter
                            icon={MemoryStick}
                            title="Memory"
                            pct={data.resources.ram?.used_pct ?? null}
                            pctLabel={data.resources.ram ? `${data.resources.ram.used_pct}% used` : 'unknown'}
                            sub={data.resources.ram ? `${data.resources.ram.total_gb} GB total` : '—'}
                            foot={data.resources.ram ? `${data.resources.ram.free_gb} GB free` : 'RAM size unknown'}
                        />
                        <Meter
                            icon={HardDrive}
                            title="Storage"
                            pct={data.resources.disk.used_pct}
                            pctLabel={data.resources.disk.used_pct === null ? 'unknown' : `${data.resources.disk.used_pct}% used`}
                            sub={data.resources.disk.total_gb ? `${data.resources.disk.total_gb} GB total` : '—'}
                            foot={`${data.resources.disk.free_gb ?? '—'} GB free · ${mediaLabel(data.resources.disk.media)}`}
                        />
                    </div>

                    {/* Checks */}
                    <div className="mt-4 flex flex-wrap gap-2">
                        {data.checks.map((c) => <CheckChip key={c.key} check={c} />)}
                    </div>

                    {/* Hardware advice */}
                    <div className="mt-5">
                        <div className="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            <ArrowUpCircle className="h-3.5 w-3.5" /> Upgrade advice
                        </div>
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                            {[...data.advice]
                                .sort((a, b) => rank(b.severity) - rank(a.severity))
                                .map((a) => <AdviceCard key={a.component} advice={a} />)}
                        </div>
                        <p className="mt-3 flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                            <Lightbulb className="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500" /> {data.priority}
                        </p>
                    </div>

                    {/* App info */}
                    <div className="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 border-t border-slate-100 pt-4 text-sm sm:grid-cols-4">
                        <Info label="Application" value={system.app} />
                        <Info label="Environment" value={system.env} />
                        <Info label="PHP" value={`v${system.php}`} />
                        <Info label="Laravel" value={`v${system.laravel}`} />
                        <Info label="Host" value={data.resources.host} />
                        <Info label="Operating system" value={data.resources.os} wide />
                    </div>
                </>
            )}
        </div>
    );
}

const rank = (s: Advice['severity']) => (s === 'recommended' ? 2 : s === 'consider' ? 1 : 0);
const mediaLabel = (m: string) => (m === 'SSD' ? 'SSD' : m === 'HDD' ? 'Hard drive (HDD)' : 'drive type unknown');

function Meter({ icon: Icon, title, pct, pctLabel, sub, foot }: {
    icon: React.ElementType; title: string; pct: number | null; pctLabel: string; sub: string; foot: string;
}) {
    return (
        <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
            <div className="mb-2 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <Icon className="h-4 w-4 text-slate-400" /> {title}
                </div>
                <span className="text-xs font-medium text-slate-500">{pctLabel}</span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-slate-200">
                <div className={`h-full rounded-full transition-all duration-500 ${meterColor(pct)}`} style={{ width: `${pct ?? 4}%` }} />
            </div>
            <div className="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                <span>{sub}</span>
            </div>
            <div className="mt-0.5 truncate text-[11px] text-slate-400" title={foot}>{foot}</div>
        </div>
    );
}

function CheckChip({ check }: { check: Check }) {
    const map = {
        ok: { Icon: CheckCircle2, cls: 'bg-emerald-50 text-emerald-700 ring-emerald-200/70' },
        warn: { Icon: AlertTriangle, cls: 'bg-amber-50 text-amber-700 ring-amber-200/70' },
        fail: { Icon: XCircle, cls: 'bg-rose-50 text-rose-700 ring-rose-200/70' },
    }[check.status];
    const { Icon } = map;
    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ${map.cls}`}>
            <Icon className="h-3.5 w-3.5" /> {check.label}
            <span className="font-normal opacity-70">· {check.detail}</span>
        </span>
    );
}

function AdviceCard({ advice }: { advice: Advice }) {
    const map = {
        recommended: { Icon: AlertTriangle, ring: 'border-amber-200 bg-amber-50/60', chip: 'bg-amber-100 text-amber-800', tone: 'text-amber-900', label: 'Recommended' },
        consider: { Icon: Lightbulb, ring: 'border-sky-200 bg-sky-50/60', chip: 'bg-sky-100 text-sky-800', tone: 'text-sky-900', label: 'Consider' },
        ok: { Icon: CheckCircle2, ring: 'border-emerald-200 bg-emerald-50/50', chip: 'bg-emerald-100 text-emerald-800', tone: 'text-emerald-900', label: 'Good' },
    }[advice.severity];
    const { Icon } = map;
    return (
        <div className={`flex flex-col rounded-xl border p-4 ${map.ring}`}>
            <div className="mb-1.5 flex items-center justify-between gap-2">
                <span className="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                    <Icon className="h-4 w-4" /> {advice.component}
                </span>
                <span className={`rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${map.chip}`}>{map.label}</span>
            </div>
            <div className={`text-sm font-semibold ${map.tone}`}>{advice.title}</div>
            <p className="mt-1 text-xs leading-relaxed text-slate-600">{advice.detail}</p>
        </div>
    );
}

function SkeletonMeters() {
    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
            {[0, 1, 2].map((i) => (
                <div key={i} className="animate-pulse rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div className="mb-3 h-4 w-24 rounded bg-slate-200" />
                    <div className="h-2 rounded-full bg-slate-200" />
                    <div className="mt-3 h-3 w-20 rounded bg-slate-200" />
                </div>
            ))}
        </div>
    );
}

function Info({ label, value, wide }: { label: string; value: string; wide?: boolean }) {
    return (
        <div className={wide ? 'col-span-2' : ''}>
            <div className="text-xs text-slate-400">{label}</div>
            <div className="mt-0.5 truncate font-medium capitalize text-slate-700" title={value}>{value}</div>
        </div>
    );
}
