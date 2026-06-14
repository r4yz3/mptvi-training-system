import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { router, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { QRCodeSVG } from 'qrcode.react';
import { FormEventHandler, useState } from 'react';

interface TwoFactor { enabled: boolean; confirming: boolean; secret: string | null; qr: string | null }

export default function TwoFactorForm({ className = '' }: { className?: string }) {
    const page = usePage<PageProps<{ twoFactor: TwoFactor; recoveryCodes: string[] | null }>>();
    const { twoFactor, recoveryCodes } = page.props;

    const confirm = useForm({ code: '' });
    const disable = useForm({ password: '' });
    const [showDisable, setShowDisable] = useState(false);

    const enable = () => router.post(route('two-factor.enable'), {}, { preserveScroll: true });
    const cancel = () => router.delete(route('two-factor.disable'), { preserveScroll: true });

    const submitConfirm: FormEventHandler = (e) => {
        e.preventDefault();
        confirm.post(route('two-factor.confirm'), { preserveScroll: true, onSuccess: () => confirm.reset() });
    };
    const submitDisable: FormEventHandler = (e) => {
        e.preventDefault();
        disable.delete(route('two-factor.disable'), {
            preserveScroll: true,
            onSuccess: () => { disable.reset(); setShowDisable(false); },
        });
    };

    return (
        <section className={className}>
            <p className="text-sm text-slate-500">
                Add a second step at login using an authenticator app (Google Authenticator,
                Microsoft Authenticator, Authy). Even if your password is stolen, your account stays protected.
            </p>

            {/* Status pill */}
            <div className="mt-4">
                {twoFactor.enabled ? (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 ring-1 ring-green-200">
                        ● Two-factor is ON
                    </span>
                ) : (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-200">
                        ○ Two-factor is OFF
                    </span>
                )}
            </div>

            {/* Recovery codes (shown once, right after enabling) */}
            {recoveryCodes && recoveryCodes.length > 0 && (
                <div className="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-4">
                    <div className="text-sm font-semibold text-amber-900">Save your recovery codes</div>
                    <p className="mt-1 text-xs text-amber-800">
                        Store these somewhere safe. Each code lets you sign in once if you lose your phone. They won't be shown again.
                    </p>
                    <div className="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 font-mono text-sm text-gray-800">
                        {recoveryCodes.map((c) => <div key={c}>{c}</div>)}
                    </div>
                    <button
                        type="button"
                        onClick={() => navigator.clipboard?.writeText(recoveryCodes.join('\n'))}
                        className="mt-3 text-xs font-medium text-amber-800 underline hover:text-amber-900"
                    >
                        Copy all codes
                    </button>
                </div>
            )}

            {/* OFF → enable */}
            {!twoFactor.enabled && !twoFactor.confirming && (
                <div className="mt-5">
                    <PrimaryButton onClick={enable}>Enable two-factor</PrimaryButton>
                </div>
            )}

            {/* Setup in progress → scan QR + confirm a code */}
            {twoFactor.confirming && (
                <div className="mt-5 max-w-md">
                    <p className="text-sm text-gray-600">
                        1. Scan this QR code in your authenticator app — or enter the key manually.
                    </p>
                    <div className="mt-3 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                        {twoFactor.qr && (
                            <div className="rounded-lg border border-gray-200 bg-white p-3">
                                <QRCodeSVG value={twoFactor.qr} size={148} />
                            </div>
                        )}
                        <div className="text-sm">
                            <div className="text-gray-500">Setup key</div>
                            <code className="mt-0.5 block break-all rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-800">
                                {twoFactor.secret}
                            </code>
                        </div>
                    </div>

                    <form onSubmit={submitConfirm} className="mt-4">
                        <p className="text-sm text-gray-600">2. Enter the 6-digit code from the app to confirm.</p>
                        <div className="mt-2 flex items-start gap-2">
                            <div>
                                <InputLabel htmlFor="2fa-code" value="Code" className="sr-only" />
                                <TextInput
                                    id="2fa-code"
                                    value={confirm.data.code}
                                    onChange={(e) => confirm.setData('code', e.target.value)}
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                    placeholder="123 456"
                                    className="w-36 tracking-widest"
                                    autoFocus
                                />
                                <InputError message={confirm.errors.code} className="mt-1" />
                            </div>
                            <PrimaryButton disabled={confirm.processing}>Confirm</PrimaryButton>
                            <SecondaryButton type="button" onClick={cancel}>Cancel</SecondaryButton>
                        </div>
                    </form>
                </div>
            )}

            {/* ON → disable (password required) */}
            {twoFactor.enabled && (
                <div className="mt-5">
                    {!showDisable ? (
                        <DangerButton onClick={() => setShowDisable(true)}>Disable two-factor</DangerButton>
                    ) : (
                        <form onSubmit={submitDisable} className="max-w-md rounded-lg border border-gray-200 p-4">
                            <InputLabel htmlFor="2fa-pass" value="Confirm your password to turn off two-factor" />
                            <TextInput
                                id="2fa-pass"
                                type="password"
                                value={disable.data.password}
                                onChange={(e) => disable.setData('password', e.target.value)}
                                className="mt-1 block w-full"
                                autoComplete="current-password"
                                autoFocus
                            />
                            <InputError message={disable.errors.password} className="mt-1" />
                            <div className="mt-3 flex gap-2">
                                <DangerButton disabled={disable.processing}>Turn off</DangerButton>
                                <SecondaryButton type="button" onClick={() => { setShowDisable(false); disable.reset(); }}>Cancel</SecondaryButton>
                            </div>
                        </form>
                    )}
                </div>
            )}
        </section>
    );
}
