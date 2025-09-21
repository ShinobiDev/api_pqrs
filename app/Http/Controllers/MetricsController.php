<?php

namespace App\Http\Controllers;

use App\Services\PrometheusRegistryService;
use Illuminate\Http\Response;
use Prometheus\RenderTextFormat;

/**
 * @OA\Tag(
 *     name="Monitoring",
 *     description="Endpoints para monitoreo y métricas del sistema"
 * )
 */
class MetricsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/metrics",
     *     operationId="getMetrics",
     *     tags={"Monitoring"},
     *     summary="Obtener métricas de Prometheus",
     *     description="Endpoint para obtener métricas del sistema en formato Prometheus",
     *     @OA\Response(
     *         response=200,
     *         description="Métricas generadas exitosamente",
     *         @OA\MediaType(
     *             mediaType="text/plain",
     *             @OA\Schema(
     *                 type="string",
     *                 example="# HELP http_requests_total Total HTTP requests\n# TYPE http_requests_total counter\nhttp_requests_total{method='GET',status_code='200',route='metrics'} 1\n# HELP pqrs_total_pqrs_count Total number of PQRS records\n# TYPE pqrs_total_pqrs_count gauge\npqrs_total_pqrs_count 0"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\MediaType(
     *             mediaType="text/plain",
     *             @OA\Schema(
     *                 type="string",
     *                 example="Error generating metrics: Connection failed"
     *             )
     *         )
     *     )
     * )
     */
    public function __invoke(): Response
    {
        try {
            $registry = PrometheusRegistryService::getRegistry();
            
            // Increment metrics endpoint calls counter (without pqrs prefix as it's a system metric)
            $testCounter = $registry->getOrRegisterCounter('', 'metrics_endpoint_calls', 'Number of times metrics endpoint was called', []);
            $testCounter->inc();
            
            // Force initialization of HTTP metrics if they don't exist
            $this->ensureHttpMetricsExist($registry);
            
            // Add business metrics for PQRS domain
            $this->addBusinessMetrics($registry);
            
            $renderer = new RenderTextFormat();
            $metrics = $renderer->render($registry->getMetricFamilySamples());

            return response($metrics, 200)
                ->header('Content-Type', RenderTextFormat::MIME_TYPE);
        } catch (\Exception $e) {
            return response('Error generating metrics: ' . $e->getMessage(), 500);
        }
    }
    
    private function ensureHttpMetricsExist($registry): void
    {
        try {
            // Ensure HTTP metrics are registered and have some sample data if empty (without pqrs prefix)
            $httpRequestsCounter = $registry->getOrRegisterCounter('', 'http_requests_total', 'Total HTTP requests', ['method', 'status_code', 'route']);
            $middlewareCounter = $registry->getOrRegisterCounter('', 'middleware_executions', 'Number of middleware executions', []);
            
            // Add a sample metric to show the structure exists (without pqrs prefix as it's a HTTP/system metric)
            $metricsCounter = $registry->getOrRegisterCounter('', 'http_requests_to_metrics', 'Requests to metrics endpoint', []);
            $metricsCounter->inc();
            
        } catch (\Throwable $e) {
            error_log("Error ensuring HTTP metrics exist: " . $e->getMessage());
        }
    }
    
    private function addBusinessMetrics($registry): void
    {
        try {
            // Application system metrics (without pqrs prefix as they are general system metrics)
            $registry->getOrRegisterGauge('', 'app_version', 'Application version info', ['version'])->set(1, ['1.0.0']);
            $registry->getOrRegisterGauge('', 'app_uptime_seconds', 'Application uptime in seconds', [])->set(time() - 1725000000); // Approximate uptime
            $registry->getOrRegisterGauge('', 'database_connected', 'Database connection status', [])->set(1);
            
            // Business metrics specific to PQRS domain (keep pqrs prefix)
            $totalPqrs = \App\Models\Pqrs::count();
            $registry->getOrRegisterGauge('pqrs', 'total_pqrs_count', 'Total number of PQRS records', [])->set($totalPqrs);
            
            $totalUsers = \App\Models\User::count();
            $registry->getOrRegisterGauge('pqrs', 'total_users_count', 'Total number of users', [])->set($totalUsers);
            
        } catch (\Exception $e) {
            error_log("Error adding business metrics: " . $e->getMessage());
            // Set error metrics
            $registry->getOrRegisterGauge('', 'database_connected', 'Database connection status', [])->set(0);
            $registry->getOrRegisterGauge('', 'metrics_errors_total', 'Total metrics collection errors', [])->set(1);
        }
    }
}
