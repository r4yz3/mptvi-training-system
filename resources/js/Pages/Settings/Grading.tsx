import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Award, Plus, Trash2, CheckCircle2 } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Component { key: string; label: string; weight: number }

export default function Grading({ components, passing }: { components: Component[]; passing: number }) {
    const [rows, setRows] = useState<Component[]>(components);
    const [pass, setPass] = useState<number>(passing);
    const [saved, setSaved] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const update = (i: number, patch: Partial<Component>) => setRows(rows.map((r, idx) => (idx === i ? { ...r, ...patch } : r)));
    const remove = (i: number) => setRows(rows.filter((_, idx) => idx !== i));
    const add = () => setRows([...rows, { key: '', label: '', weight: 0 }]);

    const total = rows.reduce((s, r) => s + (Number(r.weight) || 0), 0);

    const save = () => {
        setProcessing(true);
        setError(null);
        router.put('/settings/grading', { components: rows.filter((r) => r.label.trim()), passing: pass } as any, {
            preserveScroll: true,
            onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 2500); },
            onError: (errs) => setError(Object.values(errs)[0] as string),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AppShell title="Grading system">
            <Head title="Grading system" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <div className="max-w-2xl">
                <div className="mb-3 flex items-start gap-2.5 rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3 text-sm text-brand-800">
                    <Award className="mt-0.5 h-4 w-4 shrink-0" />
                    <p>Trainees are scored 0–100 on each component below (Training → batch roster → Grades). The final grade is the <b>weighted average</b>, so weights must add up to 100%. Renaming a component keeps the scores already recorded under it; removing one hides its scores from the computation.</p>
                </div>

                <h3 className="mb-2 mt-5 text-sm font-semibold text-slate-700">Components &amp; weights</h3>
                <div className="space-y-2">
                    {rows.map((r, i) => (
                        <div key={i} className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                            <input
                                className="input flex-1"
                                placeholder="Component, e.g. Written exam…"
                                value={r.label}
                                onChange={(e) => update(i, { label: e.target.value })}
                            />
                            <label className="flex shrink-0 items-center gap-1 text-xs text-slate-500">
                                <input
                                    type="number" min={1} max={100}
                                    className="input w-16 text-center"
                                    value={r.weight || ''}
                                    onChange={(e) => update(i, { weight: Number(e.target.value) || 0 })}
                                />
                                %
                            </label>
                            <button type="button" onClick={() => remove(i)} className="shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-4 w-4" /></button>
                        </div>
                    ))}
                </div>
                <div className="mt-2 flex items-center justify-between">
                    <button type="button" onClick={add} className="btn-ghost"><Plus className="h-4 w-4" /> Add component</button>
                    <span className={`rounded-full px-3 py-1 text-sm font-semibold ${total === 100 ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'}`}>
                        Total {total}%{total !== 100 && ' — must be 100%'}
                    </span>
                </div>

                <h3 className="mb-2 mt-6 text-sm font-semibold text-slate-700">Passing grade</h3>
                <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <input
                        type="number" min={1} max={100}
                        className="input w-24 text-center"
                        value={pass || ''}
                        onChange={(e) => setPass(Number(e.target.value) || 0)}
                    />
                    <span className="text-sm text-slate-500">Final grades at or above this are marked <b className="text-emerald-600">Passed</b>; below it, <b className="text-rose-600">Failed</b>.</span>
                </div>

                {error && <p className="mt-3 text-sm text-rose-600">{error}</p>}

                <div className="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                    <button onClick={save} disabled={processing || total !== 100} className="btn-primary disabled:opacity-50">Save grading system</button>
                    {saved && <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>}
                </div>
            </div>
        </AppShell>
    );
}
