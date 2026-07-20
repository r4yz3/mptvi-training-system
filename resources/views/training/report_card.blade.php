<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Competency Achievement Record — {{ $a->display_name }}</title>
<style>
    @page { size: A4 portrait; margin: 12mm; }
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; color: #1e293b; font-size: 11px; }
    .toolbar { position: fixed; top: 8px; right: 8px; }
    .toolbar button { font: 600 12px Arial; padding: 7px 14px; border: 0; border-radius: 6px; background: #15366B; color: #fff; cursor: pointer; }
    @media print { .toolbar { display: none; } }
    .lh { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #15366B; padding-bottom: 8px; margin-bottom: 10px; }
    .lh img { width: 46px; height: 46px; object-fit: contain; }
    .lh .t { flex: 1; text-align: center; }
    .lh h1 { font-size: 13px; margin: 0; color: #15366B; }
    .lh .s { font-size: 9px; color: #64748b; margin: 1px 0; }
    .lh .title { font-weight: bold; color: #15366B; letter-spacing: .1em; font-size: 12px; margin-top: 2px; }
    .who { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px; }
    .who .row { font-size: 11px; margin: 3px 0; }
    .who .k { color: #64748b; display: inline-block; min-width: 72px; }
    .who b { color: #0f172a; }
    .result { margin: 6px 0 14px; padding: 10px 14px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; }
    .result.done { background: #ecfdf5; border: 1px solid #a7f3d0; }
    .result.prog { background: #fffbeb; border: 1px solid #fde68a; }
    .result .big { font-size: 20px; font-weight: bold; color: #15366B; }
    .result .rem { font-size: 14px; font-weight: bold; letter-spacing: .05em; }
    .result.done .rem { color: #15803d; }
    .result.prog .rem { color: #b45309; }
    .grp { font-size: 10.5px; font-weight: bold; color: #15366B; margin: 12px 0 3px; text-transform: uppercase; letter-spacing: .04em; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #15366B; color: #fff; font-size: 8.5px; text-align: left; padding: 5px 7px; text-transform: uppercase; }
    th.c, td.c { text-align: center; }
    td { border-bottom: 1px solid #e2e8f0; padding: 5px 7px; font-size: 10px; }
    .comp { color: #15803d; font-weight: bold; }
    .nyc { color: #e11d48; font-weight: bold; }
    .na { color: #94a3b8; }
    .foot { margin-top: 8px; font-size: 8.5px; color: #94a3b8; }
    .sign { margin-top: 30px; display: flex; justify-content: space-between; gap: 30px; }
    .sign .ln { border-top: 1px solid #000; padding-top: 3px; min-width: 200px; text-align: center; font-size: 9.5px; }
    .sign .ln small { color: #64748b; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>

    <div class="lh">
        <img src="/magsaysay-logo.png" alt="">
        <div class="t">
            <h1>MAXIMINO PELLERIN SR. TECHNICAL AND VOCATIONAL INSTITUTE</h1>
            <div class="s">PESO Magsaysay · Davao del Sur</div>
            <div class="title">COMPETENCY ACHIEVEMENT RECORD</div>
        </div>
        <img src="/mptvi-logo.png" alt="">
    </div>

    <div class="who">
        <div>
            <div class="row"><span class="k">Trainee</span> <b>{{ $a->display_name }}</b></div>
            <div class="row"><span class="k">Qualification</span> {{ $a->program?->title ?? '—' }}{{ $a->program?->level ? ' ('.$a->program->level.')' : '' }}</div>
        </div>
        <div style="text-align:right">
            <div class="row"><span class="k">School year</span> <b>{{ $a->school_year ?? '—' }}</b></div>
            <div class="row"><span class="k">Generated</span> {{ now()->format('M j, Y') }}</div>
        </div>
    </div>

    @php $complete = $summary['complete']; @endphp
    <div class="result {{ $complete ? 'done' : 'prog' }}">
        <div>
            <div style="font-size:9px;color:#64748b;text-transform:uppercase">Units competent</div>
            <div class="big">{{ $summary['competent'] }} / {{ $summary['total'] }}</div>
        </div>
        <div class="rem">{{ $complete ? 'COMPETENT — ALL UNITS' : 'IN PROGRESS' }}</div>
    </div>

    @if($summary['total'] === 0)
        <p class="na" style="font-size:11px">No Units of Competency are defined for this qualification yet.</p>
    @else
        @php $groups = collect($summary['units'])->groupBy('type')->sortBy(fn ($g, $t) => array_search($t, ['Basic','Common','Core'])); @endphp
        @foreach($groups as $type => $units)
            <div class="grp">{{ $type }} competencies</div>
            <table>
                <thead>
                    <tr><th style="width:34px">Code</th><th>Unit of Competency</th><th class="c" style="width:110px">Result</th><th class="c" style="width:80px">Date</th></tr>
                </thead>
                <tbody>
                    @foreach($units as $u)
                        <tr>
                            <td class="na">{{ $u['code'] ?? '—' }}</td>
                            <td>{{ $u['title'] }}@if(!empty($u['remarks']))<br><span class="na" style="font-size:9px">{{ $u['remarks'] }}</span>@endif</td>
                            <td class="c">
                                @if($u['result'] === 'Competent')<span class="comp">Competent</span>
                                @elseif($u['result'])<span class="nyc">Not Yet Competent</span>
                                @else<span class="na">Not yet rated</span>@endif
                            </td>
                            <td class="c na">{{ $u['rated_at'] ? \Illuminate\Support\Carbon::parse($u['rated_at'])->format('M j, Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <div class="foot">
        This is an institutional competency record. National certification is issued only after a successful competency assessment by an accredited
        TESDA assessor. Generated {{ now()->format('F j, Y · g:i A') }} by {{ $user->name }}.
    </div>

    <div class="sign">
        <div class="ln">Instructor / Trainer<br><small>Name & signature</small></div>
        <div class="ln">{{ config('lpf.signatories.checked_by.name') }}<br><small>{{ config('lpf.signatories.checked_by.title') }}</small></div>
    </div>
</body>
</html>
