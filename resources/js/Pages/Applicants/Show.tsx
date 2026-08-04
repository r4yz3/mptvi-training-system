import { Fragment, ReactNode, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft, Pencil, Trash2, Lock, UserCircle2, Power, Printer, Check, XCircle,
    CalendarDays, MapPin, Phone, User, IdCard, HeartPulse, Users2, Landmark, ClipboardList,
    GraduationCap, ListChecks, LifeBuoy, FileSignature, Sparkles, ShieldCheck, Banknote, Award, type LucideIcon,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import StatusBadge from '@/Components/StatusBadge';
import TraineeStatusBadge from '@/Components/TraineeStatusBadge';
import DocumentChecklist from '@/Components/DocumentChecklist';
import { PageProps } from '@/types';

const STAGES = ['Registered', 'Enrolled', 'In training'];

// Keyed by category key (same map the registration form uses), so renamed or
// admin-created categories still get a sensible icon.
const SECTION_ICON: Record<string, LucideIcon> = {
    'sec-profile': User, 'sec-address': MapPin, 'sec-contact': Phone, 'sec-personal': IdCard,
    'sec-health': HeartPulse, 'sec-family': Users2, 'sec-govids': Landmark, 'sec-course': GraduationCap,
    'sec-classification': ListChecks, 'sec-disability': LifeBuoy, 'sec-additional': Sparkles,
    'sec-consent': ShieldCheck, 'sec-verify': FileSignature,
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
    signatory?: string | null;
    // custom
    type?: string;
    options?: string[] | null;
}
interface Signatory { name: string; title: string }
interface Layout { sections: SectionDef[]; fields: FieldDef[] }

interface Applicant {
    id: number;
    display_name: string;
    photo_url: string | null;
    active: boolean;
    status: string;
    trainee_status: string | null;
    class_session: string | null;
    school_year: string | null;
    program: { id?: number; title: string; level: string | null } | null;
    // full-only fields (present when pii)
    [key: string]: unknown;
}

interface DocItem {
    key: number; label: string; copies: number; status: string; note: string;
}
interface CustomFieldDef { key: string; label: string; type: string; section: string }

interface SubjectRow { subject_id: number; code: string | null; title: string; category: string; units: number; grade: number | null; remark: string | null; graded_at: string | null }
interface GradeInfo { subjects: SubjectRow[]; total: number; graded: number; major_gwa: number | null; minor_gwa: number | null; gwa: number | null; complete: boolean; remark: string }

interface FeeRow { category: string; expected: number; paid: number; balance: number; status: string }
interface Fees { school_year: string | null; misc: FeeRow; extras: FeeRow[] }

export default function ApplicantShow({
    applicant, pii, documents, canVerifyDocs, traineeStatuses, eduLevels, gradeInfo, canGrade, assessmentResult, fees, layout, signatories,
}: {
    applicant: Applicant;
    pii: boolean;
    documents: DocItem[] | null;
    canVerifyDocs: boolean;
    customFields: CustomFieldDef[] | null;
    traineeStatuses: string[];
    eduLevels: { key: string; label: string }[];
    gradeInfo: GradeInfo;
    canGrade: boolean;
    assessmentResult: string | null;
    fees: Fees | null;
    layout: Layout | null;
    signatories: { checked_by: Signatory; approved_by: Signatory } | null;
}) {
    const { auth } = usePage<PageProps>().props;
    const toggle = useForm({});
    const del = useForm({});

    const setTraineeStatus = (value: string) =>
        router.put(`/applicants/${applicant.id}/trainee-status`, { trainee_status: value }, { preserveScroll: true });

    const onToggle = () =>
        toggle.put(`/applicants/${applicant.id}/active`, { preserveScroll: true });

    const onDelete = () => {
        if (confirm(`Delete applicant “${applicant.display_name}”? This cannot be undone.`)) {
            del.delete(`/applicants/${applicant.id}`);
        }
    };

    return (
        <AppShell title="Applicant profile">
            <Head title={applicant.display_name} />

            <Link href="/applicants" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to applicants
            </Link>

            {/* Hero header */}
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="h-1.5 bg-gradient-to-r from-brand-600 to-brand-400" />
                <div className="p-5 sm:p-6">
                    <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div className="flex gap-4">
                            {applicant.photo_url ? (
                                <img src={applicant.photo_url} alt="" className="h-20 w-20 rounded-2xl object-cover ring-2 ring-slate-100" />
                            ) : (
                                <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-100"><UserCircle2 className="h-12 w-12 text-slate-300" /></div>
                            )}
                            <div className="min-w-0">
                                <h2 className="text-xl font-semibold text-slate-800">{applicant.display_name}</h2>
                                <div className="mt-1.5 flex flex-wrap items-center gap-2">
                                    <StatusBadge status={applicant.status} />
                                    {!applicant.active && (
                                        <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Inactive</span>
                                    )}
                                    <TraineeStatusBadge status={applicant.trainee_status} />
                                </div>
                                {applicant.program && (
                                    <div className="mt-1.5 flex items-center gap-1.5 text-sm text-slate-600">
                                        <GraduationCap className="h-4 w-4 text-slate-400" />
                                        {applicant.program.title}
                                        {applicant.program.level && <span className="rounded bg-brand-50 px-1.5 py-0.5 text-xs font-medium text-brand-700">{applicant.program.level}</span>}
                                    </div>
                                )}
                                {pii && (
                                    <div className="mt-3 flex flex-wrap gap-1.5">
                                        <Pill icon={CalendarDays} value={applicant.age ? `${applicant.age} yrs` : null} />
                                        <Pill icon={User} value={applicant.sex as string | null} />
                                        <Pill icon={MapPin} value={applicant.barangay as string | null} />
                                        <Pill icon={Phone} value={applicant.contact as string | null} />
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            {auth.can['trainee.status'] && (
                                <label className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm shadow-sm">
                                    <GraduationCap className="h-4 w-4 text-slate-400" />
                                    <span className="text-xs font-medium text-slate-500">Training:</span>
                                    <select
                                        value={applicant.trainee_status ?? ''}
                                        onChange={(e) => setTraineeStatus(e.target.value)}
                                        className="border-0 bg-transparent p-0 pr-6 text-sm font-medium text-slate-700 focus:ring-0"
                                    >
                                        <option value="">Not set</option>
                                        {traineeStatuses.map((s) => <option key={s} value={s}>{s}</option>)}
                                    </select>
                                </label>
                            )}
                            {pii && (
                                <a href={`/applicants/${applicant.id}/print`} target="_blank" rel="noopener noreferrer" className="btn-ghost">
                                    <Printer className="h-4 w-4" /> Print form
                                </a>
                            )}
                            {auth.can['active'] && (
                                <button onClick={onToggle} disabled={toggle.processing} className="btn-ghost">
                                    <Power className="h-4 w-4" /> {applicant.active ? 'Deactivate' : 'Activate'}
                                </button>
                            )}
                            {auth.can['applicant.edit'] && (
                                <Link href={`/applicants/${applicant.id}/edit`} className="btn-ghost">
                                    <Pencil className="h-4 w-4" /> Edit
                                </Link>
                            )}
                            {auth.can['applicant.delete'] && (
                                <button onClick={onDelete} disabled={del.processing} className="btn-ghost text-rose-600 hover:bg-rose-50">
                                    <Trash2 className="h-4 w-4" /> Delete
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Pipeline progress */}
            <div className="mt-6">
                <PipelineStepper status={applicant.status} />
            </div>

            {/* Assessment result — manually set by admin / registrar */}
            <div className="mt-6">
                <AssessmentControl applicantId={applicant.id} result={assessmentResult} canAssess={canGrade} />
            </div>

            {/* Training grades — job data, visible to pii and non-pii roles alike */}
            <div className="mt-6">
                <GradesPanel info={gradeInfo} canGrade={canGrade} applicantId={applicant.id} />
            </div>

            {fees && (
                <div className="mt-6">
                    <FeesPanel fees={fees} />
                </div>
            )}

            {pii ? (
                <>
                    <FullProfile a={applicant} layout={layout} eduLevels={eduLevels} signatories={signatories} />
                    {documents && (
                        <div className="mt-6">
                            <DocumentChecklist
                                applicantId={applicant.id}
                                documents={documents}
                                canVerify={canVerifyDocs}
                            />
                        </div>
                    )}
                </>
            ) : (
                <LimitedNotice a={applicant} />
            )}
        </AppShell>
    );
}

function LimitedNotice({ a }: { a: Applicant }) {
    return (
        <div className="mt-6 space-y-6">
            <Section title="Enrollment">
                <Field label="Program">{a.program?.title ?? '—'}</Field>
                <Field label="NC level">{a.program?.level ?? '—'}</Field>
                <Field label="Class session">{a.class_session ?? '—'}</Field>
                <Field label="School year">{a.school_year ?? '—'}</Field>
            </Section>
            <div className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <Lock className="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <div className="font-medium">Personal information is restricted</div>
                    <p className="mt-0.5 text-amber-700">
                        Your role can see enrollment details only. Address, contact, birth
                        information, family, health, classification and documents are hidden under
                        the Data Privacy Act (R.A. 10173).
                    </p>
                </div>
            </div>
        </div>
    );
}

/**
 * Renders the profile straight from the admin-configured form layout, so the
 * categories, labels, order and set of fields match the registration form
 * exactly — hide, rename, move or add a field in the Form Builder and it
 * changes here too, with no code change.
 */
function FullProfile({ a, layout, eduLevels, signatories }: {
    a: Applicant;
    layout: Layout | null;
    eduLevels: { key: string; label: string }[];
    signatories: { checked_by: Signatory; approved_by: Signatory } | null;
}) {
    if (!layout) return null;

    // The 2×2 photo is already shown in the header, so it isn't repeated here.
    const visible = layout.fields.filter((f) => f.widget !== 'photo');
    const fieldsOf = (key: string) => visible.filter((f) => f.section === key);
    const sections = layout.sections.filter((sec) => fieldsOf(sec.key).length > 0);

    return (
        <div className="mt-6 space-y-6">
            {sections.map((sec) => (
                <Section key={sec.key} sectionKey={sec.key} title={sec.label} note={sec.note}>
                    {fieldsOf(sec.key).map((f) => (
                        <FieldValue key={f.key} field={f} a={a} eduLevels={eduLevels} signatories={signatories} />
                    ))}
                </Section>
            ))}
        </div>
    );
}

/** Mirrors the form's colspan rules so the read view lines up with the inputs. */
function spanClass(c: FieldDef['colspan']): string {
    if (c === 'full') return 'col-span-2 md:col-span-4';
    if (c === 2) return 'md:col-span-2';
    return '';
}

/** Read-only counterpart of the form's FieldRenderer — one widget per case. */
function FieldValue({ field, a, eduLevels, signatories }: {
    field: FieldDef;
    a: Applicant;
    eduLevels: { key: string; label: string }[];
    signatories: { checked_by: Signatory; approved_by: Signatory } | null;
}) {
    const span = spanClass(field.colspan);

    // Custom fields live in custom_data, same as the form reads them.
    if (field.kind === 'custom') {
        const custom = (a.custom_data as Record<string, unknown> | null) ?? {};
        const val = custom[field.key];
        const text = typeof val === 'boolean'
            ? (val ? 'Yes' : 'No')
            : (val == null || val === '' ? '—' : String(val));
        return <Field label={field.label} span={span}>{text}</Field>;
    }

    const raw = a[field.key];
    const text = raw == null || raw === '' ? '—' : String(raw);

    switch (field.widget) {
        case 'program':
            return (
                <Field label={field.label} span={span || 'md:col-span-2'}>
                    {a.program
                        ? <>{a.program.title}{a.program.level && <span className="ml-1.5 rounded bg-brand-50 px-1.5 py-0.5 text-xs font-medium text-brand-700">{a.program.level}</span>}</>
                        : '—'}
                </Field>
            );

        case 'classifications': {
            const list = (raw as string[] | null) ?? [];
            return (
                <div className={span || 'col-span-2 md:col-span-4'}>
                    <div className="text-xs font-medium text-slate-400">{field.label}</div>
                    <div className="mt-1 flex flex-wrap gap-2">
                        {list.length > 0
                            ? list.map((c) => (
                                <span key={c} className="rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-sky-700">{c}</span>
                            ))
                            : <span className="text-sm text-slate-400">None</span>}
                    </div>
                </div>
            );
        }

        case 'consent':
            return (
                <div className={span || 'col-span-2 md:col-span-4'}>
                    <div className="flex items-start gap-2 text-sm text-slate-600">
                        {raw
                            ? <Check className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                            : <XCircle className="mt-0.5 h-4 w-4 shrink-0 text-slate-300" />}
                        <span className={raw ? '' : 'text-slate-400'}>{field.label}</span>
                    </div>
                </div>
            );

        case 'education_history':
            return (
                <EducationBackground
                    label={field.label}
                    levels={eduLevels}
                    history={(raw as Record<string, Record<string, string>> | null) ?? {}}
                />
            );

        case 'signature': {
            const title = field.signatory && signatories
                ? signatories[field.signatory as 'checked_by' | 'approved_by']?.title
                : null;
            return (
                <div className={span}>
                    <div className="mb-1 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">{field.label}</div>
                    <div className="flex h-16 items-end justify-center border-b border-slate-200 pb-1 text-[10px] text-slate-300">
                        signature on printed form
                    </div>
                    {text !== '—' && (
                        <div className="mt-1 pt-1 text-center text-xs font-medium text-slate-600">{text}</div>
                    )}
                    {title && <div className="mt-0.5 text-center text-[11px] italic text-slate-400">{title}</div>}
                </div>
            );
        }

        case 'date':
            return <Field label={field.label} span={span}>{text.slice(0, 10)}</Field>;

        case 'textarea':
            return (
                <div className={span || 'col-span-2 md:col-span-4'}>
                    <div className="text-xs font-medium text-slate-400">{field.label}</div>
                    <p className="mt-0.5 whitespace-pre-line text-sm font-medium text-slate-800">{text}</p>
                </div>
            );

        default:
            return <Field label={field.label} span={span}>{text}</Field>;
    }
}

function Pill({ icon: Icon, value }: { icon: LucideIcon; value: string | null }) {
    if (!value) return null;
    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/70">
            <Icon className="h-3.5 w-3.5 text-slate-400" /> {value}
        </span>
    );
}

function PipelineStepper({ status }: { status: string }) {
    if (status === 'Disqualified') {
        return (
            <div className="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <XCircle className="h-5 w-5 shrink-0" />
                <span className="font-medium">This applicant was disqualified during screening.</span>
            </div>
        );
    }
    const idx = STAGES.indexOf(status);
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Pipeline progress</h3>
            <div className="flex items-start overflow-x-auto pb-1">
                {STAGES.map((st, i) => {
                    const done = i < idx;
                    const current = i === idx;
                    return (
                        <Fragment key={st}>
                            <div className="flex shrink-0 flex-col items-center" style={{ minWidth: 84 }}>
                                <div className={`flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold ${
                                    done ? 'bg-brand-600 text-white' : current ? 'bg-brand-100 text-brand-700 ring-2 ring-brand-500' : 'bg-slate-100 text-slate-400'
                                }`}>
                                    {done ? <Check className="h-4 w-4" /> : i + 1}
                                </div>
                                <span className={`mt-1.5 text-center text-[11px] leading-tight ${current ? 'font-semibold text-brand-700' : done ? 'text-slate-600' : 'text-slate-400'}`}>{st}</span>
                            </div>
                            {i < STAGES.length - 1 && (
                                <div className={`mt-4 h-0.5 flex-1 ${i < idx ? 'bg-brand-500' : 'bg-slate-200'}`} style={{ minWidth: 16 }} />
                            )}
                        </Fragment>
                    );
                })}
            </div>
        </div>
    );
}

const EDU_STATUS_STYLE: Record<string, string> = {
    Graduate: 'bg-emerald-50 text-emerald-700',
    Undergraduate: 'bg-amber-50 text-amber-700',
    Ongoing: 'bg-sky-50 text-sky-700',
};

const CAT_STYLE: Record<string, string> = {
    Major: 'bg-indigo-50 text-indigo-700',
    Minor: 'bg-sky-50 text-sky-700',
};

function AssessmentControl({ applicantId, result, canAssess }: { applicantId: number; result: string | null; canAssess: boolean }) {
    const set = (value: string | null) =>
        router.put(`/applicants/${applicantId}/assessment`, { assessment_result: value }, { preserveScroll: true });

    const tone = result === 'Competent'
        ? 'bg-emerald-50 text-emerald-700'
        : result === 'Not Yet Competent' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500';

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5">
                <div className="flex items-center gap-2.5">
                    <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Award className="h-4 w-4" /></span>
                    <div>
                        <h3 className="text-sm font-semibold text-slate-700">Assessment result</h3>
                        <p className="text-xs text-slate-400">Set by admin / registrar after the competency assessment.</p>
                    </div>
                </div>
                {canAssess ? (
                    <div className="flex flex-wrap items-center gap-2">
                        <button
                            onClick={() => set('Competent')}
                            className={`rounded-lg px-3 py-1.5 text-xs font-medium ring-1 ring-inset ${result === 'Competent' ? 'bg-emerald-600 text-white ring-emerald-600' : 'bg-white text-emerald-700 ring-emerald-200 hover:bg-emerald-50'}`}
                        >
                            Competent
                        </button>
                        <button
                            onClick={() => set('Not Yet Competent')}
                            className={`rounded-lg px-3 py-1.5 text-xs font-medium ring-1 ring-inset ${result === 'Not Yet Competent' ? 'bg-amber-500 text-white ring-amber-500' : 'bg-white text-amber-700 ring-amber-200 hover:bg-amber-50'}`}
                        >
                            Not Yet Competent
                        </button>
                        {result && (
                            <button onClick={() => set(null)} className="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100">
                                Clear
                            </button>
                        )}
                    </div>
                ) : (
                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${tone}`}>{result ?? 'Not yet assessed'}</span>
                )}
            </div>
        </div>
    );
}

function GradesPanel({ info, canGrade, applicantId }: { info: GradeInfo; canGrade: boolean; applicantId: number }) {
    const [editing, setEditing] = useState(false);
    const [grades, setGrades] = useState<Record<number, string>>(() =>
        Object.fromEntries(info.subjects.map((s) => [s.subject_id, s.grade !== null ? s.grade.toFixed(2) : ''])));
    const save = useForm({});

    if (info.total === 0) return null; // no subjects defined / not a trainee — keep the profile clean

    const fmt = (g: number | null) => (g === null ? '—' : g.toFixed(2));
    const remarkTone = info.remark === 'Passed' ? 'bg-emerald-50 text-emerald-700'
        : info.remark === 'Failed' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-500';

    const submit = () => {
        save.transform(() => ({
            graded_at: new Date().toISOString().slice(0, 10),
            grades: info.subjects.map((s) => ({ subject_id: s.subject_id, grade: grades[s.subject_id] || null })),
        }));
        save.put(`/applicants/${applicantId}/grades`, { preserveScroll: true, onSuccess: () => setEditing(false) });
    };

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                <div className="flex items-center gap-2.5">
                    <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><GraduationCap className="h-4 w-4" /></span>
                    <h3 className="text-sm font-semibold text-slate-700">Grades <span className="font-normal text-slate-400">· GWA {fmt(info.gwa)}</span></h3>
                </div>
                <div className="flex items-center gap-2">
                    <span className="text-xs text-slate-500">Major {fmt(info.major_gwa)} · Minor {fmt(info.minor_gwa)}</span>
                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${remarkTone}`}>{info.remark}</span>
                    <a href={`/applicants/${applicantId}/grade-slip`} target="_blank" rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <Printer className="h-3.5 w-3.5" /> Grade slip
                    </a>
                    {canGrade && !editing && (
                        <button onClick={() => setEditing(true)} className="inline-flex items-center gap-1 rounded-md bg-brand-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-brand-700">
                            <Pencil className="h-3.5 w-3.5" /> Enter grades
                        </button>
                    )}
                </div>
            </div>
            <div className="divide-y divide-slate-50">
                {info.subjects.map((s) => (
                    <div key={s.subject_id} className="flex items-center justify-between gap-3 px-5 py-2.5">
                        <div className="flex min-w-0 items-center gap-2">
                            <span className={`inline-flex shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium ${CAT_STYLE[s.category] ?? 'bg-slate-100 text-slate-500'}`}>{s.category}</span>
                            <span className="truncate text-sm text-slate-700">{s.title}</span>
                            <span className="shrink-0 text-[10px] text-slate-400">{s.units}u</span>
                        </div>
                        {editing ? (
                            <input
                                type="number" step="0.25" min="1" max="5" inputMode="decimal"
                                className="input !w-24 shrink-0 py-1 text-center text-xs"
                                placeholder="—"
                                value={grades[s.subject_id] ?? ''}
                                onChange={(e) => setGrades((g) => ({ ...g, [s.subject_id]: e.target.value }))}
                            />
                        ) : (
                            <span className={`shrink-0 text-xs font-semibold ${s.remark === 'Passed' ? 'text-emerald-600' : s.remark === 'Failed' ? 'text-rose-600' : 'text-slate-300'}`}>
                                {s.grade !== null ? s.grade.toFixed(2) : '—'}
                            </span>
                        )}
                    </div>
                ))}
            </div>
            {editing && (
                <div className="flex items-center justify-between gap-2 border-t border-slate-100 bg-slate-50/40 px-5 py-3">
                    <span className="text-xs text-slate-400">1.00 highest · 3.00 passing · 5.00 fail. Leave blank to clear.</span>
                    <div className="flex gap-2">
                        <button onClick={() => setEditing(false)} className="btn-ghost">Cancel</button>
                        <button onClick={submit} disabled={save.processing} className="btn-primary">Save grades</button>
                    </div>
                </div>
            )}
        </div>
    );
}

function FeesPanel({ fees }: { fees: Fees }) {
    const peso = (n: number) => '₱' + n.toLocaleString();
    const badge = (status: string) => {
        const map: Record<string, string> = {
            Paid: 'bg-emerald-50 text-emerald-700', Partial: 'bg-sky-50 text-sky-700',
            Unpaid: 'bg-amber-50 text-amber-700', Free: 'bg-slate-100 text-slate-500',
        };
        return map[status] ?? 'bg-slate-100 text-slate-500';
    };
    const rows: FeeRow[] = [fees.misc, ...fees.extras];
    const totalDue = rows.reduce((s, r) => s + r.balance, 0);

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center justify-between gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                <div className="flex items-center gap-2.5">
                    <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Banknote className="h-4 w-4" /></span>
                    <h3 className="text-sm font-semibold text-slate-700">Fees &amp; payments{fees.school_year ? <span className="ml-1 font-normal text-slate-400">· {fees.school_year}</span> : null}</h3>
                </div>
                <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${totalDue > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'}`}>
                    {totalDue > 0 ? `${peso(totalDue)} due` : 'Fully paid'}
                </span>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full min-w-[420px] text-sm">
                    <thead>
                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th className="px-5 py-2 font-medium">Fee</th>
                            <th className="px-3 py-2 text-right font-medium">Due</th>
                            <th className="px-3 py-2 text-right font-medium">Paid</th>
                            <th className="px-3 py-2 text-right font-medium">Balance</th>
                            <th className="px-5 py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50">
                        {rows.map((r) => (
                            <tr key={r.category}>
                                <td className="px-5 py-2 text-slate-700">{r.category}</td>
                                <td className="px-3 py-2 text-right text-slate-500">{peso(r.expected)}</td>
                                <td className="px-3 py-2 text-right text-slate-500">{peso(r.paid)}</td>
                                <td className="px-3 py-2 text-right font-medium text-slate-800">{peso(r.balance)}</td>
                                <td className="px-5 py-2"><span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${badge(r.status)}`}>{r.status}</span></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="border-t border-slate-100 bg-slate-50/40 px-5 py-2 text-xs text-slate-400">
                Extra fee amounts are set in Settings → Fees (per program, per school year). “Others” is collected ad-hoc and not tracked here.
            </div>
        </div>
    );
}

/** The education grid, read-only. Only levels the applicant actually filled in. */
function EducationBackground({ label, levels, history }: { label: string; levels: { key: string; label: string }[]; history: Record<string, Record<string, string>> }) {
    const rows = levels
        .map(({ key, label: lvl }) => {
            const r = history[key] ?? {};
            return { key, label: lvl, school: r.school ?? '', started: r.started ?? '', graduated: r.graduated ?? '', status: r.status ?? '' };
        })
        .filter((r) => r.school || r.started || r.graduated || r.status);

    return (
        <div className="col-span-2 md:col-span-4">
            <div className="mb-1 text-xs font-medium text-slate-400">{label}</div>
            {rows.length === 0 ? (
                <div className="text-sm font-medium text-slate-800">—</div>
            ) : (
                <div className="overflow-x-auto rounded-xl border border-slate-200">
                    <table className="min-w-full text-sm">
                        <thead>
                            <tr className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th className="px-3 py-2">Level</th>
                                <th className="px-3 py-2">School / Institution</th>
                                <th className="px-3 py-2">Year started</th>
                                <th className="px-3 py-2">Year graduated</th>
                                <th className="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.map((r) => (
                                <tr key={r.key}>
                                    <td className="whitespace-nowrap px-3 py-2.5 font-medium text-slate-600">{r.label}</td>
                                    <td className="px-3 py-2.5 text-slate-800">{r.school || '—'}</td>
                                    <td className="px-3 py-2.5 text-slate-600">{r.started || '—'}</td>
                                    <td className="px-3 py-2.5 text-slate-600">{r.graduated || '—'}</td>
                                    <td className="px-3 py-2.5">
                                        {r.status
                                            ? <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${EDU_STATUS_STYLE[r.status] ?? 'bg-slate-100 text-slate-600'}`}>{r.status}</span>
                                            : <span className="text-slate-400">—</span>}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

function Section({ title, sectionKey, note, children }: { title: string; sectionKey?: string; note?: string | null; children: ReactNode }) {
    const Icon = sectionKey ? SECTION_ICON[sectionKey] ?? ClipboardList : undefined;
    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                {Icon && <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Icon className="h-4 w-4" /></span>}
                <h3 className="text-sm font-semibold text-slate-700">{title}</h3>
            </div>
            {note && <p className="px-5 pt-3 text-center text-sm italic text-slate-500">{note}</p>}
            <div className="grid grid-cols-2 gap-x-6 gap-y-4 p-5 md:grid-cols-4">{children}</div>
        </div>
    );
}

function Field({ label, span, children }: { label: string; span?: string; children: ReactNode }) {
    return (
        <div className={span}>
            <div className="text-xs font-medium text-slate-400">{label}</div>
            <div className="mt-0.5 text-sm font-medium text-slate-800">{children}</div>
        </div>
    );
}
