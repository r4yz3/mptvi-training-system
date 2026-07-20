import { Fragment, ReactNode, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft, Pencil, Trash2, Lock, UserCircle2, Power, Printer, Check, XCircle,
    CalendarDays, MapPin, Phone, User, IdCard, HeartPulse, Users2, Landmark,
    GraduationCap, ListChecks, LifeBuoy, FileSignature, Sparkles, FileText, Banknote, type LucideIcon,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import StatusBadge from '@/Components/StatusBadge';
import TraineeStatusBadge from '@/Components/TraineeStatusBadge';
import DocumentChecklist from '@/Components/DocumentChecklist';
import { PageProps } from '@/types';

const STAGES = ['Registered', 'Qualified', 'In training', 'For assessment', 'Certified'];

const SECTION_ICON: Record<string, LucideIcon> = {
    'Enrollment': GraduationCap,
    'Personal information': IdCard,
    'Address & contact': MapPin,
    'Course & schedule': GraduationCap,
    'Health': HeartPulse,
    'Family background': Users2,
    'Government-issued IDs': Landmark,
    'Emergency contact': LifeBuoy,
    'Classification & disability': ListChecks,
    'Additional Information': Sparkles,
    'Remarks': FileText,
    '10 · Verification': FileSignature,
};

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

interface UnitResult { unit_id: number; code: string | null; title: string; type: string; result: string | null; rated_at: string | null; remarks: string | null }
interface CompetencyInfo { units: UnitResult[]; total: number; competent: number; complete: boolean }

interface FeeRow { category: string; expected: number; paid: number; balance: number; status: string }
interface Fees { school_year: string | null; misc: FeeRow; extras: FeeRow[] }

export default function ApplicantShow({
    applicant, pii, documents, canVerifyDocs, customFields, traineeStatuses, eduLevels, competencyInfo, canGrade, fees,
}: {
    applicant: Applicant;
    pii: boolean;
    documents: DocItem[] | null;
    canVerifyDocs: boolean;
    customFields: CustomFieldDef[] | null;
    traineeStatuses: string[];
    eduLevels: { key: string; label: string }[];
    competencyInfo: CompetencyInfo;
    canGrade: boolean;
    fees: Fees | null;
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

            {/* Training grades — job data, visible to pii and non-pii roles alike */}
            <div className="mt-6">
                <CompetencyPanel info={competencyInfo} canGrade={canGrade} applicantId={applicant.id} />
            </div>

            {fees && (
                <div className="mt-6">
                    <FeesPanel fees={fees} />
                </div>
            )}

            {pii ? (
                <>
                    <FullProfile a={applicant} customFields={customFields ?? []} eduLevels={eduLevels} />
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

function FullProfile({ a, customFields, eduLevels }: { a: Applicant; customFields: CustomFieldDef[]; eduLevels: { key: string; label: string }[] }) {
    const v = (k: string) => (a[k] as string | null) || '—';
    const classifications = (a.classifications as string[] | null) ?? [];
    const customData = (a.custom_data as Record<string, unknown> | null) ?? {};
    const fmtCustom = (val: unknown) => (typeof val === 'boolean' ? (val ? 'Yes' : 'No') : (val == null || val === '' ? '—' : String(val)));

    return (
        <div className="mt-6 space-y-6">
            <Section title="Personal information">
                <Field label="Sex">{v('sex')}</Field>
                <Field label="Age">{a.age ? `${a.age}` : '—'}</Field>
                <Field label="Civil status">{v('civil_status')}</Field>
                <Field label="Birthdate">{v('birthdate')}</Field>
                <Field label="Birthplace">{[v('birthplace_city'), a.birthplace_province, a.birthplace_region].filter((x) => x && x !== '—').join(', ') || '—'}</Field>
                <Field label="Nationality">{v('nationality')}</Field>
                <Field label="Religion">{v('religion')}</Field>
                <Field label="Ethnic group">{v('ethnic_group')}</Field>
                <Field label="Education">{v('education')}</Field>
                <Field label="School last attended">{v('school_last_attended')}</Field>
                <Field label="Year graduated">{v('year_graduated')}</Field>
            </Section>

            <EducationBackground levels={eduLevels} history={(a.education_history as Record<string, Record<string, string>> | null) ?? {}} />

            <Section title="Address & contact">
                <Field label="Street">{v('street')}</Field>
                <Field label="Barangay">{v('barangay')}</Field>
                <Field label="City / Municipality">{v('city')}</Field>
                <Field label="Province">{v('province')}</Field>
                <Field label="Region">{v('region')}</Field>
                <Field label="Contact no.">{v('contact')}</Field>
                <Field label="Email / FB">{v('email')}</Field>
            </Section>

            <Section title="Course & schedule">
                <Field label="Program">{a.program?.title ?? '—'}</Field>
                <Field label="NC level">{a.program?.level ?? '—'}</Field>
                <Field label="Scholarship">{v('scholarship')}</Field>
                <Field label="Class session">{v('class_session')}</Field>
                <Field label="School year">{v('school_year')}</Field>
                <Field label="Employment (pre-training)">{v('emp_status')}</Field>
                <Field label="Employer / position">{[v('employer_name'), a.employer_position].filter((x) => x && x !== '—').join(' · ') || '—'}</Field>
            </Section>

            <Section title="Health">
                <Field label="Height">{v('height')}</Field>
                <Field label="Weight">{v('weight')}</Field>
                <Field label="Blood type">{v('blood_type')}</Field>
                <Field label="Eyesight">{v('eyesight')}</Field>
                <Field label="Hearing">{v('hearing')}</Field>
                <Field label="Medical issues">{v('medical')}</Field>
            </Section>

            <Section title="Family background">
                <Field label="Father">{v('father_name')}</Field>
                <Field label="Father's occupation">{v('father_occupation')}</Field>
                <Field label="Mother">{v('mother_name')}</Field>
                <Field label="Mother's maiden name">{v('mother_maiden_name')}</Field>
                <Field label="Mother's occupation">{v('mother_occupation')}</Field>
                <Field label="Spouse">{v('spouse_name')}</Field>
                <Field label="Spouse's occupation">{v('spouse_occupation')}</Field>
                <Field label="Guardian">{v('guardian_name')}</Field>
            </Section>

            <Section title="Government-issued IDs">
                <Field label="SSS No.">{v('sss_no')}</Field>
                <Field label="GSIS No.">{v('gsis_no')}</Field>
                <Field label="TIN">{v('tin_no')}</Field>
                <Field label="PhilHealth No.">{v('philhealth_no')}</Field>
            </Section>

            <Section title="Emergency contact">
                <Field label="Contact person">{v('emergency_name')}</Field>
                <Field label="Relationship">{v('emergency_relationship')}</Field>
                <Field label="Contact no.">{v('emergency_contact')}</Field>
                <Field label="Address">{v('emergency_address')}</Field>
            </Section>

            {(classifications.length > 0 || Boolean(a.classification_other) || Boolean(a.disability_type)) && (
                <Section title="Classification & disability">
                    <div className="col-span-full flex flex-wrap gap-2">
                        {classifications.map((c) => (
                            <span key={c} className="rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-sky-700">{c}</span>
                        ))}
                        {a.classification_other ? (
                            <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600">{a.classification_other as string}</span>
                        ) : null}
                        {classifications.length === 0 && !a.classification_other && (
                            <span className="text-sm text-slate-400">No classifications</span>
                        )}
                    </div>
                    {a.disability_type ? (
                        <>
                            <Field label="Disability type">{v('disability_type')}</Field>
                            <Field label="Cause">{v('disability_cause')}</Field>
                        </>
                    ) : null}
                </Section>
            )}

            {a.remarks ? (
                <Section title="Remarks">
                    <p className="col-span-full text-sm text-slate-600">{a.remarks as string}</p>
                </Section>
            ) : null}

            {customFields.length > 0 && (
                <Section title="Additional Information">
                    {customFields.map((f) => (
                        <Field key={f.key} label={f.label}>{fmtCustom(customData[f.key])}</Field>
                    ))}
                </Section>
            )}

            <Section title="10 · Verification">
                <Field label="Date accomplished">{v('date_accomplished').slice(0, 10)}</Field>
                <Field label="Date received">{v('date_received').slice(0, 10)}</Field>
                <div className="col-span-full grid grid-cols-1 gap-5 md:grid-cols-3">
                    <NameView label="Interviewed by" name={v('interviewed_by')} />
                    <NameView label="Checked by" name={v('checked_by')} />
                    <NameView label="Approved by" name={v('approved_by')} />
                </div>
            </Section>
        </div>
    );
}

function NameView({ label, name }: { label: string; name: string }) {
    return (
        <div>
            <div className="mb-1 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</div>
            <div className="flex h-16 items-end justify-center border-b border-slate-200 pb-1 text-[10px] text-slate-300">
                signature on printed form
            </div>
            {name && name !== '—' && (
                <div className="mt-1 pt-1 text-center text-xs font-medium text-slate-600">{name}</div>
            )}
        </div>
    );
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

const UNIT_TYPE_STYLE: Record<string, string> = {
    Basic: 'bg-sky-50 text-sky-700',
    Common: 'bg-violet-50 text-violet-700',
    Core: 'bg-emerald-50 text-emerald-700',
};

function CompetencyPanel({ info, canGrade, applicantId }: { info: CompetencyInfo; canGrade: boolean; applicantId: number }) {
    // Editable rating state (unit_id -> result), seeded from the current ratings.
    const [editing, setEditing] = useState(false);
    const [ratings, setRatings] = useState<Record<number, string>>(() =>
        Object.fromEntries(info.units.map((u) => [u.unit_id, u.result ?? ''])));
    const save = useForm({});

    if (info.total === 0) return null; // no units defined / not a trainee — keep the profile clean

    const submit = () => {
        save.transform(() => ({
            rated_at: new Date().toISOString().slice(0, 10),
            ratings: info.units.map((u) => ({ unit_id: u.unit_id, result: ratings[u.unit_id] || null })),
        }));
        save.put(`/applicants/${applicantId}/competency`, {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    };

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                <div className="flex items-center gap-2.5">
                    <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><GraduationCap className="h-4 w-4" /></span>
                    <h3 className="text-sm font-semibold text-slate-700">Competency achievement</h3>
                </div>
                <div className="flex items-center gap-2">
                    <span className="text-sm font-semibold text-slate-600">{info.competent}/{info.total} Competent</span>
                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${info.complete ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                        {info.complete ? 'Complete' : 'In progress'}
                    </span>
                    <a href={`/applicants/${applicantId}/report-card`} target="_blank" rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <Printer className="h-3.5 w-3.5" /> Report card
                    </a>
                    {canGrade && !editing && (
                        <button onClick={() => setEditing(true)} className="inline-flex items-center gap-1 rounded-md bg-brand-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-brand-700">
                            <Pencil className="h-3.5 w-3.5" /> Rate
                        </button>
                    )}
                </div>
            </div>
            <div className="divide-y divide-slate-50">
                {info.units.map((u) => (
                    <div key={u.unit_id} className="flex items-center justify-between gap-3 px-5 py-2.5">
                        <div className="flex min-w-0 items-center gap-2">
                            <span className={`inline-flex shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium ${UNIT_TYPE_STYLE[u.type] ?? 'bg-slate-100 text-slate-500'}`}>{u.type}</span>
                            <span className="truncate text-sm text-slate-700">{u.title}</span>
                        </div>
                        {editing ? (
                            <select
                                className="input !w-40 shrink-0 py-1 text-xs"
                                value={ratings[u.unit_id] ?? ''}
                                onChange={(e) => setRatings((r) => ({ ...r, [u.unit_id]: e.target.value }))}
                            >
                                <option value="">— Not rated —</option>
                                <option value="Competent">Competent</option>
                                <option value="Not Yet Competent">Not Yet Competent</option>
                            </select>
                        ) : (
                            <span className={`shrink-0 text-xs font-semibold ${u.result === 'Competent' ? 'text-emerald-600' : u.result ? 'text-amber-600' : 'text-slate-300'}`}>
                                {u.result === 'Competent' ? 'Competent' : u.result ? 'Not yet' : '—'}
                            </span>
                        )}
                    </div>
                ))}
            </div>
            {editing && (
                <div className="flex justify-end gap-2 border-t border-slate-100 bg-slate-50/40 px-5 py-3">
                    <button onClick={() => setEditing(false)} className="btn-ghost">Cancel</button>
                    <button onClick={submit} disabled={save.processing} className="btn-primary">Save ratings</button>
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

function EducationBackground({ levels, history }: { levels: { key: string; label: string }[]; history: Record<string, Record<string, string>> }) {
    const rows = levels
        .map(({ key, label }) => {
            const r = history[key] ?? {};
            return { key, label, school: r.school ?? '', started: r.started ?? '', graduated: r.graduated ?? '', status: r.status ?? '' };
        })
        .filter((r) => r.school || r.started || r.graduated || r.status);
    if (rows.length === 0) return null;

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><GraduationCap className="h-4 w-4" /></span>
                <h3 className="text-sm font-semibold text-slate-700">Educational background</h3>
            </div>
            <div className="overflow-x-auto">
                <table className="min-w-full text-sm">
                    <thead>
                        <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th className="px-5 py-2">Level</th>
                            <th className="px-5 py-2">School / Institution</th>
                            <th className="px-5 py-2">Started</th>
                            <th className="px-5 py-2">Graduated</th>
                            <th className="px-5 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map((r) => (
                            <tr key={r.key}>
                                <td className="whitespace-nowrap px-5 py-2.5 font-medium text-slate-600">{r.label}</td>
                                <td className="px-5 py-2.5 text-slate-800">{r.school || '—'}</td>
                                <td className="px-5 py-2.5 text-slate-600">{r.started || '—'}</td>
                                <td className="px-5 py-2.5 text-slate-600">{r.graduated || '—'}</td>
                                <td className="px-5 py-2.5">
                                    {r.status
                                        ? <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${EDU_STATUS_STYLE[r.status] ?? 'bg-slate-100 text-slate-600'}`}>{r.status}</span>
                                        : <span className="text-slate-400">—</span>}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    const Icon = SECTION_ICON[title];
    const clean = title.replace(/^\d+\s·\s/, '');
    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                {Icon && <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Icon className="h-4 w-4" /></span>}
                <h3 className="text-sm font-semibold text-slate-700">{clean}</h3>
            </div>
            <div className="grid grid-cols-2 gap-x-6 gap-y-4 p-5 md:grid-cols-4">{children}</div>
        </div>
    );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <div className="text-xs font-medium text-slate-400">{label}</div>
            <div className="mt-0.5 text-sm font-medium text-slate-800">{children}</div>
        </div>
    );
}
