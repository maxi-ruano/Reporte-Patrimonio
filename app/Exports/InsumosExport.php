<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InsumosExport implements FromCollection, WithHeadings
{
    
    protected $lotesImpresos;

    public function __construct($lotesImpresos)
    {
        $this->lotesImpresos = $lotesImpresos;
    }

    public function collection()
    {
        return collect($this->lotesImpresos);
    }

    public function headings(): array
    {
        return [
            'Lote_id',
            'Sucursal',
            'Control_desde',
            'Control_hasta',
            'Cant. Lotes',
            'Cant. Impresos',
            'Descartes',
             'Blancos',
              'NroKit',
             'NroCaja'
        ];
    }

    public function map($loteImpreso): array
    {
        return [
            $loteImpreso['lote_id'],
            $loteImpreso['sucursal'],
            $loteImpreso['control_desde'],
            $loteImpreso['control_hasta'],
            $loteImpreso['cantidadLote'],
            $loteImpreso['cantidadBlancos'],
            $loteImpreso['cantidadDescartados'],
            $loteImpreso['cantidadImpresos'],
            $loteImpreso['nroKit'],
            $loteImpreso['nroCaja'],
        ];
    }






}


