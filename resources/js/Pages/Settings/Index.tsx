import { Head, Link } from '@inertiajs/react';
import {
    SlidersHorizontal, UserCog, GraduationCap, Stamp, Building2, ShieldCheck, Palette,
    ListChecks, List, BookOpen, DatabaseBackup, ChevronRight, Users2, ShieldAlert, Award, Wand2, Banknote,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import SystemHealthPanel from '@/Components/SystemHealthPanel';

interface Stats { customFields: number; hiddenSections: number; users: number; programs: number; applicants: number; assessor: string; institution: string; checkedBy: string; roles: number }
interface System { app: string; env: string; php: string; laravel: string }

interface Card { href: string; icon: React.ElementType; title: string; desc: string; meta: string; warn?: boolean }
interface Group { group: string; blurb: string; icon: React.ElementType; tint: string; cards: Card[] }

export default function SettingsIndex({ stats, system }: { stats: Stats; system: System }) {
    const groups: Group[] = [
        {
            group: 'Organization',
            blurb: 'How the school identifies itself on screen and on paper.',
            icon: Building2,
            tint: 'bg-brand-50 text-brand-600',
            cards: [
                { href: '/settings/institution', icon: Building2, title: 'Institution Profile', desc: 'School name, office, address and contact used across printed documents.', meta: stats.institution },
                { href: '/settings/signatories', icon: Stamp, title: 'Signatories & Certificate', desc: 'Accredited assessor and the Checked-by / Approved-by signatories on documents.', meta: stats.assessor ? `Assessor: ${stats.assessor}` : 'No assessor set', warn: !stats.assessor },
                { href: '/settings/branding', icon: Palette, title: 'Branding & Logos', desc: 'Primary color and the school + Magsaysay logos.', meta: 'Colors & logos' },
                { href: '/settings/academic', icon: BookOpen, title: 'Academic Defaults', desc: 'School year, eligibility age range, fee defaults and certificate numbering.', meta: 'Defaults & numbering' },
            ],
        },
        {
            group: 'Customization',
            blurb: 'Shape the registration form, catalog and reference data.',
            icon: Wand2,
            tint: 'bg-violet-50 text-violet-600',
            cards: [
                { href: '/settings/form-builder', icon: SlidersHorizontal, title: 'Form Builder', desc: 'Customize the registration form — show/hide sections and add custom fields.', meta: `${stats.customFields} custom field${stats.customFields === 1 ? '' : 's'}${stats.hiddenSections ? ` · ${stats.hiddenSections} hidden` : ''}` },
                { href: '/settings/requirements', icon: ListChecks, title: 'Document Requirements', desc: 'The documentary-requirements checklist recorded for each applicant.', meta: 'Required documents' },
                { href: '/settings/education', icon: GraduationCap, title: 'Education Grid', desc: 'Levels and status options in the Educational Attainment table.', meta: 'Registration form' },
                { href: '/settings/subjects', icon: Award, title: 'Subjects & Grading', desc: 'Each program’s Major / Minor subjects and units for the numeric (1.00–5.00) grades.', meta: 'Grade sheet' },
                { href: '/settings/fees', icon: Banknote, title: 'Fees (uniform, assessment)', desc: 'Extra-fee amounts per program, per school year, so the cashier can track who still owes them.', meta: 'Per school year' },
                { href: '/settings/lists', icon: List, title: 'Reference Lists', desc: 'The dropdown option lists used across the registration form.', meta: 'Form options' },
                { href: '/programs', icon: GraduationCap, title: 'Programs', desc: 'TESDA programs — level, fees, training hours and enrolled trainees.', meta: `${stats.programs} program${stats.programs === 1 ? '' : 's'}` },
            ],
        },
        {
            group: 'Access & control',
            blurb: 'Who can do what, and keeping the system safe.',
            icon: ShieldCheck,
            tint: 'bg-emerald-50 text-emerald-600',
            cards: [
                { href: '/users', icon: UserCog, title: 'User Accounts', desc: 'Create staff accounts and assign roles.', meta: `${stats.users} account${stats.users === 1 ? '' : 's'}` },
                { href: '/settings/access', icon: ShieldCheck, title: 'Roles & Access', desc: 'What each role can do and which modules they can open.', meta: `${stats.roles} roles` },
                { href: '/settings/security', icon: ShieldAlert, title: 'Security', desc: 'Login activity, two-factor adoption and your security checklist.', meta: 'Audit & protection' },
                { href: '/settings/backups', icon: DatabaseBackup, title: 'Backups', desc: 'Run, download and manage encrypted backups of the database and files.', meta: 'Daily · encrypted' },
            ],
        },
    ];

    const quickStats = [
        { icon: Users2, label: 'Applicants', value: stats.applicants },
        { icon: GraduationCap, label: 'Programs', value: stats.programs },
        { icon: UserCog, label: 'Staff accounts', value: stats.users },
        { icon: SlidersHorizontal, label: 'Custom fields', value: stats.customFields },
    ];

    return (
        <AppShell title="Settings">
            <Head title="Settings" />

            {/* Header */}
            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-3.5">
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-sm">
                        <SlidersHorizontal className="h-6 w-6" />
                    </div>
                    <div>
                        <h2 className="text-xl font-semibold text-slate-800">System settings</h2>
                        <p className="mt-0.5 max-w-xl text-sm text-slate-500">
                            Configure how the system behaves — institution details, forms, access and backups.
                        </p>
                    </div>
                </div>
                <span className="inline-flex items-center gap-1.5 self-start rounded-full bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200/70">
                    <ShieldCheck className="h-3.5 w-3.5" /> Administrators only
                </span>
            </div>

            {/* Quick stats — one unified panel */}
            <div className="mb-9 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="grid grid-cols-2 divide-slate-100 sm:grid-cols-4 sm:divide-x">
                    {quickStats.map((s, i) => {
                        const Icon = s.icon;
                        return (
                            <div key={s.label} className={`flex items-center gap-3 p-4 sm:p-5 ${i >= 2 ? 'border-t border-slate-100 sm:border-t-0' : ''} ${i % 2 === 1 ? 'border-l border-slate-100 sm:border-l-0' : ''}`}>
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                    <Icon className="h-5 w-5" />
                                </div>
                                <div className="min-w-0">
                                    <div className="text-2xl font-semibold leading-none tracking-tight text-slate-800">{s.value}</div>
                                    <div className="mt-1 truncate text-xs text-slate-500">{s.label}</div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Setting groups */}
            <div className="space-y-9">
                {groups.map((g) => {
                    const GIcon = g.icon;
                    return (
                        <section key={g.group}>
                            <div className="mb-3.5 flex items-center gap-3">
                                <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${g.tint}`}>
                                    <GIcon className="h-5 w-5" />
                                </span>
                                <div className="min-w-0">
                                    <h3 className="text-sm font-semibold text-slate-800">{g.group}</h3>
                                    <p className="truncate text-xs text-slate-400">{g.blurb}</p>
                                </div>
                                <span className="ml-auto shrink-0 text-xs font-medium text-slate-400">{g.cards.length} settings</span>
                            </div>

                            <div className="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                {g.cards.map((c) => {
                                    const Icon = c.icon;
                                    return (
                                        <Link
                                            key={c.href}
                                            href={c.href}
                                            className="group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-brand-300 hover:shadow-md"
                                        >
                                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-colors duration-200 group-hover:bg-brand-600 group-hover:text-white">
                                                <Icon className="h-5 w-5" />
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <h4 className="truncate font-semibold text-slate-800 group-hover:text-brand-700">{c.title}</h4>
                                                    <ChevronRight className="ml-auto h-4 w-4 shrink-0 text-slate-300 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-brand-500" />
                                                </div>
                                                <p className="mt-0.5 text-sm leading-snug text-slate-500">{c.desc}</p>
                                                <span className={`mt-2 inline-flex max-w-full items-center gap-1 truncate rounded-md px-2 py-0.5 text-xs font-medium ${c.warn ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500'}`}>
                                                    {c.warn && <ShieldAlert className="h-3 w-3 shrink-0" />}
                                                    <span className="truncate">{c.meta}</span>
                                                </span>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        </section>
                    );
                })}
            </div>

            {/* System health & hardware advice */}
            <div className="mt-9">
                <SystemHealthPanel system={system} />
            </div>
        </AppShell>
    );
}
