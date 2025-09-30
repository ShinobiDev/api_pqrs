<?php

namespace App\Services;

use App\Models\User;
use App\DTOs\Users\UserDTO;
use App\DTOs\Users\EditUserDTO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UserService
{
    public function index()
    {
        return User::with(['type', 'client', 'documentType', 'role', 'status'])->get();
    }

    public function getClientUsers()
    {
        return User::with(['type', 'client', 'documentType', 'role', 'status'])
            ->where('role_id', function ($query) {
                $query->select('id')
                    ->from('roles')
                    ->where('name', 'like', '%client%')
                    ->orWhere('name', 'like', '%Client%')
                    ->first();
            })
            ->get();
    }

    public function show(User $user)
    {
        return User::with(['type', 'client', 'documentType', 'role', 'status'])
            ->findOrFail($user->id);
    }

    public function store(UserDTO $dto): User
    {
        try {
            $hashedPassword = Hash::make($dto->password);

            $user = User::create([
                'user_type_id' => $dto->user_type_id,
                'name' => $dto->name,
                'document_type_id' => $dto->document_type_id,
                'document' => $dto->document,
                'role_id' => $dto->role_id,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'status_id' => $dto->status_id,
                'password' => $hashedPassword,
                'client_id' => $dto->client_id,
            ]);

            return $user;
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error de base de datos al crear el usuario: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new \Exception('Ocurrió un error inesperado al crear el usuario: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(EditUserDTO $dto): User
    {
        $user = User::findOrFail($dto->id);

        $updateData = [
            'name' => $dto->name,
            'user_type_id' => $dto->user_type_id,
            'document_type_id' => $dto->document_type_id,
            'document' => $dto->document,
            'role_id' => $dto->role_id,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'status_id' => $dto->status_id,
            'client_id' => $dto->client_id,
        ];

        // Solo actualizar la contraseña si se proporciona una nueva
        if (!empty($dto->password)) {
            $updateData['password'] = Hash::make($dto->password);
        }

        $user->update($updateData);

        // Refrescar el modelo con todas las relaciones
        return User::with(['type', 'client', 'documentType', 'role', 'status'])
            ->findOrFail($user->id);
    }

    public function getUsersByClient($clientId)
    {
        return User::with(['type', 'client', 'documentType', 'role', 'status'])
            ->where('client_id', $clientId)
            ->get();
    }

    public function getActiveClientUsers()
    {
        return User::with(['type', 'client', 'documentType', 'role', 'status'])
            ->where('user_type_id', 10) // Tipo cliente
            ->where('status_id', 1) // Estado activo (asumiendo que 1 = activo)
            ->get();
    }

    public function getInactiveClientUsers()
    {
        return User::with(['type', 'client', 'documentType', 'role', 'status'])
            ->where('user_type_id', 10) // Tipo cliente
            ->where('status_id', '!=', 1) // Estado diferente a activo
            ->get();
    }

    public function getDeletedClientUsers()
    {
        return User::onlyTrashed()
            ->with(['type', 'client', 'documentType', 'role', 'status'])
            ->where('user_type_id', 10) // Tipo cliente
            // ->where('status_id', 2) // Estado eliminado
            ->get();
    }

    public function getAllClientUsers()
    {
        return User::withTrashed()
            ->with(['type', 'client', 'documentType', 'role', 'status'])
            ->where('user_type_id', 10) // Tipo cliente
            ->get();
    }

    public function destroy(User $user): bool
    {
        try {
            // Cambiar el status_id a 2 antes del soft delete
            $user->update(['status_id' => 2]);

            // Realizar el soft delete
            return $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error de base de datos al eliminar el usuario: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new \Exception('Ocurrió un error inesperado al eliminar el usuario: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Exporta los clientes en formato Excel
     * @param bool $includeDeleted - Incluir clientes eliminados
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportClients($includeDeleted = false)
    {
        try {
            return Excel::download(new \App\Exports\ClientsExport($includeDeleted), 'clientes_' . date('Y-m-d_H-i-s') . '.xlsx');
        } catch (\Exception $e) {
            throw new \Exception('Error al exportar clientes: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Importa clientes desde un archivo CSV/Excel
     * @param \Illuminate\Http\UploadedFile $file - Archivo a importar
     * @return array - Resultado de la importación
     */
    public function importClients($file)
    {
        try {
            DB::beginTransaction();

            $import = new \App\Imports\ClientsImport();
            
            // Importar el archivo
            Excel::import($import, $file);

            $successCount = $import->getSuccessCount();
            $errors = $import->getErrors();
            $failures = $import->getFailures();

            // Generar archivo de errores si hay fallos
            $errorFileUrl = null;
            if (!empty($errors) || !empty($failures)) {
                $errorFileUrl = $this->generateErrorFile($errors, $failures);
            }

            // Si hay errores críticos, hacer rollback
            if (!empty($errors)) {
                DB::rollback();
                return [
                    'success' => false,
                    'message' => 'Error durante la importación',
                    'errors' => $errors,
                    'failures' => $failures,
                    'imported_count' => 0,
                    'error_file_url' => $errorFileUrl
                ];
            }

            // Si solo hay fallos de validación pero algunos registros se procesaron
            if (!empty($failures) && $successCount == 0) {
                DB::rollback();
                return [
                    'success' => false,
                    'message' => 'No se pudo importar ningún registro debido a errores de validación',
                    'errors' => $errors,
                    'failures' => $failures,
                    'imported_count' => 0,
                    'error_file_url' => $errorFileUrl
                ];
            }

            DB::commit();

            $response = [
                'success' => true,
                'message' => "Importación completada. {$successCount} clientes importados.",
                'imported_count' => $successCount,
                'errors' => $errors,
                'failures' => $failures
            ];

            // Si hubo algunos fallos pero también éxitos, incluir el archivo de errores
            if (!empty($failures)) {
                $response['error_file_url'] = $errorFileUrl;
                $response['message'] .= " Se encontraron algunos errores en el archivo.";
            }

            return $response;

        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Error al importar clientes: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Genera un archivo Excel con los errores de importación
     * @param array $errors - Errores generales
     * @param array $failures - Errores de validación por fila
     * @return string - URL del archivo generado
     */
    private function generateErrorFile($errors, $failures)
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "errores_importacion_{$timestamp}.xlsx";
            $filepath = "public/import-errors/{$filename}";
            
            // Crear directorio si no existe
            $directory = storage_path('app/public/import-errors');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Generar el archivo Excel con los errores
            Excel::store(new \App\Exports\ImportErrorsExport($errors, $failures), $filepath);

            // Devolver la URL pública del archivo
            return url("storage/import-errors/{$filename}");

        } catch (\Exception $e) {
            // Si no se puede generar el archivo, devolver null
            return null;
        }
    }
}
