import { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Plus, Pencil, Trash2, X, Layers, CalendarDays, Clock, Users, GraduationCap, Banknote } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import { PageProps } from '@/types';

interface Batch {
    id: number; program_id: number; code: string; class_session: string; class_days: string;
    school_year: string | null; capacity: number; trainer: string | null; venue: string | null;
    start_date: string | null; end_date: string | null; status: string;
}
interface Program {
    id: number; title: string; qualification: string | null; level: string | null;
    hours: number; fee: number; slots: number; active: boolean;
    batches_count: number; applicants_count: number; batches: Batch[];
}
interface Options {
    levels: string[]; class_sessions: string[]; class_days: string[]; batch_statuses: string[];
}

const BATCH_STATUS: Record<string, string> = {
    Planned: 'bg-slate-100 text-slate-600', Open: 'bg-emerald-100 text-emerald-700',
    Ongoing: 'bg-indigo-100 text-indigo-700', Closed: 'bg-amber-100 text-amber-700',
    Completed: 'bg-brand-100 text-brand-700',
};

const fmtDate = (d: string | null) => {
    if (!d) return '—';
    const dt = new Date(d);
    return isNaN(dt.getTime()) ? d.slice(0, 10) : dt.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

export default function ProgramsIndex({ programs, options }: { programs: Program[]; options: Options }) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.can['program.manage'];
    const [progModal, setProgModal] = useState<Program | 'new' | null>(null);
    const [batchModal, setBatchModal] = useState<{ programId: number; batch: Batch | null } | null>(null);

    const totalBatches = programs.reduce((s, p) => s + p.batches_count, 0);
    const totalApplicants = programs.reduce((s, p) => s + p.applicants_count, 0);

    return (
        <AppShell title="Programs & batches">
            <Head title="Programs & batches" />

            {/* Summary */}
            <div className="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-wrap gap-2.5">
                    <MiniStat icon={<GraduationCap className="h-4 w-4" />} value={programs.length} label="Programs" />
                    <MiniStat icon={<Layers className="h-4 w-4" />} value={totalBatches} label="Batches" />
                    <MiniStat icon={<Users className="h-4 w-4" />} value={totalApplicants} label="Applicants" />
                </div>
                {canManage && (
                    <button onClick={() => setProgModal('new')} className="btn-primary self-start sm:self-auto">
                        <Plus className="h-4 w-4" /> Add program
                    </button>
                )}
            </div>

            <div className="space-y-4">
                {programs.map((p) => (
                    <div key={p.id} className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-start sm:justify-between">
                            <div className="flex gap-3.5">
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><GraduationCap className="h-6 w-6" /></span>
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-base font-semibold text-slate-800">{p.title}</h3>
                                        {p.level && <span className="rounded bg-brand-50 px-1.5 py-0.5 text-xs font-medium text-brand-700">{p.level}</span>}
                                        {!p.active && <span className="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">Inactive</span>}
                                    </div>
                                    {p.qualification && <div className="mt-0.5 text-xs text-slate-400">{p.qualification}</div>}
                                    <div className="mt-2 flex flex-wrap gap-1.5">
                                        <Tag icon={<Clock className="h-3.5 w-3.5" />}>{p.hours} hrs</Tag>
                                        <Tag icon={<Banknote className="h-3.5 w-3.5" />}>₱{p.fee.toLocaleString()}</Tag>
                                        <Tag icon={<Users className="h-3.5 w-3.5" />}>{p.applicants_count} applicants</Tag>
                                        <Tag icon={<Layers className="h-3.5 w-3.5" />}>{p.batches_count} batches</Tag>
                                    </div>
                                </div>
                            </div>
                            {canManage && (
                                <div className="flex shrink-0 gap-1">
                                    <button onClick={() => setBatchModal({ programId: p.id, batch: null })} className="btn-ghost px-2.5 py-1.5 text-xs">
                                        <Plus className="h-3.5 w-3.5" /> Batch
                                    </button>
                                    <button onClick={() => setProgModal(p)} className="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-brand-600"><Pencil className="h-4 w-4" /></button>
                                    <button
                                        onClick={() => { if (confirm(`Delete program “${p.title}”?`)) router.delete(`/programs/${p.id}`, { preserveScroll: true }); }}
                                        className="rounded-md p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600"
                                    ><Trash2 className="h-4 w-4" /></button>
                                </div>
                            )}
                        </div>

                        {p.batches.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        <tr>
                                            <th className="px-5 py-2">Batch</th><th className="px-5 py-2">Session</th>
                                            <th className="px-5 py-2">Schedule</th><th className="px-5 py-2">Dates</th>
                                            <th className="px-5 py-2">Trainer</th><th className="px-5 py-2">Status</th>
                                            {canManage && <th className="px-5 py-2" />}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-50">
                                        {p.batches.map((b) => (
                                            <tr key={b.id} className="hover:bg-slate-50">
                                                <td className="px-5 py-2.5 font-medium text-slate-700">{b.code}<div className="text-xs font-normal text-slate-400">{b.school_year}</div></td>
                                                <td className="px-5 py-2.5 text-slate-600">{b.class_session}</td>
                                                <td className="px-5 py-2.5 text-slate-600">{b.class_days}<div className="text-xs text-slate-400">cap {b.capacity}</div></td>
                                                <td className="px-5 py-2.5 text-slate-600">
                                                    <div className="inline-flex items-center gap-1 text-xs"><CalendarDays className="h-3.5 w-3.5" /> {fmtDate(b.start_date)}</div>
                                                    <div className="text-xs text-slate-400">→ {fmtDate(b.end_date)}</div>
                                                </td>
                                                <td className="px-5 py-2.5 text-slate-600">{b.trainer ?? '—'}<div className="text-xs text-slate-400">{b.venue}</div></td>
                                                <td className="px-5 py-2.5"><span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${BATCH_STATUS[b.status]}`}>{b.status}</span></td>
                                                {canManage && (
                                                    <td className="px-5 py-2.5">
                                                        <div className="flex justify-end gap-1">
                                                            <button onClick={() => setBatchModal({ programId: p.id, batch: b })} className="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600"><Pencil className="h-3.5 w-3.5" /></button>
                                                            <button onClick={() => { if (confirm(`Delete batch “${b.code}”?`)) router.delete(`/batches/${b.id}`, { preserveScroll: true }); }} className="rounded p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-3.5 w-3.5" /></button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="flex items-center justify-between px-5 py-4">
                                <span className="text-sm text-slate-400">No batches scheduled yet.</span>
                                {canManage && (
                                    <button onClick={() => setBatchModal({ programId: p.id, batch: null })} className="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:underline">
                                        <Plus className="h-3.5 w-3.5" /> Add the first batch
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                ))}
                {programs.length === 0 && (
                    <div className="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center text-sm text-slate-400">
                        <GraduationCap className="mx-auto mb-2 h-8 w-8 text-slate-300" />
                        No programs yet.
                    </div>
                )}
            </div>

            {progModal && <ProgramModal program={progModal === 'new' ? null : progModal} options={options} onClose={() => setProgModal(null)} />}
            {batchModal && <BatchModal programId={batchModal.programId} batch={batchModal.batch} options={options} onClose={() => setBatchModal(null)} />}
        </AppShell>
    );
}

function MiniStat({ icon, value, label }: { icon: React.ReactNode; value: number; label: string }) {
    return (
        <div className="flex items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">{icon}</span>
            <span className="text-lg font-semibold leading-none text-slate-800">{value}</span>
            <span className="truncate text-xs font-medium text-slate-400">{label}</span>
        </div>
    );
}

function Tag({ icon, children }: { icon: React.ReactNode; children: React.ReactNode }) {
    return (
        <span className="inline-flex items-center gap-1 rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/70">
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
        title: program?.title ?? '', qualification: program?.qualification ?? '', level: program?.level ?? 'NC II',
        hours: program?.hours ?? 0, fee: program?.fee ?? 1000, slots: program?.slots ?? 25, active: program?.active ?? true,
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onClose };
        isEdit ? put(`/programs/${program!.id}`, opts) : post('/programs', opts);
    };
    return (
        <Modal title={isEdit ? 'Edit program' : 'Add program'} onClose={onClose}>
            <form onSubmit={submit} className="space-y-3">
                <Fld label="Title" error={errors.title}><input className="input" value={data.title} onChange={(e) => setData('title', e.target.value)} autoFocus /></Fld>
                <div className="grid grid-cols-2 gap-3">
                    <Fld label="Qualification / sector"><input className="input" value={data.qualification} onChange={(e) => setData('qualification', e.target.value)} /></Fld>
                    <Fld label="NC level"><select className="input" value={data.level} onChange={(e) => setData('level', e.target.value)}>{options.levels.map((l) => <option key={l}>{l}</option>)}</select></Fld>
                    <Fld label="Training hours" error={errors.hours}><input type="number" className="input" value={data.hours} onChange={(e) => setData('hours', Number(e.target.value))} /></Fld>
                    <Fld label="Misc fee (₱)" error={errors.fee}><input type="number" className="input" value={data.fee} onChange={(e) => setData('fee', Number(e.target.value))} /></Fld>
                    <Fld label="Default slots"><input type="number" className="input" value={data.slots} onChange={(e) => setData('slots', Number(e.target.value))} /></Fld>
                    <label className="flex items-end gap-2 pb-2 text-sm text-slate-600"><input type="checkbox" className="rounded border-slate-300 text-brand-600" checked={data.active} onChange={(e) => setData('active', e.target.checked)} /> Active</label>
                </div>
                <Actions processing={processing} onClose={onClose} label={isEdit ? 'Save' : 'Create'} />
            </form>
        </Modal>
    );
}

function BatchModal({ programId, batch, options, onClose }: { programId: number; batch: Batch | null; options: Options; onClose: () => void }) {
    const isEdit = !!batch;
    const { data, setData, post, put, processing, errors } = useForm({
        program_id: programId, code: batch?.code ?? '2026-A', class_session: batch?.class_session ?? 'Morning',
        class_days: batch?.class_days ?? 'Mon–Fri', school_year: batch?.school_year ?? '2026–2027',
        capacity: batch?.capacity ?? 25, trainer: batch?.trainer ?? '', venue: batch?.venue ?? '',
        start_date: batch?.start_date ? batch.start_date.slice(0, 10) : '', status: batch?.status ?? 'Planned',
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onClose };
        isEdit ? put(`/batches/${batch!.id}`, opts) : post('/batches', opts);
    };
    return (
        <Modal title={isEdit ? 'Edit batch' : 'Add batch'} onClose={onClose}>
            <form onSubmit={submit} className="space-y-3">
                <div className="grid grid-cols-2 gap-3">
                    <Fld label="Batch code" error={errors.code}><input className="input" value={data.code} onChange={(e) => setData('code', e.target.value)} autoFocus /></Fld>
                    <Fld label="School year"><input className="input" value={data.school_year} onChange={(e) => setData('school_year', e.target.value)} /></Fld>
                    <Fld label="Class session"><select className="input" value={data.class_session} onChange={(e) => setData('class_session', e.target.value)}>{options.class_sessions.map((s) => <option key={s}>{s}</option>)}</select></Fld>
                    <Fld label="Class days"><select className="input" value={data.class_days} onChange={(e) => setData('class_days', e.target.value)}>{options.class_days.map((s) => <option key={s}>{s}</option>)}</select></Fld>
                    <Fld label="Start date"><input type="date" className="input" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} /></Fld>
                    <Fld label="Capacity"><input type="number" className="input" value={data.capacity} onChange={(e) => setData('capacity', Number(e.target.value))} /></Fld>
                    <Fld label="Trainer"><input className="input" value={data.trainer} onChange={(e) => setData('trainer', e.target.value)} /></Fld>
                    <Fld label="Venue"><input className="input" value={data.venue} onChange={(e) => setData('venue', e.target.value)} /></Fld>
                    <Fld label="Status"><select className="input" value={data.status} onChange={(e) => setData('status', e.target.value)}>{options.batch_statuses.map((s) => <option key={s}>{s}</option>)}</select></Fld>
                </div>
                <p className="text-xs text-slate-400">End date is auto-computed from program hours, session, and class-days pattern on save.</p>
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
