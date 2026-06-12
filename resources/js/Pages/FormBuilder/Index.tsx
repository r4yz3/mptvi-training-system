import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Pencil, Trash2, X, Eye, EyeOff, ListPlus, GripVertical, ArrowLeft, Lock } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Section { id: number; key: string; label: string; enabled: boolean }
interface CustomField {
    id: number; label: string; key: string; type: string;
    options: string[] | null; section: string; required: boolean; enabled: boolean;
    show_in_list: boolean; filterable: boolean; show_on_dashboard: boolean;
}
interface BuiltinField {
    key: string; label: string; default_label: string; section: string;
    locked: boolean; enabled: boolean; required: boolean;
}
interface Props {
    sections: Section[];
    fields: CustomField[];
    builtinFields: BuiltinField[];
    sectionOptions: { value: string; label: string }[];
    fieldTypes: string[];
}

export default function FormBuilderIndex({ sections, fields, builtinFields, sectionOptions, fieldTypes }: Props) {
    const [editing, setEditing] = useState<CustomField | 'new' | null>(null);
    const [editingBuiltin, setEditingBuiltin] = useState<BuiltinField | null>(null);
    const sectionLabel = (key: string) => sectionOptions.find((s) => s.value === key)?.label ?? key;
    const builtinBySection = sectionOptions
        .map((s) => ({ ...s, fields: builtinFields.filter((f) => f.section === s.value) }))
        .filter((s) => s.fields.length > 0);

    return (
        <AppShell title="Form Builder">
            <Head title="Form Builder" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Settings
            </Link>

            <p className="mb-5 max-w-2xl text-sm text-slate-500">
                Customize the applicant registration form. Show or hide the built-in sections, and add your
                own custom fields — they appear on the registration form and the applicant profile.
            </p>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Sections */}
                <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 px-5 py-3.5">
                        <h3 className="text-sm font-semibold text-slate-800">Built-in sections</h3>
                        <p className="text-xs text-slate-400">Toggle which sections appear on the form.</p>
                    </div>
                    <ul className="divide-y divide-slate-100">
                        {sections.map((s) => (
                            <li key={s.id} className="flex items-center justify-between px-5 py-2.5">
                                <span className={`flex items-center gap-2 text-sm ${s.enabled ? 'text-slate-700' : 'text-slate-400 line-through'}`}>
                                    <GripVertical className="h-4 w-4 text-slate-300" /> {s.label}
                                </span>
                                <button
                                    onClick={() => router.put(`/settings/form-builder/sections/${s.id}/toggle`, {}, { preserveScroll: true })}
                                    className={`inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium ${s.enabled ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'}`}
                                >
                                    {s.enabled ? <><Eye className="h-3.5 w-3.5" /> Shown</> : <><EyeOff className="h-3.5 w-3.5" /> Hidden</>}
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>

                {/* Custom fields */}
                <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800">Custom fields</h3>
                            <p className="text-xs text-slate-400">{fields.length} field{fields.length === 1 ? '' : 's'}</p>
                        </div>
                        <button onClick={() => setEditing('new')} className="btn-primary px-3 py-1.5 text-xs">
                            <Plus className="h-3.5 w-3.5" /> Add field
                        </button>
                    </div>
                    {fields.length === 0 ? (
                        <div className="px-5 py-12 text-center text-sm text-slate-400">
                            <ListPlus className="mx-auto mb-2 h-7 w-7 text-slate-300" />
                            No custom fields yet. Add one to extend the form.
                        </div>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {fields.map((f) => (
                                <li key={f.id} className="flex items-center justify-between px-5 py-3">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <span className="truncate text-sm font-medium text-slate-800">{f.label}</span>
                                            {f.required && <span className="rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-medium text-rose-600">Required</span>}
                                            {!f.enabled && <span className="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">Disabled</span>}
                                        </div>
                                        <div className="text-xs text-slate-400">
                                            {f.type}{f.type === 'select' && f.options ? ` (${f.options.length})` : ''} · {sectionLabel(f.section)}
                                        </div>
                                        <div className="mt-1 flex flex-wrap gap-1">
                                            {f.show_in_list && <span className="rounded bg-sky-50 px-1.5 py-0.5 text-[10px] font-medium text-sky-600">In list</span>}
                                            {f.filterable && <span className="rounded bg-violet-50 px-1.5 py-0.5 text-[10px] font-medium text-violet-600">Filterable</span>}
                                            {f.show_on_dashboard && <span className="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-600">Dashboard</span>}
                                        </div>
                                    </div>
                                    <div className="flex shrink-0 gap-1">
                                        <button onClick={() => setEditing(f)} className="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600"><Pencil className="h-4 w-4" /></button>
                                        <button onClick={() => { if (confirm(`Delete field “${f.label}”? Existing values are kept but hidden.`)) router.delete(`/settings/form-builder/fields/${f.id}`, { preserveScroll: true }); }} className="rounded-md p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><Trash2 className="h-4 w-4" /></button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            {/* Built-in fields */}
            <div className="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 px-5 py-3.5">
                    <h3 className="text-sm font-semibold text-slate-800">Built-in form fields</h3>
                    <p className="text-xs text-slate-400">Rename, hide, or require any standard field. Locked fields can be renamed only.</p>
                </div>
                <div className="divide-y divide-slate-100">
                    {builtinBySection.map((sec) => (
                        <div key={sec.value} className="px-5 py-3">
                            <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{sec.label}</div>
                            <div className="grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
                                {sec.fields.map((f) => (
                                    <button
                                        key={f.key}
                                        onClick={() => setEditingBuiltin(f)}
                                        className="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2 text-left text-sm hover:border-brand-200 hover:bg-slate-50"
                                    >
                                        <span className={`truncate ${f.enabled ? 'text-slate-700' : 'text-slate-400 line-through'}`}>{f.label}</span>
                                        <span className="flex shrink-0 items-center gap-1">
                                            {f.required && <span className="rounded bg-rose-50 px-1 py-0.5 text-[10px] font-medium text-rose-600">Req</span>}
                                            {!f.enabled && <span className="rounded bg-slate-100 px-1 py-0.5 text-[10px] text-slate-500">Hidden</span>}
                                            {f.locked && <Lock className="h-3 w-3 text-slate-300" />}
                                            <Pencil className="h-3.5 w-3.5 text-slate-300" />
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {editing && (
                <FieldModal
                    field={editing === 'new' ? null : editing}
                    sectionOptions={sectionOptions}
                    fieldTypes={fieldTypes}
                    onClose={() => setEditing(null)}
                />
            )}
            {editingBuiltin && (
                <BuiltinModal field={editingBuiltin} onClose={() => setEditingBuiltin(null)} />
            )}
        </AppShell>
    );
}

function BuiltinModal({ field, onClose }: { field: BuiltinField; onClose: () => void }) {
    const { data, setData, put, processing, errors } = useForm({
        label: field.label === field.default_label ? '' : field.label,
        enabled: field.enabled,
        required: field.required,
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/settings/form-builder/builtin/${field.key}`, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 className="text-base font-semibold text-slate-800">Edit field</h3>
                        <p className="text-xs text-slate-400">{field.default_label}{field.locked && ' · system field (locked)'}</p>
                    </div>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-3 px-5 py-4">
                    <label className="block">
                        <span className="mb-1 block text-xs font-medium text-slate-600">Label</span>
                        <input className="input" value={data.label} onChange={(e) => setData('label', e.target.value)} placeholder={field.default_label} />
                        {errors.label && <span className="text-xs text-rose-600">{errors.label}</span>}
                    </label>
                    <div className="space-y-2">
                        <label className={`flex items-center gap-2 text-sm ${field.locked ? 'text-slate-300' : 'text-slate-600'}`}>
                            <input type="checkbox" disabled={field.locked} className="rounded border-slate-300 text-brand-600 disabled:opacity-40" checked={data.enabled} onChange={(e) => setData('enabled', e.target.checked)} /> Show this field on the form
                        </label>
                        <label className={`flex items-center gap-2 text-sm ${field.locked ? 'text-slate-300' : 'text-slate-600'}`}>
                            <input type="checkbox" disabled={field.locked} className="rounded border-slate-300 text-brand-600 disabled:opacity-40" checked={data.required} onChange={(e) => setData('required', e.target.checked)} /> Required
                        </label>
                        {field.locked && <p className="text-[11px] text-slate-400">This is a system-critical field — it stays visible &amp; required. You can still rename it.</p>}
                    </div>
                    <div className="flex justify-end gap-2 pt-1">
                        <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function FieldModal({
    field, sectionOptions, fieldTypes, onClose,
}: {
    field: CustomField | null;
    sectionOptions: { value: string; label: string }[];
    fieldTypes: string[];
    onClose: () => void;
}) {
    const isEdit = !!field;
    const { data, setData, post, put, processing, errors } = useForm({
        label: field?.label ?? '',
        type: field?.type ?? 'text',
        section: field?.section ?? 'sec-additional',
        options_text: (field?.options ?? []).join('\n'),
        required: field?.required ?? false,
        enabled: field?.enabled ?? true,
        show_in_list: field?.show_in_list ?? false,
        filterable: field?.filterable ?? false,
        show_on_dashboard: field?.show_on_dashboard ?? false,
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onClose };
        isEdit ? put(`/settings/form-builder/fields/${field!.id}`, opts) : post('/settings/form-builder/fields', opts);
    };
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 className="text-base font-semibold text-slate-800">{isEdit ? 'Edit custom field' : 'Add custom field'}</h3>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                <form onSubmit={submit} className="space-y-3 px-5 py-4">
                    <label className="block">
                        <span className="mb-1 block text-xs font-medium text-slate-600">Field label</span>
                        <input className="input" value={data.label} onChange={(e) => setData('label', e.target.value)} autoFocus />
                        {errors.label && <span className="text-xs text-rose-600">{errors.label}</span>}
                    </label>
                    <div className="grid grid-cols-2 gap-3">
                        <label className="block">
                            <span className="mb-1 block text-xs font-medium text-slate-600">Type</span>
                            <select className="input" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                                {fieldTypes.map((t) => <option key={t} value={t}>{t}</option>)}
                            </select>
                        </label>
                        <label className="block">
                            <span className="mb-1 block text-xs font-medium text-slate-600">Section</span>
                            <select className="input" value={data.section} onChange={(e) => setData('section', e.target.value)}>
                                {sectionOptions.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                            </select>
                        </label>
                    </div>
                    {data.type === 'select' && (
                        <label className="block">
                            <span className="mb-1 block text-xs font-medium text-slate-600">Dropdown options (one per line)</span>
                            <textarea className="input" rows={4} value={data.options_text} onChange={(e) => setData('options_text', e.target.value)} placeholder={'Option A\nOption B'} />
                        </label>
                    )}
                    <div className="flex gap-5 pt-1">
                        <label className="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" className="rounded border-slate-300 text-brand-600" checked={data.required} onChange={(e) => setData('required', e.target.checked)} /> Required
                        </label>
                        <label className="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" className="rounded border-slate-300 text-brand-600" checked={data.enabled} onChange={(e) => setData('enabled', e.target.checked)} /> Enabled
                        </label>
                    </div>
                    <div className="rounded-lg bg-slate-50 p-3">
                        <div className="mb-2 text-xs font-medium text-slate-500">Where else should this field appear?</div>
                        <div className="space-y-2">
                            <label className="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" className="rounded border-slate-300 text-brand-600" checked={data.show_in_list} onChange={(e) => setData('show_in_list', e.target.checked)} /> Show as a column in the Applicants list
                            </label>
                            <label className="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" className="rounded border-slate-300 text-brand-600" checked={data.filterable} onChange={(e) => setData('filterable', e.target.checked)} /> Allow filtering / searching by this field
                            </label>
                            <label className="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" className="rounded border-slate-300 text-brand-600" checked={data.show_on_dashboard} onChange={(e) => setData('show_on_dashboard', e.target.checked)} /> Show a breakdown on the dashboard
                                <span className="text-xs text-slate-400">(dropdown/checkbox)</span>
                            </label>
                        </div>
                        <p className="mt-2 text-[11px] text-slate-400">Custom fields are always included in the Applicants CSV export.</p>
                    </div>
                    <div className="flex justify-end gap-2 pt-2">
                        <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary">{isEdit ? 'Save' : 'Add field'}</button>
                    </div>
                </form>
            </div>
        </div>
    );
}
