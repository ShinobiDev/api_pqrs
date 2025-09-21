<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="PQRS API Documentation",
 *     version="1.0.0",
 *     description="API para el sistema de gestión de PQRS (Peticiones, Quejas, Reclamos y Sugerencias) con monitoreo y métricas integradas",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Enter token in format: Bearer <token>"
 * )
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints de autenticación y autorización"
 * )
 * @OA\Tag(
 *     name="PQRS",
 *     description="Gestión de Peticiones, Quejas, Reclamos y Sugerencias"
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="Gestión de usuarios del sistema"
 * )
 * @OA\Tag(
 *     name="Roles",
 *     description="Gestión de roles y permisos"
 * )
 * @OA\Tag(
 *     name="Configuration",
 *     description="Configuración de tipos, estados, ciudades y respuestas"
 * )
 * @OA\Tag(
 *     name="Monitoring",
 *     description="Endpoints para monitoreo, métricas y salud del sistema"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
