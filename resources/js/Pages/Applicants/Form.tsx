import { createContext, FormEvent, ReactNode, useContext, useEffect, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft, User, MapPin, Phone, IdCard, HeartPulse, Users2, Landmark,
    GraduationCap, ListChecks, LifeBuoy, ShieldCheck, FileSignature, ClipboardList, Sparkles,
    type LucideIcon,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import PhotoCapture from '@/Components/PhotoCapture';

interface Signatory { name: string; title: string }

// Section metadata — keyed by the exact <Section title="…"> used below. Drives the
// numbered icon header on each card and the sticky section navigator.
interface SectionMeta { title: string; id: string; nav: string; icon: LucideIcon }
const SECTIONS: SectionMeta[] = [
    { title: '1 · Learner / Manpower Profile', id: 'sec-profile', nav: 'Profile', icon: User },
    { title: 'Address', id: 'sec-address', nav: 'Address', icon: MapPin },
    { title: 'Contact', id: 'sec-contact', nav: 'Contact', icon: Phone },
    { title: '3 · Personal Information', id: 'sec-personal', nav: 'Personal', icon: IdCard },
    { title: 'Guardian & Health', id: 'sec-health', nav: 'Health', icon: HeartPulse },
    { title: 'Family Background', id: 'sec-family', nav: 'Family', icon: Users2 },
    { title: 'Government-issued IDs', id: 'sec-govids', nav: 'Gov’t IDs', icon: Landmark },
    { title: '7–8 · Course / Qualification & Scholarship', id: 'sec-course', nav: 'Course', icon: GraduationCap },
    { title: '4 · Learner / Trainee Classification', id: 'sec-classification', nav: 'Classification', icon: ListChecks },
    { title: '5–6 · Disability (PWD only) & Emergency contact', id: 'sec-disability', nav: 'Emergency', icon: LifeBuoy },
    { title: 'Additional Information', id: 'sec-additional', nav: 'Additional', icon: Sparkles },
    { title: '9 · Privacy Consent', id: 'sec-consent', nav: 'Consent', icon: ShieldCheck },
    { title: '10 · Verification', id: 'sec-verify', nav: 'Verification', icon: FileSignature },
];
const META = Object.fromEntries(SECTIONS.map((s, i) => [s.title, { ...s, num: i + 1 }]));

// Admin-defined custom fields, shared with each Section via context.
interface CustomFieldDef { key: string; label: string; type: string; options: string[] | null; section: string; required: boolean }
interface CustomCtx {
    fields: CustomFieldDef[];
    values: Record<string, string | number | boolean | null>;
    set: (key: string, val: string | number | boolean | null) => void;
    errors: Record<string, string>;
    disabled: string[];
}
const CustomFieldsContext = createContext<CustomCtx | null>(null);

// Admin overrides for built-in fields (hide / relabel / required), keyed by field key.
interface FieldSetting { label: string; enabled: boolean; required: boolean }
const FieldSettingsContext = createContext<Record<string, FieldSetting>>({});

interface Options {
    programs: { id: number; title: string; level: string | null }[];
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
    signatories: { checked_by: Signatory; approved_by: Signatory };
    customFields: CustomFieldDef[];
    disabledSections: string[];
    fieldSettings: Record<string, FieldSetting>;
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
        school_year: s('school_year', '2026–2027'),
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

    // Scroll-spy: highlight the section nearest the top of the viewport.
    const [active, setActive] = useState(SECTIONS[0].id);
    useEffect(() => {
        const obs = new IntersectionObserver(
            (entries) => entries.forEach((e) => e.isIntersecting && setActive(e.target.id)),
            { rootMargin: '-25% 0px -65% 0px' },
        );
        SECTIONS.forEach((s) => { const el = document.getElementById(s.id); if (el) obs.observe(el); });
        return () => obs.disconnect();
    }, []);
    const jump = (id: string) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Custom-field context shared with each Section.
    const customCtx: CustomCtx = {
        fields: options.customFields ?? [],
        values: data.custom,
        set: (key, val) => setData('custom', { ...data.custom, [key]: val }),
        errors: errors as Record<string, string>,
        disabled: options.disabledSections ?? [],
    };
    const hasAdditional = customCtx.fields.some((f) => f.section === 'sec-additional');
    // Nav: drop hidden sections + the Additional section when it has no custom fields.
    const navSections = SECTIONS.filter(
        (s) => !customCtx.disabled.includes(s.id) && (s.id !== 'sec-additional' || hasAdditional),
    );

    return (
      <FieldSettingsContext.Provider value={options.fieldSettings ?? {}}>
      <CustomFieldsContext.Provider value={customCtx}>
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
                        {navSections.map((s, i) => {
                            const Icon = s.icon;
                            const on = active === s.id;
                            return (
                                <button
                                    key={s.id}
                                    type="button"
                                    onClick={() => jump(s.id)}
                                    className={`flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition ${
                                        on ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-500 hover:bg-slate-50'
                                    }`}
                                >
                                    <span className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[11px] font-semibold ${on ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400'}`}>
                                        {i + 1}
                                    </span>
                                    <Icon className="h-4 w-4 shrink-0" />
                                    <span className="truncate">{s.nav}</span>
                                </button>
                            );
                        })}
                    </div>
                </nav>

                <form onSubmit={submit} className="space-y-6 pb-28">
                {/* Name + photo */}
                <Section title="1 · Learner / Manpower Profile">
                    <div className="col-span-full grid grid-cols-1 gap-6 lg:grid-cols-[1fr_auto]">
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <F name="last_name" label="Last name" req error={errors.last_name}><input className="input" value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} /></F>
                            <F name="first_name" label="First name" req error={errors.first_name}><input className="input" value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} /></F>
                            <F name="middle_name" label="Middle name"><input className="input" value={data.middle_name} onChange={(e) => setData('middle_name', e.target.value)} /></F>
                            <F name="ext_name" label="Ext. (Jr./Sr.)"><input className="input" value={data.ext_name} onChange={(e) => setData('ext_name', e.target.value)} /></F>
                        </div>
                        <PhotoCapture existing={s('photo_url')} onChange={(f) => setData('photo', f)} />
                    </div>
                </Section>

                {/* Address */}
                <Section title="Address">
                    <F name="street" label="No., Street"><input className="input" value={data.street} onChange={(e) => setData('street', e.target.value)} /></F>
                    <F name="barangay" label="Barangay" req error={errors.barangay}><input className="input" value={data.barangay} onChange={(e) => setData('barangay', e.target.value)} /></F>
                    <F name="district" label="District"><input className="input" value={data.district} onChange={(e) => setData('district', e.target.value)} /></F>
                    <F name="city" label="City / Municipality"><input className="input" value={data.city} onChange={(e) => setData('city', e.target.value)} /></F>
                    <F name="province" label="Province"><input className="input" value={data.province} onChange={(e) => setData('province', e.target.value)} /></F>
                    <Sel name="region" label="Region" value={data.region} opts={options.regions} onChange={(v) => setData('region', v)} />
                </Section>

                {/* Contact */}
                <Section title="Contact">
                    <F name="email" label="Email / Facebook"><input className="input" value={data.email} onChange={(e) => setData('email', e.target.value)} /></F>
                    <F name="contact" label="Contact no." req error={errors.contact}><input className="input" value={data.contact} onChange={(e) => setData('contact', e.target.value)} /></F>
                    <F name="nationality" label="Nationality"><input className="input" value={data.nationality} onChange={(e) => setData('nationality', e.target.value)} /></F>
                    <F name="religion" label="Religion"><input className="input" value={data.religion} onChange={(e) => setData('religion', e.target.value)} /></F>
                    <F name="ethnic_group" label="Ethnic group / IP affiliation"><input className="input" value={data.ethnic_group} onChange={(e) => setData('ethnic_group', e.target.value)} placeholder="If Indigenous People" /></F>
                </Section>

                {/* Personal */}
                <Section title="3 · Personal Information">
                    <Sel name="sex" label="Sex" req value={data.sex} opts={options.sex} onChange={(v) => setData('sex', v)} />
                    <Sel name="civil_status" label="Civil status" value={data.civil_status} opts={options.civil_status} onChange={(v) => setData('civil_status', v)} />
                    <Sel name="emp_status" label="Employment status" value={data.emp_status} opts={options.emp_status} onChange={(v) => setData('emp_status', v)} blank />
                    <Sel name="emp_type" label="Employment type" value={data.emp_type} opts={options.emp_type} onChange={(v) => setData('emp_type', v)} blank />
                    <F name="employer_name" label="Employer / Company (if employed)"><input className="input" value={data.employer_name} onChange={(e) => setData('employer_name', e.target.value)} /></F>
                    <F name="employer_position" label="Position (if employed)"><input className="input" value={data.employer_position} onChange={(e) => setData('employer_position', e.target.value)} /></F>
                    <F name="birthdate" label="Birthdate" error={errors.birthdate}><input type="date" className="input" value={data.birthdate} onChange={(e) => setData('birthdate', e.target.value)} /></F>
                    <F name="birthplace_city" label="Birthplace — City"><input className="input" value={data.birthplace_city} onChange={(e) => setData('birthplace_city', e.target.value)} /></F>
                    <F name="birthplace_province" label="Birthplace — Province"><input className="input" value={data.birthplace_province} onChange={(e) => setData('birthplace_province', e.target.value)} /></F>
                    <F name="birthplace_region" label="Birthplace — Region"><input className="input" value={data.birthplace_region} onChange={(e) => setData('birthplace_region', e.target.value)} /></F>
                    <Sel name="education" label="Educational attainment" value={data.education} opts={options.education} onChange={(v) => setData('education', v)} />
                    <F name="school_last_attended" label="School last attended"><input className="input" value={data.school_last_attended} onChange={(e) => setData('school_last_attended', e.target.value)} /></F>
                    <F name="year_graduated" label="Year graduated"><input className="input" value={data.year_graduated} onChange={(e) => setData('year_graduated', e.target.value)} placeholder="e.g. 2018" /></F>
                </Section>

                {/* Guardian + Health */}
                <Section title="Guardian & Health">
                    <F name="guardian_name" label="Parent / Guardian name"><input className="input" value={data.guardian_name} onChange={(e) => setData('guardian_name', e.target.value)} /></F>
                    <F name="guardian_address" label="Guardian address"><input className="input" value={data.guardian_address} onChange={(e) => setData('guardian_address', e.target.value)} /></F>
                    <F name="height" label="Height"><input className="input" value={data.height} onChange={(e) => setData('height', e.target.value)} placeholder="e.g. 165 cm" /></F>
                    <F name="weight" label="Weight"><input className="input" value={data.weight} onChange={(e) => setData('weight', e.target.value)} placeholder="e.g. 60 kg" /></F>
                    <Sel name="blood_type" label="Blood type" value={data.blood_type} opts={options.blood_types} onChange={(v) => setData('blood_type', v)} blank />
                    <Sel name="eyesight" label="Eyesight" value={data.eyesight} opts={options.rating} onChange={(v) => setData('eyesight', v)} blank />
                    <Sel name="hearing" label="Hearing" value={data.hearing} opts={options.rating} onChange={(v) => setData('hearing', v)} blank />
                    <F name="medical" label="Medical issues"><input className="input" value={data.medical} onChange={(e) => setData('medical', e.target.value)} placeholder="None / specify" /></F>
                </Section>

                {/* Family */}
                <Section title="Family Background">
                    <F name="father_name" label="Father's name"><input className="input" value={data.father_name} onChange={(e) => setData('father_name', e.target.value)} /></F>
                    <F name="father_occupation" label="Father's occupation"><input className="input" value={data.father_occupation} onChange={(e) => setData('father_occupation', e.target.value)} /></F>
                    <F name="mother_name" label="Mother's name"><input className="input" value={data.mother_name} onChange={(e) => setData('mother_name', e.target.value)} /></F>
                    <F name="mother_maiden_name" label="Mother's maiden name"><input className="input" value={data.mother_maiden_name} onChange={(e) => setData('mother_maiden_name', e.target.value)} /></F>
                    <F name="mother_occupation" label="Mother's occupation"><input className="input" value={data.mother_occupation} onChange={(e) => setData('mother_occupation', e.target.value)} /></F>
                    <F name="family_rank" label="Position in family"><input className="input" value={data.family_rank} onChange={(e) => setData('family_rank', e.target.value)} placeholder="e.g. 2nd of 4" /></F>
                    <F name="siblings" label="No. of siblings"><input className="input" value={data.siblings} onChange={(e) => setData('siblings', e.target.value)} /></F>
                    <F name="spouse_name" label="Spouse's name"><input className="input" value={data.spouse_name} onChange={(e) => setData('spouse_name', e.target.value)} /></F>
                    <F name="spouse_occupation" label="Spouse's occupation"><input className="input" value={data.spouse_occupation} onChange={(e) => setData('spouse_occupation', e.target.value)} /></F>
                    <F name="children" label="No. of children"><input className="input" value={data.children} onChange={(e) => setData('children', e.target.value)} /></F>
                </Section>

                {/* Government IDs */}
                <Section title="Government-issued IDs">
                    <F name="sss_no" label="SSS No."><input className="input" value={data.sss_no} onChange={(e) => setData('sss_no', e.target.value)} /></F>
                    <F name="gsis_no" label="GSIS No."><input className="input" value={data.gsis_no} onChange={(e) => setData('gsis_no', e.target.value)} /></F>
                    <F name="tin_no" label="TIN"><input className="input" value={data.tin_no} onChange={(e) => setData('tin_no', e.target.value)} /></F>
                    <F name="philhealth_no" label="PhilHealth No."><input className="input" value={data.philhealth_no} onChange={(e) => setData('philhealth_no', e.target.value)} /></F>
                </Section>

                {/* Course */}
                <Section title="7–8 · Course / Qualification & Scholarship">
                    <div className="col-span-full md:col-span-2">
                        <F name="program_id" label="Course / qualification" req error={errors.program_id}>
                            <select className="input" value={data.program_id} onChange={(e) => setData('program_id', Number(e.target.value))}>
                                {options.programs.map((p) => (
                                    <option key={p.id} value={p.id}>{p.title} {p.level ? `(${p.level})` : ''}</option>
                                ))}
                            </select>
                        </F>
                    </div>
                    <Sel name="scholarship" label="Scholarship package" value={data.scholarship} opts={options.scholarship} onChange={(v) => setData('scholarship', v)} />
                    <Sel name="class_session" label="Class session" value={data.class_session} opts={options.class_session} onChange={(v) => setData('class_session', v)} blank />
                    <F name="school_year" label="School year"><input className="input" value={data.school_year} onChange={(e) => setData('school_year', e.target.value)} placeholder="2026–2027" /></F>
                </Section>

                {/* Classification */}
                <Section title="4 · Learner / Trainee Classification">
                    <div className="col-span-full grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {options.classifications.map((c) => (
                            <label key={c} className="flex items-start gap-2 text-sm text-slate-600">
                                <input type="checkbox" className="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={data.classifications.includes(c)} onChange={() => toggleClassification(c)} />
                                {c}
                            </label>
                        ))}
                    </div>
                    <div className="col-span-full">
                        <F name="classification_other" label="Others (specify)"><input className="input" value={data.classification_other} onChange={(e) => setData('classification_other', e.target.value)} /></F>
                    </div>
                </Section>

                {/* Disability + Emergency */}
                <Section title="5–6 · Disability (PWD only) & Emergency contact">
                    <Sel name="disability_type" label="Type of disability" value={data.disability_type} opts={options.disability_types} onChange={(v) => setData('disability_type', v)} blank blankLabel="None / N/A" />
                    <Sel name="disability_cause" label="Cause of disability" value={data.disability_cause} opts={options.disability_causes} onChange={(v) => setData('disability_cause', v)} blank blankLabel="None / N/A" />
                    <div className="hidden md:block" />
                    <div className="hidden md:block" />
                    <F name="emergency_name" label="Emergency — contact person"><input className="input" value={data.emergency_name} onChange={(e) => setData('emergency_name', e.target.value)} /></F>
                    <F name="emergency_relationship" label="Relationship"><input className="input" value={data.emergency_relationship} onChange={(e) => setData('emergency_relationship', e.target.value)} /></F>
                    <F name="emergency_contact" label="Contact no."><input className="input" value={data.emergency_contact} onChange={(e) => setData('emergency_contact', e.target.value)} /></F>
                    <F name="emergency_address" label="Emergency address"><input className="input" value={data.emergency_address} onChange={(e) => setData('emergency_address', e.target.value)} /></F>
                </Section>

                {/* Additional Information — admin custom fields targeting this section */}
                <Section title="Additional Information" customOnly />

                {/* Consent */}
                <Section title="9 · Privacy Consent">
                    <div className="col-span-full space-y-3">
                        <Check
                            label="The applicant consents to the collection and processing of this personal data for training administration, in accordance with R.A. 10173 (Data Privacy Act)."
                            checked={data.privacy_consent}
                            onChange={(v) => setData('privacy_consent', v)}
                        />
                        <F name="remarks" label="Remarks"><textarea className="input" rows={2} value={data.remarks} onChange={(e) => setData('remarks', e.target.value)} /></F>
                    </div>
                </Section>

                {/* Verification */}
                <Section title="10 · Verification">
                    <p className="col-span-full text-center text-sm italic text-slate-500">
                        This is to certify that the information stated above is true and correct.
                        The applicant signs the printed form on release.
                    </p>

                    <F name="date_accomplished" label="Date accomplished"><input type="date" className="input" value={data.date_accomplished} onChange={(e) => setData('date_accomplished', e.target.value)} /></F>
                    <F name="date_received" label="Date received"><input type="date" className="input" value={data.date_received} onChange={(e) => setData('date_received', e.target.value)} /></F>

                    <div className="col-span-full grid grid-cols-1 gap-5 md:grid-cols-3">
                        <NameBox label="Interviewed by">
                            <input className="input text-center" placeholder="Interviewer's name" value={data.interviewed_by} onChange={(e) => setData('interviewed_by', e.target.value)} />
                        </NameBox>
                        <NameBox label="Checked by">
                            <input className="input text-center font-semibold uppercase" value={data.checked_by} onChange={(e) => setData('checked_by', e.target.value)} />
                            <div className="mt-1 text-center text-xs italic text-slate-500">{options.signatories.checked_by.title}</div>
                        </NameBox>
                        <NameBox label="Approved by">
                            <input className="input text-center font-semibold uppercase" value={data.approved_by} onChange={(e) => setData('approved_by', e.target.value)} />
                            <div className="mt-1 text-center text-xs italic text-slate-500">{options.signatories.approved_by.title}</div>
                        </NameBox>
                    </div>
                </Section>

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
      </CustomFieldsContext.Provider>
      </FieldSettingsContext.Provider>
    );
}

function NameBox({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <div className="mb-1 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</div>
            <div className="flex h-16 items-end justify-center border-b border-slate-300 pb-1 text-[10px] text-slate-300">
                signature on printed form
            </div>
            <div className="mt-2 pt-2">{children}</div>
        </div>
    );
}

function Section({ title, children, customOnly }: { title: string; children?: ReactNode; customOnly?: boolean }) {
    const ctx = useContext(CustomFieldsContext);
    const meta = META[title];
    const Icon = meta?.icon ?? ClipboardList;
    const clean = title.replace(/^[\d–-]+\s·\s/, '');

    // Hidden by admin?
    if (meta && ctx?.disabled.includes(meta.id)) return null;

    const customFields = ctx?.fields.filter((f) => f.section === meta?.id) ?? [];
    // Additional Information card only appears when it actually has custom fields.
    if (customOnly && customFields.length === 0) return null;

    return (
        <section id={meta?.id} className="scroll-mt-24 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <Icon className="h-[18px] w-[18px]" />
                </div>
                <div>
                    {meta && <div className="text-[10px] font-semibold uppercase tracking-wider text-brand-500">Section {meta.num}</div>}
                    <h3 className="text-sm font-semibold text-slate-800">{clean}</h3>
                </div>
            </div>
            <div className="grid grid-cols-2 gap-x-6 gap-y-4 p-5 md:grid-cols-4">
                {children}
                {customFields.map((f) => <CustomFieldInput key={f.key} field={f} ctx={ctx!} />)}
            </div>
        </section>
    );
}

function CustomFieldInput({ field, ctx }: { field: CustomFieldDef; ctx: CustomCtx }) {
    const val = ctx.values[field.key];
    const err = ctx.errors[`custom.${field.key}`];

    if (field.type === 'checkbox') {
        return (
            <label className="col-span-2 flex items-center gap-2 text-sm text-slate-600 md:col-span-4">
                <input type="checkbox" className="rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={!!val} onChange={(e) => ctx.set(field.key, e.target.checked)} />
                {field.label}{field.required && <span className="text-rose-500">*</span>}
            </label>
        );
    }

    let input: ReactNode;
    if (field.type === 'textarea') {
        input = <textarea className="input" rows={2} value={(val as string) ?? ''} onChange={(e) => ctx.set(field.key, e.target.value)} />;
    } else if (field.type === 'select') {
        input = (
            <select className="input" value={(val as string) ?? ''} onChange={(e) => ctx.set(field.key, e.target.value)}>
                <option value="">—</option>
                {(field.options ?? []).map((o) => <option key={o} value={o}>{o}</option>)}
            </select>
        );
    } else {
        const t = field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : 'text';
        input = <input type={t} className="input" value={(val as string) ?? ''} onChange={(e) => ctx.set(field.key, e.target.value)} />;
    }

    return (
        <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">
                {field.label} {field.required && <span className="text-rose-500">*</span>}
            </span>
            {input}
            {err && <span className="mt-1 block text-xs text-rose-600">{err}</span>}
        </label>
    );
}

function F({ name, label, req, error, children }: { name?: string; label: string; req?: boolean; error?: string; children: ReactNode }) {
    const settings = useContext(FieldSettingsContext);
    const s = name ? settings[name] : undefined;
    if (s && !s.enabled) return null; // hidden by admin
    const effLabel = s?.label || label;
    const effReq = s ? s.required : req;
    return (
        <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">
                {effLabel} {effReq && <span className="text-rose-500">*</span>}
            </span>
            {children}
            {error && <span className="mt-1 block text-xs text-rose-600">{error}</span>}
        </label>
    );
}

function Sel({
    name, label, value, opts, onChange, req, blank, blankLabel,
}: {
    name?: string; label: string; value: string; opts: string[]; onChange: (v: string) => void;
    req?: boolean; blank?: boolean; blankLabel?: string;
}) {
    return (
        <F name={name} label={label} req={req}>
            <select className="input" value={value} onChange={(e) => onChange(e.target.value)}>
                {blank && <option value="">{blankLabel ?? '—'}</option>}
                {opts.map((o) => <option key={o} value={o}>{o}</option>)}
            </select>
        </F>
    );
}

function Check({ name, label, checked, onChange }: { name?: string; label: string; checked: boolean; onChange: (v: boolean) => void }) {
    const settings = useContext(FieldSettingsContext);
    const s = name ? settings[name] : undefined;
    if (s && !s.enabled) return null;
    return (
        <label className="flex items-start gap-2 text-sm text-slate-600">
            <input type="checkbox" className="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={checked} onChange={(e) => onChange(e.target.checked)} />
            <span>{s?.label || label}</span>
        </label>
    );
}

