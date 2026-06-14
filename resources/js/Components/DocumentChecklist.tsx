import { useState } from 'react';
import { router } from '@inertiajs/react';
import { FileText, CheckCircle2, Clock, MinusCircle, Save } from 'lucide-react';

interface DocItem {
    key: number;
    label: string;
    copies: number;
    status: string;
    note: string;
}

const STATUSES = ['Pending', 'Submitted', 'Not applicable'] as const;

const STATUS_STYLE: Record<string, string> = {
    Pending: 'bg-slate-100 text-slate-600',
    Submitted: 'bg-emerald-100 text-emerald-700',
    'Not applicable': 'bg-amber-100 text-amber-700',
};

function StatusIcon({ status }: { status: string }) {
    if (status === 'Submitted') return <CheckCircle2 className="h-3 w-3" />;
    if (status === 'Not applicable') return <MinusCircle className="h-3 w-3" />;
    return <Clock className="h-3 w-3" />;
}

export default function DocumentChecklist({
    applicantId, documents, canVerify,
}: {
    applicantId: number;
    documents: DocItem[];
    canVerify: boolean;
}) {
    const settled = documents.filter((d) => d.status === 'Submitted' || d.status === 'Not applicable').length;

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-1 flex items-center justify-between">
                <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">
                    Documentary requirements
                </h3>
                <span className="text-xs text-slate-400">{settled}/{documents.length} noted</span>
            </div>
            <p className="mb-4 text-xs text-slate-400">
                Note what the applicant presented for each requirement. No files are uploaded —
                mark <b>Not applicable</b> if they can't provide that document.
            </p>

            <div className="divide-y divide-slate-100">
                {documents.map((d) => (
                    <DocRow key={d.key} applicantId={applicantId} item={d} canVerify={canVerify} />
                ))}
            </div>
        </div>
    );
}

function DocRow({ applicantId, item, canVerify }: { applicantId: number; item: DocItem; canVerify: boolean }) {
    const [status, setStatus] = useState(item.status);
    const [note, setNote] = useState(item.note ?? '');
    const [busy, setBusy] = useState(false);

    const dirty = status !== item.status || (note ?? '') !== (item.note ?? '');

    const save = () => {
        setBusy(true);
        router.post(`/applicants/${applicantId}/documents`,
            { requirement_key: item.key, status, note },
            { preserveScroll: true, onFinish: () => setBusy(false) });
    };

    return (
        <div className="py-3">
            <div className="flex flex-wrap items-center gap-2">
                <FileText className="h-5 w-5 shrink-0 text-slate-400" />
                <span className="text-sm font-medium text-slate-800">{item.label}</span>
                {item.copies > 1 && (
                    <span className="rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-medium text-brand-700">{item.copies} pcs</span>
                )}
                {!canVerify && (
                    <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLE[item.status] ?? STATUS_STYLE.Pending}`}>
                        <StatusIcon status={item.status} /> {item.status}
                    </span>
                )}
            </div>

            {canVerify ? (
                <div className="mt-2 flex flex-col gap-2 pl-7 sm:flex-row sm:items-start">
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="input w-full sm:w-44"
                    >
                        {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <input
                        type="text"
                        value={note}
                        onChange={(e) => setNote(e.target.value)}
                        placeholder="Note (e.g. photocopy only, will bring original next week)…"
                        maxLength={1000}
                        className="input flex-1"
                        onKeyDown={(e) => { if (e.key === 'Enter' && dirty) save(); }}
                    />
                    <button
                        onClick={save}
                        disabled={!dirty || busy}
                        className="btn-primary shrink-0 disabled:opacity-40"
                    >
                        <Save className="h-3.5 w-3.5" /> {busy ? 'Saving…' : 'Save'}
                    </button>
                </div>
            ) : (
                item.note ? <p className="mt-1 pl-7 text-xs text-slate-500">{item.note}</p> : null
            )}
        </div>
    );
}
