@php
    $cb  = fn ($on) => '<span class="cb'.($on ? ' on' : '').'"></span>';
    $ci  = fn ($on, $t) => '<label class="ci">'.$cb($on).'<span>'.e($t).'</span></label>';
    $cks = fn ($arr, $sel, $cols = 3) => '<div class="cks" style="grid-template-columns:repeat('.$cols.',1fr)">'
        .collect($arr)->map(fn ($x) => $ci(is_array($sel) ? in_array($x, $sel ?? []) : $sel === $x, $x))->implode('').'</div>';
    $cell = fn ($lab, $val, $flex = 1) => '<div class="cell" style="flex:'.$flex.'"><div class="lab">'.e($lab).'</div>'
        .'<div class="val">'.($val === null || $val === '' ? '&nbsp;' : e($val)).'</div></div>';
    $birth = $a->birthdate;
    $custom = $a->custom_data ?? [];
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Learner Profile Form — {{ $a->display_name }}</title>
<style>
    @page { size: A4; margin: 9mm; }
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; color: #111; font-size: 10.5px; }
    .toolbar { position: fixed; top: 8px; right: 8px; }
    .toolbar button { font: 600 12px Arial; padding: 7px 14px; border: 0; border-radius: 6px; background: #15366B; color: #fff; cursor: pointer; }
    @media print { .toolbar { display: none; } }
    .hd { display: flex; align-items: center; gap: 10px; border: 1.5px solid #000; padding: 6px 8px; }
    .hd .t { flex: 1; text-align: center; }
    .hd h1 { font-size: 11.5px; margin: 3px 0 1px; }
    .hd .s { font-size: 9px; margin: 1px 0; }
    .hd .logo { width: 54px; height: 54px; flex-shrink: 0; object-fit: contain; }
    .hd .pic { width: 70px; height: 86px; border: 1px solid #000; font-size: 7px; color: #555; display: flex; align-items: flex-end; justify-content: center; text-align: center; background-size: cover; background-position: center; }
    .p2head { text-align: center; font-size: 8.5px; font-weight: bold; border: 1px solid #000; padding: 4px; margin-top: 6px; }
    .pgbreak { break-before: page; page-break-before: always; }
    .grid, .cks, .sign { break-inside: avoid; page-break-inside: avoid; }
    .sec { background: #e2e2e2; border: 1px solid #555; border-top: none; padding: 3px 6px; font-weight: bold; font-size: 9.5px; break-after: avoid; page-break-after: avoid; }
    .sec.top { border-top: 1px solid #555; margin-top: 6px; }
    .grid { display: flex; flex-wrap: wrap; border: 1px solid #555; border-top: none; }
    .cell { border-right: 1px solid #bbb; border-bottom: 1px solid #bbb; padding: 4px 7px; flex: 1; min-width: 128px; }
    .cell .lab { font-size: 7px; color: #555; text-transform: uppercase; letter-spacing: .02em; }
    .cell .val { font-size: 11px; min-height: 19px; font-weight: bold; word-break: break-word; }
    .cks { display: grid; gap: 1px 8px; border: 1px solid #555; border-top: none; padding: 4px 6px; }
    .ci { display: flex; align-items: center; gap: 4px; font-size: 8.8px; padding: 1.5px 0; line-height: 1.25; }
    .cb { display: inline-block; width: 9px; height: 9px; border: 1px solid #000; flex-shrink: 0; }
    .cb.on { background: #000; box-shadow: inset 0 0 0 1.5px #fff; }
    .sign { display: flex; gap: 20px; border: 1px solid #555; border-top: none; padding: 16px 8px 6px; }
    .sign .b { flex: 1; text-align: center; }
    .sign .ln { border-top: 1px solid #000; margin-top: 26px; padding-top: 2px; font-size: 8px; }
    .sign img { max-height: 40px; max-width: 180px; position: relative; top: 8px; }
    .note { font-size: 8.7px; font-style: italic; text-align: center; padding: 4px; }
</style>
</head>
<body onload="window.print()">
    <div class="toolbar"><button onclick="window.print()">Print</button></div>

    {{-- Letterhead (Magsaysay LGU seal left · MPTVI logo right · 2x2 photo far right) --}}
    <div class="hd">
        <img class="logo" src="/magsaysay-logo.png" alt="Magsaysay">
        <div class="t">
            <div class="s">Republic of the Philippines</div>
            <div class="s">Province of Davao del Sur · Municipality of Magsaysay</div>
            <h1>MAXIMINO PELLERIN SR. TECHNICAL AND VOCATIONAL INSTITUTE</h1>
            <div class="s"><b>ENROLLMENT FORM · LEARNER'S PROFILE</b></div>
            <div class="s" style="font-size:7.5px">TESDA MIS 03-01 (Ver. 2021)</div>
        </div>
        <img class="logo" src="/mptvi-logo.png" alt="MPTVI">
        <div class="pic" style="{{ $a->photo_url ? "background-image:url('{$a->photo_url}')" : '' }}">{{ $a->photo_url ? '' : 'PICTURE' }}</div>
    </div>

    <div class="sec">1 · Registration</div>
    <div class="grid">{!! $cell('Entry date', optional($a->registered_at)->format('Y-m-d')) !!}</div>

    <div class="sec">2 · Learner / Manpower Profile</div>
    <div class="grid">{!! $cell('Last name, Ext.', trim($a->last_name.' '.$a->ext_name), 1.4).$cell('First name', $a->first_name, 1.2).$cell('Middle name', $a->middle_name, 1.2) !!}</div>
    <div class="grid">{!! $cell('No., Street / Purok', $a->street, 2).$cell('Barangay', $a->barangay, 1.3).$cell('District', $a->district) !!}</div>
    <div class="grid">{!! $cell('City / Municipality', $a->city, 1.3).$cell('Province', $a->province, 1.3).$cell('Region', $a->region) !!}</div>
    <div class="grid">{!! $cell('Email / Facebook', $a->email, 2).$cell('Contact no.', $a->contact).$cell('Nationality', $a->nationality).$cell('Religion', $a->religion) !!}</div>

    <div class="sec">3 · Personal Information</div>
    <div class="grid">
        <div class="cell" style="flex:1"><div class="lab">Sex</div>{!! $cks($lpf['sex'], $a->sex, 2) !!}</div>
        <div class="cell" style="flex:1.4"><div class="lab">Civil status</div>{!! $cks($lpf['civil_status'], $a->civil_status, 2) !!}</div>
    </div>
    <div class="grid">
        <div class="cell" style="flex:1"><div class="lab">Employment status</div>{!! $cks($lpf['emp_status'], $a->emp_status, 2) !!}</div>
        <div class="cell" style="flex:1.4"><div class="lab">Employment type</div>{!! $cks($lpf['emp_type'], $a->emp_type, 4) !!}</div>
    </div>
    <div class="grid">{!! $cell('Month of birth', $birth ? $birth->format('F') : '').$cell('Day', $birth ? $birth->format('j') : '').$cell('Year', $birth ? $birth->format('Y') : '').$cell('Age', $a->age) !!}</div>
    <div class="grid">{!! $cell('Birthplace — City', $a->birthplace_city).$cell('Province', $a->birthplace_province).$cell('Region', $a->birthplace_region) !!}</div>

    <div class="sec">Educational attainment before the training</div>
    {!! $cks($lpf['education'], $a->education, 3) !!}
    <div class="grid">{!! $cell('School last attended', $a->school_last_attended, 2).$cell('Year graduated', $a->year_graduated) !!}</div>
    @php $eduHist = (array) ($a->education_history ?? []); @endphp
    @if(collect($eduHist)->contains(fn ($r) => array_filter((array) $r)))
        <table style="width:100%;border-collapse:collapse;margin:4px 0 6px;font-size:9px;">
            <thead>
                <tr style="background:#eef2f7;">
                    <th style="border:1px solid #cbd5e1;padding:3px 5px;text-align:left;">Level</th>
                    <th style="border:1px solid #cbd5e1;padding:3px 5px;text-align:left;">School / Institution</th>
                    <th style="border:1px solid #cbd5e1;padding:3px 5px;text-align:left;">Started</th>
                    <th style="border:1px solid #cbd5e1;padding:3px 5px;text-align:left;">Graduated</th>
                    <th style="border:1px solid #cbd5e1;padding:3px 5px;text-align:left;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($lpf['education_levels'] ?? []) as $lvl)
                    @php $r = (array) ($eduHist[$lvl['key']] ?? []); @endphp
                    <tr>
                        <td style="border:1px solid #cbd5e1;padding:3px 5px;">{{ $lvl['label'] }}</td>
                        <td style="border:1px solid #cbd5e1;padding:3px 5px;">{{ $r['school'] ?? '' }}</td>
                        <td style="border:1px solid #cbd5e1;padding:3px 5px;">{{ $r['started'] ?? '' }}</td>
                        <td style="border:1px solid #cbd5e1;padding:3px 5px;">{{ $r['graduated'] ?? '' }}</td>
                        <td style="border:1px solid #cbd5e1;padding:3px 5px;">{{ $r['status'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <div class="grid">{!! $cell('Parent / Guardian name', $a->guardian_name).$cell('Guardian complete address', $a->guardian_address, 2) !!}</div>

    <div class="sec">Health Information</div>
    <div class="grid">{!! $cell('Height', $a->height).$cell('Weight', $a->weight).$cell('Blood type', $a->blood_type).$cell('Eyesight', $a->eyesight).$cell('Hearing', $a->hearing).$cell('Medical issues', $a->medical, 2) !!}</div>

    <div class="sec">Family Background</div>
    <div class="grid">{!! $cell('Father', $a->father_name).$cell('Occupation', $a->father_occupation).$cell('Mother (maiden)', trim($a->mother_name.' '.($a->mother_maiden_name ? '('.$a->mother_maiden_name.')' : ''))).$cell('Occupation', $a->mother_occupation) !!}</div>
    <div class="grid">{!! $cell('Spouse', $a->spouse_name).$cell('Occupation', $a->spouse_occupation).$cell('No. of siblings', $a->siblings).$cell('No. of children', $a->children).$cell('Position in family', $a->family_rank) !!}</div>

    <div class="sec">In case of emergency</div>
    <div class="grid">{!! $cell('Contact person', $a->emergency_name).$cell('Relationship', $a->emergency_relationship).$cell('Contact no.', $a->emergency_contact).$cell('Address', $a->emergency_address, 2) !!}</div>

    {{-- PAGE 2 --}}
    <div class="pgbreak"></div>
    <div class="p2head">MAXIMINO PELLERIN SR. TVI · Enrollment / Learner's Profile — Page 2 of 2 · {{ $a->display_name }}</div>

    <div class="sec top">4 · Learner / Trainee / Student (Clients) Classification</div>
    {!! $cks($lpf['classifications'], $a->classifications, 3) !!}
    <div class="grid">{!! $cell('Others (specify)', $a->classification_other, 1) !!}</div>

    <div class="grid">
        <div class="cell" style="flex:1.4"><div class="lab">5 · Type of disability (PWD only)</div><div class="val">{{ $a->disability_type ?: 'N/A' }}</div></div>
        <div class="cell"><div class="lab">6 · Cause of disability</div><div class="val">{{ $a->disability_cause ?: 'N/A' }}</div></div>
    </div>

    <div class="grid">{!! $cell('7 · Name of course / qualification', $program?->title, 2).$cell('8 · Scholarship package', $a->scholarship ?: 'None').$cell('Class session', $a->class_session).$cell('School year', $a->school_year) !!}</div>

    <div class="sec top">Government-issued IDs &amp; Employment</div>
    <div class="grid">{!! $cell('SSS No.', $a->sss_no).$cell('GSIS No.', $a->gsis_no).$cell('TIN', $a->tin_no).$cell('PhilHealth No.', $a->philhealth_no) !!}</div>
    <div class="grid">{!! $cell('Employer / Company', $a->employer_name, 2).$cell('Position', $a->employer_position).$cell('Ethnic group', $a->ethnic_group) !!}</div>

    @if($customFields->count())
        <div class="sec top">Additional Information</div>
        <div class="grid">
            @foreach($customFields as $f)
                @php $cv = $custom[$f['key']] ?? null; $cv = is_bool($cv) ? ($cv ? 'Yes' : 'No') : $cv; @endphp
                {!! $cell($f['label'], $cv) !!}
            @endforeach
        </div>
    @endif

    <div class="sec top">9 · Privacy Consent and Disclaimer</div>
    <div class="note" style="text-align:left;padding:4px 6px">I hereby attest that I have read and understood the Privacy Notice of TESDA and give my consent to the processing of my personal information in this Learner's Profile, in accordance with R.A. 10173.</div>
    <div class="grid"><div class="cell" style="flex:1">{!! $cks(['Agree', 'Disagree'], $a->privacy_consent ? 'Agree' : 'Disagree', 2) !!}</div></div>

    <div class="sec top">10 · Verification</div>
    <div class="note">This is to certify that the information stated above is true and correct.</div>
    <div class="sign">
        <div class="b"><div class="ln">Applicant's signature over printed name<br><b>{{ $a->display_name }}</b></div></div>
        <div class="b"><div class="ln">Date accomplished<br><b>{{ optional($a->date_accomplished)->format('Y-m-d') }}</b></div></div>
        <div class="b"><div class="ln">Date received<br><b>{{ optional($a->date_received)->format('Y-m-d') }}</b></div></div>
        <div class="b" style="flex:0 0 76px"><div style="height:48px;border:1px solid #000"></div><div class="ln" style="border:none;margin-top:2px;padding-top:0">Right Thumbmark</div></div>
    </div>
    <div class="sign" style="border-top:none">
        <div class="b"><div style="text-align:left;font-size:9px">Interviewed by:</div><div class="ln"><b>{{ $a->interviewed_by ?: ' ' }}</b><br>&nbsp;</div></div>
        <div class="b"><div style="text-align:left;font-size:9px">Checked by:</div><div class="ln"><b>{{ $a->checked_by ?: $signatories['checked_by']['name'] }}</b><br>{{ $signatories['checked_by']['title'] }}</div></div>
        <div class="b"><div style="text-align:left;font-size:9px">Approved by:</div><div class="ln"><b>{{ $a->approved_by ?: $signatories['approved_by']['name'] }}</b><br>{{ $signatories['approved_by']['title'] }}</div></div>
    </div>
</body>
</html>
