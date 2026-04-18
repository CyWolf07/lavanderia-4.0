<?php

$compiled = env('VIEW_COMPILED_PATH');

if ($compiled && ! preg_match('/^(?:[A-Za-z]:[\\\\\\/]|[\\\\\\/])/', $compiled)) {
    $compiled = base_path($compiled);
}

return [
    'paths' => [
        resource_path('views'),
    ],

    'compiled' => $compiled ?: realpath(storage_path('framework/views')) ?: storage_path('framework/views'),
];
