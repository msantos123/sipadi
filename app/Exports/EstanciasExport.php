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
        $persona = (object) $estancia->persona;
        $reserva = (object) $estancia->reserva;
        $establecimiento = (object) $reserva->establecimiento;
        $sucursal = $establecimiento->sucursales[0] ?? null;
        $departamento = $sucursal ? ((object) $sucursal->departamento)->nombre : 'N/A';


        return [
            $persona->nombres . ' ' . $persona->apellido_paterno . ' ' . $persona->apellido_materno,
            $persona->tipo_documento . ': ' . $persona->nro_documento,
            ((object) $persona->nacionalidad)->nombre,
            $establecimiento->razon_social,
            $sucursal ? $sucursal['nombre_sucursal'] : 'N/A',
            $departamento,
            $reserva->fecha_entrada,
            $reserva->fecha_salida,
            $estancia->nro_cuarto,
            ((object) $estancia->tipo_cuarto)->nombre,
        ];
    }
}
