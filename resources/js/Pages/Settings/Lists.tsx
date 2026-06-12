import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, List, Plus, Trash2, CheckCircle2 } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface ListDef { key: string; label: string; items: string[] }

export default function Lists({ lists }: { lists: ListDef[] }) {
    const [active, setActive] = useState(lists[0]?.key ?? '');
    const [draft, setDraft] = useState<Record<string, string[]>>(() => Object.fromEntries(lists.map((l) => [l.key, l.items])));
    const [saved, setSaved] = useState('');
    const [processing, setProcessing] = useState(false);

    const cur = lists.find((l) => l.key === active)!;
    const items = draft[active] ?? [];
    const setItems = (next: string[]) => setDraft({ ...draft, [active]: next });

    const save = () => {
        setProcessing(true);
        router.put('/settings/lists', { key: active, items: items.map((i) => i.trim()).filter(Boolean) }, {
            preserveScroll: true,
            onSuccess: () => { setSaved(active); setTimeout(() => setSaved(''), 2500); },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AppShell title="Reference lists">
            <Head title="Reference lists" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <div className="flex flex-col gap-5 lg:flex-row">
                {/* List picker */}
                <div className="w-full shrink-0 lg:w-56">
                    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        {lists.map((l) => (
                            <button
                                key={l.key}
                                onClick={() => setActive(l.key)}
                                className={`flex w-full items-center justify-between gap-2 border-b border-slate-50 px-4 py-2.5 text-left text-sm last:border-0 ${active === l.key ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-600 hover:bg-slate-50'}`}
                            >
                                {l.label}
                                <span className="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">{(draft[l.key] ?? l.items).length}</span>
                            </button>
                        ))}
                    </div>
                </div>

                {/* Editor */}
                <div className="max-w-xl flex-1">
                    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <header className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><List className="h-4 w-4" /></span>
                            <div>
                                <h3 className="text-sm font-semibold text-slate-800">{cur.label}</h3>
                                <p className="text-xs text-slate-500">Options shown in the registration form for this field.</p>
                            </div>
                        </header>
                        <div className="space-y-2 p-5">
                            {items.map((v, i) => (
                                <div key={i} className="flex items-center gap-2">
                                    <span className="w-5 text-right text-xs text-slate-300">{i + 1}</span>
                                    <input className="input flex-1" value={v} onChange={(e) => setItems(items.map((x, idx) => (idx === i ? e.target.value : x)))} />
                                    <button onClick={() => setItems(items.filter((_, idx) => idx !== i))} className="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-4 w-4" /></button>
                                </div>
                            ))}
                            {items.length === 0 && <p className="text-sm text-slate-400">No options. Add one below.</p>}
                            <button onClick={() => setItems([...items, ''])} className="btn-ghost mt-1"><Plus className="h-4 w-4" /> Add option</button>
                        </div>
                        <div className="flex items-center gap-3 border-t border-slate-100 px-5 py-4">
                            <button onClick={save} disabled={processing} className="btn-primary">Save “{cur.label}”</button>
                            {saved === active && <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>}
                        </div>
                    </div>
                </div>
            </div>
        </AppShell>
    );
}
