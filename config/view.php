<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Do not use realpath() here — if config:cache runs before the directory
    | exists, realpath() returns false and Blade compilation breaks on PHP 8.4.
    |
    */

    'compiled' => env('VIEW_COMPILED_PATH', storage_path('framework/views')),

];
