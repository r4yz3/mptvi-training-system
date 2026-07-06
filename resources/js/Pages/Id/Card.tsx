import { Head, Link, router } from '@inertiajs/react';
import { QRCodeSVG } from 'qrcode.react';
import { ArrowLeft, Printer, BadgeCheck, Clock, UserCircle2 } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';

interface Card {
    id: number; name: string; photo_url: string | null;
    program: string | null; level: string | null; batch: string | null;
    barangay: string | null; province: string | null; contact: string | null;
    emergency_name: string | null; emergency_contact: string | null;
    school_year: string | null; issued: string | null;
}
interface Signatory { name: string; title: string }

function fmtDate(d: string | null) {
    if (!d) return null;
    const dt = new Date(d + 'T00:00:00');
    return isNaN(dt.getTime()) ? d : dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

export default function IdCard({ applicant, canIssue, signatory }: { applicant: Card; canIssue: boolean; signatory: Signatory }) {
    const issued = fmtDate(applicant.issued);
    const address = [
        applicant.barangay ? `Brgy. ${applicant.barangay}` : null,
        'Magsaysay',
        applicant.province ?? 'Davao del Sur',
    ].filter(Boolean).join(', ');

    return (
        <AppShell title="Trainee ID card">
            <Head title={`ID — ${applicant.name}`} />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div className="flex items-center gap-3">
                    <Link href="/idsystem" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                        <ArrowLeft className="h-4 w-4" /> Back
                    </Link>
                    {issued
                        ? <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"><BadgeCheck className="h-3.5 w-3.5" /> Issued {issued}</span>
                        : <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700"><Clock className="h-3.5 w-3.5" /> Not yet issued</span>}
                </div>
                <div className="flex gap-2">
                    {canIssue && (
                        <button onClick={() => router.put(`/idsystem/${applicant.id}/issue`, {}, { preserveScroll: true })} className="btn-ghost">
                            <BadgeCheck className="h-4 w-4" /> {applicant.issued ? 'Re-issue' : 'Mark issued'}
                        </button>
                    )}
                    <button onClick={() => window.print()} className="btn-primary"><Printer className="h-4 w-4" /> Print</button>
                </div>
            </div>

            <p className="mb-4 text-xs text-slate-400 print:hidden">Standard student ID size — CR80, 54 × 85.6 mm. Prints true to size.</p>

            <div className="flex flex-wrap justify-center gap-8 print:gap-4" id="cards">
                {/* FRONT */}
                <div className="flex flex-col">
                    <div className="mb-2 text-center text-[10px] font-semibold uppercase tracking-widest text-slate-300 print:hidden">Front</div>
                    <div className="id-card relative flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg ring-1 ring-black/5 print:shadow-none">
                        {/* Header */}
                        <div className="flex items-center justify-center gap-1.5 bg-gradient-to-br from-brand-800 via-brand-700 to-brand-600 px-2 py-1.5 text-center text-white">
                            <img src="/mptvi-logo.png" alt="" className="h-7 w-7 shrink-0 rounded-full bg-white object-contain p-px ring-1 ring-white/40" />
                            <div className="leading-tight">
                                <div className="text-[5px] uppercase tracking-wider text-white/70">Republic of the Philippines</div>
                                <div className="text-[7.5px] font-bold leading-tight">Maximino Pellerin Sr.</div>
                                <div className="text-[6px] font-semibold text-white/90">Technical and Vocational Institute</div>
                                <div className="text-[5.5px] text-white/80">PESO Magsaysay · Davao del Sur</div>
                            </div>
                            <img src="/magsaysay-logo.png" alt="" className="h-7 w-7 shrink-0 rounded-full bg-white object-contain p-px ring-1 ring-white/40" />
                        </div>
                        <div className="bg-amber-400 py-px text-center text-[6px] font-bold uppercase tracking-[0.2em] text-brand-900">Trainee Identification Card</div>

                        {/* Body */}
                        <div className="flex flex-1 flex-col items-center justify-between px-3 py-2">
                            {applicant.photo_url
                                ? <img src={applicant.photo_url} alt="" className="h-[72px] w-[72px] rounded-md border-2 border-white object-cover shadow ring-1 ring-slate-200" />
                                : <UserCircle2 className="h-[72px] w-[72px] text-slate-200" />}

                            <div className="text-center">
                                <div className="text-[10px] font-extrabold uppercase leading-tight text-slate-800">{applicant.name}</div>
                                <div className="mt-0.5 inline-block rounded-full bg-brand-50 px-1.5 py-px text-[6px] font-medium text-brand-700">
                                    {applicant.program ?? '—'}{applicant.level ? ` · ${applicant.level}` : ''}
                                </div>
                            </div>

                            <div className="flex w-full items-end justify-between">
                                <div className="space-y-px text-[6px] text-slate-900">
                                    <div><span className="text-slate-900">Batch</span> <span className="font-semibold text-slate-900">{applicant.batch ?? '—'}</span></div>
                                    <div><span className="text-slate-900">S.Y.</span> <span className="font-semibold text-slate-900">{applicant.school_year ?? '—'}</span></div>
                                    <div><span className="text-slate-900">Issued</span> <span className="font-semibold text-slate-900">{issued ?? '—'}</span></div>
                                </div>
                                <div className="rounded border border-slate-100 bg-white p-0.5 shadow-sm">
                                    <QRCodeSVG value={String(applicant.id)} size={42} level="M" />
                                </div>
                            </div>

                            {/* Authorized signatory */}
                            <div className="w-full text-center">
                                <div className="mx-auto w-32 border-t border-slate-300 pt-px text-[6px] font-semibold uppercase leading-tight text-slate-900">{signatory.name}</div>
                                <div className="text-[5px] text-slate-900">{signatory.title}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* BACK */}
                <div className="flex flex-col">
                    <div className="mb-2 text-center text-[10px] font-semibold uppercase tracking-widest text-slate-300 print:hidden">Back</div>
                    <div className="id-card flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg ring-1 ring-black/5 print:shadow-none">
                        <div className="bg-gradient-to-r from-brand-800 to-brand-600 py-1 text-center text-[8px] font-semibold uppercase tracking-wide text-white">Trainee Information</div>
                        <div className="flex flex-1 flex-col px-3 py-2 text-[7px]">
                            <div className="space-y-1">
                                <Row label="Address" value={address} />
                                <Row label="Contact no." value={applicant.contact} />
                                <Row label="Emergency contact" value={applicant.emergency_name} />
                                <Row label="Emergency no." value={applicant.emergency_contact} />
                                <Row label="Date issued" value={issued} />
                            </div>

                            <div className="mt-1.5 rounded bg-brand-50 px-2 py-0.5 text-center text-[6px] font-semibold text-brand-700">
                                Valid for S.Y. {applicant.school_year ?? '—'}
                            </div>

                            <div className="mt-auto text-center">
                                <div className="h-6" />
                                <div className="border-t border-dashed border-slate-300 pt-0.5 text-[6px] text-slate-900">
                                    Signature of Holder
                                </div>
                            </div>
                            <p className="pt-1 text-center text-[5.5px] leading-snug text-slate-900">
                                If found, please return to the MPTVI office, Magsaysay, Davao del Sur.
                            </p>
                        </div>
                        <div className="bg-slate-100 py-0.5 text-center text-[6px] uppercase tracking-[0.2em] text-slate-900">
                            Maximino Pellerin Sr. TVI
                        </div>
                    </div>
                </div>
            </div>

            <style>{`
                .id-card { width: 54mm; height: 85.6mm; }
                #cards, #cards * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                @media print {
                    body * { visibility: hidden; }
                    #cards, #cards * { visibility: visible; }
                    #cards { position: absolute; left: 0; top: 0; }
                }
            `}</style>
        </AppShell>
    );
}

function Row({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="flex justify-between gap-2 border-b border-slate-100 pb-0.5">
            <span className="shrink-0 text-slate-900">{label}</span>
            <span className="truncate text-right font-medium text-slate-900">{value || '—'}</span>
        </div>
    );
}
