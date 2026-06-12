<?php

/*
|--------------------------------------------------------------------------
| MPTVI documentary requirements (per their enrollment poster)
|--------------------------------------------------------------------------
| Index = stable key stored on the documents table. `physical` items are
| supplies checked off as received (no file upload); the rest are uploadable
| files held on the PRIVATE disk and served only via the authenticated
| FileController, with every access written to the audit log (R.A. 10173).
*/

return [
    ['key' => 0, 'label' => '2×2 Picture (2 pcs)',                                          'physical' => false],
    ['key' => 1, 'label' => 'Barangay Clearance',                                           'physical' => false],
    ['key' => 2, 'label' => 'PSA Birth Certificate or Marriage Contract (photocopy, 2 pcs)', 'physical' => false],
    ['key' => 3, 'label' => 'School Record — Form 137 / Diploma / Report Card (+ TOR if college grad)', 'physical' => false],
    ['key' => 4, 'label' => 'Brown Envelope, Long (2 pcs)',                                  'physical' => true],
    ['key' => 5, 'label' => 'Brown Folder, Long (1 pc)',                                     'physical' => true],
];
