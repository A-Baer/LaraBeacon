<?php

return [
    // Unchanged from the Laravel 12 skeleton: env() may return bool for "true"/"false", but not for a URL.
    'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',

    // A real invalid call in a configuration file must still be reported.
    'prefix' => rtrim(['not', 'a', 'string'], '/'),

    // A possibly missing value without a default (bool|string|null) stays reported.
    'separator' => trim(env('LARABEACON_TEST_SEPARATOR')),
];
