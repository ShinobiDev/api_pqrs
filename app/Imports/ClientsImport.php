<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Type;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Throwable;

class ClientsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    private $errors = [];
    private $failures = [];
    private $successCount = 0;

    /**
     * Transforma cada fila del CSV en un modelo User
     */
    public function model(array $row)
    {
        try {
            // Convertir datos numéricos a string para evitar problemas con Excel
            $row = $this->normalizeRowData($row);
            
            // Buscar el tipo de documento por nombre
            $documentType = Type::where('name', 'LIKE', '%' . trim($row['tipo_documento']) . '%')->first();
            
            if (!$documentType) {
                throw new \Exception("Tipo de documento '{$row['tipo_documento']}' no encontrado");
            }

            // Generar contraseña aleatoria
            $randomPassword = $this->generateRandomPassword();

            $user = new User([
                'user_type_id' => 10, // Fijo para clientes
                'name' => trim($row['nombre'] ?? ''),
                'document_type_id' => $documentType->id, // Obtenido dinámicamente por nombre
                'document' => trim($row['documento'] ?? ''),
                'role_id' => 4, // Fijo para clientes
                'email' => trim($row['email'] ?? ''),
                'phone' => trim($row['phone'] ?? ''),
                'status_id' => 1, // Fijo para clientes
                'password' => Hash::make($randomPassword),
                'client_id' => null // Null para este servicio
            ]);

            $this->successCount++;
            return $user;

        } catch (\Exception $e) {
            $this->errors[] = "Error en fila: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Normaliza los datos de la fila convirtiendo números a strings
     */
    private function normalizeRowData(array $row): array
    {
        // Convertir documento a string si es numérico
        if (isset($row['documento']) && is_numeric($row['documento'])) {
            $row['documento'] = (string) $row['documento'];
        }
        
        // Convertir teléfono a string si es numérico
        if (isset($row['phone']) && is_numeric($row['phone'])) {
            $row['phone'] = (string) $row['phone'];
        }
        
        // Asegurar que todos los campos de texto sean strings
        if (isset($row['nombre'])) {
            $row['nombre'] = (string) $row['nombre'];
        }
        
        if (isset($row['tipo_documento'])) {
            $row['tipo_documento'] = (string) $row['tipo_documento'];
        }
        
        if (isset($row['email'])) {
            $row['email'] = (string) $row['email'];
        }
        
        return $row;
    }

    /**
     * Prepara los datos antes de la validación
     */
    public function prepareForValidation($data, $index)
    {
        // Normalizar datos antes de la validación
        return $this->normalizeRowData($data);
    }

    /**
     * Define las reglas de validación para cada fila
     */
    public function rules(): array
    {
        return [
            '*.nombre' => 'required|max:255',
            '*.tipo_documento' => 'required|max:100',
            '*.documento' => [
                'required',
                'max:50',
                Rule::unique('users', 'document')
            ],
            '*.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
            ],
            '*.phone' => 'nullable|max:20',
        ];
    }

    /**
     * Mensajes de validación personalizados
     */
    public function customValidationMessages()
    {
        return [
            '*.nombre.required' => 'El nombre es obligatorio',
            '*.nombre.max' => 'El nombre no puede tener más de 255 caracteres',
            
            '*.tipo_documento.required' => 'El tipo de documento es obligatorio',
            '*.tipo_documento.max' => 'El tipo de documento no puede tener más de 100 caracteres',
            
            '*.documento.required' => 'El documento es obligatorio',
            '*.documento.unique' => 'El documento ya existe en la base de datos',
            '*.documento.max' => 'El documento no puede tener más de 50 caracteres',
            
            '*.email.required' => 'El email es obligatorio',
            '*.email.email' => 'El email debe tener formato válido',
            '*.email.unique' => 'El email ya existe en la base de datos',
            '*.email.max' => 'El email no puede tener más de 255 caracteres',
            
            '*.phone.max' => 'El teléfono no puede tener más de 20 caracteres',
        ];
    }

    /**
     * Genera una contraseña aleatoria
     */
    private function generateRandomPassword(): string
    {
        // Generar contraseña de 12 caracteres con letras, números y símbolos
        return Str::random(12);
    }

    /**
     * Maneja errores durante la importación
     */
    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
    }

    /**
     * Maneja fallos de validación
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }

    /**
     * Tamaño del lote para inserción
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Tamaño del chunk para lectura
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Obtiene los errores ocurridos durante la importación
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtiene los fallos de validación
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Obtiene el número de registros procesados exitosamente
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
}