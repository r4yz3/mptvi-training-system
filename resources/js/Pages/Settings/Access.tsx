import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ShieldCheck, Check, Minus } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Role { key: string; label: string }
interface ModuleRow { label: string; group: string; roles: string[] }
interface Props {
    roles: Role[];
    capabilities: string[];
    matrix: Record<string, string[]>;
    modules: ModuleRow[];
}

export default function Access({ roles, capabilities, matrix, modules }: Props) {
    // admin — and registrar (granted full admin access) — implicitly hold everything (Gate::before).
    const FULL_ACCESS = ['admin', 'registrar'];
    const can = (roleKey: string, cap: string) => FULL_ACCESS.includes(roleKey) || (matrix[roleKey] ?? []).includes(cap);
    const canModule = (roleKey: string, mod: ModuleRow) => mod.roles.includes(roleKey);

    const Cell = ({ on }: { on: boolean }) => on
        ? <span className="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><Check className="h-3.5 w-3.5" /></span>
        : <span className="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-50 text-slate-300"><Minus className="h-3 w-3" /></span>;

    return (
        <AppShell title="Roles & access">
            <Head title="Roles & access" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <div className="mb-4 flex items-start gap-2.5 rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3 text-sm text-brand-800">
                <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0" />
                <p>Reference view of the system's role permissions. <b>Administrator</b> holds every capability. To change who holds a role, use <Link href="/users" className="font-medium underline">User Accounts</Link>.</p>
            </div>

            <Matrix title="Capabilities" rowLabel="Capability" roles={roles} rows={capabilities.map((c) => ({ key: c, label: c }))} test={(r, c) => can(r, c.key)} Cell={Cell} />

            <div className="mt-6">
                <Matrix title="Module access" rowLabel="Module" roles={roles} rows={modules.map((m) => ({ key: m.label, label: m.label, group: m.group, mod: m }))} test={(r, row) => canModule(r, row.mod)} Cell={Cell} />
            </div>
        </AppShell>
    );
}

function Matrix({ title, rowLabel, roles, rows, test, Cell }: {
    title: string; rowLabel: string; roles: Role[];
    rows: any[]; test: (roleKey: string, row: any) => boolean; Cell: (p: { on: boolean }) => JSX.Element;
}) {
    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">{title}</div>
            <div className="overflow-x-auto">
                <table className="min-w-full text-sm">
                    <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th className="px-4 py-2.5 text-left">{rowLabel}</th>
                            {roles.map((r) => <th key={r.key} className="px-3 py-2.5 text-center font-medium normal-case">{r.label}</th>)}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map((row) => (
                            <tr key={row.key} className="hover:bg-slate-50">
                                <td className="px-4 py-2 font-mono text-xs text-slate-600">
                                    {row.label}{row.group && <span className="ml-2 rounded bg-slate-100 px-1.5 py-0.5 font-sans text-[10px] text-slate-400">{row.group}</span>}
                                </td>
                                {roles.map((r) => <td key={r.key} className="px-3 py-2 text-center"><Cell on={test(r.key, row)} /></td>)}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
