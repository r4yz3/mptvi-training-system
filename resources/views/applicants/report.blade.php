<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Applicants Report</title>
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
    .meta { display: flex; justify-content: space-between; align-items: flex-start; font-size: 9px; color: #475569; margin-bottom: 8px; }
    .filters { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0 10px; }
    .chip { background: #eef3fb; border: 1px solid #d6e1f3; border-radius: 4px; padding: 2px 8px; font-size: 9px; }
    .chip b { color: #15366B; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #15366B; color: #fff; font-size: 8.5px; text-align: left; padding: 5px 6px; text-transform: uppercase; letter-spacing: .02em; }
    td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; font-size: 9.5px; vertical-align: top; }
    tr:nth-child(even) td { background: #f8fafc; }
    .st { display: inline-block; border-radius: 3px; padding: 1px 6px; font-size: 8.5px; font-weight: 600; }
    .foot { margin-top: 14px; font-size: 9px; color: #64748b; }
    .sign { margin-top: 30px; display: flex; gap: 60px; }
    .sign .b { font-size: 9px; }
    .sign .ln { border-top: 1px solid #000; padding-top: 2px; min-width: 200px; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>

    <div class="lh">
        <img src="/magsaysay-logo.png" alt="">
        <div class="t">
            <h1>MAXIMINO PELLERIN SR. TECHNICAL AND VOCATIONAL INSTITUTE</h1>
            <div class="s">PESO Magsaysay · Davao del Sur · TESDA-accredited</div>
            <div class="s" style="font-weight:bold;color:#15366B;letter-spacing:.08em">APPLICANTS / LEARNERS REPORT</div>
        </div>
        <img src="/mptvi-logo.png" alt="">
    </div>

    <div class="meta">
        <div><b>{{ $count }}</b> record{{ $count === 1 ? '' : 's' }}</div>
        <div>Generated {{ now()->format('M j, Y · g:i A') }}</div>
    </div>

    @if(count($summary))
        <div class="filters">
            <span style="font-size:9px;color:#64748b">Filters:</span>
            @foreach($summary as [$k, $v])
                <span class="chip"><b>{{ $k }}:</b> {{ $v }}</span>
            @endforeach
        </div>
    @else
        <div class="filters"><span style="font-size:9px;color:#94a3b8">No filters — all applicants</span></div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:18px">#</th>
                <th>Name</th>
                <th>Sex</th>
                <th>Age</th>
                <th>Barangay</th>
                <th>Contact</th>
                <th>Program</th>
                <th>Level</th>
                <th>Session</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $a)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><b>{{ $a->display_name }}</b></td>
                    <td>{{ $a->sex }}</td>
                    <td>{{ $a->age }}</td>
                    <td>{{ $a->barangay }}</td>
                    <td>{{ $a->contact }}</td>
                    <td>{{ $a->program?->title }}</td>
                    <td>{{ $a->program?->level }}</td>
                    <td>{{ $a->class_session }}</td>
                    <td>{{ $a->status }}{{ $a->active ? '' : ' (inactive)' }}</td>
                </tr>
            @empty
                <tr><td colspan="11" style="text-align:center;padding:20px;color:#94a3b8">No applicants match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="sign">
        @if(! $isAdmin)
            <div class="b"><div class="ln">{{ $user->name }}<br><span style="color:#64748b">Prepared by</span></div></div>
        @endif
        <div class="b"><div class="ln">{{ config('lpf.signatories.approved_by.name') }}<br><span style="color:#64748b">{{ config('lpf.signatories.approved_by.title') }}</span></div></div>
    </div>

    <div class="foot">Maximino Pellerin Sr. TVI — Training Management System · This report reflects records at the time of generation.</div>
</body>
</html>
