import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-slate-100 p-4">
            <Link href="/" className="mb-5 flex items-center gap-3">
                <img src="/mptvi-logo.png" alt="MPTVI" className="h-14 w-14 rounded-full bg-white object-contain p-1 shadow ring-1 ring-slate-200" />
                <img src="/magsaysay-logo.png" alt="Magsaysay" className="h-14 w-14 rounded-full bg-white object-contain p-1 shadow ring-1 ring-slate-200" />
            </Link>

            <div className="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white px-6 py-6 shadow-lg sm:max-w-md sm:px-8">
                {children}
            </div>
        </div>
    );
}
