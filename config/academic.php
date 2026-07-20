<?php

/*
|--------------------------------------------------------------------------
| Academic defaults & numbering
|--------------------------------------------------------------------------
| Defaults for eligibility, form pre-fill, and certificate numbering.
| Admin overrides (Settings → Academic defaults) are merged over these at
| runtime by AppServiceProvider, so every config('academic.*') read reflects
| the saved settings.
*/

return [
    'school_year' => '',          // single calendar year, e.g. "2026" — pre-fills new registrations
    'default_session' => '',      // Morning | Afternoon | Whole-day
    'default_fee' => 0,           // default program misc fee (whole pesos)
    'age_min' => 15,              // eligibility age range
    'age_max' => 60,
    'cert_prefix' => 'CK2',       // certificate number prefix → PREFIX-YYYY-####
];
