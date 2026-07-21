<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Report of Grades — {{ $a->display_name }}</title>
<style>
    @page { size: A4 portrait; margin: 12mm; }
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; color: #1e293b; font-size: 11px; }
    .toolbar { position: fixed; top: 8px; right: 8px; }
    .toolbar button { font: 600 12px Arial; padding: 7px 14px; border: 0; border-radius: 6px; background: #15366B; color: #fff; cursor: pointer; }
    @media print { .toolbar { display: none; } }
    .lh { text-align: center; border-bottom: 2px solid #15366B; padding-bottom: 8px; margin-bottom: 10px; }
    .lh img { width: 46px; height: 46px; object-fit: contain; }
    .lh h1 { font-size: 13px; margin: 4px 0 0; color: #15366B; }
    .lh .s { font-size: 9px; color: #64748b; margin: 1px 0; }
    .lh .title { font-weight: bold; color: #15366B; letter-spacing: .1em; font-size: 12px; margin-top: 3px; }
    .who { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px; }
    .who .row { font-size: 11px; margin: 3px 0; }
    .who .k { color: #64748b; display: inline-block; min-width: 72px; }
    .who b { color: #0f172a; }
    .result { margin: 6px 0 14px; padding: 10px 14px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; }
    .result.pass { background: #ecfdf5; border: 1px solid #a7f3d0; }
    .result.fail { background: #fef2f2; border: 1px solid #fecaca; }
    .result.prog { background: #fffbeb; border: 1px solid #fde68a; }
    .result .gwa { font-size: 22px; font-weight: bold; color: #15366B; }
    .result .rem { font-size: 14px; font-weight: bold; letter-spacing: .05em; }
    .result.pass .rem { color: #15803d; }
    .result.fail .rem { color: #b91c1c; }
    .result.prog .rem { color: #b45309; }
    .subgwa { display: flex; gap: 22px; font-size: 10px; color: #475569; margin-top: 3px; }
    .subgwa b { color: #15366B; }
    .grp { font-size: 10.5px; font-weight: bold; color: #15366B; margin: 12px 0 3px; text-transform: uppercase; letter-spacing: .04em; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #15366B; color: #fff; font-size: 8.5px; text-align: left; padding: 5px 7px; text-transform: uppercase; }
    th.c, td.c { text-align: center; }
    th.r, td.r { text-align: right; }
    td { border-bottom: 1px solid #e2e8f0; padding: 5px 7px; font-size: 10px; }
    .pass-g { color: #15803d; font-weight: bold; }
    .fail-g { color: #e11d48; font-weight: bold; }
    .na { color: #94a3b8; }
    tfoot td { font-weight: bold; border-top: 2px solid #15366B; background: #eef3fb; }
    .foot { margin-top: 10px; font-size: 8.5px; color: #94a3b8; }
    .sign { margin-top: 30px; display: flex; justify-content: space-between; gap: 30px; }
    .sign .ln { border-top: 1px solid #000; padding-top: 3px; min-width: 200px; text-align: center; font-size: 9.5px; }
    .sign .ln small { color: #64748b; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>

    <div class="lh">
        <img src="/mptvi-logo.png" alt="">
        <h1>MAXIMINO PELLERIN SR. TECHNICAL AND VOCATIONAL INSTITUTE</h1>
        <div class="s">{{ \App\Models\Setting::institution()['address'] ?? 'Magsaysay, Davao del Sur' }}</div>
        <div class="title">REPORT OF GRADES</div>
    </div>

    <div class="who">
        <div>
            <div class="row"><span class="k">Trainee</span> <b>{{ $a->display_name }}</b></div>
            <div class="row"><span class="k">Program</span> {{ $a->program?->title ?? '—' }}{{ $a->program?->level ? ' ('.$a->program->level.')' : '' }}</div>
        </div>
        <div style="text-align:right">
            <div class="row"><span class="k">School year</span> <b>{{ $a->school_year ?? '—' }}</b></div>
            <div class="row"><span class="k">Generated</span> {{ now()->format('M j, Y') }}</div>
        </div>
    </div>

    @php
        $cls = $summary['remark'] === 'Passed' ? 'pass' : ($summary['remark'] === 'Failed' ? 'fail' : 'prog');
        $fmt = fn ($g) => $g === null ? '—' : number_format($g, 2);
    @endphp
    <div class="result {{ $cls }}">
        <div>
            <div style="font-size:9px;color:#64748b;text-transform:uppercase">General weighted average</div>
            <div class="gwa">{{ $fmt($summary['gwa']) }}</div>
            <div class="subgwa">
                <span>Major GWA: <b>{{ $fmt($summary['major_gwa']) }}</b></span>
                <span>Minor GWA: <b>{{ $fmt($summary['minor_gwa']) }}</b></span>
            </div>
        </div>
        <div class="rem">{{ strtoupper($summary['remark']) }}</div>
    </div>

    @if($summary['total'] === 0)
        <p class="na" style="font-size:11px">No subjects are defined for this program yet.</p>
    @else
        @php $groups = collect($summary['subjects'])->groupBy('category')->sortBy(fn ($g, $c) => $c === 'Major' ? 0 : 1); @endphp
        @foreach($groups as $category => $subjects)
            <div class="grp">{{ $category }} subjects</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:44px">Code</th><th>Subject</th>
                        <th class="c" style="width:44px">Units</th><th class="c" style="width:60px">Grade</th>
                        <th class="c" style="width:80px">Remark</th><th class="c" style="width:80px">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjects as $s)
                        <tr>
                            <td class="na">{{ $s['code'] ?? '—' }}</td>
                            <td>{{ $s['title'] }}</td>
                            <td class="c">{{ $s['units'] }}</td>
                            <td class="c">{{ $s['grade'] !== null ? number_format($s['grade'], 2) : '—' }}</td>
                            <td class="c">
                                @if($s['remark'] === 'Passed')<span class="pass-g">Passed</span>
                                @elseif($s['remark'] === 'Failed')<span class="fail-g">Failed</span>
                                @else<span class="na">Not yet graded</span>@endif
                            </td>
                            <td class="c na">{{ $s['graded_at'] ? \Illuminate\Support\Carbon::parse($s['graded_at'])->format('M j, Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <div class="foot">
        Grade scale: 1.00 (highest) – 5.00 (fail); 3.00 is the lowest passing grade. GWA is the unit-weighted average of all graded subjects.
        Generated {{ now()->format('F j, Y · g:i A') }} by {{ $user->name }}.
    </div>

    <div class="sign">
        <div class="ln">Instructor / Trainer<br><small>Name &amp; signature</small></div>
        <div class="ln">{{ config('lpf.signatories.checked_by.name') }}<br><small>Registrar</small></div>
    </div>
</body>
</html>
