<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tracefy SDK
    |--------------------------------------------------------------------------
    |
    | Compatibility config file for projects that publish tracefy-sdk.php.
    | New integrations should prefer config/tracefy-client.php.
    |
    */

    'enabled' => env('TRACEFY_ENABLED', true),

    'token' => env('TRACEFY_TOKEN', env('TRACEFY_ENVIRONMENT_TOKEN')),

    'environment_tokens' => [
        'production' => env('TRACEFY_PRODUCTION_TOKEN'),
        'homologation' => env('TRACEFY_HOMOLOGATION_TOKEN', env('TRACEFY_STAGING_TOKEN')),
        'staging' => env('TRACEFY_STAGING_TOKEN'),
        'testing' => env('TRACEFY_TESTING_TOKEN'),
    ],

    'base_url' => env('TRACEFY_ENDPOINT', 'https://tracefy.webtechnology.com.br'),
    'endpoint' => env('TRACEFY_ENDPOINT', 'https://tracefy.webtechnology.com.br'),

    'ingest_endpoint' => env('TRACEFY_INGEST_ENDPOINT'),

    'heartbeat_endpoint' => env('TRACEFY_HEARTBEAT_ENDPOINT'),

    'test_endpoint' => env('TRACEFY_TEST_ENDPOINT'),

    'telemetry_route' => '/api/telemetry/v3',

    'telemetry_endpoints' => [
        'heartbeat' => 'heartbeat',
        'test' => 'test',
    ],

    'environment' => env('TRACEFY_ENVIRONMENT', env('APP_ENV', 'production')),

    'release' => env('TRACEFY_RELEASE', '1.0.0'),

    'sdk' => [
        'version' => env('TRACEFY_SDK_VERSION', 'tracefy-sdk'),
    ],

    'metadata' => [
        'deploy_id' => env('TRACEFY_DEPLOY'),
        'server_name' => env('TRACEFY_SERVER_NAME', gethostname()),
    ],

    'http' => [
        'timeout_seconds' => (int) env('TRACEFY_HTTP_TIMEOUT', 2),
        'retries' => (int) env('TRACEFY_HTTP_RETRIES', 1),
        'retry_delay_ms' => (int) env('TRACEFY_HTTP_RETRY_DELAY_MS', 200),
    ],

    'v3' => [
        'schema_version' => 'v3',
        'ingest_path' => '/api/telemetry/v3/ingest',
        'heartbeat_path' => '/api/telemetry/v3/heartbeat',
        'test_path' => '/api/telemetry/v3/test',
        'max_event_bytes' => (int) env('TRACEFY_MAX_EVENT_BYTES', 65536),
        'max_events_per_chunk' => (int) env('TRACEFY_MAX_EVENTS_PER_CHUNK', 25),
        'max_chunk_bytes' => (int) env('TRACEFY_MAX_CHUNK_BYTES', 262144),
    ],

    'capture' => [
        'records' => env('TRACEFY_CAPTURE_RECORDS', true),
    ],

    'sampling' => [
        'requests' => (float) env('TRACEFY_SAMPLE_REQUESTS', 1.0),
        'commands' => (float) env('TRACEFY_SAMPLE_COMMANDS', 1.0),
        'exceptions' => (float) env('TRACEFY_SAMPLE_EXCEPTIONS', 1.0),
        'scheduled_tasks' => (float) env('TRACEFY_SAMPLE_SCHEDULED_TASKS', env('TRACEFY_SAMPLE_TASKS', 1.0)),
    ],

    'ignore' => [
        'cache' => env('TRACEFY_IGNORE_CACHE', false),
        'mail' => env('TRACEFY_IGNORE_MAIL', false),
        'notifications' => env('TRACEFY_IGNORE_NOTIFICATIONS', false),
        'queries' => env('TRACEFY_IGNORE_QUERIES', false),
        'outgoing_requests' => env('TRACEFY_IGNORE_OUTGOING_REQUESTS', env('TRACEFY_IGNORE_OUTGOING', false)),
    ],

    'ingest' => [
        'timeout' => (float) env('TRACEFY_INGEST_TIMEOUT', env('TRACEFY_HTTP_TIMEOUT', 2.0)),
        'buffer_size' => (int) env('TRACEFY_INGEST_BUFFER', 500),
    ],

    'privacy' => [
        'capture_source_code' => env('TRACEFY_CAPTURE_SOURCE_CODE', true),
        'capture_payload' => env('TRACEFY_CAPTURE_PAYLOAD', false),
        'redact_fields' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('TRACEFY_REDACT_FIELDS', '_token,password,password_confirmation,token,api_token,api-token,secret'))
        ))),
        'redact_headers' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('TRACEFY_REDACT_HEADERS', 'Authorization,Cookie,Proxy-Authorization,X-XSRF-TOKEN'))
        ))),
    ],

    'sanitize' => [
        'fields' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('TRACEFY_SENSITIVE_FIELDS', '*password*,*token*,*authorization*,*cookie*,*secret*,*credential*'))
        ))),
        'headers' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('TRACEFY_SENSITIVE_HEADERS', 'authorization,cookie,set-cookie,proxy-authorization,x-api-key,x-tracefy-token,x-xsrf-token'))
        ))),
    ],

    'telemetry' => [
        'max_string_length' => (int) env('TRACEFY_TELEMETRY_MAX_STRING_LENGTH', 4000),
        'max_depth' => (int) env('TRACEFY_TELEMETRY_MAX_DEPTH', 10),
        'max_items' => (int) env('TRACEFY_TELEMETRY_MAX_ITEMS', 200),
    ],

    'performance' => [
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

    'options' => [
        'app_name' => env('APP_NAME', 'Tracefy SDK'),
    ],
];
