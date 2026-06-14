import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Download, FileSpreadsheet, FileText, Check, X, Clock, Ban, CheckCircle2, ShieldCheck, Hourglass,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Item {
    id: number; type: string; label: string; format: string; summary: string;
    status: 'pending' | 'approved' | 'rejected' | 'downloaded';
    reason: string | null; requester: string | null; reviewer: string | null;
    downloadable: boolean; expired: boolean; requested_at: string; requested_at_full: string;
}

const STATUS: Record<Item['status'], { label: string; cls: string; Icon: typeof Clock }> = {
    pending: { label: 'Awaiting approval', cls: 'bg-amber-50 text-amber-700', Icon: Hourglass },
    approved: { label: 'Approved', cls: 'bg-emerald-50 text-emerald-700', Icon: CheckCircle2 },
    downloaded: { label: 'Downloaded', cls: 'bg-slate-100 text-slate-600', Icon: Check },
    rejected: { label: 'Rejected', cls: 'bg-rose-50 text-rose-700', Icon: Ban },
};

function startDownload(item: Item) {
    const url = `/downloads/${item.id}/file`;
    if (item.format === 'pdf') window.open(url, '_blank', 'noopener');
    else window.location.href = url;
}

export default function DownloadsIndex({ canApprove, mine, pending, reviewed }: {
    canApprove: boolean; mine: Item[]; pending: Item[]; reviewed: Item[];
}) {
    return (
        <AppShell title="Downloads">
            <Head title="Downloads" />

            <div className="mb-6 flex items-start gap-3.5">
                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-sm">
                    <Download className="h-6 w-6" />
                </div>
                <div>
                    <h2 className="text-xl font-semibold text-slate-800">Downloads</h2>
                    <p className="mt-0.5 max-w-xl text-sm text-slate-500">
                        {canApprove
                            ? 'Review export requests from staff, then approve or reject them. Approved files are generated fresh and expire after 24 hours.'
                            : 'Reports and exports you’ve requested. An administrator approves each one before you can download it.'}
                    </p>
                </div>
            </div>

            {/* Admin: pending approvals */}
            {canApprove && (
                <Section title="Pending approval" count={pending.length} accent>
                    {pending.length === 0
                        ? <Empty>No requests waiting for approval.</Empty>
                        : pending.map((it) => <ApprovalRow key={it.id} item={it} />)}
                </Section>
            )}

            {/* Everyone: my requests / downloads */}
            <Section title={canApprove ? 'My downloads' : 'My export requests'} count={mine.length}>
                {mine.length === 0
                    ? <Empty>You haven’t requested any exports yet. Use the “Report / Export” button on Cashier, Applicants or Reports.</Empty>
                    : mine.map((it) => <MineRow key={it.id} item={it} />)}
            </Section>

            {/* Admin: reviewed history */}
            {canApprove && reviewed.length > 0 && (
                <Section title="Recently reviewed" count={reviewed.length}>
                    {reviewed.map((it) => <HistoryRow key={it.id} item={it} />)}
                </Section>
            )}
        </AppShell>
    );
}

function ApprovalRow({ item }: { item: Item }) {
    const [rejecting, setRejecting] = useState(false);
    const [reason, setReason] = useState('');

    const approve = () => router.put(`/downloads/${item.id}/approve`, {}, { preserveScroll: true });
    const reject = () => router.put(`/downloads/${item.id}/reject`, { reason }, { preserveScroll: true, onSuccess: () => setRejecting(false) });

    return (
        <div className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center">
            <FormatIcon format={item.format} />
            <div className="min-w-0 flex-1">
                <div className="text-sm font-medium text-slate-800">{item.label}</div>
                <div className="text-xs text-slate-500">{item.summary}</div>
                <div className="mt-0.5 text-xs text-slate-400">
                    Requested by <span className="font-medium text-slate-600">{item.requester}</span> · {item.requested_at}
                </div>
            </div>
            {!rejecting ? (
                <div className="flex shrink-0 gap-2">
                    <button onClick={approve} className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                        <Check className="h-3.5 w-3.5" /> Approve
                    </button>
                    <button onClick={() => setRejecting(true)} className="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">
                        <X className="h-3.5 w-3.5" /> Reject
                    </button>
                </div>
            ) : (
                <div className="flex shrink-0 items-center gap-2">
                    <input autoFocus value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Reason…" className="input w-44 text-sm" onKeyDown={(e) => { if (e.key === 'Enter' && reason) reject(); }} />
                    <button onClick={reject} disabled={!reason} className="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700 disabled:opacity-40">Reject</button>
                    <button onClick={() => setRejecting(false)} className="rounded-lg px-2 py-1.5 text-xs text-slate-500 hover:bg-slate-100">Cancel</button>
                </div>
            )}
        </div>
    );
}

function MineRow({ item }: { item: Item }) {
    const s = STATUS[item.status];
    const SIcon = s.Icon;
    return (
        <div className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center">
            <FormatIcon format={item.format} />
            <div className="min-w-0 flex-1">
                <div className="text-sm font-medium text-slate-800">{item.label}</div>
                <div className="text-xs text-slate-500">{item.summary}</div>
                {item.status === 'rejected' && item.reason && (
                    <div className="mt-0.5 text-xs text-rose-600">Rejected: {item.reason}</div>
                )}
                {item.expired && item.status !== 'rejected' && (
                    <div className="mt-0.5 text-xs text-slate-400">Link expired — request again.</div>
                )}
            </div>
            <span className={`inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${s.cls}`}>
                <SIcon className="h-3.5 w-3.5" /> {s.label}
            </span>
            {item.downloadable && (
                <button onClick={() => startDownload(item)} className="btn-primary shrink-0 text-xs">
                    <Download className="h-3.5 w-3.5" /> Download
                </button>
            )}
        </div>
    );
}

function HistoryRow({ item }: { item: Item }) {
    const s = STATUS[item.status];
    const SIcon = s.Icon;
    return (
        <div className="flex items-center gap-3 px-4 py-2.5 text-sm">
            <FormatIcon format={item.format} small />
            <div className="min-w-0 flex-1">
                <span className="font-medium text-slate-700">{item.label}</span>
                <span className="text-slate-400"> · {item.requester}</span>
            </div>
            {item.reviewer && <span className="hidden text-xs text-slate-400 sm:inline">by {item.reviewer}</span>}
            <span className={`inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${s.cls}`}>
                <SIcon className="h-3 w-3" /> {s.label}
            </span>
        </div>
    );
}

function FormatIcon({ format, small }: { format: string; small?: boolean }) {
    const Icon = format === 'pdf' ? FileText : FileSpreadsheet;
    const size = small ? 'h-4 w-4' : 'h-5 w-5';
    return (
        <div className={`flex shrink-0 items-center justify-center rounded-lg ${small ? 'h-7 w-7' : 'h-9 w-9'} ${format === 'pdf' ? 'bg-rose-50 text-rose-500' : 'bg-emerald-50 text-emerald-600'}`}>
            <Icon className={size} />
        </div>
    );
}

function Section({ title, count, accent, children }: { title: string; count: number; accent?: boolean; children: React.ReactNode }) {
    return (
        <div className="mb-6">
            <div className="mb-2.5 flex items-center gap-2">
                <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-400">{title}</h3>
                {count > 0 && (
                    <span className={`rounded-full px-1.5 py-0.5 text-[10px] font-semibold ${accent ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'}`}>{count}</span>
                )}
            </div>
            <div className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm">
                {children}
            </div>
        </div>
    );
}

function Empty({ children }: { children: React.ReactNode }) {
    return <div className="flex items-center gap-2 px-4 py-6 text-sm text-slate-400"><ShieldCheck className="h-4 w-4" /> {children}</div>;
}
