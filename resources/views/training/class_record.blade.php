<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Achievement Chart — {{ $batch->code }}</title>
<style>
    @page { size: A4 landscape; margin: 10mm; }
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; color: #1e293b; font-size: 10px; }
    .toolbar { position: fixed; top: 8px; right: 8px; }
    .toolbar button { font: 600 12px Arial; padding: 7px 14px; border: 0; border-radius: 6px; background: #15366B; color: #fff; cursor: pointer; }
    @media print { .toolbar { display: none; } }
    .lh { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #15366B; padding-bottom: 8px; margin-bottom: 8px; }
    .lh img { width: 46px; height: 46px; object-fit: contain; }
    .lh .t { flex: 1; text-align: center; }
    .lh h1 { font-size: 13px; margin: 0; color: #15366B; }
    .lh .s { font-size: 9px; color: #64748b; margin: 1px 0; }
    .lh .title { font-weight: bold; color: #15366B; letter-spacing: .08em; font-size: 11px; margin-top: 2px; }
    .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
    .chip { background: #eef3fb; border: 1px solid #d6e1f3; border-radius: 4px; padding: 2px 8px; font-size: 9px; }
    .chip b { color: #15366B; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #cbd5e1; }
    thead th { background: #15366B; color: #fff; font-size: 8px; padding: 4px 4px; text-transform: uppercase; }
    thead th.grp { font-size: 8.5px; }
    thead th.u { width: 22px; font-size: 9px; }
    td { padding: 4px 4px; font-size: 9.5px; text-align: center; }
    td.name { text-align: left; white-space: nowrap; }
    td.idx { color: #94a3b8; }
    tr:nth-child(even) td { background: #f8fafc; }
    .c { color: #15803d; font-weight: bold; }        /* competent */
    .nyc { color: #e11d48; font-weight: bold; }       /* not yet competent */
    .na { color: #cbd5e1; }                            /* not rated */
    .stat { font-weight: bold; }
    .stat.done { color: #15803d; }
    .stat.prog { color: #b45309; }
    .legend { margin-top: 8px; font-size: 8.5px; color: #475569; }
    .legend .grp { font-weight: bold; color: #15366B; margin-top: 3px; }
    .legend ol { margin: 2px 0 0; padding-left: 16px; columns: 2; }
    .key { margin-top: 6px; font-size: 8.5px; color: #64748b; }
    .sign { margin-top: 24px; display: flex; justify-content: space-between; gap: 40px; }
    .sign .ln { border-top: 1px solid #000; padding-top: 3px; min-width: 220px; text-align: center; font-size: 9.5px; }
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
            <div class="title">COMPETENCY ACHIEVEMENT CHART</div>
        </div>
        <img src="/mptvi-logo.png" alt="">
    </div>

    <div class="meta">
        <span class="chip">Batch: <b>{{ $batch->code }}</b></span>
        <span class="chip">Qualification: <b>{{ $batch->program?->title ?? '—' }}</b>@if($batch->program?->level) ({{ $batch->program->level }})@endif</span>
        <span class="chip">Trainees: <b>{{ $roster->count() }}</b></span>
        <span class="chip">Units: <b>{{ $units->count() }}</b></span>
        <span class="chip">Generated: <b>{{ now()->format('M j, Y') }}</b></span>
    </div>

    @if($units->isEmpty())
        <p style="color:#e11d48;font-size:11px">No Units of Competency are defined for this qualification. Set them up in Settings → Competency Standards.</p>
    @else
        @php
            $groups = $units->groupBy('type')->sortBy(fn ($g, $t) => array_search($t, ['Basic','Common','Core']));
            $ordered = $groups->flatten(1);            // flat, group-ordered list
            $num = [];                                  // unit_id → column number
            $i = 1; foreach ($ordered as $u) { $num[$u->id] = $i++; }
        @endphp
        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="idx">#</th>
                    <th rowspan="2" style="text-align:left">Trainee</th>
                    @foreach($groups as $type => $gUnits)
                        <th colspan="{{ $gUnits->count() }}" class="grp">{{ $type }}</th>
                    @endforeach
                    <th rowspan="2">Status</th>
                </tr>
                <tr>
                    @foreach($ordered as $u)
                        <th class="u" title="{{ $u->title }}">{{ $num[$u->id] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($roster as $r => $row)
                    @php $byUnit = collect($row['summary']['units'])->keyBy('unit_id'); @endphp
                    <tr>
                        <td class="idx">{{ $r + 1 }}</td>
                        <td class="name">{{ $row['name'] }}</td>
                        @foreach($ordered as $u)
                            @php $res = $byUnit[$u->id]['result'] ?? null; @endphp
                            @if($res === 'Competent')
                                <td class="c">&check;</td>
                            @elseif($res)
                                <td class="nyc">&times;</td>
                            @else
                                <td class="na">&middot;</td>
                            @endif
                        @endforeach
                        <td class="stat {{ $row['summary']['complete'] ? 'done' : 'prog' }}">
                            {{ $row['summary']['competent'] }}/{{ $row['summary']['total'] }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $units->count() + 3 }}" style="color:#94a3b8;padding:14px">No trainees enrolled in this batch.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="key">Legend: <span class="c">&check;</span> Competent &nbsp; <span class="nyc">&times;</span> Not Yet Competent &nbsp; <span class="na">&middot;</span> Not yet rated. Status = units rated Competent / total units.</div>

        <div class="legend">
            @foreach($groups as $type => $gUnits)
                <div class="grp">{{ $type }} competencies</div>
                <ol start="{{ $num[$gUnits->first()->id] }}">
                    @foreach($gUnits as $u)<li>{{ $u->code ? $u->code.' — ' : '' }}{{ $u->title }}</li>@endforeach
                </ol>
            @endforeach
        </div>
    @endif

    <div class="sign">
        <div class="ln">Instructor / Trainer<br><small>Name & signature</small></div>
        <div class="ln">{{ config('lpf.signatories.checked_by.name') }}<br><small>{{ config('lpf.signatories.checked_by.title') }}</small></div>
        <div class="ln">{{ config('lpf.signatories.approved_by.name') }}<br><small>{{ config('lpf.signatories.approved_by.title') }}</small></div>
    </div>
</body>
</html>
