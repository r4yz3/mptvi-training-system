import { Link } from '@inertiajs/react';
import clsx from 'clsx';

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

export default function Pagination({
    links,
    from,
    to,
    total,
}: {
    links: PageLink[];
    from: number | null;
    to: number | null;
    total: number;
}) {
    if (total === 0) return null;

    return (
        <div className="flex flex-col items-center justify-between gap-3 px-1 py-3 sm:flex-row">
            <p className="text-sm text-slate-500">
                Showing <span className="font-medium">{from ?? 0}</span>–
                <span className="font-medium">{to ?? 0}</span> of{' '}
                <span className="font-medium">{total}</span>
            </p>
            <div className="flex flex-wrap gap-1">
                {links.map((l, i) => (
                    <Link
                        key={i}
                        href={l.url ?? '#'}
                        preserveScroll
                        preserveState
                        className={clsx(
                            'min-w-9 rounded-md border px-3 py-1.5 text-center text-sm',
                            l.active
                                ? 'border-brand-600 bg-brand-600 text-white'
                                : l.url
                                  ? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                  : 'cursor-default border-transparent text-slate-300',
                        )}
                        dangerouslySetInnerHTML={{ __html: l.label }}
                    />
                ))}
            </div>
        </div>
    );
}
