import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, BookMarked, Plus, Trash2, CheckCircle2, ChevronDown } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Subject { id: number | null; code: string | null; title: string; category: string; units: number }
interface Program { id: number; title: string; level: string | null; subjects: Subject[] }

const CAT_STYLE: Record<string, string> = {
    Major: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    Minor: 'bg-sky-50 text-sky-700 ring-sky-200',
};

export default function Subjects({ programs, categories }: { programs: Program[]; categories: string[] }) {
    const [openId, setOpenId] = useState<number | null>(programs[0]?.id ?? null);

    return (
        <AppShell title="Subjects & grading">
            <Head title="Subjects & grading" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <div className="max-w-3xl">
                <div className="mb-4 flex items-start gap-2.5 rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3 text-sm text-brand-800">
                    <BookMarked className="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                        Define each program's <b>Major</b> and <b>Minor</b> subjects with their <b>units</b>. The registrar then records a numeric
                        grade (<b>1.00</b> highest → <b>5.00</b> fail; <b>3.00</b> passing) per subject on the trainee's profile. The GWA is the
                        unit-weighted average — Major subjects carry more units, so they weigh more.
                    </p>
                </div>

                <div className="space-y-3">
                    {programs.map((p) => (
                        <ProgramCard key={p.id} program={p} categories={categories} open={openId === p.id}
                            onToggle={() => setOpenId(openId === p.id ? null : p.id)} />
                    ))}
                    {programs.length === 0 && (
                        <p className="rounded-xl border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-400">
                            No programs yet — add one under Programs first.
                        </p>
                    )}
                </div>
            </div>
        </AppShell>
    );
}

function ProgramCard({ program, categories, open, onToggle }: { program: Program; categories: string[]; open: boolean; onToggle: () => void }) {
    const [rows, setRows] = useState<Subject[]>(program.subjects);
    const [saved, setSaved] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const update = (i: number, patch: Partial<Subject>) => setRows(rows.map((r, idx) => (idx === i ? { ...r, ...patch } : r)));
    const remove = (i: number) => setRows(rows.filter((_, idx) => idx !== i));
    const add = (category: string) => setRows([...rows, { id: null, code: '', title: '', category, units: category === 'Major' ? 3 : 2 }]);

    const counts = categories.reduce((a, c) => ({ ...a, [c]: rows.filter((r) => r.category === c).length }), {} as Record<string, number>);
    const totalUnits = (c: string) => rows.filter((r) => r.category === c).reduce((s, r) => s + (Number(r.units) || 0), 0);

    const save = () => {
        setProcessing(true);
        setError(null);
        router.put(`/settings/subjects/${program.id}`, { subjects: rows.filter((r) => r.title.trim()) } as any, {
            preserveScroll: true,
            onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 2500); },
            onError: (errs) => setError((Object.values(errs)[0] as string) ?? 'Could not save.'),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <button onClick={onToggle} className="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left hover:bg-slate-50">
                <div className="min-w-0">
                    <p className="truncate font-semibold text-slate-800">{program.title}</p>
                    <p className="mt-0.5 text-xs text-slate-500">
                        {rows.length} subject{rows.length === 1 ? '' : 's'}
                        {categories.map((c) => counts[c] > 0 && <span key={c}> · {counts[c]} {c} ({totalUnits(c)}u)</span>)}
                    </p>
                </div>
                <ChevronDown className={`h-5 w-5 shrink-0 text-slate-400 transition ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <div className="border-t border-slate-100 px-4 py-4">
                    {categories.map((category) => (
                        <div key={category} className="mb-4">
                            <div className="mb-1.5 flex items-center justify-between">
                                <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ${CAT_STYLE[category] ?? 'bg-slate-50 text-slate-600 ring-slate-200'}`}>
                                    {category} subjects
                                </span>
                                <button type="button" onClick={() => add(category)} className="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                                    <Plus className="h-3.5 w-3.5" /> Add {category}
                                </button>
                            </div>
                            <div className="space-y-2">
                                {rows.map((r, i) => r.category === category && (
                                    <div key={i} className="flex items-center gap-2">
                                        <input
                                            className="input w-24 shrink-0 font-mono text-xs"
                                            placeholder="Code"
                                            value={r.code ?? ''}
                                            onChange={(e) => update(i, { code: e.target.value })}
                                        />
                                        <input
                                            className="input flex-1"
                                            placeholder={`${category} subject…`}
                                            value={r.title}
                                            onChange={(e) => update(i, { title: e.target.value })}
                                        />
                                        <input
                                            type="number" min="1" max="20"
                                            className="input w-16 shrink-0 text-center"
                                            title="Units"
                                            value={r.units}
                                            onChange={(e) => update(i, { units: Number(e.target.value) })}
                                        />
                                        <button type="button" onClick={() => remove(i)} className="shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-4 w-4" /></button>
                                    </div>
                                ))}
                                {counts[category] === 0 && <p className="px-1 text-xs italic text-slate-300">No {category.toLowerCase()} subjects.</p>}
                            </div>
                        </div>
                    ))}

                    {error && <p className="mb-2 text-sm text-rose-600">{error}</p>}

                    <div className="flex items-center gap-3 border-t border-slate-100 pt-3">
                        <button onClick={save} disabled={processing} className="btn-primary disabled:opacity-50">Save subjects</button>
                        {saved && <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>}
                    </div>
                </div>
            )}
        </div>
    );
}
