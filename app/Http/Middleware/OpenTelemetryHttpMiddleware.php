<?php

namespace App\Http\Middleware;

use App\Services\PrometheusRegistryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OpenTelemetryHttpMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $start = microtime(true);
            
            /** @var SymfonyResponse $response */
            $response = $next($request);
            
            // Record metrics after response is generated
            $this->recordMetrics($request, $response, microtime(true) - $start);
            
            return $response;
        } catch (\Throwable $e) {
            // Log error but don't break the app
            error_log("OpenTelemetry middleware error: " . $e->getMessage());
            
            // Continue with the request even if metrics fail
            return $next($request);
        }
    }

    private function recordMetrics(Request $request, SymfonyResponse $response, float $duration): void
    {
        try {
            // Skip metrics endpoint to avoid recursion
            if ($request->path() === 'api/metrics') {
                return;
            }
            
            $registry = PrometheusRegistryService::getRegistry();
            
            // Get request information
            $method = $request->method();
            $statusCode = (string) $response->getStatusCode();
            $route = $this->cleanRouteName($request->path());
            $contentLength = $response->headers->get('Content-Length', strlen($response->getContent()));
            
            // Record metrics in Prometheus registry (without pqrs prefix for HTTP metrics)
            $counter = $registry->getOrRegisterCounter('', 'http_requests_total', 'Total HTTP requests', ['method', 'status_code', 'route']);
            $counter->inc([$method, $statusCode, $route]);
            
            $durationHistogram = $registry->getOrRegisterHistogram('', 'http_request_duration_seconds', 'HTTP request duration in seconds', ['method', 'route']);
            $durationHistogram->observe($duration, [$method, $route]);
            
            $sizeHistogram = $registry->getOrRegisterHistogram('', 'http_response_size_bytes', 'HTTP response size in bytes', ['method', 'route']);
            $sizeHistogram->observe((float) $contentLength, [$method, $route]);
            
            $statusCounter = $registry->getOrRegisterCounter('', 'http_responses_by_status', 'HTTP responses by status code', ['status_code', 'route']);
            $statusCounter->inc([$statusCode, $route]);
            
            $debugCounter = $registry->getOrRegisterCounter('', 'middleware_executions', 'Number of middleware executions', []);
            $debugCounter->inc();
            
            if ($response->getStatusCode() >= 400) {
                $errorCounter = $registry->getOrRegisterCounter('', 'http_errors_total', 'Total HTTP errors', ['status_code', 'route']);
                $errorCounter->inc([$statusCode, $route]);
            }
            
            // Also persist directly to Redis for cross-request persistence
            $this->persistToRedis($method, $statusCode, $route, $duration, $contentLength);
            
        } catch (\Throwable $e) {
            error_log("OpenTelemetry metrics error: " . $e->getMessage());
        }
    }

    private function persistToRedis(string $method, string $statusCode, string $route, float $duration, int $contentLength): void
    {
        try {
            $redisConnection = \Illuminate\Support\Facades\Redis::connection('metrics');
            
            // Persist HTTP request count
            $labelKey = json_encode([$method, $statusCode, $route]);
            $redisConnection->hincrby('prometheus:http_requests_total', $labelKey, 1);
            
            // Persist middleware execution count
            $redisConnection->incr('prometheus:middleware_executions');
            
            // Persist request duration (simplified - store last N values)
            $durationKey = "prometheus:durations:{$method}:{$route}";
            $redisConnection->lpush($durationKey, $duration);
            $redisConnection->ltrim($durationKey, 0, 99); // Keep last 100 durations
            
            // Persist response size (simplified)
            $sizeKey = "prometheus:sizes:{$method}:{$route}";
            $redisConnection->lpush($sizeKey, $contentLength);
            $redisConnection->ltrim($sizeKey, 0, 99); // Keep last 100 sizes
            
            // Set expiration for cleanup (1 hour)
            $redisConnection->expire($durationKey, 3600);
            $redisConnection->expire($sizeKey, 3600);
            
        } catch (\Throwable $e) {
            error_log("Error persisting metrics to Redis: " . $e->getMessage());
        }
    }
    
    private function cleanRouteName(string $path): string
    {
        // Remove leading slash and replace remaining slashes with underscores
        $cleaned = ltrim($path, '/');
        $cleaned = str_replace('/', '_', $cleaned);
        
        // Handle empty path (root)
        if (empty($cleaned)) {
            return 'root';
        }
        
        return $cleaned;
    }
}
