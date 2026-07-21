import clsx from 'clsx';

const STYLES: Record<string, string> = {
    Registered: 'bg-slate-100 text-slate-700',
    Enrolled: 'bg-sky-100 text-sky-700',
    'In training': 'bg-indigo-100 text-indigo-700',
    'For assessment': 'bg-amber-100 text-amber-700',
    Certified: 'bg-brand-100 text-brand-700',
    Disqualified: 'bg-rose-100 text-rose-700',
};

export default function StatusBadge({ status }: { status: string }) {
    return (
        <span
            className={clsx(
                'inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium',
                STYLES[status] ?? 'bg-slate-100 text-slate-700',
            )}
        >
            {status}
        </span>
    );
}
