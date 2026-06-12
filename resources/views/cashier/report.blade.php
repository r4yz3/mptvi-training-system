<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Payments Report</title>
<style>
    @page { size: A4 landscape; margin: 10mm; }
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; color: #1e293b; font-size: 10px; }
    .toolbar { position: fixed; top: 8px; right: 8px; }
    .toolbar button { font: 600 12px Arial; padding: 7px 14px; border: 0; border-radius: 6px; background: #15366B; color: #fff; cursor: pointer; }
    @media print { .toolbar { display: none; } }
    .lh { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #15366B; padding-bottom: 8px; margin-bottom: 10px; }
    .lh img { width: 48px; height: 48px; object-fit: contain; }
    .lh .t { flex: 1; text-align: center; }
    .lh h1 { font-size: 13px; margin: 0; color: #15366B; }
    .lh .s { font-size: 9px; color: #64748b; margin: 1px 0; }
    .summ { display: flex; gap: 12px; margin-bottom: 8px; }
    .card { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 10px; }
    .card .k { font-size: 8px; color: #64748b; text-transform: uppercase; }
    .card .v { font-size: 15px; font-weight: bold; color: #15366B; }
    .filters { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0 10px; }
    .chip { background: #eef3fb; border: 1px solid #d6e1f3; border-radius: 4px; padding: 2px 8px; font-size: 9px; }
    .chip b { color: #15366B; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #15366B; color: #fff; font-size: 8.5px; text-align: left; padding: 5px 6px; text-transform: uppercase; }
    th.r, td.r { text-align: right; }
    td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; font-size: 9.5px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .void { color: #94a3b8; text-decoration: line-through; }
    tfoot td { font-weight: bold; border-top: 2px solid #15366B; }
    .foot { margin-top: 14px; font-size: 9px; color: #64748b; }
    .sign { margin-top: 30px; display: flex; gap: 60px; }
    .sign .ln { border-top: 1px solid #000; padding-top: 2px; min-width: 220px; font-size: 9px; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>

    <div class="lh">
        <img src="/magsaysay-logo.png" alt="">
        <div class="t">
            <h1>MAXIMINO PELLERIN SR. TECHNICAL AND VOCATIONAL INSTITUTE</h1>
            <div class="s">PESO Magsaysay · Davao del Sur</div>
            <div class="s" style="font-weight:bold;color:#15366B;letter-spacing:.08em">PAYMENTS / COLLECTIONS REPORT</div>
        </div>
        <img src="/mptvi-logo.png" alt="">
    </div>

    <div class="summ">
        <div class="card"><div class="k">Total collected</div><div class="v">&#8369;{{ number_format($collected) }}</div></div>
        <div class="card"><div class="k">Payments</div><div class="v">{{ $count }}</div></div>
        @if($voided > 0)<div class="card"><div class="k">Voided (excluded)</div><div class="v" style="color:#e11d48">&#8369;{{ number_format($voided) }}</div></div>@endif
        <div class="card"><div class="k">Generated</div><div class="v" style="font-size:11px">{{ now()->format('M j, Y · g:i A') }}</div></div>
    </div>

    @if(count($summary))
        <div class="filters">
            <span style="font-size:9px;color:#64748b">Filters:</span>
            @foreach($summary as [$k, $v])
                <span class="chip"><b>{{ $k }}:</b> {{ $v }}</span>
            @endforeach
        </div>
    @else
        <div class="filters"><span style="font-size:9px;color:#94a3b8">No filters — all payments</span></div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:18px">#</th>
                <th>Date</th>
                <th>OR No.</th>
                <th>Learner</th>
                <th>Program</th>
                <th>Method</th>
                <th>Type</th>
                <th class="r">Amount</th>
                <th>Cashier</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ optional($p->paid_at)->format('Y-m-d') }}</td>
                    <td style="font-family:monospace;font-size:8.5px">{{ $p->or_number ?: '—' }}</td>
                    <td>{{ $p->applicant?->display_name }}</td>
                    <td>{{ $p->applicant?->program?->title }}</td>
                    <td>{{ $p->method }}</td>
                    <td>{{ $p->type }}</td>
                    <td class="r {{ $p->isVoided() ? 'void' : '' }}">&#8369;{{ number_format($p->amount) }}</td>
                    <td>{{ $p->cashier?->name }}</td>
                    <td>{{ $p->isVoided() ? 'VOID' : 'Valid' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;padding:20px;color:#94a3b8">No payments match the selected filters.</td></tr>
            @endforelse
        </tbody>
        @if($count > 0)
            <tfoot>
                <tr><td colspan="7" class="r">Total collected (excluding voided)</td><td class="r">&#8369;{{ number_format($collected) }}</td><td colspan="2"></td></tr>
            </tfoot>
        @endif
    </table>

    <div class="sign">
        <div><div class="ln">{{ $user->name }}<br><span style="color:#64748b">Prepared by — {{ config('rbac.roles')[$user->roleKey()] ?? '' }}</span></div></div>
        <div><div class="ln">{{ config('lpf.signatories.approved_by.name') }}<br><span style="color:#64748b">{{ config('lpf.signatories.approved_by.title') }}</span></div></div>
    </div>

    <div class="foot">Maximino Pellerin Sr. TVI — Training Management System · Collections report. Voided payments are listed but excluded from totals.</div>
</body>
</html>
