import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Plus, Pencil, Trash2, X, Eye, EyeOff, GripVertical, ArrowLeft, Lock,
    ChevronUp, ChevronDown, RotateCcw, FolderPlus, Layers,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Section { id: number; key: string; label: string; note: string | null; enabled: boolean; sort_order: number }
interface CustomField {
    id: number; label: string; key: string; type: string;
    options: string[] | null; section: string; required: boolean; enabled: boolean; position: number | null;
    show_in_list: boolean; filterable: boolean; show_on_dashboard: boolean;
}
interface BuiltinField {
    key: string; label: string; default_label: string; section: string; default_section: string;
    position: number; locked: boolean; enabled: boolean; required: boolean; deleted: boolean; widget: string;
}
interface Props {
    sections: Section[];
    fields: CustomField[];
    builtinFields: BuiltinField[];
    sectionOptions: { value: string; label: string }[];
    fieldTypes: string[];
}

// A unified layout item (built-in or custom) shown inside a category.
interface Item {
    kind: 'builtin' | 'custom';
    key: string;
    label: string;
    locked: boolean;
    enabled: boolean;
    required: boolean;
    typeLabel: string;
}

function buildGroups(sections: Section[], builtin: BuiltinField[], custom: CustomField[]) {
    return sections.map((sec) => {
        const items: (Item & { pos: number })[] = [];
        builtin.filter((b) => b.section === sec.key && !b.deleted).forEach((b) =>
            items.push({ kind: 'builtin', key: b.key, label: b.label, locked: b.locked, enabled: b.enabled, required: b.required, typeLabel: b.widget, pos: b.position }));
        custom.filter((c) => c.section === sec.key).forEach((c) =>
            items.push({ kind: 'custom', key: c.key, label: c.label, locked: false, enabled: c.enabled, required: c.required, typeLabel: c.type, pos: c.position ?? 1000 + c.id }));
        items.sort((a, b) => a.pos - b.pos);
        return { section: sec, items: items.map(({ pos, ...rest }) => rest) };
    });
}

export default function FormBuilderIndex({ sections, fields, builtinFields, sectionOptions, fieldTypes }: Props) {
    const [editingCustom, setEditingCustom] = useState<CustomField | { section: string } | null>(null);
    const [editingBuiltin, setEditingBuiltin] = useState<BuiltinField | null>(null);
    const [editingSection, setEditingSection] = useState<Section | 'new' | null>(null);

    // Local, drag-reorderable copy of the layout. Re-synced whenever the server props change.
    const [groups, setGroups] = useState(() => buildGroups(sections, builtinFields, fields));
    const sig = JSON.stringify([sections, builtinFields.map((b) => [b.key, b.section, b.position, b.deleted, b.label, b.enabled, b.required]), fields.map((f) => [f.key, f.section, f.position, f.label])]);
    useEffect(() => { setGroups(buildGroups(sections, builtinFields, fields)); }, [sig]); // eslint-disable-line react-hooks/exhaustive-deps

    // Mirror the latest layout so onDragEnd persists the committed order, not a stale closure.
    const groupsRef = useRef(groups);
    groupsRef.current = groups;
    const drag = useRef<{ s: number; i: number } | null>(null);

    const persist = (gs: ReturnType<typeof buildGroups>) => {
        const items = gs.flatMap((g) => g.items.map((it, i) => ({ kind: it.kind, key: it.key, section: g.section.key, position: i })));
        router.put('/settings/form-builder/layout', { items }, { preserveScroll: true });
    };

    const moveTo = (targetS: number, targetI: number) => {
        const d = drag.current;
        if (!d) return;
        setGroups((prev) => {
            const next = prev.map((g) => ({ ...g, items: [...g.items] }));
            const [moved] = next[d.s].items.splice(d.i, 1);
            if (!moved) return prev;
            const clampI = Math.min(targetI, next[targetS].items.length);
            next[targetS].items.splice(clampI, 0, moved);
            drag.current = { s: targetS, i: clampI };
            return next;
        });
    };

    const deletedBuiltins = builtinFields.filter((b) => b.deleted);

    const moveSection = (idx: number, dir: -1 | 1) => {
        const order = sections.map((s) => s.id);
        const j = idx + dir;
        if (j < 0 || j >= order.length) return;
        [order[idx], order[j]] = [order[j], order[idx]];
        router.put('/settings/form-builder/sections-reorder', { order }, { preserveScroll: true });
    };

    return (
        <AppShell title="Form Builder">
            <Head title="Form Builder" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Settings
            </Link>

            <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <p className="max-w-2xl text-sm text-slate-500">
                    Build the applicant registration form exactly how you want it. Add categories, rename or hide any
                    field, mark fields required, <strong>drag fields to reorder or move them between categories</strong>,
                    and delete anything you don’t need. Locked fields stay required for the system to work.
                </p>
                <button onClick={() => setEditingSection('new')} className="btn-primary shrink-0 px-3 py-2 text-sm">
                    <FolderPlus className="h-4 w-4" /> Add category
                </button>
            </div>

            <div className="space-y-4">
                {groups.map((g, si) => (
                    <div key={g.section.key} className="rounded-xl border border-slate-200 bg-white shadow-sm">
                        {/* Category header */}
                        <div className="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                            <div className="flex min-w-0 items-center gap-2">
                                <div className="flex flex-col">
                                    <button onClick={() => moveSection(si, -1)} disabled={si === 0} className="text-slate-300 hover:text-brand-600 disabled:opacity-30"><ChevronUp className="h-4 w-4" /></button>
                                    <button onClick={() => moveSection(si, 1)} disabled={si === groups.length - 1} className="text-slate-300 hover:text-brand-600 disabled:opacity-30"><ChevronDown className="h-4 w-4" /></button>
                                </div>
                                <Layers className="h-4 w-4 shrink-0 text-brand-500" />
                                <div className="min-w-0">
                                    <h3 className={`truncate text-sm font-semibold ${g.section.enabled ? 'text-slate-800' : 'text-slate-400 line-through'}`}>{g.section.label}</h3>
                                    {g.section.note && <p className="truncate text-xs text-slate-400">{g.section.note}</p>}
                                </div>
                            </div>
                            <div className="flex shrink-0 items-center gap-1">
                                <button
                                    onClick={() => router.put(`/settings/form-builder/sections/${g.section.id}/toggle`, {}, { preserveScroll: true })}
                                    className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ${g.section.enabled ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'}`}
                                >
                                    {g.section.enabled ? <><Eye className="h-3.5 w-3.5" /> Shown</> : <><EyeOff className="h-3.5 w-3.5" /> Hidden</>}
                                </button>
                                <button onClick={() => setEditingSection(g.section)} className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600"><Pencil className="h-4 w-4" /></button>
                                <button
                                    onClick={() => { if (confirm(`Delete category “${g.section.label}”? Its fields move to the first category.`)) router.delete(`/settings/form-builder/sections/${g.section.id}`, { preserveScroll: true }); }}
                                    className="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                ><Trash2 className="h-4 w-4" /></button>
                            </div>
                        </div>

                        {/* Fields (drag to reorder / move) */}
                        <ul
                            className="min-h-[44px] divide-y divide-slate-50 px-2 py-2"
                            onDragOver={(e) => e.preventDefault()}
                            onDragEnter={() => g.items.length === 0 && moveTo(si, 0)}
                        >
                            {g.items.length === 0 && (
                                <li className="px-3 py-2 text-center text-xs text-slate-300">Drop fields here, or add a custom field below.</li>
                            )}
                            {g.items.map((it, ii) => (
                                <li
                                    key={`${it.kind}:${it.key}`}
                                    draggable
                                    onDragStart={() => { drag.current = { s: si, i: ii }; }}
                                    onDragEnter={() => moveTo(si, ii)}
                                    onDragOver={(e) => e.preventDefault()}
                                    onDragEnd={() => { persist(groupsRef.current); drag.current = null; }}
                                    className="group flex cursor-grab items-center justify-between gap-2 rounded-lg px-2 py-2 hover:bg-slate-50 active:cursor-grabbing"
                                >
                                    <div className="flex min-w-0 items-center gap-2">
                                        <GripVertical className="h-4 w-4 shrink-0 text-slate-300" />
                                        <span className={`truncate text-sm ${it.enabled ? 'text-slate-700' : 'text-slate-400 line-through'}`}>{it.label}</span>
                                        {it.required && <span className="shrink-0 rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-medium text-rose-600">Req</span>}
                                        {!it.enabled && <span className="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">Hidden</span>}
                                        {it.locked && <Lock className="h-3 w-3 shrink-0 text-slate-300" />}
                                        <span className="shrink-0 rounded bg-slate-50 px-1.5 py-0.5 text-[10px] text-slate-400">{it.typeLabel}{it.kind === 'custom' ? ' · custom' : ''}</span>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-0.5 opacity-0 transition group-hover:opacity-100">
                                        <button
                                            onClick={() => it.kind === 'builtin'
                                                ? setEditingBuiltin(builtinFields.find((b) => b.key === it.key)!)
                                                : setEditingCustom(fields.find((f) => f.key === it.key)!)}
                                            className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600"
                                        ><Pencil className="h-4 w-4" /></button>
                                        {it.kind === 'custom' ? (
                                            <button
                                                onClick={() => { if (confirm(`Delete field “${it.label}”? Existing values are kept but hidden.`)) router.delete(`/settings/form-builder/fields/${fields.find((f) => f.key === it.key)!.id}`, { preserveScroll: true }); }}
                                                className="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                            ><Trash2 className="h-4 w-4" /></button>
                                        ) : !it.locked ? (
                                            <button
                                                onClick={() => { if (confirm(`Remove field “${it.label}” from the form?`)) router.delete(`/settings/form-builder/builtin/${it.key}`, { preserveScroll: true }); }}
                                                className="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                            ><Trash2 className="h-4 w-4" /></button>
                                        ) : null}
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <div className="border-t border-slate-50 px-3 py-2">
                            <button onClick={() => setEditingCustom({ section: g.section.key })} className="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                                <Plus className="h-3.5 w-3.5" /> Add custom field to this category
                            </button>
                        </div>
                    </div>
                ))}
            </div>

            {/* Removed built-in fields — restore */}
            {deletedBuiltins.length > 0 && (
                <div className="mt-6 rounded-xl border border-slate-200 bg-slate-50/60 shadow-sm">
                    <div className="border-b border-slate-100 px-5 py-3">
                        <h3 className="text-sm font-semibold text-slate-700">Removed fields</h3>
                        <p className="text-xs text-slate-400">These standard fields are off the form. Restore any to bring it back.</p>
                    </div>
                    <div className="flex flex-wrap gap-2 p-4">
                        {deletedBuiltins.map((b) => (
                            <button
                                key={b.key}
                                onClick={() => router.put(`/settings/form-builder/builtin/${b.key}/restore`, {}, { preserveScroll: true })}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 hover:border-brand-200 hover:text-brand-600"
                            >
                                <RotateCcw className="h-3.5 w-3.5" /> {b.label}
                            </button>
                        ))}
                    </div>
                </div>
            )}

            {editingSection && (
                <SectionModal section={editingSection === 'new' ? null : editingSection} onClose={() => setEditingSection(null)} />
            )}
            {editingBuiltin && (
                <BuiltinModal field={editingBuiltin} sectionOptions={sectionOptions} onClose={() => setEditingBuiltin(null)} />
            )}
            {editingCustom && (
                <FieldModal
                    field={'id' in editingCustom ? editingCustom : null}
                    presetSection={'id' in editingCustom ? editingCustom.section : editingCustom.section}
                    sectionOptions={sectionOptions}
                    fieldTypes={fieldTypes}
                    onClose={() => setEditingCustom(null)}
                />
            )}
        </AppShell>
    );
}

function SectionModal({ section, onClose }: { section: Section | null; onClose: () => void }) {
    const isEdit = !!section;
    const { data, setData, post, put, processing, errors } = useForm({
        label: section?.label ?? '',
        note: section?.note ?? '',
        enabled: section?.enabled ?? true,
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onClose };
        isEdit ? put(`/settings/form-builder/sections/${section!.id}`, opts) : post('/settings/form-builder/sections', opts);
    };
    return (
        <Modal title={isEdit ? 'Edit category' : 'Add category'} onClose={onClose}>
            <form onSubmit={submit} className="space-y-3 px-5 py-4">
                <label className="block">
                    <span className="mb-1 block text-xs font-medium text-slate-600">Category name</span>
                    <input className="input" value={data.label} onChange={(e) => setData('label', e.target.value)} autoFocus />
                    {errors.label && <span className="text-xs text-rose-600">{errors.label}</span>}
                </label>
                {isEdit && (
                    <label className="block">
                        <span className="mb-1 block text-xs font-medium text-slate-600">Helper text (optional)</span>
                        <textarea className="input" rows={2} value={data.note} onChange={(e) => setData('note', e.target.value)} placeholder="Shown under the category heading on the form" />
                    </label>
                )}
                <div className="flex justify-end gap-2 pt-1">
                    <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                    <button type="submit" disabled={processing} className="btn-primary">{isEdit ? 'Save' : 'Add category'}</button>
                </div>
            </form>
        </Modal>
    );
}

function BuiltinModal({ field, sectionOptions, onClose }: { field: BuiltinField; sectionOptions: { value: string; label: string }[]; onClose: () => void }) {
    const { data, setData, put, processing, errors } = useForm({
        label: field.label === field.default_label ? '' : field.label,
        section: field.section,
        enabled: field.enabled,
        required: field.required,
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/settings/form-builder/builtin/${field.key}`, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal title="Edit field" subtitle={`${field.default_label}${field.locked ? ' · system field (locked)' : ''}`} onClose={onClose}>
            <form onSubmit={submit} className="space-y-3 px-5 py-4">
                <label className="block">
                    <span className="mb-1 block text-xs font-medium text-slate-600">Label</span>
                    <input className="input" value={data.label} onChange={(e) => setData('label', e.target.value)} placeholder={field.default_label} />
                    {errors.label && <span className="text-xs text-rose-600">{errors.label}</span>}
                </label>
                <label className="block">
                    <span className="mb-1 block text-xs font-medium text-slate-600">Category</span>
                    <select className="input" value={data.section} onChange={(e) => setData('section', e.target.value)}>
                        {sectionOptions.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                    </select>
                </label>
                <div className="space-y-2">
                    <label className={`flex items-center gap-2 text-sm ${field.locked ? 'text-slate-300' : 'text-slate-600'}`}>
                        <input type="checkbox" disabled={field.locked} className="rounded border-slate-300 text-brand-600 disabled:opacity-40" checked={data.enabled} onChange={(e) => setData('enabled', e.target.checked)} /> Show this field on the form
                    </label>
                    <label className={`flex items-center gap-2 text-sm ${field.locked ? 'text-slate-300' : 'text-slate-600'}`}>
                        <input type="checkbox" disabled={field.locked} className="rounded border-slate-300 text-brand-600 disabled:opacity-40" checked={data.required} onChange={(e) => setData('required', e.target.checked)} /> Required
                    </label>
                    {field.locked && <p className="text-[11px] text-slate-400">This is a system-critical field — it stays visible &amp; required. You can still rename it and move it to another category.</p>}
                </div>
                <div className="flex justify-end gap-2 pt-1">
                    <button type="button" onClick={onClose} className="btn-ghost">Cancel</button>
                    <button type="submit" disabled={processing} className="btn-primary">Save</button>
                </div>
            </form>
        </Modal>
    );
}

function FieldModal({
    field, presetSection, sectionOptions, fieldTypes, onClose,
}: {
    field: CustomField | null;
    presetSection: string;
    sectionOptions: { value: string; label: string }[];
    fieldTypes: string[];
    onClose: () => void;
}) {
    const isEdit = !!field;
    const { data, setData, post, put, processing, errors } = useForm({
        label: field?.label ?? '',
        type: field?.type ?? 'text',
        section: field?.section ?? presetSection,
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
        <Modal title={isEdit ? 'Edit custom field' : 'Add custom field'} onClose={onClose}>
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
                        <span className="mb-1 block text-xs font-medium text-slate-600">Category</span>
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
        </Modal>
    );
}

function Modal({ title, subtitle, onClose, children }: { title: string; subtitle?: string; onClose: () => void; children: React.ReactNode }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md rounded-xl bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 className="text-base font-semibold text-slate-800">{title}</h3>
                        {subtitle && <p className="text-xs text-slate-400">{subtitle}</p>}
                    </div>
                    <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100"><X className="h-5 w-5" /></button>
                </div>
                {children}
            </div>
        </div>
    );
}
