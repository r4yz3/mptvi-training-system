import { Head, Link } from '@inertiajs/react';
import { SlidersHorizontal, UserCog, GraduationCap, Stamp, Building2, ShieldCheck, Palette, ListChecks, List, BookOpen, DatabaseBackup, ChevronRight } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

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
                { href: '/settings/requirements', icon: ListChecks, title: 'Document Requirements', desc: 'Manage the documentary-requirements checklist applicants must submit.', meta: 'Required documents' },
                { href: '/settings/lists', icon: List, title: 'Reference Lists', desc: 'Edit the dropdown option lists used across the registration form.', meta: 'Form options' },
                { href: '/programs', icon: GraduationCap, title: 'Programs & Batches', desc: 'Manage TESDA programs, fees, training hours and class batches.', meta: `${stats.programs} program${stats.programs === 1 ? '' : 's'}` },
            ],
        },
        {
            group: 'Access & control',
            cards: [
                { href: '/users', icon: UserCog, title: 'User Accounts', desc: 'Create staff accounts and assign roles.', meta: `${stats.users} account${stats.users === 1 ? '' : 's'}` },
                { href: '/settings/access', icon: ShieldCheck, title: 'Roles & Access', desc: 'Review what each role can do and which modules they can open.', meta: `${stats.roles} roles` },
                { href: '/settings/backups', icon: DatabaseBackup, title: 'Backups', desc: 'Run, download and manage encrypted backups of the database and files.', meta: 'Daily · encrypted' },
            ],
        },
    ];

    return (
        <AppShell title="Settings">
            <Head title="Settings" />

            <p className="mb-5 max-w-2xl text-sm text-slate-500">Configure the system. These tools are available to Administrators only.</p>

            <div className="space-y-7">
                {groups.map((g) => (
                    <div key={g.group}>
                        <h3 className="mb-2.5 text-xs font-semibold uppercase tracking-wide text-slate-400">{g.group}</h3>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {g.cards.map((c) => {
                                const Icon = c.icon;
                                return (
                                    <Link key={c.href} href={c.href} className="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow">
                                        <div className="mb-3 flex items-center justify-between">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><Icon className="h-6 w-6" /></div>
                                            <ChevronRight className="h-5 w-5 text-slate-300 transition group-hover:text-brand-500" />
                                        </div>
                                        <h4 className="text-base font-semibold text-slate-800">{c.title}</h4>
                                        <p className="mt-1 flex-1 text-sm text-slate-500">{c.desc}</p>
                                        <div className="mt-3 truncate text-xs font-medium text-brand-600">{c.meta}</div>
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>

            {/* System info */}
            <div className="mt-8 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">System</h3>
                <div className="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
                    <Info label="Application" value={system.app} />
                    <Info label="Environment" value={system.env} />
                    <Info label="Applicants on record" value={String(stats.applicants)} />
                    <Info label="Laravel" value={`v${system.laravel}`} />
                </div>
            </div>
        </AppShell>
    );
}

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <div className="text-xs text-slate-400">{label}</div>
            <div className="font-medium capitalize text-slate-700">{value}</div>
        </div>
    );
}
