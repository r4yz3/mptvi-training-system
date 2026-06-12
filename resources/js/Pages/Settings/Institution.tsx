import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2, CheckCircle2 } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Values { name: string; short_name: string; office: string; address: string; contact: string; email: string }

export default function Institution({ values }: { values: Values }) {
    const { data, setData, put, processing, recentlySuccessful } = useForm({ ...values });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/settings/institution', { preserveScroll: true });
    };

    const field = (key: keyof Values, label: string, placeholder = '', hint = '') => (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-slate-600">{label}</span>
            <input className="input" placeholder={placeholder} value={data[key]} onChange={(e) => setData(key, e.target.value)} />
            {hint && <span className="mt-1 block text-xs text-slate-400">{hint}</span>}
        </label>
    );

    return (
        <AppShell title="Institution profile">
            <Head title="Institution profile" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <form onSubmit={submit} className="max-w-2xl space-y-5">
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <header className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Building2 className="h-4 w-4" /></span>
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800">Institution profile</h3>
                            <p className="text-xs text-slate-500">Printed on the certificate, LPF, ID and official receipts.</p>
                        </div>
                    </header>
                    <div className="space-y-4 p-5">
                        {field('name', 'Full institution name', 'Maximino Pellerin Sr. Technical and Vocational Institute')}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {field('short_name', 'Short name / acronym', 'MPTVI')}
                            {field('office', 'Office', 'Public Employment Service Office (PESO)')}
                        </div>
                        {field('address', 'Address', 'Magsaysay, Davao del Sur', 'Used on the certificate “Issued at …” line.')}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {field('contact', 'Contact number', '0900 000 0000')}
                            {field('email', 'Email', 'office@example.gov.ph')}
                        </div>
                    </div>
                </section>

                <div className="flex items-center gap-3">
                    <button type="submit" disabled={processing} className="btn-primary">Save profile</button>
                    {recentlySuccessful && (
                        <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved</span>
                    )}
                </div>
            </form>
        </AppShell>
    );
}
