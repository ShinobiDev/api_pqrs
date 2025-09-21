<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\Stream\StreamTransportFactory;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface as SdkMeterProviderInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\Attributes\ServiceAttributes;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/opentelemetry.php', 'opentelemetry');

        $this->app->singleton(TracerProviderInterface::class, function () {
            return $this->createTracerProvider();
        });

        // Provide MeterProvider (light wrapper; we will not use OTEL metrics exporter here)
        $this->app->singleton(MeterProviderInterface::class, function () {
            // SDK MeterProvider exists but exporters for metrics aren't installed; still return a provider
            if (class_exists(MeterProvider::class)) {
                return MeterProvider::builder()->build();
            }
            // Fallback: return a no-op via Globals
            return Globals::meterProvider();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/opentelemetry.php' => config_path('opentelemetry.php'),
        ], 'config');

        // Skip initialization during console commands to avoid bootstrap issues
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            return;
        }

        // Initialize tracer provider globally if enabled
        if (config('opentelemetry.traces.enabled', true)) {
            // Will be initialized when first needed by middleware
        }
    }

    private function createTracerProvider(): TracerProviderInterface
    {
        $resource = $this->createResource();
        $sampler = $this->createSampler();
        $spanExporter = $this->createSpanExporter();
        $spanProcessor = new BatchSpanProcessor($spanExporter, \OpenTelemetry\SDK\Common\Time\ClockFactory::getDefault());

        return TracerProvider::builder()
            ->addSpanProcessor($spanProcessor)
            ->setResource($resource)
            ->setSampler($sampler)
            ->build();
    }

    private function createResource(): ResourceInfo
    {
        $attributes = [
            ServiceAttributes::SERVICE_NAME => config('opentelemetry.service.name'),
            ServiceAttributes::SERVICE_VERSION => config('opentelemetry.service.version'),
            'service.namespace' => config('opentelemetry.service.namespace'),
            'deployment.environment' => config('opentelemetry.service.environment'),
        ];

        if (gethostname()) {
            $attributes['host.name'] = gethostname();
        }

        if ($containerId = $this->getContainerId()) {
            $attributes['container.id'] = $containerId;
        }

        return ResourceInfo::create(Attributes::create($attributes));
    }

    private function createSampler()
    {
        $type = config('opentelemetry.traces.sampler.type', 'parentbased_traceidratio');
        $ratio = (float) config('opentelemetry.traces.sampler.ratio', 1.0);

        return match ($type) {
            'always_on' => new AlwaysOnSampler(),
            'always_off' => new AlwaysOffSampler(),
            'traceidratio' => new TraceIdRatioBasedSampler($ratio),
            default => new ParentBased(new TraceIdRatioBasedSampler($ratio)),
        };
    }

    private function createSpanExporter()
    {
        $exporter = config('opentelemetry.traces.exporter', 'otlp');
        return match ($exporter) {
            'otlp' => new SpanExporter(
                (new OtlpHttpTransportFactory())->create(
                    config('opentelemetry.traces.endpoint'),
                    'application/x-protobuf',
                    ['timeout' => (int) config('opentelemetry.traces.timeout', 10)]
                )
            ),
            'console' => new SpanExporter((new StreamTransportFactory())->create(STDOUT, 'application/json')),
            default => new SpanExporter(
                (new OtlpHttpTransportFactory())->create(
                    config('opentelemetry.traces.endpoint'),
                    'application/x-protobuf'
                )
            ),
        };
    }

    private function getContainerId(): ?string
    {
        if (is_file('/proc/self/cgroup')) {
            $cgroup = @file_get_contents('/proc/self/cgroup') ?: '';
            if (preg_match('/docker[\/-]([a-f0-9]{64})/i', $cgroup, $m)) {
                return substr($m[1], 0, 12);
            }
        }
        return $_ENV['HOSTNAME'] ?? null;
    }
}
