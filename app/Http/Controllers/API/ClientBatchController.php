<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ClientBatchService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientBatchController extends Controller
{
    protected $service;

    public function __construct(ClientBatchService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/api/clients/batch",
     *     summary="Create a client with multiple users in one request",
     *     tags={"Clients"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client"},
     *             @OA\Property(
     *                 property="client",
     *                 type="object",
     *                 description="Client user data",
     *                 required={"name","document_type_id","document","role_id","email","phone","status_id","password","user_type_id"},
     *                 @OA\Property(property="user_type_id", type="integer", example=7),
     *                 @OA\Property(property="name", type="string", example="Cliente Principal"),
     *                 @OA\Property(property="document_type_id", type="integer", example=1),
     *                 @OA\Property(property="document", type="string", example="12345678"),
     *                 @OA\Property(property="role_id", type="integer", example=4),
     *                 @OA\Property(property="email", type="string", example="cliente@example.com"),
     *                 @OA\Property(property="phone", type="string", example="1234567890"),
     *                 @OA\Property(property="status_id", type="integer", example=1),
     *                 @OA\Property(property="password", type="string", example="password123")
     *             ),
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 description="Array of additional users for this client",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"name","document_type_id","document","role_id","email","phone","status_id","password","user_type_id"},
     *                     @OA\Property(property="user_type_id", type="integer", example=11),
     *                     @OA\Property(property="name", type="string", example="Usuario 1"),
     *                     @OA\Property(property="document_type_id", type="integer", example=1),
     *                     @OA\Property(property="document", type="string", example="87654321"),
     *                     @OA\Property(property="role_id", type="integer", example=2),
     *                     @OA\Property(property="email", type="string", example="usuario1@example.com"),
     *                     @OA\Property(property="phone", type="string", example="0987654321"),
     *                     @OA\Property(property="status_id", type="integer", example=1),
     *                     @OA\Property(property="password", type="string", example="password123")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client and users created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cliente y usuarios creados exitosamente"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function createClientWithUsers(Request $request)
    {
        try {
            // Validar la estructura principal
            $validated = $request->validate([
                'client' => 'required|array',
                'users' => 'nullable|array',
                'users.*' => 'array'
            ]);

            // Validar datos del cliente
            $clientRules = [
                'client.user_type_id' => 'required|integer|exists:types,id',
                'client.name' => 'required|string|max:255',
                'client.document_type_id' => 'required|integer|exists:types,id',
                'client.document' => 'required|string|max:50|unique:users,document',
                'client.role_id' => 'required|integer|exists:roles,id',
                'client.email' => 'required|email|max:255|unique:users,email',
                'client.phone' => 'required|string|max:20',
                'client.status_id' => 'required|integer|exists:statuses,id',
                'client.password' => 'required|string|min:8',
            ];

            // Validar datos de usuarios adicionales
            if (isset($validated['users']) && count($validated['users']) > 0) {
                foreach ($validated['users'] as $index => $user) {
                    $clientRules["users.{$index}.user_type_id"] = 'required|integer|exists:types,id';
                    $clientRules["users.{$index}.name"] = 'required|string|max:255';
                    $clientRules["users.{$index}.document_type_id"] = 'required|integer|exists:types,id';
                    $clientRules["users.{$index}.document"] = 'required|string|max:50|unique:users,document';
                    $clientRules["users.{$index}.role_id"] = 'required|integer|exists:roles,id';
                    $clientRules["users.{$index}.email"] = 'required|email|max:255|unique:users,email';
                    $clientRules["users.{$index}.phone"] = 'required|string|max:20';
                    $clientRules["users.{$index}.status_id"] = 'required|integer|exists:statuses,id';
                    $clientRules["users.{$index}.password"] = 'required|string|min:8';
                }
            }

            $validatedData = $request->validate($clientRules);

            // Crear cliente y usuarios
            $result = $this->service->createClientWithUsers($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Cliente y usuarios creados exitosamente',
                'data' => $result
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al crear el cliente y usuarios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}