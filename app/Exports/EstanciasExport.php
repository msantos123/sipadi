<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EstanciasExport implements FromCollection, WithHeadings, WithMapping
{
    protected $estancias;

    public function __construct(array $estancias)
    {
        $this->estancias = $estancias;
    }

    public function collection()
    {
        return collect($this->estancias);
    }

    public function headings(): array
    {
        return [
            'Nombre Completo',
            'Documento',
            'Nacionalidad',
            'Establecimiento',
            'Sucursal',
            'Departamento',
            'Fecha de Entrada',
            'Fecha de Salida',
            'Nro. Cuarto',
            'Tipo Cuarto',
        ];
    }

    public function map($estancia): array
    {
        $estancia = (object) $estancia;

        return [
            $estancia->persona_nombres . ' ' . $estancia->persona_apellido_paterno . ' ' . $estancia->persona_apellido_materno,
            $estancia->persona_nro_documento,
            $estancia->persona_nacionalidad,
            $estancia->establecimiento_razon_social,
            $estancia->sucursal_nombre,
            $estancia->persona_departamento,
            $estancia->fecha_hora_ingreso,
            $estancia->fecha_hora_salida_efectiva ?? 'N/A',
            $estancia->nro_cuarto,
            $estancia->tipo_cuarto_nombre,
        ];
    }
}
