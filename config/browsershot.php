<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Browsershot — PDF from print HTML (pixel-perfect with emulateMedia print)
    |--------------------------------------------------------------------------
    */

    'node_binary' => env('BROWSERSHOT_NODE', 'node'),

    'npm_binary' => env('BROWSERSHOT_NPM', 'npm'),

    'node_modules_path' => env('BROWSERSHOT_NODE_MODULES', base_path('node_modules')),

    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),

    'no_sandbox' => env('BROWSERSHOT_NO_SANDBOX', PHP_OS_FAMILY !== 'Darwin'),

    'pdf_delay_ms' => (int) env('BROWSERSHOT_PDF_DELAY_MS', 1500),

    'php_timeout' => (int) env('BROWSERSHOT_PHP_TIMEOUT', 120),

];
