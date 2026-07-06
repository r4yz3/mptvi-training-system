import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, GraduationCap, Plus, Trash2, ChevronUp, ChevronDown, CheckCircle2 } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Level { key: string; label: string }

export default function Education({ levels, statuses }: { levels: Level[]; statuses: string[] }) {
    const [rows, setRows] = useState<Level[]>(levels);
    const [stats, setStats] = useState<string[]>(statuses);
    const [saved, setSaved] = useState(false);
    const [processing, setProcessing] = useState(false);

    const update = (i: number, label: string) => setRows(rows.map((r, idx) => (idx === i ? { ...r, label } : r)));
    const remove = (i: number) => setRows(rows.filter((_, idx) => idx !== i));
    const add = () => setRows([...rows, { key: '', label: '' }]);
    const move = (i: number, dir: -1 | 1) => {
        const j = i + dir;
        if (j < 0 || j >= rows.length) return;
        const next = [...rows];
        [next[i], next[j]] = [next[j], next[i]];
        setRows(next);
    };

    const updateStat = (i: number, v: string) => setStats(stats.map((s, idx) => (idx === i ? v : s)));
    const removeStat = (i: number) => setStats(stats.filter((_, idx) => idx !== i));
    const addStat = () => setStats([...stats, '']);

    const save = () => {
        setProcessing(true);
        router.put('/settings/education', {
            levels: rows.filter((r) => r.label.trim()),
            statuses: stats.filter((s) => s.trim()),
        } as any, {
            preserveScroll: true,
            onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 2500); },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AppShell title="Educational attainment grid">
            <Head title="Education grid" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <div className="max-w-2xl">
                <div className="mb-3 flex items-start gap-2.5 rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3 text-sm text-brand-800">
                    <GraduationCap className="mt-0.5 h-4 w-4 shrink-0" />
                    <p>These rows make up the <b>Educational Attainment</b> table on the registration form (one row per level, each with school, years, and status). Removing a level hides it from the form and profile — anything already recorded under it is kept but no longer shown, and is dropped the next time that applicant is edited and saved.</p>
                </div>

                <h3 className="mb-2 mt-5 text-sm font-semibold text-slate-700">Levels (table rows)</h3>
                <div className="space-y-2">
                    {rows.map((r, i) => (
                        <div key={i} className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                            <div className="flex shrink-0 flex-col">
                                <button type="button" onClick={() => move(i, -1)} disabled={i === 0} className="rounded p-0.5 text-slate-300 hover:text-brand-600 disabled:opacity-30"><ChevronUp className="h-3.5 w-3.5" /></button>
                                <button type="button" onClick={() => move(i, 1)} disabled={i === rows.length - 1} className="rounded p-0.5 text-slate-300 hover:text-brand-600 disabled:opacity-30"><ChevronDown className="h-3.5 w-3.5" /></button>
                            </div>
                            <input
                                className="input flex-1"
                                placeholder="Level label, e.g. Senior High School…"
                                value={r.label}
                                onChange={(e) => update(i, e.target.value)}
                            />
                            <button type="button" onClick={() => remove(i)} className="shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-4 w-4" /></button>
                        </div>
                    ))}
                </div>
                <button type="button" onClick={add} className="btn-ghost mt-3"><Plus className="h-4 w-4" /> Add level</button>

                <h3 className="mb-2 mt-6 text-sm font-semibold text-slate-700">Status options (dropdown)</h3>
                <div className="space-y-2">
                    {stats.map((s, i) => (
                        <div key={i} className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                            <input
                                className="input flex-1"
                                placeholder="Status label, e.g. Graduate…"
                                value={s}
                                onChange={(e) => updateStat(i, e.target.value)}
                            />
                            <button type="button" onClick={() => removeStat(i)} className="shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-4 w-4" /></button>
                        </div>
                    ))}
                </div>
                <button type="button" onClick={addStat} className="btn-ghost mt-3"><Plus className="h-4 w-4" /> Add status</button>

                <div className="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                    <button onClick={save} disabled={processing} className="btn-primary">Save education grid</button>
                    {saved && <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>}
                </div>
            </div>
        </AppShell>
    );
}
