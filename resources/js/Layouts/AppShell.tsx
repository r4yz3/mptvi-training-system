import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    Users,
    ClipboardCheck,
    Banknote,
    CalendarDays,
    GraduationCap,
    Award,
    IdCard,
    MessageSquare,
    Megaphone,
    BarChart3,
    History,
    Settings,
    SlidersHorizontal,
    UserCog,
    Download,
    Menu,
    X,
    LogOut,
    type LucideIcon,
} from 'lucide-react';
import clsx from 'clsx';
import { PageProps } from '@/types';

const ICONS: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
    users: Users,
    'clipboard-check': ClipboardCheck,
    banknote: Banknote,
    'calendar-days': CalendarDays,
    'graduation-cap': GraduationCap,
    award: Award,
    'id-card': IdCard,
    'message-square': MessageSquare,
    megaphone: Megaphone,
    'bar-chart-3': BarChart3,
    history: History,
    settings: Settings,
    sliders: SlidersHorizontal,
    'user-cog': UserCog,
    download: Download,
};

/** Build a `:root` override of the --brand-* channels from a single primary hex. */
function brandPalette(hex: string): string | null {
    const m = /^#?([0-9a-f]{6})$/i.exec(hex.trim());
    if (!m) return null;
    const base = [parseInt(m[1].slice(0, 2), 16), parseInt(m[1].slice(2, 4), 16), parseInt(m[1].slice(4, 6), 16)] as const;
    const mix = (c: readonly number[], t: readonly number[], r: number) => c.map((v, i) => Math.round(v + (t[i] - v) * r)).join(' ');
    const W = [255, 255, 255], B = [0, 0, 0];
    const shades: Record<string, string> = {
        50: mix(base, W, 0.9), 100: mix(base, W, 0.8), 200: mix(base, W, 0.64), 300: mix(base, W, 0.46),
        400: mix(base, W, 0.26), 500: mix(base, W, 0.12), 600: base.join(' '),
        700: mix(base, B, 0.16), 800: mix(base, B, 0.3), 900: mix(base, B, 0.44), 950: mix(base, B, 0.62),
    };
    return ':root{' + Object.entries(shades).map(([k, v]) => `--brand-${k}:${v}`).join(';') + '}';
}

export default function AppShell({
    title,
    header,
    children,
}: PropsWithChildren<{ title?: string; header?: ReactNode }>) {
    const page = usePage<PageProps>();
    const { auth, nav, flash, badges } = page.props;
    const brandColor = (page.props as { branding?: { color?: string } }).branding?.color;
    const brandCss = brandColor ? brandPalette(brandColor) : null;
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const [open, setOpen] = useState(false);
    const [toast, setToast] = useState<{ kind: 'success' | 'error'; msg: string } | null>(null);

    useEffect(() => {
        if (flash?.success) setToast({ kind: 'success', msg: flash.success });
        else if (flash?.error) setToast({ kind: 'error', msg: flash.error });
    }, [flash?.success, flash?.error]);

    useEffect(() => {
        if (!toast) return;
        const t = setTimeout(() => setToast(null), 3200);
        return () => clearTimeout(t);
    }, [toast]);

    const isActive = (id: string) =>
        currentPath === `/${id}` || currentPath.startsWith(`/${id}/`);

    // Group nav items by their `group`, preserving order.
    const groups: { name: string; items: typeof nav }[] = [];
    nav.forEach((m) => {
        const name = m.group ?? '';
        const g = groups.find((x) => x.name === name);
        if (g) g.items.push(m);
        else groups.push({ name, items: [m] });
    });

    return (
        <div className="min-h-screen bg-slate-100">
            {brandCss && <style>{brandCss}</style>}
            {/* Sidebar */}
            <aside
                className={clsx(
                    'fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-gradient-to-b from-brand-700 to-brand-600 text-white transition-transform duration-200 lg:translate-x-0',
                    open ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                <div className="flex items-center gap-3 border-b border-white/10 px-4 py-4">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white shadow-sm">
                        <img src="/mptvi-logo.png" alt="MPTVI" className="h-9 w-9 object-contain" />
                    </div>
                    <div className="leading-tight">
                        <div className="text-sm font-semibold">Maximino Pellerin Sr.</div>
                        <div className="text-[11px] text-white/70">Technical &amp; Vocational Institute</div>
                    </div>
                </div>

                <nav className="nav-scroll flex-1 space-y-5 overflow-y-auto px-3 py-4">
                    {groups.map((g, gi) => (
                        <div key={g.name} className={clsx(gi > 0 && 'border-t border-white/[0.07] pt-4')}>
                            {g.name && (
                                <div className="px-3 pb-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/45">{g.name}</div>
                            )}
                            <div className="space-y-0.5">
                                {g.items.map((m) => {
                                    const Icon = ICONS[m.icon] ?? LayoutDashboard;
                                    const active = isActive(m.id);
                                    const badge = badges?.[m.id] ?? 0;
                                    return (
                                        <Link
                                            key={m.id}
                                            href={`/${m.id}`}
                                            onClick={() => setOpen(false)}
                                            aria-current={active ? 'page' : undefined}
                                            className={clsx(
                                                'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors duration-150',
                                                active
                                                    ? 'bg-white font-semibold text-brand-700 shadow-sm ring-1 ring-black/[0.04]'
                                                    : 'font-medium text-white/75 hover:bg-white/10 hover:text-white',
                                            )}
                                        >
                                            {active && <span className="absolute inset-y-2 left-0 w-[3px] rounded-r-full bg-brand-600" />}
                                            <Icon
                                                className={clsx(
                                                    'h-[18px] w-[18px] shrink-0 transition-colors',
                                                    active ? 'text-brand-600' : 'text-white/55 group-hover:text-white',
                                                )}
                                            />
                                            <span className="flex-1 truncate">{m.label}</span>
                                            {badge > 0 && (
                                                <span className={clsx(
                                                    'min-w-[18px] rounded-full px-1.5 py-0.5 text-center text-[10px] font-semibold leading-none',
                                                    active ? 'bg-brand-600 text-white' : 'bg-rose-500 text-white',
                                                )}>
                                                    {badge}
                                                </span>
                                            )}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </nav>

                <div className="border-t border-white/10 p-3">
                    <div className="flex items-center gap-2.5 rounded-lg bg-white/[0.06] px-3 py-2.5">
                        <div className="relative shrink-0">
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-xs font-semibold ring-1 ring-white/20">{auth.user.initials}</div>
                            <span className="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-brand-600" />
                        </div>
                        <div className="min-w-0 leading-tight">
                            <div className="truncate text-xs font-semibold text-white">{auth.user.name}</div>
                            <div className="truncate text-[10px] text-white/60">{auth.user.roleLabel}</div>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Mobile overlay */}
            {open && (
                <div
                    className="fixed inset-0 z-30 bg-black/40 lg:hidden"
                    onClick={() => setOpen(false)}
                />
            )}

            {/* Main column */}
            <div className="lg:pl-64">
                <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 shadow-sm sm:px-6">
                    <button
                        className="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                        onClick={() => setOpen((v) => !v)}
                        aria-label="Toggle navigation"
                    >
                        {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                    </button>

                    <h1 className="text-lg font-semibold text-slate-800">{title}</h1>

                    <div className="ml-auto flex items-center gap-3">
                        <Link href="/profile" className="group flex items-center gap-3 rounded-lg px-1.5 py-1 transition hover:bg-slate-100" title="Your profile & security">
                            <div className="hidden text-right sm:block">
                                <div className="text-sm font-medium text-slate-800">{auth.user.name}</div>
                                <div className="text-xs text-slate-500">{auth.user.roleLabel}</div>
                            </div>
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">
                                {auth.user.initials}
                            </div>
                        </Link>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                            title="Log out"
                        >
                            <LogOut className="h-5 w-5" />
                        </Link>
                    </div>
                </header>

                {header && (
                    <div className="border-b border-slate-200 bg-white px-4 py-4 sm:px-6">{header}</div>
                )}

                <main className="px-4 py-6 sm:px-6">{children}</main>
            </div>

            {/* Flash toast */}
            {toast && (
                <div
                    className={clsx(
                        'fixed bottom-5 right-5 z-50 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg',
                        toast.kind === 'success' ? 'bg-emerald-600' : 'bg-rose-600',
                    )}
                >
                    {toast.msg}
                </div>
            )}
        </div>
    );
}
