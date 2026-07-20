<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt {{ $p->or_number ?? ('AR-'.$p->id) }}</title>
<style>
    /* Two portrait quarter-page copies sit SIDE BY SIDE in the top half of an A4
       sheet. The office prints, cuts the two apart (clean = trainee, "COPY"
       watermark = file), and the blank bottom half can be reinserted for the
       next transaction. */
    @page { size: A4 portrait; margin: 0; }
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    html, body { margin: 0; }
    body { color: #1e293b; font-size: 11px; }
    .toolbar { position: fixed; top: 8px; right: 8px; z-index: 20; }
    .toolbar button { font: 600 12px Arial; padding: 7px 14px; border: 0; border-radius: 6px; background: #15366B; color: #fff; cursor: pointer; }
    @media print { .toolbar { display: none; } }

    /* The two copies live in the top half; the bottom half stays blank for reuse. */
    .sheet { display: flex; height: 148mm; border-bottom: 1px dashed #94a3b8; position: relative; }
    .cutmark { position: absolute; font-size: 8.5px; color: #94a3b8; letter-spacing: .05em; }
    .cutmark--h { left: 0; right: 0; bottom: -6px; text-align: center; }
    .cutmark--v { top: 50%; left: 50%; transform: translate(-50%,-50%); }
    .cutmark span { background: #fff; padding: 0 8px; }

    .copy { width: 50%; height: 100%; padding: 7mm 7mm; position: relative; overflow: hidden; }
    .copy--left { border-right: 1px dashed #94a3b8; }

    .frame { border: 1.5px solid #15366B; border-radius: 7px; padding: 12px 14px; position: relative; height: 100%; }

    /* Large translucent diagonal COPY watermark on the file copy. */
    .watermark { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 5; }
    .watermark span { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-32deg);
        font-size: 100px; font-weight: 800; letter-spacing: .1em; color: rgba(100,116,139,.4); white-space: nowrap; }

    .lh { text-align: center; border-bottom: 2px solid #15366B; padding-bottom: 7px; }
    .lh img { width: 40px; height: 40px; object-fit: contain; }
    .lh h1 { font-size: 12px; margin: 4px 0 0; color: #15366B; line-height: 1.2; }
    .lh .s { font-size: 8.5px; color: #64748b; margin: 2px 0 0; }

    .title { text-align: center; letter-spacing: .1em; font-weight: bold; color: #15366B; font-size: 12px; margin: 10px 0 1px; }
    .rno { text-align: center; font-size: 9.5px; color: #64748b; margin-bottom: 10px; }
    .rno b { color: #15366B; }
    .row { margin: 7px 0; font-size: 11px; }
    .row .k { display: block; color: #64748b; font-size: 9px; }
    .row .v { display: block; border-bottom: 1px dotted #94a3b8; padding-bottom: 1px; font-weight: 600; color: #0f172a; }
    .amt { margin: 12px 0 8px; padding: 8px 11px; background: #f1f5f9; border-radius: 5px; }
    .amt .lbl { font-size: 8px; color: #64748b; text-transform: uppercase; }
    .amt .big { font-size: 22px; font-weight: bold; color: #15366B; }
    .amt .words { font-size: 9px; font-style: italic; color: #475569; margin-top: 1px; }
    .chips { display: flex; gap: 6px; font-size: 10px; margin-top: 6px; }
    .chips .chip { background: #eef3fb; border: 1px solid #d6e1f3; border-radius: 4px; padding: 1px 7px; }
    .sign { margin-top: 26px; text-align: center; }
    .sign .ln { border-top: 1px solid #000; padding-top: 3px; font-size: 9px; }
    .void { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-28deg); font-size: 58px; font-weight: bold; color: rgba(225,29,72,.2); letter-spacing: .1em; z-index: 6; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>

    @php
        $control = $p->or_number ?? ('AR-'.str_pad($p->id, 5, '0', STR_PAD_LEFT));
        $orgLine = $inst['address'] ?? null;
        $contactLine = collect([$inst['contact'] ?? null, $inst['email'] ?? null])->filter()->implode(' · ');
        // First copy is the clean original (trainee); second carries the COPY watermark (file).
        $copies = [false, true];
    @endphp

    <div class="sheet">
        <div class="cutmark cutmark--v"><span>&#9986;</span></div>
        <div class="cutmark cutmark--h"><span>&#9986; cut here</span></div>

        @foreach($copies as $i => $isCopy)
        <div class="copy {{ $i === 0 ? 'copy--left' : '' }}">
            <div class="frame">
                @if($p->voided_at)<div class="void">VOID</div>@endif
                @if($isCopy)<div class="watermark"><span>COPY</span></div>@endif

                <div class="lh">
                    <img src="/mptvi-logo.png" alt="">
                    <h1>{{ $inst['name'] ?? 'Maximino Pellerin Sr. Technical and Vocational Institute' }}</h1>
                    @if($orgLine)<div class="s">{{ $orgLine }}</div>@endif
                    @if($contactLine)<div class="s">{{ $contactLine }}</div>@endif
                </div>

                <div class="title">ACKNOWLEDGEMENT RECEIPT</div>
                <div class="rno">Control No. <b>{{ $control }}</b> &nbsp;·&nbsp; {{ $p->paid_at?->format('F j, Y') }}</div>

                <div class="row"><span class="k">Received from</span><span class="v">{{ $a?->display_name ?? '—' }}</span></div>
                <div class="row"><span class="k">Program</span><span class="v">{{ $a?->program?->title ?? '—' }}{{ $a?->program?->level ? ' ('.$a->program->level.')' : '' }}</span></div>
                <div class="row"><span class="k">Particulars</span><span class="v">{{ $p->category }}{{ $p->description ? ' — '.$p->description : '' }}</span></div>

                <div class="amt">
                    <div class="lbl">Amount paid</div>
                    <div class="big">&#8369;{{ number_format($p->amount) }}</div>
                    <div class="words">{{ $amountWords }}</div>
                </div>

                <div class="chips">
                    <span class="chip">Payment: <b>{{ $p->method }}</b></span>
                    <span class="chip">Type: <b>{{ $p->type }}</b></span>
                </div>

                <div class="sign">
                    <div class="ln"><b>{{ $p->cashier?->name ?? '' }}</b><br><span style="color:#64748b">Cashier — signature over printed name</span></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</body>
</html>
