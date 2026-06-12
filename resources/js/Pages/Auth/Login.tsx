import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Mail, Lock, Eye, EyeOff, LogIn, CheckCircle2 } from 'lucide-react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });
    const [showPw, setShowPw] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-slate-100 via-slate-100 to-brand-50 p-4">
            <Head title="Log in" />

            <div className="relative w-full max-w-md">
                {/* Card */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                    {/* Branded header — logos sit on the navy band, always readable */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-brand-800 via-brand-700 to-brand-600 px-6 pb-6 pt-7 text-center text-white">
                        <div className="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/5" />
                        <div className="pointer-events-none absolute -left-12 bottom-0 h-32 w-32 rounded-full bg-white/5" />
                        <div className="relative mb-3 flex items-center justify-center gap-3">
                            <img src="/mptvi-logo.png" alt="MPTVI" className="h-16 w-16 rounded-full bg-white object-contain p-1 shadow-lg ring-2 ring-white/30" />
                            <img src="/magsaysay-logo.png" alt="Magsaysay" className="h-16 w-16 rounded-full bg-white object-contain p-1 shadow-lg ring-2 ring-white/30" />
                        </div>
                        <h1 className="relative text-base font-bold leading-tight">Maximino Pellerin Sr.</h1>
                        <p className="relative text-sm font-medium text-white/90">Technical and Vocational Institute</p>
                        <p className="relative mt-0.5 text-xs text-white/70">PESO Magsaysay · Davao del Sur</p>
                    </div>

                    <div className="px-6 pb-6 pt-5 sm:px-8">
                        <h2 className="text-base font-semibold text-slate-800">Sign in to your account</h2>
                        <p className="mb-5 mt-0.5 text-sm text-slate-500">Training Management System</p>

                        {status && (
                            <div className="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                                <CheckCircle2 className="h-4 w-4" /> {status}
                            </div>
                        )}

                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label htmlFor="email" className="mb-1 block text-xs font-medium text-slate-600">Email</label>
                                <div className="relative">
                                    <Mail className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        autoComplete="username"
                                        autoFocus
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="input pl-9"
                                        placeholder="you@adfirm.net"
                                    />
                                </div>
                                {errors.email && <p className="mt-1 text-xs text-rose-600">{errors.email}</p>}
                            </div>

                            <div>
                                <label htmlFor="password" className="mb-1 block text-xs font-medium text-slate-600">Password</label>
                                <div className="relative">
                                    <Lock className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                                    <input
                                        id="password"
                                        type={showPw ? 'text' : 'password'}
                                        name="password"
                                        value={data.password}
                                        autoComplete="current-password"
                                        onChange={(e) => setData('password', e.target.value)}
                                        className="input px-9"
                                        placeholder="••••••••"
                                    />
                                    <button type="button" onClick={() => setShowPw((v) => !v)} className="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
                                        {showPw ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                                {errors.password && <p className="mt-1 text-xs text-rose-600">{errors.password}</p>}
                            </div>

                            <div className="flex items-center justify-between">
                                <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', (e.target.checked || false) as false)}
                                        className="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                    />
                                    Remember me
                                </label>
                                {canResetPassword && (
                                    <Link href={route('password.request')} className="text-sm font-medium text-brand-600 hover:text-brand-700">
                                        Forgot password?
                                    </Link>
                                )}
                            </div>

                            <button type="submit" disabled={processing} className="btn-primary w-full justify-center py-2.5">
                                <LogIn className="h-4 w-4" /> {processing ? 'Signing in…' : 'Sign in'}
                            </button>
                        </form>
                    </div>
                    <div className="border-t border-slate-100 bg-slate-50 px-6 py-3 text-center text-xs text-slate-400 sm:px-8">
                        Authorized personnel only · TESDA-accredited training center
                    </div>
                </div>

                <p className="mt-5 text-center text-xs text-slate-400">© {new Date().getFullYear()} MPTVI · PESO Magsaysay</p>
            </div>
        </div>
    );
}
