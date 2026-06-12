import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ListChecks, Plus, Trash2, GripVertical, CheckCircle2, FileText, Package } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Req { key: number; label: string; physical: boolean; enabled: boolean }

export default function Requirements({ requirements }: { requirements: Req[] }) {
    const [rows, setRows] = useState<Req[]>(requirements);
    const [saved, setSaved] = useState(false);
    const [processing, setProcessing] = useState(false);

    const update = (i: number, patch: Partial<Req>) => setRows(rows.map((r, idx) => (idx === i ? { ...r, ...patch } : r)));
    const remove = (i: number) => setRows(rows.filter((_, idx) => idx !== i));
    const add = () => setRows([...rows, { key: -1, label: '', physical: false, enabled: true }]);

    const save = () => {
        setProcessing(true);
        router.put('/settings/requirements', { requirements: rows.filter((r) => r.label.trim()) } as any, {
            preserveScroll: true,
            onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 2500); },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AppShell title="Documentary requirements">
            <Head title="Requirements" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <div className="max-w-2xl">
                <div className="mb-3 flex items-start gap-2.5 rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3 text-sm text-brand-800">
                    <ListChecks className="mt-0.5 h-4 w-4 shrink-0" />
                    <p>These are the documents applicants must submit. <b>File</b> items are uploaded &amp; verified; <b>Supply</b> items are physical (checked off as received). Changes apply to screening and the applicant document checklist.</p>
                </div>

                <div className="space-y-2">
                    {rows.map((r, i) => (
                        <div key={i} className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                            <GripVertical className="h-4 w-4 shrink-0 text-slate-300" />
                            <input
                                className="input flex-1"
                                placeholder="Requirement label…"
                                value={r.label}
                                onChange={(e) => update(i, { label: e.target.value })}
                            />
                            <button
                                type="button"
                                onClick={() => update(i, { physical: !r.physical })}
                                className={`inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-1.5 text-xs font-medium ${r.physical ? 'bg-amber-50 text-amber-700' : 'bg-sky-50 text-sky-700'}`}
                                title="Toggle file vs supply"
                            >
                                {r.physical ? <Package className="h-3.5 w-3.5" /> : <FileText className="h-3.5 w-3.5" />}
                                {r.physical ? 'Supply' : 'File'}
                            </button>
                            <label className="inline-flex shrink-0 cursor-pointer items-center gap-1 text-xs text-slate-500">
                                <input type="checkbox" className="rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={r.enabled} onChange={(e) => update(i, { enabled: e.target.checked })} />
                                On
                            </label>
                            <button type="button" onClick={() => remove(i)} className="shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-4 w-4" /></button>
                        </div>
                    ))}
                </div>

                <button type="button" onClick={add} className="btn-ghost mt-3"><Plus className="h-4 w-4" /> Add requirement</button>

                <div className="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                    <button onClick={save} disabled={processing} className="btn-primary">Save requirements</button>
                    {saved && <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>}
                </div>
            </div>
        </AppShell>
    );
}
