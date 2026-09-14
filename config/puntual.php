<?php

return [
    'timezone' => 'America/Bogota',
    'vapid_public_key' => env('PUNTUAL_VAPID_PUBLIC_KEY'),
    'vapid_private_key' => env('PUNTUAL_VAPID_PRIVATE_KEY'),
    'vapid_subject' => env('PUNTUAL_VAPID_SUBJECT', env('APP_URL')),
    'apk_url' => env('PUNTUAL_APK_URL', '/downloads/lavanderia-exclusiva.apk'),
];
