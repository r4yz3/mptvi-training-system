import { FormEvent, useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Plus, Pencil, Trash2, X, Search, Eye, EyeOff, ShieldCheck } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface UserRow {
    id: number; name: string; email: string;
    role: string | null; roleLabel: string | null; is_self: boolean;
}
interface RoleOption { value: string; label: string }
interface RoleCount { key: string; label: string; count: number }

const ROLE_BADGE: Record<string, string> = {
    admin: 'bg-brand-100 text-brand-700',
    manager: 'bg-violet-100 text-violet-700',
    registrar: 'bg-sky-100 text-sky-700',
    cashier: 'bg-emerald-100 text-emerald-700',
    coordinator: 'bg-amber-100 text-amber-700',
};
const ROLE_HELP: Record<string, string> = {
    admin: 'Full access to every module and all settings.',
    manager: 'Secretary — enrollment, training, assessment, reports (no finance).',
    registrar: 'Applicant records, screening, documents, IDs.',
    cashier: 'Records and voids payments only.',
    coordinator: 'Attendance, assessment and program management.',
};
function initials(name: string) {
    const p = name.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
}

export default function UsersIndex({ users, roles, roleCounts }: { users: UserRow[]; roles: RoleOption[]; roleCounts: RoleCount[] }) {
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [creating, setCreating] = useState(false);
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return users;
        return users.filter((u) => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || (u.roleLabel ?? '').toLowerCase().includes(q));
    }, [users, search]);

    return (
        <AppShell title="Users">
            <Head title="Users" />

            <div className="mb-5 flex flex-wrap items-center gap-2.5">
                <span className="inline-flex items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><ShieldCheck className="h-4 w-4" /></span>
                    <span className="text-lg font-semibold leading-none text-slate-800">{users.length}</span>
                    <span className="text-xs font-medium text-slate-400">Staff accounts</span>
                </span>
                {roleCounts.filter((r) => r.count > 0).map((r) => (
                    <span key={r.key} className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-2 shadow-sm">
                        <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${ROLE_BADGE[r.key] ?? 'bg-slate-100 text-slate-600'}`}>{r.label}</span>
                        <span className="text-sm font-semibold text-slate-700">{r.count}</span>
                    </span>
                ))}
            </div>

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="relative max-w-xs flex-1">
                    <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <input className="input pl-9" placeholder="Search name, email, role…" value={search} onChange={(e) => setSearch(e.target.value)} />
                </div>
                <button onClick={() => setCreating(true)} className="btn-primary"><Plus className="h-4 w-4" /> Add user</button>
            </div>

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th className="px-4 py-3">Name</th>
                            <th className="px-4 py-3">Email</th>
                            <th className="px-4 py-3">Role</th>
                            <th className="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {filtered.map((u) => (
                            <tr key={u.id} className="hover:bg-slate-50">
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-3">
                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-brand-500 text-xs font-semibold text-white">{initials(u.name)}</span>
                                        <span className="font-medium text-slate-800">{u.name}{u.is_self && <span className="ml-2 text-xs font-normal text-slate-400">(you)</span>}</span>
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-slate-600">{u.email}</td>
                                <td className="px-4 py-3">
                                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${ROLE_BADGE[u.role ?? ''] ?? 'bg-slate-100 text-slate-600'}`}>{u.roleLabel ?? '—'}</span>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-1">
                                        <button onClick={() => setEditing(u)} className="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-brand-600" title="Edit"><Pencil className="h-4 w-4" /></button>
                                        <DeleteButton user={u} />
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {filtered.length === 0 && (
                            <tr><td colSpan={4} className="px-4 py-12 text-center text-sm text-slate-400">No users match your search.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            {creating && <UserModal roles={roles} onClose={() => setCreating(false)} />}
            {editing && <UserModal roles={roles} user={editing} onClose={() => setEditing(null)} />}
        </AppShell>
    );
}

function DeleteButton({ user }: { user: UserRow }) {
    const { delete: destroy, processing } = useForm();
    const onDelete = () => {
        if (confirm(`Delete user “${user.name}”? This cannot be undone.`)) {
            destroy(`/users/${user.id}`, { preserveScroll: true });
        }
    };
    return (
        <button onClick={onDelete} disabled={processing || user.is_self}
            className="rounded-md p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600 disabled:cursor-not-allowed disabled:opacity-40"
            title={user.is_self ? 'You cannot delete yourself' : 'Delete'}>
            <Trash2 className="h-4 w-4" />
        </button>
    );
}

function UserModal({ roles, user, onClose }: { roles: RoleOption[]; user?: UserRow; onClose: () => void }) {
    const isEdit = !!user;
    const [showPw, setShowPw] = useState(false);
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: user?.name ?? '', email: user?.email ?? '',
        role: user?.role ?? roles[0]?.value ?? '', password: '', password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { reset(); onClose(); } };
        if (isEdit) put(`/users/${user!.id}`, opts);
        else post('/users', opts);
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 className="text-base font-semibold text-slate-800">{isEdit ? 'Edit user' : 'Add user'}</h3>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-4 px-5 py-4">
                    <Field label="Full name" error={errors.name}>
                        <input className="input" value={data.name} onChange={(e) => setData('name', e.target.value)} autoFocus />
                    </Field>
                    <Field label="Email" error={errors.email}>
                        <input type="email" className="input" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    </Field>
                    <Field label="Role" error={errors.role}>
                        <select className="input" value={data.role} onChange={(e) => setData('role', e.target.value)}>
                            {roles.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
                        </select>
                        {ROLE_HELP[data.role] && <span className="mt-1 block text-xs text-slate-400">{ROLE_HELP[data.role]}</span>}
                    </Field>
                    <Field label={isEdit ? 'New password (leave blank to keep)' : 'Password'} error={errors.password}>
                        <div className="relative">
                            <input type={showPw ? 'text' : 'password'} className="input pr-10" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                            <button type="button" onClick={() => setShowPw((v) => !v)} className="absolute right-2 top-2 text-slate-400 hover:text-slate-600">
                                {showPw ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </button>
                        </div>
                    </Field>
                    <Field label="Confirm password">
                        <input type={showPw ? 'text' : 'password'} className="input" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                    </Field>

                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary">{isEdit ? 'Save changes' : 'Create user'}</button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">{label}</span>
            {children}
            {error && <span className="mt-1 block text-xs text-rose-600">{error}</span>}
        </label>
    );
}
