import { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus, Pencil, Trash2, X, Clock, Users, GraduationCap, Banknote, ChevronDown, ChevronRight } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import StatusBadge from '@/Components/StatusBadge';
import { PageProps } from '@/types';

interface Trainee { id: number; name: string; status: string; active: boolean }
interface Program {
    id: number; title: string; qualification: string | null; training_type: string; level: string | null;
    hours: number; fee: number; slots: number; active: boolean;
    applicants_count: number; trainees: Trainee[];
}
interface Options {
    training_types: { value: string; label: string }[];
    levels: string[];
}

export default function ProgramsIndex({ programs, options }: { programs: Program[]; options: Options }) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.can['program.manage'];
    const [progModal, setProgModal] = useState<Program | 'new' | null>(null);
    const [open, setOpen] = useState<Record<number, boolean>>({});

    const totalTrainees = programs.reduce((s, p) => s + p.applicants_count, 0);
    const toggle = (id: number) => setOpen((o) => ({ ...o, [id]: !o[id] }));

    return (
        <AppShell title="Programs">
            <Head title="Programs" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div className="flex gap-2.5">
                    <Stat icon={<GraduationCap className="h-5 w-5" />} label="Programs" value={programs.length} />
                    <Stat icon={<Users className="h-5 w-5" />} label="Trainees" value={totalTrainees} />
                </div>
                {canManage && (
                    <button onClick={() => setProgModal('new')} className="btn-primary">
                        <Plus className="h-4 w-4" /> Add program
                    </button>
                )}
            </div>

            <div className="space-y-3">
                {programs.map((p) => {
                    const isOpen = !!open[p.id];
                    const community = p.training_type === 'community_based';
                    return (
                        <div key={p.id} className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div className="flex items-start gap-3 p-4">
                                <button
                                    onClick={() => toggle(p.id)}
                                    className="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                                    title={isOpen ? 'Hide trainees' : 'Show trainees'}
                                >
                                    <GraduationCap className="h-6 w-6" />
                                </button>
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-base font-semibold text-slate-800">{p.title}</h3>
                                        {p.level && <span className="rounded bg-brand-50 px-1.5 py-0.5 text-xs font-medium text-brand-700">{p.level}</span>}
                                        {community
                                            ? <span className="rounded bg-emerald-50 px-1.5 py-0.5 text-xs font-medium text-emerald-700">Community-Based</span>
                                            : <span className="rounded bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700">School-Based</span>}
                                        {!p.active && <span className="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">Inactive</span>}
                                    </div>
                                    {p.qualification && <div className="mt-0.5 text-xs text-slate-400">{p.qualification}</div>}
                                    <div className="mt-2 flex flex-wrap gap-1.5">
                                        <Tag icon={<Clock className="h-3.5 w-3.5" />}>{p.hours} hrs</Tag>
                                        {community
                                            ? <Tag icon={<Banknote className="h-3.5 w-3.5" />}>Free</Tag>
                                            : <Tag icon={<Banknote className="h-3.5 w-3.5" />}>₱{p.fee.toLocaleString()}</Tag>}
                                        <button onClick={() => toggle(p.id)} className="inline-flex items-center gap-1 rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-600 hover:bg-slate-100">
                                            <Users className="h-3.5 w-3.5" /> {p.applicants_count} trainee{p.applicants_count === 1 ? '' : 's'}
                                            {isOpen ? <ChevronDown className="h-3.5 w-3.5" /> : <ChevronRight className="h-3.5 w-3.5" />}
                                        </button>
                                    </div>
                                </div>
                                {canManage && (
                                    <div className="flex shrink-0 gap-1">
                                        <button onClick={() => setProgModal(p)} className="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-brand-600"><Pencil className="h-4 w-4" /></button>
                                        <button
                                            onClick={() => { if (confirm(`Delete program “${p.title}”?`)) router.delete(`/programs/${p.id}`, { preserveScroll: true }); }}
                                            className="rounded-md p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600"
                                            title={p.applicants_count > 0 ? 'A program with trainees cannot be deleted' : 'Delete program'}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                )}
                            </div>

                            {isOpen && (
                                <div className="border-t border-slate-100">
                                    {p.trainees.length === 0 ? (
                                        <p className="px-5 py-4 text-sm text-slate-400">No trainees in this program yet.</p>
                                    ) : (
                                        <ul className="divide-y divide-slate-50">
                                            {p.trainees.map((t) => (
                                                <li key={t.id}>
                                                    <Link href={`/applicants/${t.id}`} className="flex items-center justify-between gap-3 px-5 py-2.5 hover:bg-slate-50">
                                                        <span className={`truncate text-sm ${t.active ? 'text-slate-700' : 'text-slate-400 line-through'}`}>{t.name}</span>
                                                        <StatusBadge status={t.status} />
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            )}
                        </div>
                    );
                })}
                {programs.length === 0 && (
                    <div className="rounded-xl border border-slate-200 bg-white px-4 py-10 text-center shadow-sm">
                        <GraduationCap className="mx-auto h-8 w-8 text-slate-300" />
                        <p className="mt-2 text-sm text-slate-400">No programs yet.</p>
                    </div>
                )}
            </div>

            {progModal && (
                <ProgramModal program={progModal === 'new' ? null : progModal} options={options} onClose={() => setProgModal(null)} />
            )}
        </AppShell>
    );
}

function Stat({ icon, label, value }: { icon: React.ReactNode; label: string; value: number }) {
    return (
        <div className="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 shadow-sm">
            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600">{icon}</span>
            <div>
                <div className="text-lg font-semibold leading-none text-slate-800">{value}</div>
                <div className="text-xs text-slate-400">{label}</div>
            </div>
        </div>
    );
}

function Tag({ icon, children }: { icon: React.ReactNode; children: React.ReactNode }) {
    return (
        <span className="inline-flex items-center gap-1 rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-600">
            {icon} {children}
        </span>
    );
}

function Modal({ title, onClose, children }: { title: string; onClose: () => void; children: React.ReactNode }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="max-h-[calc(100vh-3rem)] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-xl">
                <div className="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                    <h3 className="text-base font-semibold text-slate-800">{title}</h3>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <div className="px-5 py-4">{children}</div>
            </div>
        </div>
    );
}

function ProgramModal({ program, options, onClose }: { program: Program | null; options: Options; onClose: () => void }) {
    const isEdit = !!program;
    const { data, setData, post, put, processing, errors } = useForm({
        title: program?.title ?? '', qualification: program?.qualification ?? '',
        training_type: program?.training_type ?? 'school_based', level: program?.level ?? 'NC II',
        hours: program?.hours ?? 0, fee: program?.fee ?? 1000, slots: program?.slots ?? 25, active: program?.active ?? true,
    });
    const isCommunity = data.training_type === 'community_based';
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onClose };
        isEdit ? put(`/programs/${program!.id}`, opts) : post('/programs', opts);
    };
    return (
        <Modal title={isEdit ? 'Edit program' : 'Add program'} onClose={onClose}>
            <form onSubmit={submit} className="space-y-3">
                <Fld label="Title" error={errors.title}><input className="input" value={data.title} onChange={(e) => setData('title', e.target.value)} autoFocus /></Fld>
                <Fld label="Training type" error={errors.training_type}>
                    <select className="input" value={data.training_type} onChange={(e) => setData('training_type', e.target.value)}>
                        {options.training_types.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                    </select>
                    {isCommunity && <span className="mt-1 block text-xs text-emerald-600">Free soft-skills training — no fee is collected.</span>}
                </Fld>
                <div className="grid grid-cols-2 gap-3">
                    <Fld label="Qualification / sector"><input className="input" value={data.qualification} onChange={(e) => setData('qualification', e.target.value)} /></Fld>
                    <Fld label="NC level"><select className="input" value={data.level} onChange={(e) => setData('level', e.target.value)}>{options.levels.map((l) => <option key={l}>{l}</option>)}</select></Fld>
                    <Fld label="Training hours" error={errors.hours}><input type="number" className="input" value={data.hours} onChange={(e) => setData('hours', Number(e.target.value))} /></Fld>
                    {!isCommunity && <Fld label="Misc fee (₱)" error={errors.fee}><input type="number" className="input" value={data.fee} onChange={(e) => setData('fee', Number(e.target.value))} /></Fld>}
                    <Fld label="Default slots"><input type="number" className="input" value={data.slots} onChange={(e) => setData('slots', Number(e.target.value))} /></Fld>
                    <label className="flex items-end gap-2 pb-2 text-sm text-slate-600"><input type="checkbox" className="rounded border-slate-300 text-brand-600" checked={data.active} onChange={(e) => setData('active', e.target.checked)} /> Active</label>
                </div>
                <Actions processing={processing} onClose={onClose} label={isEdit ? 'Save' : 'Create'} />
            </form>
        </Modal>
    );
}

function Fld({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-slate-600">{label}</span>
            {children}
            {error && <span className="mt-1 block text-xs text-rose-600">{error}</span>}
        </label>
    );
}

function Actions({ processing, onClose, label }: { processing: boolean; onClose: () => void; label: string }) {
    return (
        <div className="flex justify-end gap-2 pt-2">
            <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
            <button type="submit" disabled={processing} className="btn-primary">{label}</button>
        </div>
    );
}
