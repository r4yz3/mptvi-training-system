<?php

/*
|--------------------------------------------------------------------------
| Built-in (standard LPF) fields the admin may manage
|--------------------------------------------------------------------------
| Every standard field on the registration form is registered here so the
| Form Builder can RELABEL, HIDE, REQUIRE, DELETE, MOVE (to any category) and
| REORDER it. `locked` fields are system-critical — they may be relabeled and
| moved, but never hidden, deleted, or made optional (registration needs them).
|
| `widget` tells the data-driven registration form how to render the field:
|   text | tel | email | textarea | number | date | select | program |
|   photo | classifications | consent | signature
| For `select`, `source` names an option list passed to the form
| (sex, civil_status, regions, …); `blank` adds an empty first option.
| `colspan`: 'full' = whole row, 2 = two grid cells, else one cell.
| Keys match the data bindings in Pages/Applicants/Form.tsx.
*/

return [
    'fields' => [
        // ── Profile ──────────────────────────────────────────────────────
        ['key' => 'last_name',   'label' => 'Last name',      'section' => 'sec-profile', 'widget' => 'text', 'locked' => true],
        ['key' => 'first_name',  'label' => 'First name',     'section' => 'sec-profile', 'widget' => 'text', 'locked' => true],
        ['key' => 'middle_name', 'label' => 'Middle name',    'section' => 'sec-profile', 'widget' => 'text'],
        ['key' => 'ext_name',    'label' => 'Ext. (Jr./Sr.)', 'section' => 'sec-profile', 'widget' => 'text'],
        ['key' => 'photo',       'label' => '2×2 Photo',      'section' => 'sec-profile', 'widget' => 'photo', 'colspan' => 'full'],

        // ── Address ──────────────────────────────────────────────────────
        ['key' => 'street',   'label' => 'No., Street',         'section' => 'sec-address', 'widget' => 'text'],
        ['key' => 'barangay', 'label' => 'Barangay',            'section' => 'sec-address', 'widget' => 'text', 'locked' => true],
        ['key' => 'district', 'label' => 'District',            'section' => 'sec-address', 'widget' => 'text'],
        ['key' => 'city',     'label' => 'City / Municipality', 'section' => 'sec-address', 'widget' => 'text'],
        ['key' => 'province', 'label' => 'Province',            'section' => 'sec-address', 'widget' => 'text'],
        ['key' => 'region',   'label' => 'Region',              'section' => 'sec-address', 'widget' => 'select', 'source' => 'regions'],

        // ── Contact ──────────────────────────────────────────────────────
        ['key' => 'email',        'label' => 'Email / Facebook',  'section' => 'sec-contact', 'widget' => 'text'],
        ['key' => 'contact',      'label' => 'Contact no.',       'section' => 'sec-contact', 'widget' => 'tel', 'locked' => true],
        ['key' => 'nationality',  'label' => 'Nationality',       'section' => 'sec-contact', 'widget' => 'text'],
        ['key' => 'religion',     'label' => 'Religion',          'section' => 'sec-contact', 'widget' => 'text'],
        ['key' => 'ethnic_group', 'label' => 'Ethnic group / IP affiliation', 'section' => 'sec-contact', 'widget' => 'text', 'placeholder' => 'If Indigenous People'],

        // ── Personal ─────────────────────────────────────────────────────
        ['key' => 'sex',                 'label' => 'Sex',                 'section' => 'sec-personal', 'widget' => 'select', 'source' => 'sex', 'locked' => true],
        ['key' => 'civil_status',        'label' => 'Civil status',        'section' => 'sec-personal', 'widget' => 'select', 'source' => 'civil_status'],
        ['key' => 'emp_status',          'label' => 'Employment status',   'section' => 'sec-personal', 'widget' => 'select', 'source' => 'emp_status', 'blank' => true],
        ['key' => 'emp_type',            'label' => 'Employment type',     'section' => 'sec-personal', 'widget' => 'select', 'source' => 'emp_type', 'blank' => true],
        ['key' => 'employer_name',       'label' => 'Employer / Company (if employed)', 'section' => 'sec-personal', 'widget' => 'text'],
        ['key' => 'employer_position',   'label' => 'Position (if employed)', 'section' => 'sec-personal', 'widget' => 'text'],
        ['key' => 'birthdate',           'label' => 'Birthdate',           'section' => 'sec-personal', 'widget' => 'date'],
        ['key' => 'birthplace_city',     'label' => 'Birthplace — City',     'section' => 'sec-personal', 'widget' => 'text'],
        ['key' => 'birthplace_province', 'label' => 'Birthplace — Province', 'section' => 'sec-personal', 'widget' => 'text'],
        ['key' => 'birthplace_region',   'label' => 'Birthplace — Region',   'section' => 'sec-personal', 'widget' => 'text'],
        ['key' => 'education',           'label' => 'Educational attainment', 'section' => 'sec-personal', 'widget' => 'select', 'source' => 'education'],
        ['key' => 'school_last_attended', 'label' => 'School last attended', 'section' => 'sec-personal', 'widget' => 'text'],
        ['key' => 'year_graduated',      'label' => 'Year graduated',       'section' => 'sec-personal', 'widget' => 'text', 'placeholder' => 'e.g. 2018'],

        // ── Guardian & Health ────────────────────────────────────────────
        ['key' => 'guardian_name',    'label' => 'Parent / Guardian name', 'section' => 'sec-health', 'widget' => 'text'],
        ['key' => 'guardian_address', 'label' => 'Guardian address',       'section' => 'sec-health', 'widget' => 'text'],
        ['key' => 'height',     'label' => 'Height',         'section' => 'sec-health', 'widget' => 'text', 'placeholder' => 'e.g. 165 cm'],
        ['key' => 'weight',     'label' => 'Weight',         'section' => 'sec-health', 'widget' => 'text', 'placeholder' => 'e.g. 60 kg'],
        ['key' => 'blood_type', 'label' => 'Blood type',     'section' => 'sec-health', 'widget' => 'select', 'source' => 'blood_types', 'blank' => true],
        ['key' => 'eyesight',   'label' => 'Eyesight',       'section' => 'sec-health', 'widget' => 'select', 'source' => 'rating', 'blank' => true],
        ['key' => 'hearing',    'label' => 'Hearing',        'section' => 'sec-health', 'widget' => 'select', 'source' => 'rating', 'blank' => true],
        ['key' => 'medical',    'label' => 'Medical issues', 'section' => 'sec-health', 'widget' => 'text', 'placeholder' => 'None / specify'],

        // ── Family ───────────────────────────────────────────────────────
        ['key' => 'father_name',        'label' => "Father's name",        'section' => 'sec-family', 'widget' => 'text'],
        ['key' => 'father_occupation',  'label' => "Father's occupation",  'section' => 'sec-family', 'widget' => 'text'],
        ['key' => 'mother_name',        'label' => "Mother's name",        'section' => 'sec-family', 'widget' => 'text'],
        ['key' => 'mother_maiden_name', 'label' => "Mother's maiden name", 'section' => 'sec-family', 'widget' => 'text'],
        ['key' => 'mother_occupation',  'label' => "Mother's occupation",  'section' => 'sec-family', 'widget' => 'text'],
        ['key' => 'family_rank',        'label' => 'Position in family',   'section' => 'sec-family', 'widget' => 'text', 'placeholder' => 'e.g. 2nd of 4'],
        ['key' => 'siblings',           'label' => 'No. of siblings',      'section' => 'sec-family', 'widget' => 'text'],
        ['key' => 'spouse_name',        'label' => "Spouse's name",        'section' => 'sec-family', 'widget' => 'text'],
        ['key' => 'spouse_occupation',  'label' => "Spouse's occupation",  'section' => 'sec-family', 'widget' => 'text'],
        ['key' => 'children',           'label' => 'No. of children',      'section' => 'sec-family', 'widget' => 'text'],

        // ── Government IDs ───────────────────────────────────────────────
        ['key' => 'sss_no',        'label' => 'SSS No.',        'section' => 'sec-govids', 'widget' => 'text'],
        ['key' => 'gsis_no',       'label' => 'GSIS No.',       'section' => 'sec-govids', 'widget' => 'text'],
        ['key' => 'tin_no',        'label' => 'TIN',            'section' => 'sec-govids', 'widget' => 'text'],
        ['key' => 'philhealth_no', 'label' => 'PhilHealth No.', 'section' => 'sec-govids', 'widget' => 'text'],

        // ── Course / Qualification ───────────────────────────────────────
        ['key' => 'program_id',    'label' => 'Course / qualification', 'section' => 'sec-course', 'widget' => 'program', 'colspan' => 2, 'locked' => true],
        ['key' => 'scholarship',   'label' => 'Scholarship package',    'section' => 'sec-course', 'widget' => 'select', 'source' => 'scholarship'],
        ['key' => 'class_session', 'label' => 'Class session',          'section' => 'sec-course', 'widget' => 'select', 'source' => 'class_session', 'blank' => true],
        ['key' => 'school_year',   'label' => 'School year',            'section' => 'sec-course', 'widget' => 'text', 'placeholder' => '2026–2027'],

        // ── Classification ───────────────────────────────────────────────
        ['key' => 'classifications',      'label' => 'Learner / Trainee classification', 'section' => 'sec-classification', 'widget' => 'classifications', 'colspan' => 'full'],
        ['key' => 'classification_other', 'label' => 'Others (specify)',                  'section' => 'sec-classification', 'widget' => 'text', 'colspan' => 'full'],

        // ── Disability & Emergency ───────────────────────────────────────
        ['key' => 'disability_type',        'label' => 'Type of disability',  'section' => 'sec-disability', 'widget' => 'select', 'source' => 'disability_types', 'blank' => true, 'blankLabel' => 'None / N/A'],
        ['key' => 'disability_cause',       'label' => 'Cause of disability', 'section' => 'sec-disability', 'widget' => 'select', 'source' => 'disability_causes', 'blank' => true, 'blankLabel' => 'None / N/A'],
        ['key' => 'emergency_name',         'label' => 'Emergency — contact person', 'section' => 'sec-disability', 'widget' => 'text'],
        ['key' => 'emergency_relationship', 'label' => 'Relationship',        'section' => 'sec-disability', 'widget' => 'text'],
        ['key' => 'emergency_contact',      'label' => 'Contact no.',         'section' => 'sec-disability', 'widget' => 'tel'],
        ['key' => 'emergency_address',      'label' => 'Emergency address',   'section' => 'sec-disability', 'widget' => 'text'],

        // ── Privacy Consent ──────────────────────────────────────────────
        ['key' => 'privacy_consent', 'label' => 'The applicant consents to the collection and processing of this personal data for training administration, in accordance with R.A. 10173 (Data Privacy Act).', 'section' => 'sec-consent', 'widget' => 'consent', 'colspan' => 'full'],
        ['key' => 'remarks',         'label' => 'Remarks', 'section' => 'sec-consent', 'widget' => 'textarea', 'colspan' => 'full'],

        // ── Verification ─────────────────────────────────────────────────
        ['key' => 'date_accomplished', 'label' => 'Date accomplished', 'section' => 'sec-verify', 'widget' => 'date'],
        ['key' => 'date_received',     'label' => 'Date received',     'section' => 'sec-verify', 'widget' => 'date'],
        ['key' => 'interviewed_by',    'label' => 'Interviewed by',    'section' => 'sec-verify', 'widget' => 'signature'],
        ['key' => 'checked_by',        'label' => 'Checked by',        'section' => 'sec-verify', 'widget' => 'signature', 'signatory' => 'checked_by'],
        ['key' => 'approved_by',       'label' => 'Approved by',       'section' => 'sec-verify', 'widget' => 'signature', 'signatory' => 'approved_by'],
    ],
];
