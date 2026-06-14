import AppShell from '@/Layouts/AppShell';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { UserRound, KeyRound, ShieldCheck, TriangleAlert } from 'lucide-react';
import { ReactNode } from 'react';
import DeleteUserForm from './Partials/DeleteUserForm';
import TwoFactorForm from './Partials/TwoFactorForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    const user = usePage<PageProps>().props.auth.user;

    return (
        <AppShell title="My Profile">
            <Head title="Profile" />

            {/* Hero */}
            <div className="mb-6 flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-xl font-semibold text-white shadow-sm">
                    {user.initials}
                </div>
                <div className="min-w-0">
                    <h2 className="truncate text-xl font-semibold text-slate-800">{user.name}</h2>
                    <div className="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                        <span className="truncate">{user.email}</span>
                        <span className="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">{user.roleLabel}</span>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card icon={UserRound} title="Profile information">
                    <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} />
                </Card>

                <Card icon={KeyRound} title="Password">
                    <UpdatePasswordForm />
                </Card>

                <Card icon={ShieldCheck} title="Two-factor authentication" className="lg:col-span-2">
                    <TwoFactorForm />
                </Card>

                <Card icon={TriangleAlert} title="Danger zone" tone="danger" className="lg:col-span-2">
                    <DeleteUserForm />
                </Card>
            </div>
        </AppShell>
    );
}

function Card({
    icon: Icon, title, tone = 'default', className = '', children,
}: {
    icon: React.ElementType; title: string; tone?: 'default' | 'danger'; className?: string; children: ReactNode;
}) {
    const danger = tone === 'danger';
    return (
        <section className={`rounded-xl border bg-white shadow-sm ${danger ? 'border-rose-200' : 'border-slate-200'} ${className}`}>
            <div className={`flex items-center gap-2.5 border-b px-5 py-3.5 ${danger ? 'border-rose-100' : 'border-slate-100'}`}>
                <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${danger ? 'bg-rose-50 text-rose-600' : 'bg-brand-50 text-brand-600'}`}>
                    <Icon className="h-4 w-4" />
                </div>
                <h3 className={`text-sm font-semibold ${danger ? 'text-rose-700' : 'text-slate-700'}`}>{title}</h3>
            </div>
            <div className="p-5 sm:p-6">{children}</div>
        </section>
    );
}
