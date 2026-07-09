import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer, IdCard } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import { IdCardFront, IdCardBack, type IdCardData, type Signatory } from '@/Components/IdCardFace';

const PER_PAGE = 9; // 3 columns × 3 rows of CR80 cards on one A4

function chunk<T>(arr: T[], n: number): T[][] {
    const out: T[][] = [];
    for (let i = 0; i < arr.length; i += n) out.push(arr.slice(i, i + n));
    return out;
}

export default function IdSheet({ applicants, signatory, label }: { applicants: IdCardData[]; signatory: Signatory; label: string }) {
    const [backs, setBacks] = useState(false);
    const pages = chunk(applicants, PER_PAGE);
    const sheets = backs ? pages.length * 2 : pages.length;

    return (
        <AppShell title="Bulk ID print">
            <Head title="Bulk ID print" />

            {/* Toolbar (never printed) */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div className="flex items-center gap-3">
                    <Link href="/idsystem" className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                        <ArrowLeft className="h-4 w-4" /> Back
                    </Link>
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700">
                        <IdCard className="h-3.5 w-3.5" /> {label}
                    </span>
                    <span className="text-xs text-slate-400">{applicants.length} card{applicants.length === 1 ? '' : 's'} · {sheets} A4 page{sheets === 1 ? '' : 's'}</span>
                </div>
                <div className="flex items-center gap-3">
                    <label className="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" className="rounded border-slate-300 text-brand-600 focus:ring-brand-400" checked={backs} onChange={(e) => setBacks(e.target.checked)} />
                        Include backs
                    </label>
                    <button onClick={() => window.print()} disabled={applicants.length === 0} className="btn-primary disabled:opacity-50">
                        <Printer className="h-4 w-4" /> Print
                    </button>
                </div>
            </div>

            {applicants.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center text-sm text-slate-400 print:hidden">
                    <IdCard className="mx-auto mb-2 h-8 w-8 text-slate-300" />
                    No trainees selected for printing.
                </div>
            ) : (
                <>
                    <p className="mb-4 text-xs text-slate-400 print:hidden">
                        9 cards per A4 (3 × 3), true to CR80 size. Print, then cut along the card edges.{backs && ' Backs follow the fronts — flip the stack to print double-sided.'}
                    </p>

                    <div id="sheet">
                        {pages.map((page, pi) => (
                            <div className="sheet-page" key={`f${pi}`}>
                                {page.map((a) => <IdCardFront key={a.id} a={a} signatory={signatory} />)}
                            </div>
                        ))}
                        {backs && pages.map((page, pi) => (
                            <div className="sheet-page" key={`b${pi}`}>
                                {page.map((a) => <IdCardBack key={a.id} a={a} />)}
                            </div>
                        ))}
                    </div>
                </>
            )}

            <style>{`
                .id-card { width: 54mm; height: 85.6mm; }
                .sheet-page { display: grid; grid-template-columns: repeat(3, 54mm); gap: 4mm; justify-content: center; align-content: start; }
                #sheet, #sheet * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                @media screen {
                    .sheet-page { width: fit-content; margin: 0 auto 16px; padding: 8mm; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,.12); }
                }
                @media print {
                    @page { size: A4 portrait; margin: 8mm; }
                    body * { visibility: hidden; }
                    #sheet, #sheet * { visibility: visible; }
                    #sheet { position: absolute; left: 0; top: 0; width: 100%; }
                    .sheet-page { padding: 0; margin: 0; page-break-after: always; }
                    .sheet-page:last-child { page-break-after: auto; }
                }
            `}</style>
        </AppShell>
    );
}
