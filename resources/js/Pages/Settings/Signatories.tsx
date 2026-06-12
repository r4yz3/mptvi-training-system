import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Stamp, Award, CheckCircle2 } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Values {
    assessor: string;
    checked_name: string; checked_title: string;
    approved_name: string; approved_title: string;
}

export default function Signatories({ values }: { values: Values }) {
    const { data, setData, put, processing, recentlySuccessful } = useForm({ ...values });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/settings/signatories', { preserveScroll: true });
    };

    const field = (key: keyof Values, label: string, placeholder = '') => (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-slate-600">{label}</span>
            <input className="input" placeholder={placeholder} value={data[key]} onChange={(e) => setData(key, e.target.value)} />
        </label>
    );

    return (
        <AppShell title="Signatories & certificate settings">
            <Head title="Signatories" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <form onSubmit={submit} className="max-w-2xl space-y-5">
                {/* Assessor */}
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <header className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Award className="h-4 w-4" /></span>
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800">Accredited assessor</h3>
                            <p className="text-xs text-slate-500">Auto-fills new assessments and prints on the National Certificate.</p>
                        </div>
                    </header>
                    <div className="p-5">
                        {field('assessor', 'Full name of accredited assessor', 'e.g. Juan D. Reyes')}
                    </div>
                </section>

                {/* Signatories */}
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <header className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Stamp className="h-4 w-4" /></span>
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800">Document signatories</h3>
                            <p className="text-xs text-slate-500">Officials who check and approve official documents.</p>
                        </div>
                    </header>
                    <div className="space-y-5 p-5">
                        <div>
                            <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Checked by</div>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                {field('checked_name', 'Name')}
                                {field('checked_title', 'Position / title')}
                            </div>
                        </div>
                        <div className="border-t border-slate-100 pt-5">
                            <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Approved by</div>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                {field('approved_name', 'Name')}
                                {field('approved_title', 'Position / title')}
                            </div>
                        </div>
                    </div>
                </section>

                <p className="text-xs text-slate-400">
                    These print on the <b>Learner Profile Form</b> (Checked by / Approved by), the <b>National Certificate</b>,
                    and <b>Official Receipts</b>. The assessor also auto-fills every assessment.
                </p>

                <div className="flex items-center gap-3">
                    <button type="submit" disabled={processing} className="btn-primary">Save settings</button>
                    {recentlySuccessful && (
                        <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600">
                            <CheckCircle2 className="h-4 w-4" /> Saved
                        </span>
                    )}
                </div>
            </form>
        </AppShell>
    );
}
