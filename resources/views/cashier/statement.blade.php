<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Statement of Account — {{ $a->display_name }}</title>
<style>
    @page { size: A4 portrait; margin: 12mm; }
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; color: #1e293b; font-size: 11px; }
    .toolbar { position: fixed; top: 8px; right: 8px; }
    .toolbar button { font: 600 12px Arial; padding: 7px 14px; border: 0; border-radius: 6px; background: #15366B; color: #fff; cursor: pointer; }
    @media print { .toolbar { display: none; } }
    .lh { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #15366B; padding-bottom: 8px; margin-bottom: 10px; }
    .lh img { width: 48px; height: 48px; object-fit: contain; }
    .lh .t { flex: 1; text-align: center; }
    .lh h1 { font-size: 13px; margin: 0; color: #15366B; }
    .lh .s { font-size: 9px; color: #64748b; margin: 1px 0; }
    .lh .title { font-weight: bold; color: #15366B; letter-spacing: .08em; font-size: 11px; margin-top: 2px; }
    .who { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px; }
    .who .row { font-size: 11px; margin: 3px 0; }
    .who .row b { color: #0f172a; }
    .who .k { color: #64748b; display: inline-block; min-width: 70px; }
    .summ { display: flex; gap: 10px; margin-bottom: 12px; }
    .card { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 10px; }
    .card .k { font-size: 8px; color: #64748b; text-transform: uppercase; }
    .card .v { font-size: 16px; font-weight: bold; color: #15366B; }
    .card.due .v { color: #e11d48; }
    .card.paid .v { color: #15803d; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    th { background: #15366B; color: #fff; font-size: 8.5px; text-align: left; padding: 5px 6px; text-transform: uppercase; }
    th.r, td.r { text-align: right; }
    td { border-bottom: 1px solid #e2e8f0; padding: 5px 6px; font-size: 10px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .void { color: #94a3b8; text-decoration: line-through; }
    .badge { font-size: 8px; padding: 1px 5px; border-radius: 3px; background: #fee2e2; color: #b91c1c; }
    .feetag { font-size: 8px; color: #15366B; }
    tfoot td { font-weight: bold; border-top: 2px solid #15366B; background: #eef3fb; }
    .sec { font-size: 11px; font-weight: bold; color: #15366B; margin: 14px 0 4px; }
    .sign { margin-top: 34px; display: flex; justify-content: space-between; gap: 40px; }
    .sign .ln { border-top: 1px solid #000; padding-top: 3px; min-width: 200px; text-align: center; font-size: 10px; }
    .foot { margin-top: 16px; font-size: 8.5px; color: #94a3b8; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>

    <div class="lh">
        <img src="/magsaysay-logo.png" alt="">
        <div class="t">
            <h1>MAXIMINO PELLERIN SR. TECHNICAL AND VOCATIONAL INSTITUTE</h1>
            <div class="s">PESO Magsaysay · Davao del Sur</div>
            <div class="title">STATEMENT OF ACCOUNT</div>
        </div>
        <img src="/mptvi-logo.png" alt="">
    </div>

    <div class="who">
        <div>
            <div class="row"><span class="k">Learner</span> <b>{{ $a->display_name }}</b></div>
            <div class="row"><span class="k">Program</span> {{ $a->program?->title ?? '—' }}{{ $a->program?->level ? ' ('.$a->program->level.')' : '' }}</div>
        </div>
        <div style="text-align:right">
            <div class="row"><span class="k">Status</span> <b>{{ $payStatus }}</b></div>
            <div class="row"><span class="k">Generated</span> {{ now()->format('M j, Y · g:i A') }}</div>
        </div>
    </div>

    <div class="summ">
        <div class="card"><div class="k">Program fee</div><div class="v">&#8369;{{ number_format($fee) }}</div></div>
        <div class="card paid"><div class="k">Fee paid</div><div class="v">&#8369;{{ number_format($paid) }}</div></div>
        <div class="card due"><div class="k">Balance due</div><div class="v">&#8369;{{ number_format($balance) }}</div></div>
        <div class="card"><div class="k">Other collections</div><div class="v">&#8369;{{ number_format($other) }}</div></div>
    </div>

    <div class="sec">Payment history</div>
    <table>
        <thead>
            <tr>
                <th>Date</th><th>OR No.</th><th>Particulars</th><th>Method</th><th class="r">Amount</th><th class="r">Fee balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr class="{{ $r['voided'] ? 'void' : '' }}">
                    <td>{{ $r['date']?->format('M j, Y') }}</td>
                    <td>{{ $r['or_number'] ?? '—' }}</td>
                    <td>
                        {{ $r['category'] }}@if($r['is_fee'])<span class="feetag"> · fee</span>@endif{{ $r['description'] ? ' — '.$r['description'] : '' }}
                        @if($r['voided'])<span class="badge">VOID</span>@endif
                    </td>
                    <td>{{ $r['method'] }}</td>
                    <td class="r">&#8369;{{ number_format($r['amount']) }}</td>
                    <td class="r">@if($r['balance'] !== null)&#8369;{{ number_format($r['balance']) }}@else&mdash;@endif</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:14px">No payments recorded yet.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total fee paid</td>
                <td class="r">&#8369;{{ number_format($paid) }}</td>
                <td class="r">&#8369;{{ number_format($balance) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="sign">
        <div class="ln">Cashier / Collecting Officer</div>
        <div class="ln">Learner's signature</div>
    </div>

    <div class="foot">
        This statement reflects payments on record as of the generated date. Only Training-fee payments draw down the program-fee balance;
        other collections are shown separately. Not a BIR-registered official receipt. &nbsp;|&nbsp; Prepared by {{ $user->name }}.
    </div>
</body>
</html>
