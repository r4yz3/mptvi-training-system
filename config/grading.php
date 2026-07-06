<?php

/*
|--------------------------------------------------------------------------
| Grading system defaults
|--------------------------------------------------------------------------
| Weighted components on a 0–100 scale. Admins edit these (and the passing
| grade) at Settings → Grading system; saved overrides are merged over this
| file in Setting::applyConfigOverrides(). Component keys tie scores already
| saved on trainees to their component — renaming a label keeps the scores.
*/

return [

    'components' => [
        ['key' => 'written',    'label' => 'Written exam',            'weight' => 30],
        ['key' => 'practical',  'label' => 'Practical / performance', 'weight' => 50],
        ['key' => 'attendance', 'label' => 'Attendance & attitude',   'weight' => 20],
    ],

    // Final weighted grade at or above this passes.
    'passing' => 75,
];
