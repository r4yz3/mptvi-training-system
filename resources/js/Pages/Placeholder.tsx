import { Head } from '@inertiajs/react';
import { Construction } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

export default function Placeholder({ label }: { module: string; label: string }) {
    return (
        <AppShell title={label}>
            <Head title={label} />

            <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center shadow-sm">
                <div className="flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                    <Construction className="h-7 w-7" />
                </div>
                <h2 className="mt-4 text-lg font-semibold text-slate-800">{label}</h2>
                <p className="mt-1 max-w-md text-sm text-slate-500">
                    This module is part of the build roadmap and will be implemented in an
                    upcoming phase. The navigation, access control, and shell are already wired.
                </p>
            </div>
        </AppShell>
    );
}
