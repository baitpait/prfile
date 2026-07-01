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

    'puppeteer_cache_dir' => env('PUPPETEER_CACHE_DIR', storage_path('app/puppeteer-cache')),

    'temp_path' => env('BROWSERSHOT_TEMP_PATH', storage_path('app/browsershot-tmp')),

    'no_sandbox' => env('BROWSERSHOT_NO_SANDBOX', PHP_OS_FAMILY !== 'Darwin'),

    'pdf_delay_ms' => (int) env('BROWSERSHOT_PDF_DELAY_MS', 1500),

    'php_timeout' => (int) env('BROWSERSHOT_PHP_TIMEOUT', 120),

];
