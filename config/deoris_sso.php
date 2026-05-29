<?php

return [
    'portal_url'        => env('DEORIS_PORTAL_URL', 'https://deoris.test'),
    'portal_public_key' => env('DEORIS_PORTAL_PUBLIC_KEY', null),

    'session' => [
        'lifetime'               => env('SSO_SESSION_LIFETIME', 120),
        'expire_on_close'        => env('SSO_EXPIRE_ON_CLOSE', false),
        'exchange_timeout'       => 8,
        'token_lifetime'         => 5,
        'token_cleanup_interval' => 10,
    ],

    'iframe' => [
        'allow_embed'    => true,
        'frame_ancestors' => env('DEORIS_PORTAL_URL', 'https://deoris.test'),
    ],

    'logging' => [
        'enabled'             => env('SSO_LOGGING', true),
        'level'               => env('SSO_LOG_LEVEL', 'errors'),
        'include_user_details' => env('SSO_LOG_USER_DETAILS', false),
    ],
];
