import { FormEvent, ReactNode, useEffect, useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft, User, MapPin, Phone, IdCard, HeartPulse, Users2, Landmark,
    GraduationCap, ListChecks, LifeBuoy, ShieldCheck, FileSignature, ClipboardList, Sparkles,
    type LucideIcon,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import PhotoCapture from '@/Components/PhotoCapture';

interface Signatory { name: string; title: string }

// Icons + short nav labels for the known built-in categories. Admin-created
// categories fall back to a generic icon and their own label.
const SECTION_ICONS: Record<string, LucideIcon> = {
    'sec-profile': User, 'sec-address': MapPin, 'sec-contact': Phone, 'sec-personal': IdCard,
    'sec-health': HeartPulse, 'sec-family': Users2, 'sec-govids': Landmark, 'sec-course': GraduationCap,
    'sec-classification': ListChecks, 'sec-disability': LifeBuoy, 'sec-additional': Sparkles,
    'sec-consent': ShieldCheck, 'sec-verify': FileSignature,
};
const SECTION_NAV: Record<string, string> = {
    'sec-profile': 'Profile', 'sec-address': 'Address', 'sec-contact': 'Contact', 'sec-personal': 'Personal',
    'sec-health': 'Health', 'sec-family': 'Family', 'sec-govids': 'Gov’t IDs', 'sec-course': 'Course',
    'sec-classification': 'Classification', 'sec-disability': 'Emergency', 'sec-additional': 'Additional',
    'sec-consent': 'Consent', 'sec-verify': 'Verification',
};

interface SectionDef { key: string; label: string; note: string | null }
interface FieldDef {
    kind: 'builtin' | 'custom';
    key: string;
    label: string;
    section: string;
    required: boolean;
    colspan?: 'full' | number | null;
    // built-in
    widget?: string;
    source?: string | null;
    blank?: boolean;
    blankLabel?: string | null;
    placeholder?: string | null;
    signatory?: string | null;
    // custom
    type?: string;
    options?: string[] | null;
}

interface Options {
    programs: { id: number; title: string; level: string | null; training_type: string; fee: number }[];
    sex: string[];
    civil_status: string[];
    emp_status: string[];
    emp_type: string[];
    education: string[];
    regions: string[];
    scholarship: string[];
    class_session: string[];
    blood_types: string[];
    rating: string[];
    disability_types: string[];
    disability_causes: string[];
    classifications: string[];
    education_levels: { key: string; label: string }[];
    education_statuses: string[];
    signatories: { checked_by: Signatory; approved_by: Signatory };
    layout: { sections: SectionDef[]; fields: FieldDef[] };
}

type ApplicantData = Record<string, unknown> & { id?: number };

export default function ApplicantForm({
    applicant,
    options,
}: {
    applicant: ApplicantData | null;
    options: Options;
}) {
    const isEdit = !!applicant;
    const s = (k: string, d = '') => (applicant?.[k] as string | null) ?? d;

    const { data, setData, post, processing, errors } = useForm({
        last_name: s('last_name'),
        first_name: s('first_name'),
        middle_name: s('middle_name'),
        ext_name: s('ext_name'),
        street: s('street'),
        barangay: s('barangay'),
        district: s('district'),
        city: s('city', 'Magsaysay'),
        province: s('province', 'Davao del Sur'),
        region: s('region', 'Region XI (Davao Region)'),
        email: s('email'),
        contact: s('contact'),
        nationality: s('nationality', 'Filipino'),
        religion: s('religion'),
        ethnic_group: s('ethnic_group'),
        sex: s('sex', 'Male'),
        civil_status: s('civil_status', 'Single'),
        emp_status: s('emp_status'),
        emp_type: s('emp_type'),
        employer_name: s('employer_name'),
        employer_position: s('employer_position'),
        birthdate: s('birthdate') ? String(s('birthdate')).slice(0, 10) : '',
        birthplace_city: s('birthplace_city'),
        birthplace_province: s('birthplace_province'),
        birthplace_region: s('birthplace_region'),
        education: s('education', 'High School Graduate'),
        school_last_attended: s('school_last_attended'),
        year_graduated: s('year_graduated'),
        education_history: (applicant?.education_history as Record<string, Record<string, string>>) ?? {},
        guardian_name: s('guardian_name'),
        guardian_address: s('guardian_address'),
        height: s('height'),
        weight: s('weight'),
        blood_type: s('blood_type'),
        eyesight: s('eyesight'),
        hearing: s('hearing'),
        medical: s('medical'),
        father_name: s('father_name'),
        father_occupation: s('father_occupation'),
        mother_name: s('mother_name'),
        mother_occupation: s('mother_occupation'),
        mother_maiden_name: s('mother_maiden_name'),
        family_rank: s('family_rank'),
        siblings: s('siblings'),
        spouse_name: s('spouse_name'),
        spouse_occupation: s('spouse_occupation'),
        children: s('children'),
        program_id: (applicant?.program_id as number) ?? options.programs[0]?.id ?? '',
        scholarship: s('scholarship', 'None'),
        class_session: s('class_session'),
        school_year: s('school_year', String(new Date().getFullYear())),
        classifications: (applicant?.classifications as string[]) ?? [],
        classification_other: s('classification_other'),
        disability_type: s('disability_type'),
        disability_cause: s('disability_cause'),
        emergency_name: s('emergency_name'),
        emergency_relationship: s('emergency_relationship'),
        emergency_contact: s('emergency_contact'),
        emergency_address: s('emergency_address'),
        sss_no: s('sss_no'),
        gsis_no: s('gsis_no'),
        tin_no: s('tin_no'),
        philhealth_no: s('philhealth_no'),
        privacy_consent: (applicant?.privacy_consent as boolean) ?? false,
        remarks: s('remarks'),
        photo: null as File | null,
        // Verification (section 10)
        date_accomplished: s('date_accomplished') ? String(s('date_accomplished')).slice(0, 10) : new Date().toISOString().slice(0, 10),
        date_received: s('date_received') ? String(s('date_received')).slice(0, 10) : new Date().toISOString().slice(0, 10),
        interviewed_by: s('interviewed_by'),
        checked_by: s('checked_by') || options.signatories.checked_by.name,
        approved_by: s('approved_by') || options.signatories.approved_by.name,
        // Admin-defined custom fields (submitted as custom[key], stored in custom_data)
        custom: ((applicant?.custom_data as Record<string, string | number | boolean | null>) ?? {}),
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        // Both store and update routes are POST (multipart for the photo upload).
        post(isEdit ? `/applicants/${applicant!.id}` : '/applicants', { forceFormData: true });
    };

    const toggleClassification = (c: string) => {
        const has = data.classifications.includes(c);
        setData('classifications', has
            ? data.classifications.filter((x) => x !== c)
            : [...data.classifications, c]);
    };

    // Only render categories that actually contain at least one visible field.
    const fieldsBySection = (key: string) => options.layout.fields.filter((f) => f.section === key);
    const sections = useMemo(
        () => options.layout.sections.filter((sec) => fieldsBySection(sec.key).length > 0),
        [options.layout],
    );
    const sectionKeys = sections.map((sec) => sec.key).join(',');

    // Scroll-spy: highlight the category nearest the top of the viewport.
    const [active, setActive] = useState(sections[0]?.key ?? '');
    useEffect(() => {
        const obs = new IntersectionObserver(
            (entries) => entries.forEach((e) => e.isIntersecting && setActive(e.target.id)),
            { rootMargin: '-25% 0px -65% 0px' },
        );
        sections.forEach((sec) => { const el = document.getElementById(sec.key); if (el) obs.observe(el); });
        return () => obs.disconnect();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sectionKeys]);
    const jump = (id: string) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });

    const render = (field: FieldDef) => (
        <FieldRenderer
            key={field.key}
            field={field}
            data={data as Record<string, unknown>}
            setData={setData as (key: string, val: unknown) => void}
            errors={errors as Record<string, string>}
            options={options}
            toggleClassification={toggleClassification}
            photoUrl={s('photo_url') || undefined}
        />
    );

    return (
        <AppShell title={isEdit ? 'Edit applicant' : 'Register applicant'}>
            <Head title={isEdit ? 'Edit applicant' : 'Register applicant'} />

            {/* Header */}
            <div className="mb-5 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-white">
                        <ClipboardList className="h-6 w-6" />
                    </div>
                    <div>
                        <h2 className="text-lg font-semibold text-slate-800">
                            {isEdit ? 'Edit applicant' : 'Register applicant'}
                        </h2>
                        <p className="text-xs text-slate-500">
                            TESDA Learner Profile Form · Maximino Pellerin Sr. Technical &amp; Vocational Institute
                        </p>
                    </div>
                </div>
                <Link href={isEdit ? `/applicants/${applicant!.id}` : '/applicants'} className="btn-ghost self-start sm:self-auto">
                    <ArrowLeft className="h-4 w-4" /> Cancel
                </Link>
            </div>

            <div className="lg:grid lg:grid-cols-[212px_minmax(0,1fr)] lg:items-start lg:gap-6">
                {/* Section navigator */}
                <nav className="sticky top-20 hidden self-start lg:block">
                    <div className="rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                        {sections.map((sec, i) => {
                            const Icon = SECTION_ICONS[sec.key] ?? ClipboardList;
                            const on = active === sec.key;
                            return (
                                <button
                                    key={sec.key}
                                    type="button"
                                    onClick={() => jump(sec.key)}
                                    className={`flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition ${
                                        on ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-500 hover:bg-slate-50'
                                    }`}
                                >
                                    <span className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[11px] font-semibold ${on ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400'}`}>
                                        {i + 1}
                                    </span>
                                    <Icon className="h-4 w-4 shrink-0" />
                                    <span className="truncate">{SECTION_NAV[sec.key] ?? sec.label}</span>
                                </button>
                            );
                        })}
                    </div>
                </nav>

                <form onSubmit={submit} className="space-y-6 pb-28">
                    {sections.map((sec, i) => {
                        const Icon = SECTION_ICONS[sec.key] ?? ClipboardList;
                        return (
                            <section key={sec.key} id={sec.key} className="scroll-mt-24 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div className="flex items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                                    <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                        <Icon className="h-[18px] w-[18px]" />
                                    </div>
                                    <div>
                                        <div className="text-[10px] font-semibold uppercase tracking-wider text-brand-500">Section {i + 1}</div>
                                        <h3 className="text-sm font-semibold text-slate-800">{sec.label}</h3>
                                    </div>
                                </div>
                                {sec.note && <p className="px-5 pt-3 text-center text-sm italic text-slate-500">{sec.note}</p>}
                                <div className="grid grid-cols-2 gap-x-6 gap-y-4 p-5 md:grid-cols-4">
                                    {fieldsBySection(sec.key).map(render)}
                                </div>
                            </section>
                        );
                    })}

                    {/* Sticky action bar */}
                    <div className="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/90 px-4 py-3 shadow-[0_-4px_12px_-6px_rgba(15,23,42,0.15)] backdrop-blur sm:px-6 lg:pl-64">
                        <div className="mx-auto flex max-w-5xl items-center justify-between gap-3">
                            <p className="hidden text-xs text-slate-400 sm:block">
                                <span className="text-rose-500">*</span> Required fields. Review all sections before saving.
                            </p>
                            <div className="flex items-center gap-3">
                                <Link href={isEdit ? `/applicants/${applicant!.id}` : '/applicants'} className="btn-ghost">Cancel</Link>
                                <button type="submit" disabled={processing} className="btn-primary">
                                    {isEdit ? 'Save changes' : 'Register applicant'}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AppShell>
    );
}

// Government-form convention: typed answers appear (and are stored) in ALL
// CAPS. CSS-only while typing — the server uppercases the saved value.
const CAPS = 'uppercase placeholder:normal-case';

function spanClass(c: FieldDef['colspan']): string {
    if (c === 'full') return 'col-span-2 md:col-span-4';
    if (c === 2) return 'md:col-span-2';
    return '';
}

function FieldRenderer({
    field, data, setData, errors, options, toggleClassification, photoUrl,
}: {
    field: FieldDef;
    data: Record<string, unknown>;
    setData: (key: string, val: unknown) => void;
    errors: Record<string, string>;
    options: Options;
    toggleClassification: (c: string) => void;
    photoUrl?: string;
}) {
    const span = spanClass(field.colspan);

    // ---- custom fields (values live under data.custom[key]) ----
    if (field.kind === 'custom') {
        const custom = (data.custom as Record<string, unknown>) ?? {};
        const val = custom[field.key];
        const err = errors[`custom.${field.key}`];
        const set = (v: unknown) => setData('custom', { ...custom, [field.key]: v });

        if (field.type === 'checkbox') {
            return (
                <label className="col-span-2 flex items-center gap-2 text-sm text-slate-600 md:col-span-4">
                    <input type="checkbox" className="rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={!!val} onChange={(e) => set(e.target.checked)} />
                    {field.label}{field.required && <span className="text-rose-500">*</span>}
                </label>
            );
        }
        let input: ReactNode;
        if (field.type === 'textarea') {
            input = <textarea className={`input ${CAPS}`} rows={2} value={(val as string) ?? ''} onChange={(e) => set(e.target.value)} />;
        } else if (field.type === 'select') {
            input = (
                <select className="input" value={(val as string) ?? ''} onChange={(e) => set(e.target.value)}>
                    <option value="">—</option>
                    {(field.options ?? []).map((o) => <option key={o} value={o}>{o}</option>)}
                </select>
            );
        } else {
            const t = field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : 'text';
            input = <input type={t} className={`input ${t === 'text' ? CAPS : ''}`} value={(val as string) ?? ''} onChange={(e) => set(e.target.value)} />;
        }
        return (
            <label className={`block ${span}`}>
                <span className="mb-1 block text-sm font-medium text-slate-700">
                    {field.label} {field.required && <span className="text-rose-500">*</span>}
                </span>
                {input}
                {err && <span className="mt-1 block text-xs text-rose-600">{err}</span>}
            </label>
        );
    }

    // ---- built-in fields (values live at data[key]) ----
    const val = data[field.key];
    const err = errors[field.key];
    const set = (v: unknown) => setData(field.key, v);
    const labelEl = (
        <span className="mb-1 block text-sm font-medium text-slate-700">
            {field.label} {field.required && <span className="text-rose-500">*</span>}
        </span>
    );

    // School year is a single calendar year (trainees graduate every 6 months),
    // so offer a year dropdown from 2026 to next year.
    if (field.key === 'school_year') {
        const now = new Date().getFullYear();
        const years: string[] = [];
        for (let y = 2026; y <= now + 1; y++) years.push(String(y));
        const cur = (val as string) ?? '';
        if (cur && !years.includes(cur)) years.unshift(cur); // keep an existing/edited value visible
        return (
            <label className={`block ${span}`}>
                {labelEl}
                <select className="input" value={cur} onChange={(e) => set(e.target.value)}>
                    {years.map((y) => <option key={y} value={y}>{y}</option>)}
                </select>
                {err && <span className="mt-1 block text-xs text-rose-600">{err}</span>}
            </label>
        );
    }

    switch (field.widget) {
        case 'photo':
            return (
                <div className={span || 'col-span-2 md:col-span-4'}>
                    {labelEl}
                    <PhotoCapture existing={photoUrl} onChange={(f) => set(f)} />
                </div>
            );

        case 'classifications':
            return (
                <div className="col-span-2 md:col-span-4">
                    {labelEl}
                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {options.classifications.map((c) => (
                            <label key={c} className="flex items-start gap-2 text-sm text-slate-600">
                                <input type="checkbox" className="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={(val as string[]).includes(c)} onChange={() => toggleClassification(c)} />
                                {c}
                            </label>
                        ))}
                    </div>
                </div>
            );

        case 'consent':
            return (
                <label className="col-span-2 flex items-start gap-2 text-sm text-slate-600 md:col-span-4">
                    <input type="checkbox" className="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={!!val} onChange={(e) => set(e.target.checked)} />
                    <span>{field.label}{field.required && <span className="text-rose-500">*</span>}</span>
                </label>
            );

        case 'signature': {
            const title = field.signatory ? options.signatories[field.signatory as 'checked_by' | 'approved_by']?.title : null;
            return (
                <div className={span}>
                    <div className="mb-1 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{field.label}</div>
                    <div className="flex h-16 items-end justify-center border-b border-slate-300 pb-1 text-[10px] text-slate-300">
                        signature on printed form
                    </div>
                    <div className="mt-2 pt-2">
                        <input
                            className={`input text-center ${CAPS} ${title ? 'font-semibold' : ''}`}
                            placeholder={title ? undefined : `${field.label} name`}
                            value={(val as string) ?? ''}
                            onChange={(e) => set(e.target.value)}
                        />
                        {title && <div className="mt-1 text-center text-xs italic text-slate-500">{title}</div>}
                    </div>
                </div>
            );
        }

        case 'education_history': {
            const hist = (val as Record<string, Record<string, string>>) ?? {};
            const setCell = (lvl: string, col: string, v: string) =>
                set({ ...hist, [lvl]: { ...(hist[lvl] ?? {}), [col]: v } });
            return (
                <div className="col-span-2 md:col-span-4">
                    {labelEl}
                    <div className="overflow-x-auto rounded-xl border border-slate-200">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th className="px-3 py-2">Level</th>
                                    <th className="px-3 py-2">School / Institution</th>
                                    <th className="w-24 px-3 py-2">Year started</th>
                                    <th className="w-24 px-3 py-2">Year graduated</th>
                                    <th className="w-36 px-3 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {options.education_levels.map((lvl) => {
                                    const row = hist[lvl.key] ?? {};
                                    return (
                                        <tr key={lvl.key} className="align-middle">
                                            <td className="whitespace-nowrap px-3 py-2 font-medium text-slate-600">{lvl.label}</td>
                                            <td className="px-3 py-2">
                                                <input className={`input ${CAPS}`} value={row.school ?? ''} onChange={(e) => setCell(lvl.key, 'school', e.target.value)} placeholder="Name of school" />
                                            </td>
                                            <td className="px-3 py-2">
                                                <input className="input" value={row.started ?? ''} onChange={(e) => setCell(lvl.key, 'started', e.target.value)} placeholder="e.g. 2010" inputMode="numeric" />
                                            </td>
                                            <td className="px-3 py-2">
                                                <input className="input" value={row.graduated ?? ''} onChange={(e) => setCell(lvl.key, 'graduated', e.target.value)} placeholder="e.g. 2016" inputMode="numeric" />
                                            </td>
                                            <td className="px-3 py-2">
                                                <select className="input" value={row.status ?? ''} onChange={(e) => setCell(lvl.key, 'status', e.target.value)}>
                                                    <option value="">—</option>
                                                    {options.education_statuses.map((st) => <option key={st} value={st}>{st}</option>)}
                                                </select>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    <p className="mt-1.5 text-xs text-slate-400">Leave a row blank if it doesn’t apply. “Undergraduate” = attended but did not finish; “Ongoing” = currently enrolled.</p>
                </div>
            );
        }

        case 'program': {
            const school = options.programs.filter((p) => p.training_type !== 'community_based');
            const community = options.programs.filter((p) => p.training_type === 'community_based');
            const selected = options.programs.find((p) => p.id === Number(val));
            const isCommunity = selected?.training_type === 'community_based';
            return (
                <label className={`block ${span || 'md:col-span-2'}`}>
                    {labelEl}
                    <select className="input" value={val as number} onChange={(e) => set(Number(e.target.value))}>
                        {school.length > 0 && (
                            <optgroup label="School-Based Training (with fee)">
                                {school.map((p) => (
                                    <option key={p.id} value={p.id}>{p.title} {p.level ? `(${p.level})` : ''}</option>
                                ))}
                            </optgroup>
                        )}
                        {community.length > 0 && (
                            <optgroup label="Community-Based Training (free · soft skills)">
                                {community.map((p) => (
                                    <option key={p.id} value={p.id}>{p.title} {p.level ? `(${p.level})` : ''}</option>
                                ))}
                            </optgroup>
                        )}
                    </select>
                    {selected && (
                        <span className={`mt-1 block text-xs ${isCommunity ? 'text-emerald-600' : 'text-slate-500'}`}>
                            {isCommunity
                                ? 'Community-Based · free soft-skills training — no payment required.'
                                : `School-Based · training fee ₱${selected.fee.toLocaleString()}.`}
                        </span>
                    )}
                    {err && <span className="mt-1 block text-xs text-rose-600">{err}</span>}
                </label>
            );
        }

        case 'select': {
            const opts = (options[field.source as keyof Options] as string[]) ?? [];
            return (
                <label className={`block ${span}`}>
                    {labelEl}
                    <select className="input" value={(val as string) ?? ''} onChange={(e) => set(e.target.value)}>
                        {field.blank && <option value="">{field.blankLabel ?? '—'}</option>}
                        {opts.map((o) => <option key={o} value={o}>{o}</option>)}
                    </select>
                    {err && <span className="mt-1 block text-xs text-rose-600">{err}</span>}
                </label>
            );
        }

        case 'textarea':
            return (
                <label className={`block ${span}`}>
                    {labelEl}
                    <textarea className={`input ${CAPS}`} rows={2} value={(val as string) ?? ''} onChange={(e) => set(e.target.value)} />
                    {err && <span className="mt-1 block text-xs text-rose-600">{err}</span>}
                </label>
            );

        default: {
            const t = field.widget === 'date' ? 'date' : field.widget === 'number' ? 'number' : field.widget === 'tel' ? 'tel' : 'text';
            const caps = t === 'text' && field.key !== 'email' ? CAPS : '';
            return (
                <label className={`block ${span}`}>
                    {labelEl}
                    <input type={t} className={`input ${caps}`} value={(val as string) ?? ''} placeholder={field.placeholder ?? undefined} onChange={(e) => set(e.target.value)} />
                    {err && <span className="mt-1 block text-xs text-rose-600">{err}</span>}
                </label>
            );
        }
    }
}
