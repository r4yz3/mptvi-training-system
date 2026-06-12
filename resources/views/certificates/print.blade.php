<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>National Certificate — {{ $a->cert_number }}</title>
<style>
    @page { size: A4 landscape; margin: 12mm; }
    * { box-sizing: border-box; font-family: Georgia, 'Times New Roman', serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; color: #15202E; text-align: center; padding: 20px; }
    .toolbar { text-align: center; margin-bottom: 16px; }
    .toolbar button { font-family: system-ui, sans-serif; cursor: pointer; border: 1px solid #15366B; background: #15366B; color: #fff; border-radius: 6px; padding: 8px 18px; font-size: 13px; }
    .frame { border: 5px double #15366B; border-radius: 10px; padding: 24px 40px; }
    .logos { display: flex; align-items: center; justify-content: center; gap: 18px; }
    .logos .lg { width: 62px; height: 62px; }
    .logos .lg img { width: 100%; height: 100%; object-fit: contain; }
    .gov { font-size: 11.5px; line-height: 1.45; }
    h1 { font-size: 33px; margin: 14px 0 2px; color: #15366B; letter-spacing: 3px; }
    .s { letter-spacing: 2px; font-size: 11.5px; color: #5C665F; text-transform: uppercase; }
    .n { font-size: 30px; margin: 16px 0 2px; font-weight: bold; }
    .q { font-size: 18px; margin: 6px 0; }
    .meta { display: flex; justify-content: center; gap: 38px; margin-top: 16px; font-size: 12px; color: #444; }
    .sign { display: flex; justify-content: space-around; margin-top: 40px; }
    .sign .ln { border-top: 1px solid #000; padding-top: 3px; font-size: 11px; min-width: 210px; }
    .no { margin-top: 16px; font-size: 10.5px; color: #5C665F; }
    @media print { .toolbar { display: none; } body { padding: 0; } }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print certificate</button></div>

    <div class="frame">
        <div class="logos">
            <div class="lg"><img src="/magsaysay-logo.png" alt="Magsaysay"></div>
            <div class="gov">
                Republic of the Philippines<br>
                Province of Davao del Sur · Municipality of Magsaysay<br>
                <b>{{ strtoupper($org['name']) }}</b><br>
                {{ $org['office'] }}
            </div>
            <div class="lg"><img src="/mptvi-logo.png" alt="MPTVI"></div>
        </div>

        <h1>National Certificate</h1>
        <div class="s">This is to certify that</div>
        <div class="n">{{ $a->display_name }}</div>
        <div class="s">has satisfactorily demonstrated competency in</div>
        <div class="q"><b>{{ $program?->title }}{{ $program?->level ? ' — '.$program->level : '' }}</b></div>
        <div class="s">and was assessed COMPETENT in the national competency assessment</div>

        <div class="meta">
            <div><b>NC No.</b> {{ $a->cert_number }}</div>
            <div><b>Issued</b> {{ $issued?->format('F j, Y') ?: '—' }}</div>
            <div><b>Valid until</b> {{ $validUntil?->format('F j, Y') ?: '—' }}</div>
        </div>

        <div class="sign">
            <div class="b"><div class="ln">{{ $assessor ?: 'Accredited Assessor' }}<br>Accredited Assessor</div></div>
            <div class="b"><div class="ln"><b>{{ $signatories['approved_by']['name'] }}</b><br>{{ $signatories['approved_by']['title'] }}</div></div>
        </div>

        <div class="no">Issued at {{ $org['address'] }} · {{ $issued?->format('F j, Y') ?: '' }}</div>
    </div>
</body>
</html>
