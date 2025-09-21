<?php

namespace App\Services;

use App\Services\PrometheusRegistryService;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use Prometheus\Storage\InMemory;

class TelemetryService
{
    private $tracer;
    private $registry;
    private string $ns;

    public function __construct(TracerProviderInterface $tracerProvider)
    {
        $this->tracer = $tracerProvider->getTracer('pqrs-api-business', '1.0.0');
        $this->ns = config('opentelemetry.metrics.prometheus.namespace', 'pqrs');

        $this->registry = PrometheusRegistryService::getRegistry();

        $this->initMetrics();
    }

    private function initMetrics(): void
    {
        $ns = $this->ns;
        // PQRS
        $this->registerCounter('pqrs_created_total', 'Total number of PQRS created', ['type', 'priority', 'city']);
        $this->registerCounter('pqrs_resolved_total', 'Total number of PQRS resolved', ['type', 'resolved_by', 'resolution']);
        $this->registerHistogram('pqrs_resolution_time_seconds', 'Time taken to resolve PQRS in seconds', ['type', 'resolved_by', 'resolution']);
        $this->registerGauge('pqrs_active_total', 'Number of active PQRS', []);

        // Users
        $this->registerCounter('user_logins_total', 'Total number of user logins', ['role', 'method', 'successful']);
        $this->registerCounter('user_registrations_total', 'Total number of user registrations', ['role', 'method']);
        $this->registerGauge('users_active_total', 'Number of active users', []);

        // System
        $this->registerCounter('database_queries_total', 'Total number of database queries', ['operation', 'table']);
        $this->registerHistogram('database_query_duration_seconds', 'Duration of database queries in seconds', ['operation', 'table']);
        $this->registerCounter('cache_hits_total', 'Total number of cache hits', ['store', 'key_prefix']);
        $this->registerCounter('cache_misses_total', 'Total number of cache misses', ['store', 'key_prefix']);
    }

    // PQRS
    public function recordPqrsCreated(string $type, string $priority = 'normal', ?string $city = null): void
    {
        $labels = [$type, $priority, $city ?? 'unknown'];
        $this->registry->getCounter($this->ns, 'pqrs_created_total')->inc($labels);

        $span = $this->tracer->spanBuilder('pqrs.created')->setSpanKind(SpanKind::KIND_INTERNAL)->startSpan();
        $span->setAttributes(['type' => $type, 'priority' => $priority, 'city' => $city ?? 'unknown']);
        $span->end();
    }

    public function recordPqrsResolved(string $type, float $resolutionTimeHours, string $resolvedBy, string $resolution = 'satisfactory'): void
    {
        $labels = [$type, $resolvedBy, $resolution];
        $this->registry->getCounter($this->ns, 'pqrs_resolved_total')->inc($labels);
        $this->registry->getHistogram($this->ns, 'pqrs_resolution_time_seconds')->observe($resolutionTimeHours * 3600, $labels);

        $span = $this->tracer->spanBuilder('pqrs.resolved')->setSpanKind(SpanKind::KIND_INTERNAL)->startSpan();
        $span->setAttributes(['type' => $type, 'resolved_by' => $resolvedBy, 'resolution' => $resolution, 'resolution_time_hours' => $resolutionTimeHours]);
        $span->end();
    }

    public function updateActivePqrs(int $count): void
    {
        // For gauges, set current value by tracking delta using inc/dec is complex with in-memory storage; set via labels workaround
        // Here we simply increment/decrement to reach new value relative to zero by clearing is not available. We'll store last in static
        static $last = 0;
        $delta = $count - $last;
        if ($delta !== 0) {
            if ($delta > 0) {
                $this->registry->getGauge($this->ns, 'pqrs_active_total')->inc([], $delta);
            } else {
                $this->registry->getGauge($this->ns, 'pqrs_active_total')->dec([], -$delta);
            }
            $last = $count;
        }
    }

    // Users
    public function recordUserLogin(string $role, string $method = 'credentials', bool $successful = true): void
    {
        $this->registry->getCounter($this->ns, 'user_logins_total')->inc([$role, $method, $successful ? 'true' : 'false']);
        $span = $this->tracer->spanBuilder('user.login')->setSpanKind(SpanKind::KIND_INTERNAL)->startSpan();
        $span->setAttributes(['role' => $role, 'method' => $method, 'successful' => $successful]);
        $span->end();
    }

    public function recordUserRegistration(string $role, string $method = 'web'): void
    {
        $this->registry->getCounter($this->ns, 'user_registrations_total')->inc([$role, $method]);
        $span = $this->tracer->spanBuilder('user.registration')->setSpanKind(SpanKind::KIND_INTERNAL)->startSpan();
        $span->setAttributes(['role' => $role, 'method' => $method]);
        $span->end();
    }

    // System
    public function recordDatabaseQuery(string $operation, float $durationSeconds, string $table = ''): void
    {
        $this->registry->getCounter($this->ns, 'database_queries_total')->inc([$operation, $table]);
        $this->registry->getHistogram($this->ns, 'database_query_duration_seconds')->observe($durationSeconds, [$operation, $table]);
    }

    public function recordCacheHit(string $key, string $store = 'default'): void
    {
        $this->registry->getCounter($this->ns, 'cache_hits_total')->inc([$store, $this->keyPrefix($key)]);
    }

    public function recordCacheMiss(string $key, string $store = 'default'): void
    {
        $this->registry->getCounter($this->ns, 'cache_misses_total')->inc([$store, $this->keyPrefix($key)]);
    }

    public function createBusinessSpan(string $operationName, array $attributes = [])
    {
        $span = $this->tracer->spanBuilder($operationName)->setSpanKind(SpanKind::KIND_INTERNAL)->startSpan();
        if ($attributes) {
            $span->setAttributes($attributes);
        }
        return $span;
    }

    private function keyPrefix(string $key): string
    {
        $parts = explode(':', $key);
        return $parts[0] ?? 'unknown';
    }

    private function registerCounter(string $name, string $help, array $labels)
    {
        try { $this->registry->getCounter($this->ns, $name); } catch (\Throwable) { $this->registry->registerCounter($this->ns, $name, $help, $labels); }
    }
    private function registerHistogram(string $name, string $help, array $labels)
    {
        try { $this->registry->getHistogram($this->ns, $name); } catch (\Throwable) { $this->registry->registerHistogram($this->ns, $name, $help, $labels); }
    }
    private function registerGauge(string $name, string $help, array $labels)
    {
        try { $this->registry->getGauge($this->ns, $name); } catch (\Throwable) { $this->registry->registerGauge($this->ns, $name, $help, $labels); }
    }
}
