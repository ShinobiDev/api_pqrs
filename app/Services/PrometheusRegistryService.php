<?php

namespace App\Services;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;
use Prometheus\Storage\InMemory;
use Illuminate\Support\Facades\Redis as LaravelRedis;

class PrometheusRegistryService
{
    private static $registry = null;
    private static $metrics = [];

    public static function getRegistry(): CollectorRegistry
    {
        if (self::$registry === null) {
            // For now, use InMemory but implement a Redis-backed persistence layer
            $storage = new InMemory();
            self::$registry = new CollectorRegistry($storage);
            // Try to load persisted metrics from Redis only when explicitly enabled
            try {
                $useRedis = env('PROMETHEUS_USE_REDIS', false);
            } catch (\Throwable $e) {
                $useRedis = false;
            }

            if ($useRedis && self::redisAvailable()) {
                self::loadPersistedMetrics();
            } else if ($useRedis) {
                error_log("PrometheusRegistry: PROMETHEUS_USE_REDIS=true but Redis not available, skipping loadPersistedMetrics");
            }
            
            // Initialize basic metrics
            self::initializeMetrics();
        }
        
        // Update dynamic system metrics each time the registry is accessed
        self::updateSystemMetrics();
        
        // Persist metrics to Redis only when explicitly enabled
        try {
            $useRedis = env('PROMETHEUS_USE_REDIS', false);
        } catch (\Throwable $e) {
            $useRedis = false;
        }

        if ($useRedis && self::redisAvailable()) {
            self::persistMetricsToRedis();
        } else if ($useRedis) {
            error_log("PrometheusRegistry: PROMETHEUS_USE_REDIS=true but Redis not available, skipping persistMetricsToRedis");
        }
        
        return self::$registry;
    }

    private static function loadPersistedMetrics(): void
    {
        try {
            $redisConnection = LaravelRedis::connection('metrics');
            
            // Load HTTP request counters (without pqrs prefix)
            $httpRequests = $redisConnection->hgetall('prometheus:http_requests_total');
            if (!empty($httpRequests)) {
                $registry = self::$registry;
                $counter = $registry->getOrRegisterCounter('', 'http_requests_total', 'Total HTTP requests', ['method', 'status_code', 'route']);
                
                foreach ($httpRequests as $key => $value) {
                    $labels = json_decode($key, true);
                    if ($labels && is_array($labels) && $value > 0) {
                        for ($i = 0; $i < $value; $i++) {
                            $counter->inc($labels);
                        }
                    }
                }
            }
            
            // Load middleware executions (without pqrs prefix)
            $middlewareCount = $redisConnection->get('prometheus:middleware_executions') ?: 0;
            if ($middlewareCount > 0) {
                $registry = self::$registry;
                $counter = $registry->getOrRegisterCounter('', 'middleware_executions', 'Number of middleware executions', []);
                for ($i = 0; $i < $middlewareCount; $i++) {
                    $counter->inc();
                }
            }
            
        } catch (\Throwable $e) {
            error_log("PrometheusRegistry: Error loading persisted metrics: " . $e->getMessage());
        }
    }

    private static function persistMetricsToRedis(): void
    {
        try {
            $redisConnection = LaravelRedis::connection('metrics');
            
            // This is a simplified persistence - in a real scenario you'd want more sophisticated tracking
            $redisConnection->incr('prometheus:metrics_access_count');
            
        } catch (\Throwable $e) {
            error_log("PrometheusRegistry: Error persisting metrics: " . $e->getMessage());
        }
    }

    /**
     * Check if the configured Redis connection for metrics is reachable.
     */
    private static function redisAvailable(): bool
    {
        try {
            // If Laravel Redis facade isn't available, consider Redis unavailable
            if (!class_exists('\Illuminate\Support\Facades\Redis')) {
                return false;
            }

            $connection = LaravelRedis::connection('metrics');

            // Try a ping; some drivers support ping(), others may throw — catch all
            try {
                $result = $connection->ping();
                // ping may return +PONG, PONG or true depending on client
                return $result === true || stripos((string)$result, 'pong') !== false;
            } catch (\Throwable $e) {
                // Some drivers may not have ping; try a harmless get of a non-existent key
                try {
                    $connection->get('__prometheus_ping_probe__');
                    return true;
                } catch (\Throwable $e) {
                    return false;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function initializeMetrics(): void
    {
        $registry = self::$registry;
        
        // Initialize all metrics that will be used
        // HTTP metrics (without pqrs prefix)
        $registry->getOrRegisterCounter('', 'http_requests_total', 'Total HTTP requests', ['method', 'status_code', 'route']);
        $registry->getOrRegisterHistogram('', 'http_request_duration_seconds', 'HTTP request duration in seconds', ['method', 'route']);
        $registry->getOrRegisterCounter('', 'http_responses_by_status', 'HTTP responses by status code', ['status_code', 'route']);
        $registry->getOrRegisterHistogram('', 'http_response_size_bytes', 'HTTP response size in bytes', ['method', 'route']);
        $registry->getOrRegisterHistogram('', 'http_request_size_bytes', 'HTTP request size in bytes', ['method', 'route']);
        $registry->getOrRegisterCounter('', 'http_errors_total', 'Total HTTP errors', ['status_code', 'route']);
        $registry->getOrRegisterCounter('', 'middleware_executions', 'Number of middleware executions', []);
        $registry->getOrRegisterGauge('', 'http_requests_concurrent', 'Number of concurrent HTTP requests', []);
        
        // Application metrics (app_info is system information, not business specific)
        $appInfoGauge = $registry->getOrRegisterGauge('', 'app_info', 'Application information', ['version', 'environment']);
        
        // Safe config access
        $version = '1.0';
        $environment = 'unknown';
        try {
            if (function_exists('config')) {
                $version = config('app.version', '1.0');
                $environment = config('app.env', 'unknown');
            }
        } catch (\Throwable $e) {
            // Use defaults if config is not available
        }
        
        $appInfoGauge->set(1, [$version, $environment]);
        
        // System metrics (without pqrs prefix)
        $registry->getOrRegisterGauge('', 'php_memory_usage_bytes', 'PHP memory usage in bytes', []);
        $registry->getOrRegisterGauge('', 'php_memory_peak_bytes', 'PHP peak memory usage in bytes', []);
        $registry->getOrRegisterGauge('', 'php_memory_limit_bytes', 'PHP memory limit in bytes', []);
        $registry->getOrRegisterGauge('', 'process_uptime_seconds', 'Process uptime in seconds', []);
        $registry->getOrRegisterGauge('', 'process_cpu_usage_percent', 'Process CPU usage percentage', []);
        $registry->getOrRegisterGauge('', 'process_open_files', 'Number of open file descriptors', []);
        $registry->getOrRegisterGauge('', 'system_load_average', 'System load average', ['period']);
        $registry->getOrRegisterGauge('', 'php_opcache_hit_rate', 'PHP OPcache hit rate percentage', []);
        $registry->getOrRegisterGauge('', 'database_connections_active', 'Number of active database connections', []);
    }

    private static function updateSystemMetrics(): void
    {
        if (self::$registry === null) {
            return;
        }

        try {
            $registry = self::$registry;
            
            // Memory metrics (without pqrs prefix)
            $memoryUsageGauge = $registry->getOrRegisterGauge('', 'php_memory_usage_bytes', 'PHP memory usage in bytes', []);
            $memoryPeakGauge = $registry->getOrRegisterGauge('', 'php_memory_peak_bytes', 'PHP peak memory usage in bytes', []);
            $memoryLimitGauge = $registry->getOrRegisterGauge('', 'php_memory_limit_bytes', 'PHP memory limit in bytes', []);
            
            $memoryUsageGauge->set(memory_get_usage(true));
            $memoryPeakGauge->set(memory_get_peak_usage(true));
            
            // Parse memory limit
            $memoryLimit = ini_get('memory_limit');
            $memoryLimitBytes = self::parseMemorySize($memoryLimit);
            $memoryLimitGauge->set($memoryLimitBytes);
            
            // Process uptime (approximate based on start time)
            $uptimeGauge = $registry->getOrRegisterGauge('', 'process_uptime_seconds', 'Process uptime in seconds', []);
            $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
            $uptimeGauge->set(microtime(true) - $startTime);
            
            // CPU usage (basic estimation)
            self::updateCpuMetrics($registry);
            
            // System load average (Unix/Linux only)
            self::updateLoadAverageMetrics($registry);
            
            // OPcache metrics
            self::updateOpcacheMetrics($registry);
            
            // Database connection metrics
            self::updateDatabaseMetrics($registry);
            
        } catch (\Throwable $e) {
            error_log("Error updating system metrics: " . $e->getMessage());
        }
    }

    private static function parseMemorySize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;
        
        switch ($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }
        
        return $size;
    }

    private static function updateCpuMetrics($registry): void
    {
        try {
            // Basic CPU usage estimation using getrusage (Unix/Linux)
            if (function_exists('getrusage')) {
                $usage = getrusage();
                $cpuGauge = $registry->getOrRegisterGauge('', 'process_cpu_usage_percent', 'Process CPU usage percentage', []);
                
                // Calculate CPU usage percentage (simplified)
                $userTime = $usage['ru_utime.tv_sec'] + $usage['ru_utime.tv_usec'] / 1000000;
                $systemTime = $usage['ru_stime.tv_sec'] + $usage['ru_stime.tv_usec'] / 1000000;
                $totalTime = $userTime + $systemTime;
                
                // Very basic estimation - in a real scenario you'd need to track over time
                $cpuPercent = min(100, $totalTime * 10); // Simplified calculation
                $cpuGauge->set($cpuPercent);
            }
        } catch (\Throwable $e) {
            // Ignore CPU metrics errors
        }
    }

    private static function updateLoadAverageMetrics($registry): void
    {
        try {
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $loadGauge = $registry->getOrRegisterGauge('', 'system_load_average', 'System load average', ['period']);
                
                if ($load !== false) {
                    $loadGauge->set($load[0], ['1min']);
                    $loadGauge->set($load[1], ['5min']);
                    $loadGauge->set($load[2], ['15min']);
                }
            }
        } catch (\Throwable $e) {
            // Ignore load average errors
        }
    }

    private static function updateOpcacheMetrics($registry): void
    {
        try {
            if (function_exists('opcache_get_status')) {
                $status = opcache_get_status(false);
                if ($status !== false && isset($status['opcache_statistics'])) {
                    $hitRateGauge = $registry->getOrRegisterGauge('', 'php_opcache_hit_rate', 'PHP OPcache hit rate percentage', []);
                    
                    $stats = $status['opcache_statistics'];
                    $hits = $stats['hits'] ?? 0;
                    $misses = $stats['misses'] ?? 0;
                    $total = $hits + $misses;
                    
                    $hitRate = $total > 0 ? ($hits / $total) * 100 : 0;
                    $hitRateGauge->set($hitRate);
                }
            }
        } catch (\Throwable $e) {
            // Ignore OPcache errors
        }
    }

    private static function updateDatabaseMetrics($registry): void
    {
        try {
            // Get database connection info
            $connectionsGauge = $registry->getOrRegisterGauge('', 'database_connections_active', 'Number of active database connections', []);
            
            // For Laravel, we can check the connection status
            if (class_exists('\Illuminate\Support\Facades\DB')) {
                try {
                    \Illuminate\Support\Facades\DB::connection()->getPdo();
                    $connectionsGauge->set(1); // At least one active connection
                } catch (\Throwable $e) {
                    $connectionsGauge->set(0); // No active connections
                }
            }
        } catch (\Throwable $e) {
            // Ignore database metrics errors
        }
    }

    public static function resetRegistry(): void
    {
        self::$registry = null;
        self::$metrics = [];
    }
}