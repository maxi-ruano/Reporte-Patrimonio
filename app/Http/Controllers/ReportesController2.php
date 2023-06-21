<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InsumosExport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

use Illuminate\Http\Request;
use App\AnsvLotes;

use App\AnsvControl;
use App\AnsvDescartes;
use App\SysMultivalue;


class ReportesController2 extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:view_insumos')->only('reporteControlInsumos','exportarExcel');
    }

    public function reporteControlInsumos(Request $request)
    {
        $sucursaless = SysMultivalue::where('type', 'SUCU');

        if ($request->sucursal) {
            $sucursaless = $sucursaless->where('id', $request->sucursal);
        }

        $sucursaless = $sucursaless->get();
        $sucursalSeleccionada = $request->sucursal;
        $Todassucursales = SysMultivalue::where('type', 'SUCU')->get();
        $lotesImpresos = [];

        // Obtener los descartados por lote de la sucursal filtrada o todas las sucursales
        $lotesSucursal = AnsvLotes::when($sucursalSeleccionada, function ($query) use ($sucursalSeleccionada) {
            return $query->where('sucursal_id', $sucursalSeleccionada);
        })
            ->orderByDesc('lote_id')
            ->paginate(15);
        $lotesSucursal->appends(['sucursal' => $sucursalSeleccionada])->links();

        foreach ($lotesSucursal as $lote) {
            $descartados = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
                ->distinct()
                ->get(['control']);

            $cantidadDescartados = count($descartados);

            $cantidadLote = $lote->control_hasta - $lote->control_desde + 1;

            $cantidadImpresos = AnsvControl::whereBetween('nro_control', [$lote->control_desde, $lote->control_hasta])
                ->where('liberado', 'false')
                ->whereNotIn('nro_control', $descartados->pluck('control')) // Excluir los descartes de la consulta
                ->count('nro_control');

            $cantidadBlancos = $cantidadLote - ($cantidadImpresos + $cantidadDescartados);

            $nroKit = $lote->getAttribute('nro_kit');
            $nroCaja = $lote->getAttribute('nro_caja');

            $lotesImpresos[] = [
                'sucursal' => $sucursaless->where('id', $lote->sucursal_id)->first()->description,
                'lote_id' => $lote->lote_id,
                'nroKit' => $nroKit,
                'nroCaja' => $nroCaja,
                'cantidadImpresos' => $cantidadImpresos,
                'cantidadLote' => $cantidadLote,
                'control_desde' => $lote->control_desde,
                'control_hasta' => $lote->control_hasta,
                'cantidadBlancos' => $cantidadBlancos,
                'cantidadDescartados' => $cantidadDescartados
            ];

        }

        return view('reportes.reportesControlInsumos2', [
            'sucursales' => $sucursaless,
            'sucursalSeleccionada' => $sucursalSeleccionada,
            'Todassucursales' => $Todassucursales,
            'lotesImpresos' => $lotesImpresos,
            'lotesSucursal' => $lotesSucursal,
        ]);
    }



    public function exportarExcel(Request $request)
    {

	    $sucursales = SysMultivalue::where('type', 'SUCU')->get();

	    $sucursalSeleccionada = $request->input('sucursal');

	    // Obtener la descripción de la sucursal seleccionada
	    $sucursalesDescripcion = '';
	    foreach ($sucursales as $s) {
        	if ($s->id == $sucursalSeleccionada) {
	            $sucursalesDescripcion = $s->description;
        	    break;
	        }
	    }

	    // Obtener los datos de cantidadLote y cantidadImpresos para la sucursal seleccionada
	    $lotesImpresos = [];
	    foreach ($sucursales as $sucursal) {
		        if ($sucursalSeleccionada && $sucursal->id != $sucursalSeleccionada) {
		            continue;
		        }

	        $sucursalId = $sucursal->id;
        	// $lotesSucursal = AnsvLotes::where('sucursal_id', $sucursalId)->get();
	        $lotesSucursal = AnsvLotes::when($sucursalSeleccionada, function ($query) use ($sucursalSeleccionada) {
        	    return $query->where('sucursal_id', $sucursalSeleccionada);
	        })
        	->orderByDesc('lote_id')
	        ->paginate(15);

        	foreach ($lotesSucursal as $lote) {
	            $descartados = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
        	        ->distinct()
                	->get();

	            $cantidadDescartados = count($descartados);

        	    $cantidadImpresos = AnsvControl::whereBetween('nro_control', [$lote->control_desde, $lote->control_hasta])
	                ->where('liberado', 'false')
        	        ->whereNotIn('nro_control', $descartados->pluck('nro_control'))
                	->count('nro_control');

	            $cantidadLote = $lote->control_hasta - $lote->control_desde + 1;
        	    $cantidadBlancos = $cantidadLote - ($cantidadImpresos + $cantidadDescartados);

	            $nroKit = $lote->getAttribute('nro_kit');
        	    $nroCaja = $lote->getAttribute('nro_caja');

	            $lotesImpresos[] = [
        	        'lote_id' => $lote->lote_id,
                	'sucursal' => $sucursal->description,
	                'control_desde' => $lote->control_desde,
        	        'control_hasta' => $lote->control_hasta,
                	'cantidadLote' => $cantidadLote,
	                'cantidadBlancos' => $cantidadBlancos,
        	        'cantidadDescartados' =>$cantidadDescartados,
                	'cantidadImpresos' => $cantidadImpresos,
	                'nroKit' => $nroKit,
        	        'nroCaja' => $nroCaja,
        	    ];
	        }
    	    }

	    // Crear una nueva instancia de la clase InsumosExport y pasar los datos de $lotesImpresos
	    $export = new InsumosExport($lotesImpresos);

	    // Establecer el nombre del archivo Excel
	    if ($sucursalesDescripcion) {
        	$fileName = 'insumos_' . $sucursalesDescripcion . '_' . date('Y-m-d') . '.xlsx';
	    } else {
        	$fileName = 'insumos_' . 'TODOS'. '_' . date('Y-m-d') . '.xlsx';
	    }

	    $current_page = $request->input('page', 1);

	    $fileName = $current_page . '_' . $fileName;
	    // Generar y almacenar el archivo Excel
	    $exportPath = storage_path('app/' . $current_page . '_' . $fileName);
	    // $exportPath = storage_path('app/' . $fileName);
	    // Excel::store($export, $fileName, 'local');
	    Excel::store($export, $current_page . '_' . $fileName, 'local');

	    // Obtener el archivo Excel generado
	    $file = File::get($exportPath);

	    // Eliminar el archivo generado
	    File::delete($exportPath);

	    // Crear la respuesta con el archivo Excel adjunto
	    $response = Response::make($file, 200);
	    $response->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	    // $response->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
	    $response->header('Content-Disposition', 'attachment; filename="' . $current_page . '_' . $fileName . '"');

	    return $response;
	}


}


