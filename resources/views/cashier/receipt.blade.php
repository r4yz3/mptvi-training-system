<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt {{ $p->or_number ?? ('AR-'.$p->id) }}</title>
<style>
    /* One A4 sheet holds two identical copies (Trainee's + File). Print, cut
       along the middle line, hand one to the trainee and file the other. */
    @page { size: A4 portrait; margin: 0; }
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    html, body { margin: 0; }
    body { color: #1e293b; font-size: 12px; }
    .toolbar { position: fixed; top: 8px; right: 8px; z-index: 10; }
    .toolbar button { font: 600 12px Arial; padding: 7px 14px; border: 0; border-radius: 6px; background: #15366B; color: #fff; cursor: pointer; }
    @media print { .toolbar { display: none; } }

    /* Each copy occupies just under half of the A4 page height. Slightly under
       148.5mm so rounding never spills a blank third page. */
    .copy { height: 146mm; padding: 9mm 12mm; position: relative; }
    .copy--top { border-bottom: 1px dashed #94a3b8; }
    .cutmark { position: absolute; left: 0; right: 0; bottom: -7px; text-align: center; font-size: 9px; color: #94a3b8; letter-spacing: .05em; }
    .cutmark span { background: #fff; padding: 0 8px; }

    .frame { border: 1.5px solid #15366B; border-radius: 8px; padding: 14px 18px; position: relative; height: 100%; }
    .copytag { position: absolute; top: 10px; right: 14px; font-size: 8.5px; font-weight: bold; letter-spacing: .12em; color: #15366B; border: 1px solid #15366B; border-radius: 4px; padding: 2px 7px; }

    .lh { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #15366B; padding-bottom: 8px; }
    .lh img { width: 46px; height: 46px; object-fit: contain; }
    .lh .t { flex: 1; text-align: center; }
    .lh h1 { font-size: 13px; margin: 0; color: #15366B; line-height: 1.2; }
    .lh .s { font-size: 9px; color: #64748b; margin: 1px 0; }

    .title { text-align: center; letter-spacing: .14em; font-weight: bold; color: #15366B; font-size: 13px; margin: 10px 0 2px; }
    .rno { text-align: center; font-size: 10px; color: #64748b; margin-bottom: 12px; }
    .rno b { color: #15366B; }
    .row { display: flex; margin: 6px 0; font-size: 12px; }
    .row .k { width: 120px; color: #64748b; }
    .row .v { flex: 1; border-bottom: 1px dotted #94a3b8; padding-bottom: 1px; font-weight: 600; color: #0f172a; }
    .amt { margin: 12px 0; padding: 9px 12px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: baseline; justify-content: space-between; }
    .amt .big { font-size: 22px; font-weight: bold; color: #15366B; }
    .words { font-size: 10px; font-style: italic; color: #475569; margin-top: 2px; }
    .meta { display: flex; gap: 10px; margin-top: 6px; font-size: 11px; }
    .meta .chip { background: #eef3fb; border: 1px solid #d6e1f3; border-radius: 4px; padding: 2px 8px; }
    .sign { margin-top: 20px; display: flex; justify-content: flex-end; }
    .sign .ln { border-top: 1px solid #000; padding-top: 3px; min-width: 190px; text-align: center; font-size: 10px; }
    .foot { margin-top: 10px; font-size: 8.5px; color: #94a3b8; text-align: center; }
    .void { position: absolute; top: 42%; left: 50%; transform: translate(-50%,-50%) rotate(-18deg); font-size: 60px; font-weight: bold; color: rgba(225,29,72,.18); letter-spacing: .1em; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>

    @php
        $control = $p->or_number ?? ('AR-'.str_pad($p->id, 5, '0', STR_PAD_LEFT));
        $orgLine = collect([$inst['office'] ?? null, $inst['address'] ?? null])->filter()->implode(' · ');
        $contactLine = collect([$inst['contact'] ?? null, $inst['email'] ?? null])->filter()->implode(' · ');
        $copies = ['Trainee’s Copy', 'File Copy'];
    @endphp

    @foreach($copies as $i => $label)
    <div class="copy {{ $i === 0 ? 'copy--top' : '' }}">
        @if($i === 0)<div class="cutmark"><span>&#9986; cut here — {{ $copies[0] }} above · {{ $copies[1] }} below</span></div>@endif
        <div class="frame">
            @if($p->voided_at)<div class="void">VOID</div>@endif
            <div class="copytag">{{ strtoupper($label) }}</div>

            <div class="lh">
                <img src="/mptvi-logo.png" alt="">
                <div class="t">
                    <h1>{{ $inst['name'] ?? 'Maximino Pellerin Sr. Technical and Vocational Institute' }}</h1>
                    @if($orgLine)<div class="s">{{ $orgLine }}</div>@endif
                    @if($contactLine)<div class="s">{{ $contactLine }}</div>@endif
                </div>
            </div>

            <div class="title">ACKNOWLEDGEMENT RECEIPT</div>
            <div class="rno">Control No. <b>{{ $control }}</b> &nbsp;·&nbsp; {{ $p->paid_at?->format('F j, Y') }}</div>

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

            <div class="sign">
                <div class="ln">
                    <b>{{ $p->cashier?->name ?? '' }}</b><br>
                    <span style="color:#64748b">Cashier — signature over printed name</span>
                </div>
            </div>

            <div class="foot">This acknowledgement receipt confirms the amount received above. Not a BIR-registered official receipt. Keep for your records.</div>
        </div>
    </div>
    @endforeach
</body>
</html>
