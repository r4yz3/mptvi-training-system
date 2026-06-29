// Colored pill for a trainee's training status (Active/Inactive/Completed/Incomplete).
const STYLES: Record<string, string> = {
    Active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    Inactive: 'bg-slate-100 text-slate-500 ring-slate-200',
    Completed: 'bg-brand-50 text-brand-700 ring-brand-200',
    Incomplete: 'bg-amber-50 text-amber-700 ring-amber-200',
};

export default function TraineeStatusBadge({ status, className = '' }: { status: string | null; className?: string }) {
    if (!status) return null;
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${STYLES[status] ?? 'bg-slate-100 text-slate-600 ring-slate-200'} ${className}`}>
            {status}
        </span>
    );
}
