<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private $includeDeleted;

    public function __construct($includeDeleted = false)
    {
        $this->includeDeleted = $includeDeleted;
    }

    /**
     * Obtiene la colección de clientes para exportar
     */
    public function collection()
    {
        $query = User::where('user_type_id', 10)
                    ->with(['status', 'documentType', 'role', 'type']);

        if ($this->includeDeleted) {
            $query->withTrashed();
        }

        return $query->get();
    }

    /**
     * Define los encabezados de las columnas
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Documento',
            'Email',
            'Teléfono',
            'Cliente ID',
            'Estado',
            'Fecha de Creación',
            'Fecha de Actualización',
            'Fecha de Eliminación'
        ];
    }

    /**
     * Mapea los datos de cada cliente
     */
    public function map($client): array
    {
        return [
            $client->id,
            $client->name, // Campo nombre que sí existe
            $client->document, // Agregado: documento del cliente
            $client->email,
            $client->phone ?? '', // Teléfono con valor por defecto
            $client->client_id,
            $this->getStatusLabel($client),
            $client->created_at ? $client->created_at->format('Y-m-d H:i:s') : '',
            $client->updated_at ? $client->updated_at->format('Y-m-d H:i:s') : '',
            $client->deleted_at ? $client->deleted_at->format('Y-m-d H:i:s') : ''
        ];
    }

    /**
     * Aplica estilos a la hoja de cálculo
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
                    'startColor' => ['rgb' => '4472C4']
                ]
            ],
        ];
    }

    /**
     * Obtiene la etiqueta del estado del cliente
     */
    private function getStatusLabel($client): string
    {
        if ($client->deleted_at) {
            return 'Eliminado';
        }

        if ($client->status) {
            return $client->status->name;
        }

        // Si no tiene status pero no está eliminado, asumimos que está activo
        return $client->status_id == 1 ? 'Activo' : 'Inactivo';
    }
}