<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service Identity
    |--------------------------------------------------------------------------
    */
    'service_name'    => 'EnrollEase',
    'service_key'     => 'enroll-ease',
    'service_url'     => env('APP_URL', 'https://enrollease.deoris.test'),
    'api_version'     => 'v1',
    'trusted_portal'  => env('APP_PORTAL_URL', 'https://deoris.test'),

    /*
    |--------------------------------------------------------------------------
    | Event Publishing
    |--------------------------------------------------------------------------
    */
    'publish_enabled' => (bool) env('DEORIS_PORTAL_PUBLISH_ENABLED', false),
    'event_secret'    => env('ENROLLEASE_EVENT_SECRET', ''),
    'search_token'    => env('ENROLLEASE_SEARCH_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Redis Channels & Queues
    |--------------------------------------------------------------------------
    */
    'redis_channel'   => env('ENROLLEASE_REDIS_CHANNEL', 'enrollease.events'),
    'queues'          => [
        'enrollments' => env('QUEUE_ENROLLMENTS', 'enrollments'),
        'events'      => env('QUEUE_EVENTS',       'events'),
        'notifications' => env('QUEUE_NOTIFICATIONS', 'notifications'),
        'reports'     => env('QUEUE_REPORTS',      'reports'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Enrollment Rules
    |--------------------------------------------------------------------------
    */
    'require_admission_approval' => (bool) env('ENROLLEASE_REQUIRE_ADMISSION', false),
    'max_grade_level'            => 12,
    'min_grade_level'            => 1,

];
