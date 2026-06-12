<?php

/*
|--------------------------------------------------------------------------
| Registration form structure
|--------------------------------------------------------------------------
| Canonical list of the built-in LPF sections (kept in sync with the React
| SECTIONS array in Pages/Applicants/Form.tsx). Admin can show/hide & reorder
| these; custom fields target one of these section ids (or sec-additional).
*/

return [
    'sections' => [
        ['key' => 'sec-profile',        'label' => 'Learner / Manpower Profile'],
        ['key' => 'sec-address',        'label' => 'Address'],
        ['key' => 'sec-contact',        'label' => 'Contact'],
        ['key' => 'sec-personal',       'label' => 'Personal Information'],
        ['key' => 'sec-health',         'label' => 'Guardian & Health'],
        ['key' => 'sec-family',         'label' => 'Family Background'],
        ['key' => 'sec-govids',         'label' => 'Government-issued IDs'],
        ['key' => 'sec-course',         'label' => 'Course / Qualification & Scholarship'],
        ['key' => 'sec-classification', 'label' => 'Learner / Trainee Classification'],
        ['key' => 'sec-disability',     'label' => 'Disability & Emergency Contact'],
        ['key' => 'sec-additional',     'label' => 'Additional Information'],
        ['key' => 'sec-consent',        'label' => 'Privacy Consent'],
        ['key' => 'sec-verify',         'label' => 'Verification & Signatures'],
    ],

    'field_types' => ['text', 'textarea', 'number', 'date', 'select', 'checkbox'],
];
