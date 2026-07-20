import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, GraduationCap, CheckCircle2, Hash } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Values { school_year: string; default_session: string; default_fee: number; age_min: number; age_max: number; cert_prefix: string }

export default function Academic({ values, sessions }: { values: Values; sessions: string[] }) {
    const { data, setData, put, processing, recentlySuccessful, errors } = useForm({ ...values });
    const year = new Date().getFullYear();
    const certSample = `${(data.cert_prefix || 'CK2').toUpperCase()}-${year}-0001`;

    const submit = (e: React.FormEvent) => { e.preventDefault(); put('/settings/academic', { preserveScroll: true }); };

    return (
        <AppShell title="Academic defaults">
            <Head title="Academic defaults" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <form onSubmit={submit} className="max-w-2xl space-y-5">
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <header className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><GraduationCap className="h-4 w-4" /></span>
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800">Enrollment defaults</h3>
                            <p className="text-xs text-slate-500">Pre-fill new registrations and the eligibility check.</p>
                        </div>
                    </header>
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Current school year</span>
                            <input className="input" placeholder="2026" value={data.school_year} onChange={(e) => setData('school_year', e.target.value)} />
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Default class session</span>
                            <select className="input" value={data.default_session} onChange={(e) => setData('default_session', e.target.value)}>
                                <option value="">— none —</option>
                                {sessions.map((s) => <option key={s}>{s}</option>)}
                            </select>
                        </label>
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Default program fee (₱)</span>
                            <input type="number" min={0} className="input" value={data.default_fee} onChange={(e) => setData('default_fee', Number(e.target.value))} />
                        </label>
                        <div className="grid grid-cols-2 gap-3">
                            <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Min age</span>
                                <input type="number" className="input" value={data.age_min} onChange={(e) => setData('age_min', Number(e.target.value))} />
                            </label>
                            <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Max age</span>
                                <input type="number" className="input" value={data.age_max} onChange={(e) => setData('age_max', Number(e.target.value))} />
                                {errors.age_max && <span className="text-xs text-rose-600">{errors.age_max}</span>}
                            </label>
                        </div>
                    </div>
                </section>

                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <header className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Hash className="h-4 w-4" /></span>
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800">Certificate numbering</h3>
                            <p className="text-xs text-slate-500">Prefix for issued National Certificate numbers.</p>
                        </div>
                    </header>
                    <div className="flex flex-wrap items-end gap-4 p-5">
                        <label className="block"><span className="mb-1 block text-xs font-medium text-slate-600">Prefix</span>
                            <input className="input w-32 uppercase" value={data.cert_prefix} onChange={(e) => setData('cert_prefix', e.target.value)} />
                        </label>
                        <div className="text-sm text-slate-500">Next will look like <span className="rounded bg-slate-100 px-2 py-0.5 font-mono font-medium text-slate-700">{certSample}</span></div>
                    </div>
                </section>

                <div className="flex items-center gap-3">
                    <button type="submit" disabled={processing} className="btn-primary">Save defaults</button>
                    {recentlySuccessful && <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>}
                </div>
            </form>
        </AppShell>
    );
}
