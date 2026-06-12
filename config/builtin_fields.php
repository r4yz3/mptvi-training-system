<?php

/*
|--------------------------------------------------------------------------
| Built-in (standard LPF) fields the admin may manage
|--------------------------------------------------------------------------
| Each field can be hidden, relabeled, or required/optional via the Form
| Builder. `locked` fields are system-critical — they can be RELABELED but
| never hidden or made optional (registration depends on them).
| Keys match the data bindings in Pages/Applicants/Form.tsx.
*/

return [
    'fields' => [
        // Profile
        ['key' => 'last_name',   'label' => 'Last name',       'section' => 'sec-profile', 'locked' => true],
        ['key' => 'first_name',  'label' => 'First name',      'section' => 'sec-profile', 'locked' => true],
        ['key' => 'middle_name', 'label' => 'Middle name',     'section' => 'sec-profile'],
        ['key' => 'ext_name',    'label' => 'Ext. (Jr./Sr.)',  'section' => 'sec-profile'],
        // Address
        ['key' => 'street',   'label' => 'No., Street',         'section' => 'sec-address'],
        ['key' => 'barangay', 'label' => 'Barangay',            'section' => 'sec-address', 'locked' => true],
        ['key' => 'district', 'label' => 'District',            'section' => 'sec-address'],
        ['key' => 'city',     'label' => 'City / Municipality', 'section' => 'sec-address'],
        ['key' => 'province', 'label' => 'Province',            'section' => 'sec-address'],
        ['key' => 'region',   'label' => 'Region',              'section' => 'sec-address'],
        // Contact
        ['key' => 'email',        'label' => 'Email / Facebook',  'section' => 'sec-contact'],
        ['key' => 'contact',      'label' => 'Contact no.',       'section' => 'sec-contact', 'locked' => true],
        ['key' => 'nationality',  'label' => 'Nationality',       'section' => 'sec-contact'],
        ['key' => 'religion',     'label' => 'Religion',          'section' => 'sec-contact'],
        ['key' => 'ethnic_group', 'label' => 'Ethnic group / IP affiliation', 'section' => 'sec-contact'],
        // Personal
        ['key' => 'sex',                 'label' => 'Sex',                 'section' => 'sec-personal', 'locked' => true],
        ['key' => 'civil_status',        'label' => 'Civil status',        'section' => 'sec-personal'],
        ['key' => 'emp_status',          'label' => 'Employment status',   'section' => 'sec-personal'],
        ['key' => 'emp_type',            'label' => 'Employment type',     'section' => 'sec-personal'],
        ['key' => 'employer_name',       'label' => 'Employer / Company (if employed)', 'section' => 'sec-personal'],
        ['key' => 'employer_position',   'label' => 'Position (if employed)', 'section' => 'sec-personal'],
        ['key' => 'birthdate',           'label' => 'Birthdate',           'section' => 'sec-personal'],
        ['key' => 'birthplace_city',     'label' => 'Birthplace — City',     'section' => 'sec-personal'],
        ['key' => 'birthplace_province', 'label' => 'Birthplace — Province', 'section' => 'sec-personal'],
        ['key' => 'birthplace_region',   'label' => 'Birthplace — Region',   'section' => 'sec-personal'],
        ['key' => 'education',           'label' => 'Educational attainment', 'section' => 'sec-personal'],
        ['key' => 'school_last_attended', 'label' => 'School last attended', 'section' => 'sec-personal'],
        ['key' => 'year_graduated',      'label' => 'Year graduated',       'section' => 'sec-personal'],
        // Guardian & Health
        ['key' => 'guardian_name',    'label' => 'Parent / Guardian name', 'section' => 'sec-health'],
        ['key' => 'guardian_address', 'label' => 'Guardian address',       'section' => 'sec-health'],
        ['key' => 'height',     'label' => 'Height',         'section' => 'sec-health'],
        ['key' => 'weight',     'label' => 'Weight',         'section' => 'sec-health'],
        ['key' => 'blood_type', 'label' => 'Blood type',     'section' => 'sec-health'],
        ['key' => 'eyesight',   'label' => 'Eyesight',       'section' => 'sec-health'],
        ['key' => 'hearing',    'label' => 'Hearing',        'section' => 'sec-health'],
        ['key' => 'medical',    'label' => 'Medical issues', 'section' => 'sec-health'],
        // Family
        ['key' => 'father_name',        'label' => "Father's name",        'section' => 'sec-family'],
        ['key' => 'father_occupation',  'label' => "Father's occupation",  'section' => 'sec-family'],
        ['key' => 'mother_name',        'label' => "Mother's name",        'section' => 'sec-family'],
        ['key' => 'mother_maiden_name', 'label' => "Mother's maiden name", 'section' => 'sec-family'],
        ['key' => 'mother_occupation',  'label' => "Mother's occupation",  'section' => 'sec-family'],
        ['key' => 'family_rank',        'label' => 'Position in family',   'section' => 'sec-family'],
        ['key' => 'siblings',           'label' => 'No. of siblings',      'section' => 'sec-family'],
        ['key' => 'spouse_name',        'label' => "Spouse's name",        'section' => 'sec-family'],
        ['key' => 'spouse_occupation',  'label' => "Spouse's occupation",  'section' => 'sec-family'],
        ['key' => 'children',           'label' => 'No. of children',      'section' => 'sec-family'],
        // Government IDs
        ['key' => 'sss_no',        'label' => 'SSS No.',        'section' => 'sec-govids'],
        ['key' => 'gsis_no',       'label' => 'GSIS No.',       'section' => 'sec-govids'],
        ['key' => 'tin_no',        'label' => 'TIN',            'section' => 'sec-govids'],
        ['key' => 'philhealth_no', 'label' => 'PhilHealth No.', 'section' => 'sec-govids'],
        // Course
        ['key' => 'program_id',    'label' => 'Course / qualification', 'section' => 'sec-course', 'locked' => true],
        ['key' => 'scholarship',   'label' => 'Scholarship package',    'section' => 'sec-course'],
        ['key' => 'class_session', 'label' => 'Class session',          'section' => 'sec-course'],
        ['key' => 'school_year',   'label' => 'School year',            'section' => 'sec-course'],
        // Disability & Emergency
        ['key' => 'disability_type',        'label' => 'Type of disability',  'section' => 'sec-disability'],
        ['key' => 'disability_cause',       'label' => 'Cause of disability', 'section' => 'sec-disability'],
        ['key' => 'emergency_name',         'label' => 'Emergency — contact person', 'section' => 'sec-disability'],
        ['key' => 'emergency_relationship', 'label' => 'Relationship',        'section' => 'sec-disability'],
        ['key' => 'emergency_contact',      'label' => 'Contact no.',         'section' => 'sec-disability'],
        ['key' => 'emergency_address',      'label' => 'Emergency address',   'section' => 'sec-disability'],
    ],
];
