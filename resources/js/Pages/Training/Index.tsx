import { Head, Link } from '@inertiajs/react';
import { GraduationCap, Users, ChevronRight, ListChecks, Layers } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface BatchRow {
    id: number; code: string; program: string | null; level: string | null;
    session: string; status: string; trainees: number;
}

const STATUS: Record<string, string> = {
    Planned: 'bg-slate-100 text-slate-600', Open: 'bg-emerald-100 text-emerald-700',
    Ongoing: 'bg-indigo-100 text-indigo-700', Closed: 'bg-amber-100 text-amber-700',
    Completed: 'bg-brand-100 text-brand-700',
};

export default function TrainingIndex({ batches }: { batches: BatchRow[] }) {
    const totalTrainees = batches.reduce((s, b) => s + b.trainees, 0);

    return (
        <AppShell title="Training">
            <Head title="Training" />

            <div className="mb-5 flex flex-wrap items-center gap-2.5">
                <span className="inline-flex items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Layers className="h-4 w-4" /></span>
                    <span className="text-lg font-semibold leading-none text-slate-800">{batches.length}</span>
                    <span className="text-xs font-medium text-slate-400">Batches</span>
                </span>
                <span className="inline-flex items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Users className="h-4 w-4" /></span>
                    <span className="text-lg font-semibold leading-none text-slate-800">{totalTrainees}</span>
                    <span className="text-xs font-medium text-slate-400">Trainees</span>
                </span>
            </div>

            <p className="mb-3 text-sm text-slate-500">Select a batch to rate competencies and manage its trainees.</p>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {batches.map((b) => (
                    <Link key={b.id} href={`/training/${b.id}`} className="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow">
                        <div className="flex items-start justify-between">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><GraduationCap className="h-5 w-5" /></div>
                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${STATUS[b.status]}`}>{b.status}</span>
                        </div>
                        <div className="mt-3 font-semibold text-slate-800">{b.program} {b.level && <span className="text-slate-400">{b.level}</span>}</div>
                        <div className="text-xs text-slate-500">Batch {b.code} · {b.session}</div>
                        <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
                            <span className="inline-flex items-center gap-1.5 text-slate-500"><Users className="h-4 w-4 text-slate-400" /> {b.trainees} trainees</span>
                            <span className="inline-flex items-center gap-1 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">
                                <ListChecks className="h-3.5 w-3.5" /> Open roster
                            </span>
                            <ChevronRight className="h-4 w-4 text-slate-300 group-hover:hidden" />
                        </div>
                    </Link>
                ))}
                {batches.length === 0 && (
                    <div className="col-span-full rounded-xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center text-sm text-slate-400">
                        <GraduationCap className="mx-auto mb-2 h-8 w-8 text-slate-300" />
                        No batches yet. Create one in Programs &amp; batches.
                    </div>
                )}
            </div>
        </AppShell>
    );
}
