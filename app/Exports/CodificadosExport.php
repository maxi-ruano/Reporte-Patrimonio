<?php

namespace App\Exports;









use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;



// class CodificadosExport implements FromCollection, WithHeadings
// {
//     protected $codificados;

//     public function __construct($codificados)
//     {
//         $this->codificados = $codificados;
//     }

//     public function collection()
//     {
//         return $this->codificados;
//     }

//     public function headings(): array
//     {
//         return [
//             'Trámite ID',
//             'Número de Control',
//             'Creado por',
//         ];
//     }
// }


use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Responsable;

class CodificadosExport implements Responsable
{
    protected $codificados;

    public function __construct(Collection $codificados)
    {
        $this->codificados = $codificados;
    }

    public function toResponse($request)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="codificados.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Escribir los encabezados
            fputcsv($file, ['Trámite ID', 'Número de Control', 'Creado por']);

            // Escribir los datos
            foreach ($this->codificados as $codificado) {
                fputcsv($file, $codificado->toArray());
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

