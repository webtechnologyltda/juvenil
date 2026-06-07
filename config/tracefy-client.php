<?php

declare(strict_types=1);

return [
    'enabled' => env('TRACEFY_ENABLED', true),

    'api_key' => env('TRACEFY_API_KEY'),

    'endpoint' => env('TRACEFY_ENDPOINT', 'https://tracefy.webtechnology.com.br'),

    'event_route' => '/api/events',

    'event_endpoints' => [
        'exception' => 'exception',
        'queue' => 'queue',
        'js' => 'js',
        'performance' => 'performance',
    ],

    'environments' => array_values(array_filter(array_map(
        static fn (string $value): string => trim($value),
        explode(',', (string) env('TRACEFY_ENVIRONMENTS', 'production'))
    ))),

    'environment' => env('APP_ENV', 'production'),

    'release' => env('TRACEFY_RELEASE', '1.0.0'),

    'http' => [
        'timeout_seconds' => (int) env('TRACEFY_HTTP_TIMEOUT', 10),
        'retries' => (int) env('TRACEFY_HTTP_RETRIES', 2),
        'retry_delay_ms' => (int) env('TRACEFY_HTTP_RETRY_DELAY_MS', 200),
    ],

    'duplicate_sleep_seconds' => (int) env('TRACEFY_DUPLICATE_SLEEP_SECONDS', 30),

    'sanitize' => [
        'fields' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('TRACEFY_SENSITIVE_FIELDS', '*password*,*token*,*authorization*,*cookie*'))
        ))),
        'headers' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('TRACEFY_SENSITIVE_HEADERS', 'authorization,cookie,set-cookie,x-api-key'))
        ))),
    ],

    'queue' => [
        'enabled' => env('TRACEFY_QUEUE_MONITORING', false),
        'track_queued' => env('TRACEFY_QUEUE_TRACK_QUEUED', false),
        'track_processing' => env('TRACEFY_QUEUE_TRACK_PROCESSING', false),
        'track_processed' => env('TRACEFY_QUEUE_TRACK_PROCESSED', false),
        'track_failed' => env('TRACEFY_QUEUE_TRACK_FAILED', true),
    ],

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

    'performance' => [
        'enabled' => env('TRACEFY_PERFORMANCE_ENABLED', true),
        'slow_query_threshold_ms' => (float) env('TRACEFY_SLOW_QUERY_THRESHOLD_MS', 200),
    ],

    'frontend' => [
        'enabled' => env('TRACEFY_FRONTEND_ENABLED', true),
        'proxy' => [
            'enabled' => env('TRACEFY_FRONTEND_PROXY_ENABLED', true),
            'path' => env('TRACEFY_FRONTEND_PROXY_PATH', '/tracefy-sdk/events/js'),
        ],
        'filament' => [
            'enabled' => env('TRACEFY_FILAMENT_ENABLED', true),
            'hook' => env('TRACEFY_FILAMENT_HOOK', 'panels::head.end'),
            'asset_url' => env('TRACEFY_JS_TRACKER_ASSET_URL', '/vendor/tracefy-sdk/tracefy-js-tracker.js'),
        ],
    ],

    'trace' => [
        'max_frames' => (int) env('TRACEFY_TRACE_MAX_FRAMES', 120),
        'max_arg_depth' => (int) env('TRACEFY_TRACE_MAX_ARG_DEPTH', 2),
        'max_string_length' => (int) env('TRACEFY_TRACE_MAX_STRING_LENGTH', 2000),
        'max_message_length' => (int) env('TRACEFY_TRACE_MAX_MESSAGE_LENGTH', 250),
        'fallback_max_frames' => (int) env('TRACEFY_TRACE_FALLBACK_MAX_FRAMES', 30),
        'minimal_fallback_max_frames' => (int) env('TRACEFY_TRACE_MINIMAL_FALLBACK_MAX_FRAMES', 10),
    ],

    'send' => [
        'exceptions' => env('TRACEFY_SEND_EXCEPTIONS', true),
        'queue' => env('TRACEFY_SEND_QUEUE_EVENTS', false),
    ],
];
