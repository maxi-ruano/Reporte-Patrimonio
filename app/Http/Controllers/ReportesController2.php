<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\SysMultivalue;
use App\AnsvLotes;
use App\AnsvControl;
use Maatwebsite\Excel\Facades\Excel;
use App\InsumosExport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Controller;
use App\AnsvDescartes;

class ReportesController2 extends Controller
{
   



function reporteControlInsumos2(Request $request)
{
  
    $sucursaless = SysMultivalue::where('type', 'SUCU');



    if($request->sucursal) {

        $sucursaless = $sucursaless->where ('id',$request->sucursal);
    }
    
    $sucursaless = $sucursaless->get();

    $sucursalSeleccionada = $request->sucursal;

    $Todassucursales = SysMultivalue::where('type', 'SUCU')->get();

 
    $lotesImpresos = [];

   

      foreach ($sucursaless as $sucursal) {

        $sucursalId = $sucursal->id;

        $lotes = AnsvLotes::where('sucursal_id', $sucursalId)->get();

      
    
        foreach ($lotes as $lote) {

            $cantidadImpresos = AnsvControl::whereBetween('nro_control', [$lote->control_desde, $lote->control_hasta])
                ->where('liberado', 'false')
                ->count();



                $cantidadLote = $lote->control_hasta - $lote->control_desde ;

                 $descartados = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])->get();

                //dd($descartados);

                $lotesImpresos[] = [
                    'sucursal' => $sucursal->description,
                    'lote_id' => $lote->lote_id,
                    'cantidadImpresos' => $cantidadImpresos,
                    'cantidadLote' => $cantidadLote,
                    'control_desde' => $lote->control_desde,
                    'control_hasta' => $lote->control_hasta,
                    // 'descartados' => $descartados
                    
                ];

          }
    }




    return view('reportes.reportesControlInsumos2', [
        'sucursales' => $sucursaless,
        'sucursalSeleccionada' => $sucursalSeleccionada,
        'Todassucursales' => $Todassucursales,
        'lotesImpresos' => $lotesImpresos
        
    ]);
}


}
