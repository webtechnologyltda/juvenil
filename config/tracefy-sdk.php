<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tracefy SDK
    |--------------------------------------------------------------------------
    |
    | Base configuration for Tracefy SDK package.
    | Automatic exception capture is enabled.
    | Queue monitoring is enabled.
    | JS tracking is not enabled.
    |
    */

    'enabled' => env('TRACEFY_ENABLED', true),

    'api_key' => env('TRACEFY_API_KEY'),

    'base_url' => 'https://tracefy.webtechnology.com.br',

    'event_route' => '/api/events',

    'event_endpoints' => [
        'backend' => 'exception',
        'queue' => 'queue',
        'js' => 'js',
    ],

    'environment' => env('TRACEFY_ENV', env('APP_ENV', 'production')),

    'capture' => [
        'exceptions' => env('TRACEFY_CAPTURE_EXCEPTIONS', true),
        'fatals' => env('TRACEFY_CAPTURE_FATALS', true),
    ],

    'hooks' => [
        'unhandled_throwable' => env('TRACEFY_HOOK_UNHANDLED_THROWABLE', true),
        'unhandled_throwable_in_console' => env('TRACEFY_HOOK_UNHANDLED_THROWABLE_IN_CONSOLE', false),
        'unhandled_throwable_in_testing' => env('TRACEFY_HOOK_UNHANDLED_THROWABLE_IN_TESTING', false),
        'unhandled_throwable_in_parallel' => env('TRACEFY_HOOK_UNHANDLED_THROWABLE_IN_PARALLEL', false),
    ],

    'send' => [
        'exceptions' => env('TRACEFY_SEND_EXCEPTIONS', true),
        'queue' => env('TRACEFY_SEND_QUEUE_EVENTS', true),
    ],

    'queue' => [
        'monitoring' => env('TRACEFY_QUEUE_MONITORING', true),
    ],

    'http' => [
        'timeout_seconds' => env('TRACEFY_HTTP_TIMEOUT', 10),
        'retries' => env('TRACEFY_HTTP_RETRIES', 2),
        'retry_delay_ms' => env('TRACEFY_HTTP_RETRY_DELAY_MS', 200),
    ],

    'trace' => [
        'max_frames' => (int) env('TRACEFY_TRACE_MAX_FRAMES', 120),
        'max_arg_depth' => (int) env('TRACEFY_TRACE_MAX_ARG_DEPTH', 2),
        'max_string_length' => (int) env('TRACEFY_TRACE_MAX_STRING_LENGTH', 2000),
        'max_message_length' => (int) env('TRACEFY_TRACE_MAX_MESSAGE_LENGTH', 250),
        'fallback_max_frames' => (int) env('TRACEFY_TRACE_FALLBACK_MAX_FRAMES', 30),
        'minimal_fallback_max_frames' => (int) env('TRACEFY_TRACE_MINIMAL_FALLBACK_MAX_FRAMES', 10),
    ],

    'sanitize' => [
        'fields' => ['password', 'token'],
        'headers' => ['authorization', 'cookie', 'set-cookie', 'x-api-key'],
    ],

    'options' => [
        'app_name' => env('APP_NAME', 'Tracefy SDK'),
    ],
];
