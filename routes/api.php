<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\PqrsController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\TypeController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AnswerController;
use App\Http\Controllers\API\StatusController;
use App\Http\Controllers\API\StateController;
use App\Http\Controllers\API\CityController;
use App\Http\Controllers\API\FormDataController;
use App\Http\Controllers\UserFormDataController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\HealthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // STATUS
    Route::get('statuses', [StatusController::class, 'index']);
    Route::post('statuses', [StatusController::class, 'store']);
    Route::put('statuses/{status}', [StatusController::class, 'update']);
    Route::delete('statuses/{status}', [StatusController::class, 'destroy'])->withTrashed();

    Route::post('statuses/{id}/restore', [StatusController::class, 'restore']); // Para restaurar un status (método POST para una acción)
    Route::delete('statuses/{id}/force-delete', [StatusController::class, 'forceDelete']); // Para eliminar permanentemente (DELETE con segmento específico)

    // ROLE
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::put('roles/{role}', [RoleController::class, 'update']);
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->withTrashed();

    // TYPE
    Route::get('types', [TypeController::class, 'index']);
    Route::get('types/documents', [TypeController::class, 'getDocumentTypes']);
    Route::post('types', [TypeController::class, 'store']);
    Route::put('types/{type}', [TypeController::class, 'update']);
    Route::delete('types/{type}', [TypeController::class, 'destroy'])->withTrashed();

    // Form Data Routes
    Route::get('/users/form-data/client', [FormDataController::class, 'getClientFormData']);
    Route::get('/users/form-data/users', [UserFormDataController::class, 'index']);

    // USER
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/clients', [UserController::class, 'getClientUsers']);
    Route::get('/users/clients/active', [UserController::class, 'getActiveClientUsers']);
    Route::get('/users/clients/inactive', [UserController::class, 'getInactiveClientUsers']);
    Route::get('/users/client/{clientId}', [UserController::class, 'getUsersByClient']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->withTrashed();

    // PQRS
    Route::get('/pqrs', [PqrsController::class, 'index']);
    Route::post('/pqrs', [PqrsController::class, 'store']);
    Route::put('/pqrs/{pqrs}', [PqrsController::class, 'update']);
    Route::delete('/pqrs/{pqrs}', [PqrsController::class, 'destroy'])->withTrashed();

    // ANSWER
    Route::get('/answers', [AnswerController::class, 'index']);
    Route::post('/answers', [AnswerController::class, 'store']);
    Route::put('/answers/{answer}', [AnswerController::class, 'update']);
    Route::delete('/answers/{answer}', [AnswerController::class, 'destroy'])->withTrashed();
});

//STATE
Route::get('/states', [StateController::class, 'index']);
Route::post('/states', [StateController::class, 'store']);
Route::put('/states/{state}', [StateController::class, 'update']);
Route::delete('/states/{state}', [StateController::class, 'destroy']);

//CITY
Route::get('/cities', [CityController::class, 'index']);
Route::post('/cities', [CityController::class, 'store']);
Route::put('/cities/{city}', [CityController::class, 'update']);
Route::delete('/cities/{city}', [CityController::class, 'destroy']);

// Health check endpoint for Docker
Route::get('/health', function () {
    $db = 'error';
    $redis = 'error';

    try {
        DB::select('SELECT 1');
        $db = 'ok';
    } catch (\Throwable $e) {
        $db = 'error';
    }

    try {
        \Illuminate\Support\Facades\Redis::ping();
        $redis = 'ok';
    } catch (\Throwable $e) {
        $redis = 'unavailable';
    }

    $overall = ($db === 'ok' || $db === 'error') && ($redis === 'ok' || $redis === 'error' || $redis === 'unavailable') ? 'healthy' : 'degraded';

    return response()->json([
        'status' => $overall,
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0'),
        'environment' => app()->environment(),
        'services' => [
            'database' => $db,
            'redis' => $redis,
        ],
    ]);
});

// Metrics endpoint (no auth)
Route::get('/metrics', MetricsController::class);

// Health check endpoint (no auth)
Route::get('/health', HealthController::class);

/**
 * @OA\Get(
 *     path="/api/test/middleware",
 *     operationId="testMiddleware",
 *     tags={"Monitoring"},
 *     summary="Probar comportamiento del middleware",
 *     description="Endpoint para probar y simular el comportamiento del middleware de métricas",
 *     @OA\Response(
 *         response=200,
 *         description="Test completado exitosamente",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(
 *                     property="message",
 *                     type="string",
 *                     example="Middleware test completed"
 *                 )
 *             )
 *         )
 *     )
 * )
 */
// Test endpoint to simulate middleware behavior
Route::get('/test/middleware', function () {
    $registry = App\Services\PrometheusRegistryService::getRegistry();

    // Simulate exact middleware pattern
    $labels = ['GET', '200', 'test'];
    $counter = $registry->getOrRegisterCounter('pqrs', 'http_requests_total', 'Total HTTP requests', ['method', 'status_code', 'route']);
    $counter->inc($labels);

    return response()->json(['message' => 'Middleware test completed']);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
