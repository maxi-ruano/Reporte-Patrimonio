<?php

namespace App\Exports;

// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;

// class DescartesExport implements FromCollection, WithHeadings
// {
//     protected $descartes;

//     public function __construct($descartes)
//     {
//         $this->descartes = $descartes;
//     }

//     public function collection()
//     {
//         return $this->descartes;
//     }

//     public function headings(): array
//     {
//         return [
//             'Trámite ID',
//             'Número de Control',
//             'Creado por',
//             'Descripcion',
//         ];
//     }
// }

use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Responsable;

class DescartesExport implements Responsable
{
    protected $descartes;

    public function __construct(Collection $descartes)
    {
        $this->descartes = $descartes;
        dd($descartes);
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
            foreach ($this->descartes as $descarte) {
                fputcsv($file, $descarte->toArray());
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

