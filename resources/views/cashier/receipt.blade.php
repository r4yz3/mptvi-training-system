<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt {{ $p->or_number ?? ('AR-'.$p->id) }}</title>
<style>
    @page { size: A5 portrait; margin: 10mm; }
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; color: #1e293b; font-size: 12px; }
    .toolbar { position: fixed; top: 8px; right: 8px; }
    .toolbar button { font: 600 12px Arial; padding: 7px 14px; border: 0; border-radius: 6px; background: #15366B; color: #fff; cursor: pointer; }
    @media print { .toolbar { display: none; } }
    .frame { border: 1.5px solid #15366B; border-radius: 8px; padding: 16px 18px; position: relative; }
    .lh { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #15366B; padding-bottom: 8px; }
    .lh img { width: 46px; height: 46px; object-fit: contain; }
    .lh .t { flex: 1; text-align: center; }
    .lh h1 { font-size: 13px; margin: 0; color: #15366B; line-height: 1.2; }
    .lh .s { font-size: 9px; color: #64748b; margin: 1px 0; }
    .title { text-align: center; letter-spacing: .14em; font-weight: bold; color: #15366B; font-size: 13px; margin: 12px 0 2px; }
    .rno { text-align: center; font-size: 10px; color: #64748b; margin-bottom: 14px; }
    .rno b { color: #15366B; }
    .row { display: flex; margin: 7px 0; font-size: 12px; }
    .row .k { width: 120px; color: #64748b; }
    .row .v { flex: 1; border-bottom: 1px dotted #94a3b8; padding-bottom: 1px; font-weight: 600; color: #0f172a; }
    .amt { margin: 14px 0; padding: 10px 12px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: baseline; justify-content: space-between; }
    .amt .big { font-size: 22px; font-weight: bold; color: #15366B; }
    .words { font-size: 10px; font-style: italic; color: #475569; margin-top: 2px; }
    .meta { display: flex; gap: 10px; margin-top: 6px; font-size: 11px; }
    .meta .chip { background: #eef3fb; border: 1px solid #d6e1f3; border-radius: 4px; padding: 2px 8px; }
    .sign { margin-top: 26px; display: flex; justify-content: flex-end; }
    .sign .ln { border-top: 1px solid #000; padding-top: 3px; min-width: 190px; text-align: center; font-size: 10px; }
    .foot { margin-top: 14px; font-size: 8.5px; color: #94a3b8; text-align: center; }
    .void { position: absolute; top: 42%; left: 50%; transform: translate(-50%,-50%) rotate(-18deg); font-size: 64px; font-weight: bold; color: rgba(225,29,72,.18); letter-spacing: .1em; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>

    <div class="frame">
        @if($p->voided_at)<div class="void">VOID</div>@endif

        <div class="lh">
            <img src="/magsaysay-logo.png" alt="">
            <div class="t">
                <h1>MAXIMINO PELLERIN SR.<br>TECHNICAL AND VOCATIONAL INSTITUTE</h1>
                <div class="s">PESO Magsaysay · Davao del Sur</div>
            </div>
            <img src="/mptvi-logo.png" alt="">
        </div>

        <div class="title">ACKNOWLEDGEMENT RECEIPT</div>
        <div class="rno">No. <b>{{ $p->or_number ?? ('AR-'.str_pad($p->id, 5, '0', STR_PAD_LEFT)) }}</b> &nbsp;·&nbsp; {{ $p->paid_at?->format('F j, Y') }}</div>

        <div class="row"><div class="k">Received from</div><div class="v">{{ $a?->display_name ?? '—' }}</div></div>
        <div class="row"><div class="k">Program</div><div class="v">{{ $a?->program?->title ?? '—' }}{{ $a?->program?->level ? ' ('.$a->program->level.')' : '' }}</div></div>
        <div class="row"><div class="k">Particulars</div><div class="v">{{ $p->category }}{{ $p->description ? ' — '.$p->description : '' }}</div></div>

        <div class="amt">
            <div>
                <div style="font-size:9px;color:#64748b;text-transform:uppercase">Amount paid</div>
                <div class="words">{{ $amountWords }}</div>
            </div>
            <div class="big">&#8369;{{ number_format($p->amount) }}</div>
        </div>

        <div class="meta">
            <span class="chip">Payment: <b>{{ $p->method }}</b></span>
            <span class="chip">Type: <b>{{ $p->type }}</b></span>
        </div>

        <div class="foot">This acknowledgement receipt confirms the amount received above. Not a BIR-registered official receipt. Keep for your records.</div>
    </div>
</body>
</html>
