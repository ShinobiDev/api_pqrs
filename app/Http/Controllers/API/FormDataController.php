<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\FormDataService;
use Illuminate\Http\Request;

class FormDataController extends Controller
{
    protected $service;

    public function __construct(FormDataService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/users/form-data/client",
     *     summary="Get form data for client creation",
     *     tags={"Form Data"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Form data retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Cédula de Ciudadanía")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function getClientFormData()
    {
        try {
            $data = $this->service->getClientFormData();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los datos del formulario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}