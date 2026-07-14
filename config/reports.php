<?php

return [
    'exports' => [
        'queue_connection' => env('REPORT_EXPORT_QUEUE_CONNECTION'),
        'queue' => env('REPORT_EXPORT_QUEUE', 'default'),
        'stale_processing_minutes' => (int) env('REPORT_EXPORT_STALE_PROCESSING_MINUTES', 20),
        'template_versions' => [
            'registration_fichas' => '2026-06-23-html-export-v1',
            'tribe_quadrant' => '2026-06-23-html-export-v1',
            'sensitive_health' => '2026-06-23-html-export-v1',
            'mission_contacts' => '2026-06-23-html-export-v1',
            'registration_payments' => '2026-07-13-html-export-v1',
        ],
    ],
];
