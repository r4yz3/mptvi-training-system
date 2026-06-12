import { useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
    FileText, UploadCloud, CheckCircle2, XCircle, Download, Trash2, Square, CheckSquare, Clock,
} from 'lucide-react';

interface DocFile { id: number; name: string; size: number }
interface DocItem {
    key: number;
    label: string;
    physical: boolean;
    copies: number;
    status: string;
    reject_reason: string | null;
    document_id: number | null;
    files: DocFile[];
}

const STATUS_STYLE: Record<string, string> = {
    Pending: 'bg-slate-100 text-slate-600',
    Submitted: 'bg-amber-100 text-amber-700',
    Verified: 'bg-emerald-100 text-emerald-700',
    Rejected: 'bg-rose-100 text-rose-700',
};

function fmtSize(b: number) {
    if (b < 1024) return `${b} B`;
    if (b < 1024 * 1024) return `${(b / 1024).toFixed(0)} KB`;
    return `${(b / 1024 / 1024).toFixed(1)} MB`;
}

export default function DocumentChecklist({
    applicantId, documents, canVerify,
}: {
    applicantId: number;
    documents: DocItem[];
    canVerify: boolean;
}) {
    const [rejecting, setRejecting] = useState<DocItem | null>(null);
    const verifiedCount = documents.filter((d) => d.status === 'Verified').length;

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4 flex items-center justify-between">
                <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">
                    Documentary requirements
                </h3>
                <span className="text-xs text-slate-400">{verifiedCount}/{documents.length} verified</span>
            </div>

            <div className="divide-y divide-slate-100">
                {documents.map((d) => (
                    <div key={d.key} className="flex flex-col gap-2 py-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="flex items-start gap-3">
                            <FileText className="mt-0.5 h-5 w-5 shrink-0 text-slate-400" />
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-sm font-medium text-slate-800">{d.label}</span>
                                    {d.copies > 1 && <span className="rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-medium text-brand-700">{d.copies} pcs</span>}
                                    <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLE[d.status]}`}>
                                        {d.status === 'Verified' && <CheckCircle2 className="h-3 w-3" />}
                                        {d.status === 'Pending' && <Clock className="h-3 w-3" />}
                                        {d.status}
                                    </span>
                                    {d.physical && <span className="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">Physical</span>}
                                    {!d.physical && d.copies > 1 && (
                                        <span className={`text-[10px] font-medium ${d.files.length >= d.copies ? 'text-emerald-600' : 'text-amber-600'}`}>
                                            {d.files.length}/{d.copies} uploaded
                                        </span>
                                    )}
                                </div>
                                {d.reject_reason && <div className="mt-0.5 text-xs text-rose-500">Reason: {d.reject_reason}</div>}
                                {d.files.length > 0 && (
                                    <div className="mt-1.5 space-y-1">
                                        {d.files.map((f) => (
                                            <div key={f.id} className="flex items-center gap-2 text-xs text-slate-500">
                                                <a href={`/document-files/${f.id}/download`} className="inline-flex items-center gap-1 text-brand-600 hover:underline">
                                                    <Download className="h-3 w-3" /> {f.name}
                                                </a>
                                                <span className="text-slate-400">({fmtSize(f.size)})</span>
                                                {canVerify && (
                                                    <button
                                                        onClick={() => router.delete(`/document-files/${f.id}`, { preserveScroll: true })}
                                                        className="text-slate-300 hover:text-rose-500"
                                                        title="Remove file"
                                                    >
                                                        <Trash2 className="h-3 w-3" />
                                                    </button>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {canVerify && (
                            <div className="flex shrink-0 items-center gap-2">
                                {d.physical ? (
                                    <PhysicalToggle applicantId={applicantId} item={d} />
                                ) : (
                                    <>
                                        <UploadButton applicantId={applicantId} requirementKey={d.key} copies={d.copies} />
                                        {d.document_id && d.status !== 'Verified' && (
                                            <button
                                                onClick={() => router.put(`/documents/${d.document_id}/verify`, {}, { preserveScroll: true })}
                                                className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                                            >
                                                <CheckCircle2 className="h-3.5 w-3.5" /> Verify
                                            </button>
                                        )}
                                        {d.document_id && d.status !== 'Rejected' && d.files.length > 0 && (
                                            <button
                                                onClick={() => setRejecting(d)}
                                                className="inline-flex items-center gap-1 rounded-md border border-rose-200 px-2.5 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50"
                                            >
                                                <XCircle className="h-3.5 w-3.5" /> Reject
                                            </button>
                                        )}
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {rejecting && <RejectModal item={rejecting} onClose={() => setRejecting(null)} />}
        </div>
    );
}

function UploadButton({ applicantId, requirementKey, copies }: { applicantId: number; requirementKey: number; copies: number }) {
    const ref = useRef<HTMLInputElement>(null);
    const [busy, setBusy] = useState(false);

    const onPick = (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(e.target.files ?? []);
        if (!files.length) return;
        setBusy(true);
        router.post(`/applicants/${applicantId}/documents`,
            { requirement_key: requirementKey, files },
            { forceFormData: true, preserveScroll: true, onFinish: () => { setBusy(false); if (ref.current) ref.current.value = ''; } });
    };

    return (
        <>
            <button onClick={() => ref.current?.click()} disabled={busy} className="btn-ghost px-2.5 py-1.5 text-xs">
                <UploadCloud className="h-3.5 w-3.5" /> {busy ? 'Uploading…' : copies > 1 ? `Upload (${copies} pcs)` : 'Upload'}
            </button>
            <input ref={ref} type="file" accept="image/*,application/pdf" multiple className="hidden" onChange={onPick} />
        </>
    );
}

function PhysicalToggle({ applicantId, item }: { applicantId: number; item: DocItem }) {
    const received = item.status === 'Verified';
    const toggle = () =>
        router.post(`/applicants/${applicantId}/documents/physical`,
            { requirement_key: item.key }, { preserveScroll: true });
    return (
        <button onClick={toggle} className={`inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium ${received ? 'bg-emerald-50 text-emerald-700' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'}`}>
            {received ? <CheckSquare className="h-4 w-4" /> : <Square className="h-4 w-4" />}
            {received ? 'Received' : 'Mark received'}
        </button>
    );
}

function RejectModal({ item, onClose }: { item: DocItem; onClose: () => void }) {
    const { data, setData, put, processing, errors } = useForm({ reason: '' });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/documents/${item.document_id}/reject`, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md rounded-xl bg-white shadow-xl">
                <div className="border-b border-slate-200 px-5 py-4">
                    <h3 className="text-base font-semibold text-slate-800">Reject document</h3>
                    <p className="mt-0.5 text-xs text-slate-500">{item.label}</p>
                </div>
                <form onSubmit={submit} className="space-y-4 px-5 py-4">
                    <textarea className="input" rows={3} value={data.reason} onChange={(e) => setData('reason', e.target.value)} placeholder="Reason for rejection" autoFocus />
                    {errors.reason && <span className="block text-xs text-rose-600">{errors.reason}</span>}
                    <div className="flex justify-end gap-2">
                        <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                        <button type="submit" disabled={processing} className="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    );
}
