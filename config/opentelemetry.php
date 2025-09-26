<?php

return [
    // OpenTelemetry service resource attributes
    'service' => [
        'name' => env('OTEL_SERVICE_NAME', 'pqrs-api'),
        'version' => env('OTEL_SERVICE_VERSION', '1.0.0'),
        'environment' => env('APP_ENV', 'production'),
        'namespace' => env('OTEL_SERVICE_NAMESPACE', 'pqrs'),
    ],

    // Traces configuration
    'traces' => [
        'enabled' => env('OTEL_TRACES_ENABLED', true),
        'exporter' => env('OTEL_TRACES_EXPORTER', 'otlp'),
        'endpoint' => env('OTEL_EXPORTER_OTLP_TRACES_ENDPOINT', 'http://jaeger:4318/v1/traces'),
        'headers' => env('OTEL_EXPORTER_OTLP_HEADERS', ''),
        'timeout' => env('OTEL_EXPORTER_OTLP_TIMEOUT', 10),
        'compression' => env('OTEL_EXPORTER_OTLP_COMPRESSION', 'gzip'),

        // Sampling configuration
        'sampler' => [
            'type' => env('OTEL_TRACES_SAMPLER', 'parentbased_traceidratio'),
            'ratio' => (float) env('OTEL_TRACES_SAMPLER_ARG', 1.0),
        ],

        // Span limits
        'span_limits' => [
            'attributes' => (int) env('OTEL_SPAN_ATTRIBUTE_COUNT_LIMIT', 128),
            'events' => (int) env('OTEL_SPAN_EVENT_COUNT_LIMIT', 128),
            'links' => (int) env('OTEL_SPAN_LINK_COUNT_LIMIT', 128),
        ],
    ],

    // Metrics configuration (using Prometheus PHP client)
    'metrics' => [
        'enabled' => env('OTEL_METRICS_ENABLED', true),
        'exporter' => env('OTEL_METRICS_EXPORTER', 'prometheus'),
        'endpoint' => env('OTEL_EXPORTER_PROMETHEUS_ENDPOINT', '/metrics'),
        'interval' => (int) env('OTEL_METRIC_EXPORT_INTERVAL', 60),
        'timeout' => (int) env('OTEL_METRIC_EXPORT_TIMEOUT', 30),
        'prometheus' => [
            'namespace' => env('OTEL_EXPORTER_PROMETHEUS_NAMESPACE', ''),
        ],
    ],

    // Logs (not wired in this phase; kept for future)
    'logs' => [
        'enabled' => env('OTEL_LOGS_ENABLED', true),
        'exporter' => env('OTEL_LOGS_EXPORTER', 'otlp'),
        'endpoint' => env('OTEL_EXPORTER_OTLP_LOGS_ENDPOINT', ''),
    ],

    // Instrumentation toggles
    'instrumentation' => [
        'http' => [
            'enabled' => env('OTEL_INSTRUMENTATION_HTTP_ENABLED', true),
            'capture_headers' => env('OTEL_INSTRUMENTATION_HTTP_CAPTURE_HEADERS', true),
            'request_headers' => explode(',', env('OTEL_INSTRUMENTATION_HTTP_REQUEST_HEADERS', 'content-type,user-agent,authorization')),
            'response_headers' => explode(',', env('OTEL_INSTRUMENTATION_HTTP_RESPONSE_HEADERS', 'content-type,content-length')),
        ],
        'database' => [
            'enabled' => env('OTEL_INSTRUMENTATION_DB_ENABLED', true),
        ],
        'cache' => [
            'enabled' => env('OTEL_INSTRUMENTATION_CACHE_ENABLED', true),
        ],
        'queue' => [
            'enabled' => env('OTEL_INSTRUMENTATION_QUEUE_ENABLED', true),
        ],
    ],
];
