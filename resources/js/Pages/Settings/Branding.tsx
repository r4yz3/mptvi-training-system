import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Palette, CheckCircle2, ImageUp, RotateCcw } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

const PRESETS = ['#15366B', '#1f6feb', '#0f766e', '#7c3aed', '#b91c1c', '#c2410c', '#0e7490', '#4d7c0f'];

export default function Branding({ color, version }: { color: string; version: string }) {
    const { data, setData, post, processing, recentlySuccessful } = useForm<{ color: string; mptvi_logo: File | null; magsaysay_logo: File | null }>({
        color: color || '#15366B', mptvi_logo: null, magsaysay_logo: null,
    });
    const [mptviPrev, setMptviPrev] = useState<string | null>(null);
    const [magPrev, setMagPrev] = useState<string | null>(null);
    const bust = version ? `?v=${version}` : '';

    const submit = (e: React.FormEvent) => { e.preventDefault(); post('/settings/branding', { preserveScroll: true, forceFormData: true }); };
    const reset = () => setData('color', '');

    return (
        <AppShell title="Branding & logos">
            <Head title="Branding" />

            <Link href="/settings" className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft className="h-4 w-4" /> Back to settings
            </Link>

            <form onSubmit={submit} className="max-w-2xl space-y-5">
                {/* Color */}
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <header className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Palette className="h-4 w-4" /></span>
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800">Primary color</h3>
                            <p className="text-xs text-slate-500">Recolors buttons, the sidebar, links and highlights across the app.</p>
                        </div>
                    </header>
                    <div className="space-y-4 p-5">
                        <div className="flex flex-wrap items-center gap-3">
                            <input type="color" value={data.color || '#15366B'} onChange={(e) => setData('color', e.target.value)} className="h-10 w-14 cursor-pointer rounded border border-slate-200" />
                            <input className="input w-32 font-mono uppercase" value={data.color} onChange={(e) => setData('color', e.target.value)} placeholder="#15366B" />
                            <button type="button" onClick={reset} className="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-brand-600"><RotateCcw className="h-3.5 w-3.5" /> Reset to default navy</button>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {PRESETS.map((p) => (
                                <button key={p} type="button" onClick={() => setData('color', p)} style={{ background: p }}
                                    className={`h-7 w-7 rounded-full ring-2 ring-offset-2 ${data.color?.toLowerCase() === p.toLowerCase() ? 'ring-slate-400' : 'ring-transparent'}`} />
                            ))}
                        </div>
                        {/* Live preview */}
                        <div className="rounded-lg border border-slate-200 p-3">
                            <div className="mb-2 text-xs text-slate-400">Preview</div>
                            <div className="flex items-center gap-2">
                                <span className="rounded-md px-3 py-1.5 text-sm font-medium text-white" style={{ background: data.color || '#15366B' }}>Primary button</span>
                                <span className="rounded-full px-2.5 py-0.5 text-xs font-medium" style={{ background: tint(data.color, 0.88), color: data.color || '#15366B' }}>Badge</span>
                                <span className="text-sm font-medium" style={{ color: data.color || '#15366B' }}>Link</span>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Logos */}
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <header className="flex items-center gap-2.5 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><ImageUp className="h-4 w-4" /></span>
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800">Logos</h3>
                            <p className="text-xs text-slate-500">Used on the ID card, certificate and login. Square PNG with transparency works best.</p>
                        </div>
                    </header>
                    <div className="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">
                        <LogoField label="MPTVI logo" current={`/mptvi-logo.png${bust}`} preview={mptviPrev}
                            onPick={(f, url) => { setData('mptvi_logo', f); setMptviPrev(url); }} />
                        <LogoField label="Magsaysay logo" current={`/magsaysay-logo.png${bust}`} preview={magPrev}
                            onPick={(f, url) => { setData('magsaysay_logo', f); setMagPrev(url); }} />
                    </div>
                </section>

                <div className="flex items-center gap-3">
                    <button type="submit" disabled={processing} className="btn-primary">Save branding</button>
                    {recentlySuccessful && <span className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"><CheckCircle2 className="h-4 w-4" /> Saved · refresh to see logos</span>}
                </div>
            </form>
        </AppShell>
    );
}

function LogoField({ label, current, preview, onPick }: { label: string; current: string; preview: string | null; onPick: (f: File, url: string) => void }) {
    return (
        <div>
            <span className="mb-1.5 block text-xs font-medium text-slate-600">{label}</span>
            <div className="flex items-center gap-3">
                <img src={preview ?? current} alt="" className="h-14 w-14 rounded-lg border border-slate-200 object-contain p-1" />
                <label className="btn-ghost cursor-pointer">
                    <ImageUp className="h-4 w-4" /> Choose…
                    <input type="file" accept="image/png,image/jpeg,image/webp" className="hidden"
                        onChange={(e) => { const f = e.target.files?.[0]; if (f) onPick(f, URL.createObjectURL(f)); }} />
                </label>
            </div>
        </div>
    );
}

/** light tint of a hex for badge backgrounds in the preview */
function tint(hex: string, t: number): string {
    const m = /^#?([0-9a-f]{6})$/i.exec((hex || '#15366B').trim());
    if (!m) return '#eef3fb';
    const c = [parseInt(m[1].slice(0, 2), 16), parseInt(m[1].slice(2, 4), 16), parseInt(m[1].slice(4, 6), 16)];
    const v = c.map((x) => Math.round(x + (255 - x) * t));
    return `rgb(${v[0]} ${v[1]} ${v[2]})`;
}
