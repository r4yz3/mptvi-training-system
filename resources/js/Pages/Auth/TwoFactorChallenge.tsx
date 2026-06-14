import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { ShieldCheck, KeyRound, LogIn } from 'lucide-react';

export default function TwoFactorChallenge() {
    const { data, setData, post, processing, errors, reset } = useForm({ code: '', recovery_code: '' });
    const [useRecovery, setUseRecovery] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('two-factor.login'), { onFinish: () => reset('code', 'recovery_code') });
    };

    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-slate-100 via-slate-100 to-brand-50 p-4">
            <Head title="Two-factor verification" />

            <div className="relative w-full max-w-md">
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                    <div className="relative overflow-hidden bg-gradient-to-br from-brand-800 via-brand-700 to-brand-600 px-6 pb-6 pt-7 text-center text-white">
                        <div className="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/5" />
                        <div className="relative mb-2 flex items-center justify-center">
                            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-white/15 ring-2 ring-white/30">
                                <ShieldCheck className="h-7 w-7" />
                            </div>
                        </div>
                        <h1 className="relative text-base font-bold leading-tight">Two-factor verification</h1>
                        <p className="relative mt-0.5 text-xs text-white/70">Maximino Pellerin Sr. · Training Management System</p>
                    </div>

                    <div className="px-6 pb-6 pt-5 sm:px-8">
                        {!useRecovery ? (
                            <p className="mb-4 text-sm text-slate-500">
                                Enter the 6-digit code from your authenticator app to finish signing in.
                            </p>
                        ) : (
                            <p className="mb-4 text-sm text-slate-500">
                                Enter one of your recovery codes. Each code works once.
                            </p>
                        )}

                        <form onSubmit={submit} className="space-y-4">
                            {!useRecovery ? (
                                <div>
                                    <label htmlFor="code" className="mb-1 block text-xs font-medium text-slate-600">Authentication code</label>
                                    <input
                                        id="code"
                                        type="text"
                                        inputMode="numeric"
                                        autoComplete="one-time-code"
                                        autoFocus
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value)}
                                        className="input text-center text-lg tracking-[0.4em]"
                                        placeholder="123456"
                                    />
                                </div>
                            ) : (
                                <div>
                                    <label htmlFor="recovery_code" className="mb-1 block text-xs font-medium text-slate-600">Recovery code</label>
                                    <input
                                        id="recovery_code"
                                        type="text"
                                        autoComplete="one-time-code"
                                        autoFocus
                                        value={data.recovery_code}
                                        onChange={(e) => setData('recovery_code', e.target.value)}
                                        className="input text-center font-mono tracking-widest"
                                        placeholder="XXXXX-XXXXX"
                                    />
                                </div>
                            )}

                            {errors.code && <p className="text-xs text-rose-600">{errors.code}</p>}

                            <button type="submit" disabled={processing} className="btn-primary w-full justify-center py-2.5">
                                <LogIn className="h-4 w-4" /> {processing ? 'Verifying…' : 'Verify & sign in'}
                            </button>
                        </form>

                        <button
                            type="button"
                            onClick={() => { setUseRecovery((v) => !v); reset('code', 'recovery_code'); }}
                            className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700"
                        >
                            <KeyRound className="h-3.5 w-3.5" />
                            {useRecovery ? 'Use an authenticator code instead' : 'Use a recovery code instead'}
                        </button>
                    </div>
                    <div className="border-t border-slate-100 bg-slate-50 px-6 py-3 text-center text-xs text-slate-400 sm:px-8">
                        Lost your device? Use a recovery code, or ask an administrator.
                    </div>
                </div>
            </div>
        </div>
    );
}
