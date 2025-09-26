<?php

namespace App\Http\Controllers;

use App\Services\UserFormDataService;
use Illuminate\Http\JsonResponse;

class UserFormDataController extends Controller
{
    protected $service;

    public function __construct(UserFormDataService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        try {
            $data = $this->service->getFormData();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos del formulario: ' . $e->getMessage()
            ], 500);
        }
    }
}