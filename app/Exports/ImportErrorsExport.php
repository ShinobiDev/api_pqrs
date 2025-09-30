<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ImportErrorsExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    private $errors;
    private $failures;

    public function __construct($errors, $failures)
    {
        $this->errors = $errors;
        $this->failures = $failures;
    }

    /**
     * Devuelve los datos del array para el Excel
     */
    public function array(): array
    {
        $data = [];
        
        // Agregar errores generales
        if (!empty($this->errors)) {
            $data[] = ['ERRORES GENERALES', '', '', '', '', ''];
            foreach ($this->errors as $error) {
                $data[] = ['Error del sistema', $error, '', '', '', ''];
            }
            $data[] = ['', '', '', '', '', '']; // Línea vacía
        }

        // Agregar errores de validación por fila
        if (!empty($this->failures)) {
            $data[] = ['ERRORES DE VALIDACIÓN POR FILA', '', '', '', '', ''];
            
            foreach ($this->failures as $failure) {
                $row = $failure['row'] ?? 'N/A';
                $field = $failure['attribute'] ?? 'N/A';
                $errors = is_array($failure['errors']) ? implode(', ', $failure['errors']) : $failure['errors'];
                $values = $failure['values'] ?? [];
                
                $nombre = $values['nombre'] ?? '';
                $documento = $values['documento'] ?? '';
                $email = $values['email'] ?? '';
                
                $data[] = [
                    "Fila {$row}",
                    $field,
                    $errors,
                    $nombre,
                    $documento,
                    $email
                ];
            }
        }

        // Si no hay errores ni fallos
        if (empty($this->errors) && empty($this->failures)) {
            $data[] = ['No se encontraron errores en la importación', '', '', '', '', ''];
        }

        return $data;
    }

    /**
     * Define los encabezados de las columnas
     */
    public function headings(): array
    {
        return [
            'Tipo de Error',
            'Campo con Error',
            'Descripción del Error',
            'Nombre',
            'Documento',
            'Email'
        ];
    }

    /**
     * Aplica estilos al Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para los encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC3545'] // Color rojo para errores
                ]
            ],
            // Estilo para las filas de errores generales
            'A:F' => [
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => 'top'
                ]
            ]
        ];
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Errores de Importación';
    }
}