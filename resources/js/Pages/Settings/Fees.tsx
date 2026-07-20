import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Banknote, CheckCircle2 } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface ProgramRow {
    id: number;
    title: string;
    level: string | null;
    misc_fee: number;
    amounts: Record<string, number>;
}

interface Props {
    categories: string[];
    miscFeeCategory: string;
    years: string[];
    year: string;
    programs: ProgramRow[];
}

export default function Fees({ categories, miscFeeCategory, years, year, programs }: Props) {
    // amounts[program_id][category] — seeded from the current school year.
    const { data, setData, put, processing, recentlySuccessful } = useForm<{ school_year: string; amounts: Record<number, Record<string, number>> }>({
        school_year: year,
        amounts: Object.fromEntries(programs.map((p) => [p.id, { ...p.amounts }])),
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/settings/fees', { preserveScroll: true });
    };

    const setAmount = (programId: number, category: string, value: string) => {
        const n = value === '' ? 0 : Math.max(0, Math.floor(Number(value)));
        setData('amounts', { ...data.amounts, [programId]: { ...data.amounts[programId], [category]: n } });
    };

    // Switching the school year reloads the page with that year's saved amounts.
    const changeYear = (y: string) => router.get('/settings/fees', { year: y }, { preserveScroll: true, preserveState: false });

    const peso = (n: number) => '₱' + n.toLocaleString();

    return (
        <AppShell title="Fees">
            <Head title="Fees" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <form onSubmit={submit} className="space-y-5">
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <header className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                        <div className="flex items-center gap-2.5">
                            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Banknote className="h-4 w-4" /></span>
                            <div>
                                <h3 className="text-sm font-semibold text-slate-800">Extra fees per program</h3>
                                <p className="text-xs text-slate-500">Amounts for {categories.join(' & ')}, set per program for the chosen school year.</p>
                            </div>
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <span className="text-xs font-medium text-slate-600">School year</span>
                            <select className="input !w-auto py-1.5" value={data.school_year} onChange={(e) => changeYear(e.target.value)}>
                                {years.map((y) => <option key={y} value={y}>{y}</option>)}
                            </select>
                        </label>
                    </header>

                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[560px] text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th className="px-5 py-2.5 font-medium">Program</th>
                                    <th className="px-3 py-2.5 font-medium">{miscFeeCategory}<div className="text-[10px] font-normal normal-case text-slate-400">set on the program</div></th>
                                    {categories.map((c) => <th key={c} className="px-3 py-2.5 font-medium">{c} (₱)</th>)}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {programs.map((p) => (
                                    <tr key={p.id}>
                                        <td className="px-5 py-2.5">
                                            <div className="font-medium text-slate-800">{p.title}</div>
                                            {p.level && <div className="text-xs text-slate-400">{p.level}</div>}
                                        </td>
                                        <td className="px-3 py-2.5 text-slate-500">{peso(p.misc_fee)}</td>
                                        {categories.map((c) => (
                                            <td key={c} className="px-3 py-2.5">
                                                <input
                                                    type="number" min="0" inputMode="numeric"
                                                    className="input !w-28 py-1.5"
                                                    value={data.amounts[p.id]?.[c] ?? 0}
                                                    onChange={(e) => setAmount(p.id, c, e.target.value)}
                                                />
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                                {programs.length === 0 && (
                                    <tr><td colSpan={categories.length + 2} className="px-5 py-8 text-center text-slate-400">No programs yet.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="border-t border-slate-100 bg-slate-50/40 px-5 py-2.5 text-xs text-slate-400">
                        Set an amount to <b>0</b> when a program doesn’t charge that fee. “Others” is collected ad-hoc and isn’t scheduled here.
                    </div>
                </section>

                <div className="flex items-center gap-3">
                    <button type="submit" disabled={processing} className="btn-primary">Save fees for {data.school_year}</button>
                    {recentlySuccessful && (
                        <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>
                    )}
                </div>
            </form>
        </AppShell>
    );
}
