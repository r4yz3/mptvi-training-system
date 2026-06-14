<?php

/*
|--------------------------------------------------------------------------
| MPTVI documentary requirements (per their enrollment poster)
|--------------------------------------------------------------------------
| NOTE-ONLY (2026-06-14): each requirement is recorded with a typed note +
| a simple status (Pending / Submitted / Not applicable) on the applicant —
| no file or photo upload, since some applicants can't provide the exact
| documents and staff just note what was (or wasn't) presented. `copies` is
| informational (the number of pieces the applicant is asked to bring).
*/

return [
    ['key' => 0, 'label' => '2×2 Picture (2 pcs)',                                          'copies' => 2],
    ['key' => 1, 'label' => 'Barangay Clearance',                                           'copies' => 1],
    ['key' => 2, 'label' => 'PSA Birth Certificate or Marriage Contract (photocopy, 2 pcs)', 'copies' => 2],
    ['key' => 3, 'label' => 'School Record — Form 137 / Diploma / Report Card (+ TOR if college grad)', 'copies' => 1],
    ['key' => 4, 'label' => 'Brown Envelope, Long (2 pcs)',                                  'copies' => 2],
    ['key' => 5, 'label' => 'Brown Folder, Long (1 pc)',                                     'copies' => 1],
];
