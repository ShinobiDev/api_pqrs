<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/health",
     *     operationId="healthCheck",
     *     tags={"Monitoring"},
     *     summary="Verificar estado de salud del sistema",
     *     description="Endpoint para verificar el estado de salud de la aplicación y sus dependencias",
     *     @OA\Response(
     *         response=200,
     *         description="Sistema saludable",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     example="healthy"
     *                 ),
     *                 @OA\Property(
     *                     property="timestamp",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-09-20T20:30:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="services",
     *                     type="object",
     *                     @OA\Property(
     *                         property="database",
     *                         type="string",
     *                         example="healthy"
     *                     ),
     *                     @OA\Property(
     *                         property="application",
     *                         type="string",
     *                         example="healthy"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="version",
     *                     type="string",
     *                     example="1.0.0"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Sistema no saludable",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     example="unhealthy"
     *                 ),
     *                 @OA\Property(
     *                     property="timestamp",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-09-20T20:30:00Z"
     *                 ),
     *                 @OA\Property(
     *                     property="services",
     *                     type="object",
     *                     @OA\Property(
     *                         property="database",
     *                         type="string",
     *                         example="unhealthy"
     *                     ),
     *                     @OA\Property(
     *                         property="application",
     *                         type="string",
     *                         example="healthy"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="error",
     *                     type="string",
     *                     example="Database connection failed"
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function __invoke(): JsonResponse
    {
        try {
            $services = [
                'database' => 'healthy',
                'application' => 'healthy'
            ];
            
            // Check database connection
            try {
                DB::connection()->getPdo();
                $services['database'] = 'healthy';
            } catch (\Exception $e) {
                $services['database'] = 'unhealthy';
                throw new \Exception('Database connection failed: ' . $e->getMessage());
            }
            
            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'services' => $services,
                'version' => '1.0.0'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'timestamp' => now()->toISOString(),
                'services' => $services ?? ['application' => 'healthy', 'database' => 'unhealthy'],
                'error' => $e->getMessage()
            ], 503);
        }
    }
}