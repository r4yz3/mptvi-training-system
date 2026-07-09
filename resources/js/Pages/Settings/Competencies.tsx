import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ListChecks, Plus, Trash2, CheckCircle2, ChevronDown } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Unit { id: number | null; code: string | null; title: string; type: string }
interface Program { id: number; title: string; level: string | null; units: Unit[] }

const TYPE_STYLE: Record<string, string> = {
    Basic: 'bg-sky-50 text-sky-700 ring-sky-200',
    Common: 'bg-violet-50 text-violet-700 ring-violet-200',
    Core: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
};

export default function Competencies({ programs, types }: { programs: Program[]; types: string[] }) {
    const [openId, setOpenId] = useState<number | null>(programs[0]?.id ?? null);

    return (
        <AppShell title="Competency standards">
            <Head title="Competency standards" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <div className="max-w-3xl">
                <div className="mb-4 flex items-start gap-2.5 rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3 text-sm text-brand-800">
                    <ListChecks className="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                        Define each qualification's <b>Units of Competency</b> (TESDA: Basic, Common, Core). During training, each trainee is rated
                        <b> Competent / Not Yet Competent</b> per unit (Training → batch roster → Competency) — this forms the Achievement Chart. A
                        trainee is institutionally complete once <b>every</b> unit is Competent.
                    </p>
                </div>

                <div className="space-y-3">
                    {programs.map((p) => (
                        <ProgramCard key={p.id} program={p} types={types} open={openId === p.id}
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

function ProgramCard({ program, types, open, onToggle }: { program: Program; types: string[]; open: boolean; onToggle: () => void }) {
    const [rows, setRows] = useState<Unit[]>(program.units);
    const [saved, setSaved] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const update = (i: number, patch: Partial<Unit>) => setRows(rows.map((r, idx) => (idx === i ? { ...r, ...patch } : r)));
    const remove = (i: number) => setRows(rows.filter((_, idx) => idx !== i));
    const add = (type: string) => setRows([...rows, { id: null, code: '', title: '', type }]);

    const counts = types.reduce((a, t) => ({ ...a, [t]: rows.filter((r) => r.type === t).length }), {} as Record<string, number>);

    const save = () => {
        setProcessing(true);
        setError(null);
        router.put(`/settings/competencies/${program.id}`, { units: rows.filter((r) => r.title.trim()) } as any, {
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
                        {rows.length} unit{rows.length === 1 ? '' : 's'}
                        {types.map((t) => counts[t] > 0 && <span key={t}> · {counts[t]} {t}</span>)}
                    </p>
                </div>
                <ChevronDown className={`h-5 w-5 shrink-0 text-slate-400 transition ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <div className="border-t border-slate-100 px-4 py-4">
                    {types.map((type) => (
                        <div key={type} className="mb-4">
                            <div className="mb-1.5 flex items-center justify-between">
                                <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ${TYPE_STYLE[type] ?? 'bg-slate-50 text-slate-600 ring-slate-200'}`}>
                                    {type} competencies
                                </span>
                                <button type="button" onClick={() => add(type)} className="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                                    <Plus className="h-3.5 w-3.5" /> Add {type}
                                </button>
                            </div>
                            <div className="space-y-2">
                                {rows.map((r, i) => r.type === type && (
                                    <div key={i} className="flex items-center gap-2">
                                        <input
                                            className="input w-28 shrink-0 font-mono text-xs"
                                            placeholder="Code"
                                            value={r.code ?? ''}
                                            onChange={(e) => update(i, { code: e.target.value })}
                                        />
                                        <input
                                            className="input flex-1"
                                            placeholder={`${type} unit of competency…`}
                                            value={r.title}
                                            onChange={(e) => update(i, { title: e.target.value })}
                                        />
                                        <button type="button" onClick={() => remove(i)} className="shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-4 w-4" /></button>
                                    </div>
                                ))}
                                {counts[type] === 0 && <p className="px-1 text-xs italic text-slate-300">No {type.toLowerCase()} units.</p>}
                            </div>
                        </div>
                    ))}

                    {error && <p className="mb-2 text-sm text-rose-600">{error}</p>}

                    <div className="flex items-center gap-3 border-t border-slate-100 pt-3">
                        <button onClick={save} disabled={processing} className="btn-primary disabled:opacity-50">Save units</button>
                        {saved && <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>}
                    </div>
                </div>
            )}
        </div>
    );
}
