<?php

/*
|--------------------------------------------------------------------------
| TESDA Learner Profile Form — reference option lists
|--------------------------------------------------------------------------
| Ported verbatim from the demo constants. Shared with the React registration
| form (passed as props) and used for server-side validation (Rule::in).
*/

return [

    'statuses' => [
        'Registered', 'Enrolled', 'In training', 'For assessment', 'Certified', 'Disqualified',
    ],

    // Trainee training status — independent of the pipeline + the app-wide active flag.
    'trainee_statuses' => ['Active', 'Inactive', 'Completed', 'Incomplete'],

    // Educational background grid — one row per level.
    'education_levels' => [
        ['key' => 'elementary',  'label' => 'Elementary'],
        ['key' => 'junior_high', 'label' => 'Junior High School'],
        ['key' => 'senior_high', 'label' => 'Senior High School'],
        ['key' => 'college',     'label' => 'College / Vocational'],
        ['key' => 'postgrad',    'label' => 'Post-Graduate'],
    ],
    'education_statuses' => ['Graduate', 'Undergraduate', 'Ongoing'],

    // Cashier payment categories. Only the miscellaneous fee counts toward the
    // program-fee balance + pipeline; the rest are extra collections.
    // "Others" requires a description (the cashier specifies what it is for).
    'payment_categories' => ['Miscellaneous fee', 'School uniform', 'Assessment fee', 'Others'],
    'training_fee_category' => 'Miscellaneous fee',
    'other_category' => 'Others',
    // Extra fees that carry an expected amount set per program + school year
    // (Settings → Fees), so the cashier can track who still owes them. The
    // Miscellaneous fee lives on the program; "Others" is ad-hoc (no amount).
    'scheduled_fee_categories' => ['School uniform', 'Assessment fee'],

    'sex' => ['Male', 'Female'],

    'civil_status' => ['Single', 'Married', 'Separated/Divorced/Annulled', 'Widow/er', 'Common Law/Live-In'],

    'emp_status' => ['Wage-Employed', 'Underemployed', 'Self-Employed', 'Unemployed'],

    'emp_type' => ['None', 'Casual', 'Probationary', 'Contractual', 'Regular', 'Job Order', 'Permanent', 'Temporary'],

    'education' => [
        'No Grade Completed', 'Elementary Undergraduate', 'Elementary Graduate',
        'High School Undergraduate', 'High School Graduate', 'Junior High (K-12)', 'Senior High (K-12)',
        'Post-Secondary Non-Tertiary/Technical Vocational Course Undergraduate',
        'Post-Secondary Non-Tertiary/Technical Vocational Course Graduate',
        'College Undergraduate', 'College Graduate', 'Masteral', 'Doctorate', 'TVET Graduate',
    ],

    'regions' => [
        'Region XI (Davao Region)', 'NCR', 'CAR', 'Region I (Ilocos)', 'Region II (Cagayan Valley)',
        'Region III (Central Luzon)', 'Region IV-A (CALABARZON)', 'Region IV-B (MIMAROPA)', 'Region V (Bicol)',
        'Region VI (Western Visayas)', 'Region VII (Central Visayas)', 'Region VIII (Eastern Visayas)',
        'Region IX (Zamboanga)', 'Region X (Northern Mindanao)', 'Region XII (SOCCSKSARGEN)',
        'Region XIII (Caraga)', 'BARMM',
    ],

    'scholarship' => ['None', 'TWSP', 'PESFA', 'STEP', 'TESDA Scholarship', 'Others'],

    'class_session' => ['Morning', 'Afternoon', 'Whole-day'],

    'blood_types' => ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'],

    'rating' => ['Good', 'Medium', 'Poor'],

    'disability_types' => [
        'Mental/Intellectual', 'Visual Disability', 'Orthopedic (Musculoskeletal) Disability',
        'Hearing Disability', 'Speech Impairment', 'Multiple Disabilities', 'Psychosocial Disability',
        'Disability Due to Chronic Illness', 'Learning Disability',
    ],

    'disability_causes' => ['Congenital/Inborn', 'Illness', 'Injury'],

    // Default verification signatories (the school officials who check/approve the LPF).
    'signatories' => [
        'checked_by' => ['name' => 'WENDY FE D. SILVANO', 'title' => 'Skills Training Focal / Registrar'],
        'approved_by' => ['name' => 'LEONILE P. ESCARPE', 'title' => 'School Administrator / CTEC'],
    ],

    'classifications' => [
        '4Ps Beneficiary', 'Agrarian Reform Beneficiary', 'Balik Probinsya', 'Displaced Workers',
        'Drug Dependents Surrenderees/Surrenderers', 'Family Members of AFP and PNP Killed-in-Action',
        'Family Members of AFP and PNP Wounded-in-Action', 'Farmers and Fishermen',
        'Indigenous People & Cultural Communities', 'Industry Workers', 'Inmates and Detainees',
        'MILF Beneficiary', 'Out-of-School-Youth', 'Overseas Filipino Workers (OFW) Dependent',
        'RCEF-RESP', 'Rebel Returnees/Decommissioned Combatants',
        'Returning/Repatriated Overseas Filipino Workers (OFW)', 'Student', 'TESDA Alumni',
        'TVET Trainers', 'Uniformed Personnel', 'Victim of Natural Disasters and Calamities',
        'Wounded-in-Action AFP & PNP Personnel',
    ],

];
