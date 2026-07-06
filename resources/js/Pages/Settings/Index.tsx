import { Head, Link } from '@inertiajs/react';
import {
    SlidersHorizontal, UserCog, GraduationCap, Stamp, Building2, ShieldCheck, Palette,
    ListChecks, List, BookOpen, DatabaseBackup, ChevronRight, Users2, ShieldAlert,
} from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import SystemHealthPanel from '@/Components/SystemHealthPanel';

interface Stats { customFields: number; hiddenSections: number; users: number; programs: number; applicants: number; assessor: string; institution: string; checkedBy: string; roles: number }
interface System { app: string; env: string; php: string; laravel: string }

interface Card { href: string; icon: React.ElementType; title: string; desc: string; meta: string }

export default function SettingsIndex({ stats, system }: { stats: Stats; system: System }) {
    const groups: { group: string; cards: Card[] }[] = [
        {
            group: 'Organization',
            cards: [
                { href: '/settings/institution', icon: Building2, title: 'Institution Profile', desc: 'School name, office, address and contact used across printed documents.', meta: stats.institution },
                { href: '/settings/signatories', icon: Stamp, title: 'Signatories & Certificate', desc: 'Accredited assessor and the Checked-by / Approved-by signatories on documents.', meta: stats.assessor ? `Assessor: ${stats.assessor}` : 'No assessor set' },
                { href: '/settings/branding', icon: Palette, title: 'Branding & Logos', desc: 'Set the primary color and upload the school + Magsaysay logos.', meta: 'Colors & logos' },
                { href: '/settings/academic', icon: BookOpen, title: 'Academic Defaults', desc: 'School year, eligibility age range, fee defaults and certificate numbering.', meta: 'Defaults & numbering' },
            ],
        },
        {
            group: 'Customization',
            cards: [
                { href: '/settings/form-builder', icon: SlidersHorizontal, title: 'Form Builder', desc: 'Customize the registration form — show/hide sections and add custom fields.', meta: `${stats.customFields} custom field${stats.customFields === 1 ? '' : 's'}${stats.hiddenSections ? ` · ${stats.hiddenSections} hidden` : ''}` },
                { href: '/settings/requirements', icon: ListChecks, title: 'Document Requirements', desc: 'Manage the documentary-requirements checklist recorded for each applicant.', meta: 'Required documents' },
                { href: '/settings/education', icon: GraduationCap, title: 'Education Grid', desc: 'Edit the levels and status options in the Educational Attainment table.', meta: 'Registration form' },
                { href: '/settings/lists', icon: List, title: 'Reference Lists', desc: 'Edit the dropdown option lists used across the registration form.', meta: 'Form options' },
                { href: '/programs', icon: GraduationCap, title: 'Programs & Batches', desc: 'Manage TESDA programs, fees, training hours and class batches.', meta: `${stats.programs} program${stats.programs === 1 ? '' : 's'}` },
            ],
        },
        {
            group: 'Access & control',
            cards: [
                { href: '/users', icon: UserCog, title: 'User Accounts', desc: 'Create staff accounts and assign roles.', meta: `${stats.users} account${stats.users === 1 ? '' : 's'}` },
                { href: '/settings/access', icon: ShieldCheck, title: 'Roles & Access', desc: 'Review what each role can do and which modules they can open.', meta: `${stats.roles} roles` },
                { href: '/settings/security', icon: ShieldAlert, title: 'Security', desc: 'Login activity log, two-factor adoption and your security posture checklist.', meta: 'Audit & protection' },
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
            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex items-start gap-3.5">
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

            {/* Quick stats */}
            <div className="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
                {quickStats.map((s) => {
                    const Icon = s.icon;
                    return (
                        <div key={s.label} className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <Icon className="h-5 w-5" />
                            </div>
                            <div className="min-w-0">
                                <div className="text-xl font-semibold leading-none text-slate-800">{s.value}</div>
                                <div className="mt-1 truncate text-xs text-slate-500">{s.label}</div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Setting groups */}
            <div className="space-y-8">
                {groups.map((g) => (
                    <div key={g.group}>
                        <div className="mb-3 flex items-center gap-2.5">
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-400">{g.group}</h3>
                            <span className="h-px flex-1 bg-slate-200/70" />
                            <span className="text-[11px] font-medium text-slate-300">{g.cards.length}</span>
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {g.cards.map((c) => {
                                const Icon = c.icon;
                                return (
                                    <Link
                                        key={c.href}
                                        href={c.href}
                                        className="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md"
                                    >
                                        <div className="mb-3 flex items-center justify-between">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition-colors duration-200 group-hover:bg-brand-600 group-hover:text-white">
                                                <Icon className="h-6 w-6" />
                                            </div>
                                            <ChevronRight className="h-5 w-5 text-slate-300 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-brand-500" />
                                        </div>
                                        <h4 className="text-base font-semibold text-slate-800 group-hover:text-brand-700">{c.title}</h4>
                                        <p className="mt-1 flex-1 text-sm text-slate-500">{c.desc}</p>
                                        <div className="mt-3 truncate border-t border-slate-100 pt-3 text-xs font-medium text-brand-600">{c.meta}</div>
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>

            {/* System health & hardware advice */}
            <div className="mt-8">
                <SystemHealthPanel system={system} />
            </div>
        </AppShell>
    );
}
