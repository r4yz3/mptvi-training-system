import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Printer, BadgeCheck, Clock } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import { IdCardFront, IdCardBack, fmtIdDate, type IdCardData, type Signatory } from '@/Components/IdCardFace';

export default function IdCard({ applicant, canIssue, signatory }: { applicant: IdCardData; canIssue: boolean; signatory: Signatory }) {
    const issued = fmtIdDate(applicant.issued);

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
                <div className="flex flex-col">
                    <div className="mb-2 text-center text-[10px] font-semibold uppercase tracking-widest text-slate-300 print:hidden">Front</div>
                    <IdCardFront a={applicant} signatory={signatory} />
                </div>
                <div className="flex flex-col">
                    <div className="mb-2 text-center text-[10px] font-semibold uppercase tracking-widest text-slate-300 print:hidden">Back</div>
                    <IdCardBack a={applicant} />
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
