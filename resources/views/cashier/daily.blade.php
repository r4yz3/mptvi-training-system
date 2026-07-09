<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Daily Cash Report — {{ $date->format('M j, Y') }}</title>
<style>
    @page { size: A4 portrait; margin: 12mm; }
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
    .lh .title { font-weight: bold; color: #15366B; letter-spacing: .08em; font-size: 11px; margin-top: 2px; }
    .meta { display: flex; justify-content: space-between; font-size: 10px; color: #475569; margin-bottom: 8px; }
    .meta b { color: #15366B; }
    .summ { display: flex; gap: 10px; margin-bottom: 12px; }
    .card { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 10px; }
    .card .k { font-size: 8px; color: #64748b; text-transform: uppercase; }
    .card .v { font-size: 16px; font-weight: bold; color: #15366B; }
    .cols { display: flex; gap: 16px; }
    .cols > div { flex: 1; }
    .sec { font-size: 10.5px; font-weight: bold; color: #15366B; margin: 4px 0 4px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th { background: #15366B; color: #fff; font-size: 8px; text-align: left; padding: 4px 6px; text-transform: uppercase; }
    th.r, td.r { text-align: right; }
    td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; font-size: 9.5px; }
    tr:nth-child(even) td { background: #f8fafc; }
    tfoot td { font-weight: bold; border-top: 2px solid #15366B; background: #eef3fb; }
    .void { color: #94a3b8; text-decoration: line-through; }
    .badge { font-size: 7.5px; padding: 1px 4px; border-radius: 3px; background: #fee2e2; color: #b91c1c; }
    .sign { margin-top: 28px; display: flex; justify-content: space-between; gap: 40px; }
    .sign .ln { border-top: 1px solid #000; padding-top: 3px; min-width: 200px; text-align: center; font-size: 10px; }
    .foot { margin-top: 14px; font-size: 8.5px; color: #94a3b8; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>

    <div class="lh">
        <img src="/magsaysay-logo.png" alt="">
        <div class="t">
            <h1>MAXIMINO PELLERIN SR. TECHNICAL AND VOCATIONAL INSTITUTE</h1>
            <div class="s">PESO Magsaysay · Davao del Sur</div>
            <div class="title">DAILY CASH COLLECTION REPORT</div>
        </div>
        <img src="/mptvi-logo.png" alt="">
    </div>

    <div class="meta">
        <div>Date: <b>{{ $date->format('F j, Y') }}</b>@if($cashierName) &nbsp;·&nbsp; Cashier: <b>{{ $cashierName }}</b>@else &nbsp;·&nbsp; <b>All cashiers</b>@endif</div>
        <div>OR range: <b>{{ $orFrom ?? '—' }}</b> &ndash; <b>{{ $orTo ?? '—' }}</b></div>
    </div>

    <div class="summ">
        <div class="card"><div class="k">Total collected</div><div class="v">&#8369;{{ number_format($collected) }}</div></div>
        <div class="card"><div class="k">Payments</div><div class="v">{{ $count }}</div></div>
        @if($voidedCount > 0)<div class="card"><div class="k">Voided (excluded)</div><div class="v" style="color:#e11d48">{{ $voidedCount }}</div></div>@endif
        <div class="card"><div class="k">Generated</div><div class="v" style="font-size:11px">{{ now()->format('g:i A') }}</div></div>
    </div>

    <div class="cols">
        <div>
            <div class="sec">By payment method</div>
            <table>
                <thead><tr><th>Method</th><th class="r">Count</th><th class="r">Amount</th></tr></thead>
                <tbody>
                    @forelse($byMethod as $m)
                        <tr><td>{{ $m['method'] }}</td><td class="r">{{ $m['count'] }}</td><td class="r">&#8369;{{ number_format($m['total']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center;color:#94a3b8">No collections.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr><td>Total</td><td class="r">{{ $count }}</td><td class="r">&#8369;{{ number_format($collected) }}</td></tr></tfoot>
            </table>
        </div>
        <div>
            <div class="sec">By category</div>
            <table>
                <thead><tr><th>Category</th><th class="r">Count</th><th class="r">Amount</th></tr></thead>
                <tbody>
                    @forelse($byCategory as $c)
                        <tr><td>{{ $c['category'] }}</td><td class="r">{{ $c['count'] }}</td><td class="r">&#8369;{{ number_format($c['total']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center;color:#94a3b8">No collections.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr><td>Total</td><td class="r">{{ $count }}</td><td class="r">&#8369;{{ number_format($collected) }}</td></tr></tfoot>
            </table>
        </div>
    </div>

    @if($byCashier->isNotEmpty())
        <div class="sec">By cashier</div>
        <table>
            <thead><tr><th>Cashier</th><th class="r">Count</th><th class="r">Amount</th></tr></thead>
            <tbody>
                @foreach($byCashier as $c)
                    <tr><td>{{ $c['cashier'] }}</td><td class="r">{{ $c['count'] }}</td><td class="r">&#8369;{{ number_format($c['total']) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><td>Total</td><td class="r">{{ $count }}</td><td class="r">&#8369;{{ number_format($collected) }}</td></tr></tfoot>
        </table>
    @endif

    <div class="sec">Transactions</div>
    <table>
        <thead>
            <tr>
                <th>OR No.</th><th>Learner</th><th>Category</th><th>Method</th><th>Type</th>
                @if($showCashierCol)<th>Cashier</th>@endif
                <th class="r">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $p)
                <tr class="{{ $p->isVoided() ? 'void' : '' }}">
                    <td>{{ $p->or_number ?? '—' }}</td>
                    <td>{{ $p->applicant?->display_name ?? '—' }}</td>
                    <td>{{ $p->category }}@if($p->isVoided()) <span class="badge">VOID</span>@endif</td>
                    <td>{{ $p->method }}</td>
                    <td>{{ $p->type }}</td>
                    @if($showCashierCol)<td>{{ $p->cashier?->name ?? '—' }}</td>@endif
                    <td class="r">&#8369;{{ number_format($p->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $showCashierCol ? 7 : 6 }}" style="text-align:center;color:#94a3b8;padding:14px">No transactions on this date.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ $showCashierCol ? 6 : 5 }}">Total collected (excludes voided)</td>
                <td class="r">&#8369;{{ number_format($collected) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="sign">
        <div class="ln">Prepared by — {{ $user->name }}</div>
        <div class="ln">Verified / Received by</div>
    </div>

    <div class="foot">
        Cash on hand should reconcile to the total collected above. Voided payments are listed but excluded from all totals.
        @if($voidedCount > 0) Voided today: {{ $voidedCount }} (&#8369;{{ number_format($voidedTotal) }}).@endif
    </div>
</body>
</html>
