<?php




use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Responsable;

class BlancosExport implements Responsable
{
    protected $blancos;

    public function __construct(Collection $blancos)
    {
        $this->blancos = $blancos;
        dd($blancos);
    }

    public function toResponse($request)
    
    {
        dd($this->blancos);
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="blancos.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Agregar el BOM
            fputs($file, "\xEF\xBB\xBF");
            // Escribir los encabezados
             fputcsv($file, ['Número de Control']);
        


            // Escribir los datos
            foreach ($this->blancos as $blanco) {
                fputcsv($file, [$blanco->numero_control]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

