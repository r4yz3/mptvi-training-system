import { useState, FormEvent } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Upload, Download, FileSpreadsheet, CheckCircle2, AlertTriangle, Users, GraduationCap } from 'lucide-react';
import AppShell from '@/Layouts/AppShell';
import { PageProps } from '@/types';

interface ProgramOpt { id: number; title: string }
interface PreviewRow { line: number; data: Record<string, string>; ok: boolean; error: string }
interface Preview { type: string; token: string; ext: string; rows: PreviewRow[]; ok: number; errors: number }

export default function ImportIndex({ canTrainees, canGrades, programs }: { canTrainees: boolean; canGrades: boolean; programs: ProgramOpt[] }) {
    const { flash } = usePage<PageProps & { flash: { import_preview?: Preview } }>().props as any;
    const preview: Preview | undefined = flash?.import_preview;
    const [tab, setTab] = useState<'trainees' | 'grades'>(canTrainees ? 'trainees' : 'grades');

    return (
        <AppShell title="Import (Excel/CSV)">
            <Head title="Import" />

            <div className="mb-5 max-w-3xl rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3 text-sm text-brand-800">
                Upload trainees or grades from a spreadsheet. <b>Download the template</b>, fill it in Excel, then upload — every row is
                checked and you get a preview before anything is saved. CSV and .xlsx are both accepted.
            </div>

            <div className="mb-4 flex gap-2">
                {canTrainees && <TabButton active={tab === 'trainees'} onClick={() => setTab('trainees')} icon={<Users className="h-4 w-4" />}>Trainees</TabButton>}
                {canGrades && <TabButton active={tab === 'grades'} onClick={() => setTab('grades')} icon={<GraduationCap className="h-4 w-4" />}>Grades</TabButton>}
            </div>

            {tab === 'trainees' && canTrainees && <TraineePanel />}
            {tab === 'grades' && canGrades && <GradePanel programs={programs} />}

            {preview && preview.type === tab && <PreviewPanel preview={preview} />}
        </AppShell>
    );
}

function TabButton({ active, onClick, icon, children }: { active: boolean; onClick: () => void; icon: React.ReactNode; children: React.ReactNode }) {
    return (
        <button onClick={onClick} className={`inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium ${active ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'}`}>
            {icon} {children}
        </button>
    );
}

function Card({ children }: { children: React.ReactNode }) {
    return <div className="max-w-3xl rounded-xl border border-slate-200 bg-white p-5 shadow-sm">{children}</div>;
}

function TraineePanel() {
    const { data, setData, post, processing, errors } = useForm<{ file: File | null }>({ file: null });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/import/trainees/preview', { forceFormData: true, preserveScroll: true });
    };
    return (
        <Card>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 className="text-sm font-semibold text-slate-800">Import trainees</h3>
                    <p className="mt-0.5 text-xs text-slate-500">Columns: Last name, First name, …, Program, School year. Program is matched by title.</p>
                </div>
                <a href="/import/trainees/template" className="btn-ghost"><Download className="h-4 w-4" /> Template</a>
            </div>
            <form onSubmit={submit} className="mt-4 flex flex-wrap items-center gap-3">
                <input type="file" accept=".csv,.xlsx" onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                    className="block text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100" />
                <button type="submit" disabled={processing || !data.file} className="btn-primary disabled:opacity-40"><Upload className="h-4 w-4" /> Check file</button>
            </form>
            {errors.file && <p className="mt-2 text-sm text-rose-600">{errors.file}</p>}
        </Card>
    );
}

function GradePanel({ programs }: { programs: ProgramOpt[] }) {
    const [program, setProgram] = useState<string>(programs[0] ? String(programs[0].id) : '');
    const { data, setData, post, processing, errors } = useForm<{ file: File | null }>({ file: null });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/import/grades/preview', { forceFormData: true, preserveScroll: true });
    };
    return (
        <Card>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 className="text-sm font-semibold text-slate-800">Import grades</h3>
                    <p className="mt-0.5 text-xs text-slate-500">Download a program template pre-filled with its trainees × subjects, enter the 1.00–5.00 grades, then upload.</p>
                </div>
                <div className="flex items-center gap-2">
                    <select className="input !w-auto py-1.5" value={program} onChange={(e) => setProgram(e.target.value)}>
                        {programs.map((p) => <option key={p.id} value={p.id}>{p.title}</option>)}
                    </select>
                    <a href={`/import/grades/template?program=${program}`} className="btn-ghost"><Download className="h-4 w-4" /> Template</a>
                </div>
            </div>
            <form onSubmit={submit} className="mt-4 flex flex-wrap items-center gap-3">
                <input type="file" accept=".csv,.xlsx" onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                    className="block text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100" />
                <button type="submit" disabled={processing || !data.file} className="btn-primary disabled:opacity-40"><Upload className="h-4 w-4" /> Check file</button>
            </form>
            {errors.file && <p className="mt-2 text-sm text-rose-600">{errors.file}</p>}
        </Card>
    );
}

function PreviewPanel({ preview }: { preview: Preview }) {
    const commit = () => router.post(`/import/${preview.type}/commit`, { token: preview.token, ext: preview.ext });
    const cols = preview.rows[0] ? Object.keys(preview.rows[0].data) : [];

    return (
        <div className="mt-5 max-w-5xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                <div className="flex items-center gap-2.5">
                    <FileSpreadsheet className="h-5 w-5 text-brand-600" />
                    <div className="text-sm">
                        <span className="inline-flex items-center gap-1 font-medium text-emerald-700"><CheckCircle2 className="h-4 w-4" /> {preview.ok} ready</span>
                        {preview.errors > 0 && <span className="ml-3 inline-flex items-center gap-1 font-medium text-amber-700"><AlertTriangle className="h-4 w-4" /> {preview.errors} with issues (skipped)</span>}
                    </div>
                </div>
                <button onClick={commit} disabled={preview.ok === 0} className="btn-primary disabled:opacity-40">
                    <CheckCircle2 className="h-4 w-4" /> Import {preview.ok} row{preview.ok === 1 ? '' : 's'}
                </button>
            </div>
            <div className="max-h-[26rem] overflow-auto">
                <table className="min-w-full text-sm">
                    <thead className="sticky top-0 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th className="px-3 py-2">Row</th>
                            <th className="px-3 py-2">Status</th>
                            {cols.map((c) => <th key={c} className="whitespace-nowrap px-3 py-2">{c}</th>)}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {preview.rows.map((r, i) => (
                            <tr key={i} className={r.ok ? '' : 'bg-amber-50/40'}>
                                <td className="px-3 py-2 text-slate-400">{r.line}</td>
                                <td className="px-3 py-2">
                                    {r.ok
                                        ? <span className="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><CheckCircle2 className="h-3.5 w-3.5" /> OK</span>
                                        : <span className="inline-flex items-center gap-1 text-xs font-medium text-amber-700" title={r.error}><AlertTriangle className="h-3.5 w-3.5" /> {r.error}</span>}
                                </td>
                                {cols.map((c) => <td key={c} className="whitespace-nowrap px-3 py-2 text-slate-600">{r.data[c]}</td>)}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
