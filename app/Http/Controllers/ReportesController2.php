<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\AnsvLotes;
use App\Tramites;
use App\AnsvControl;
use App\AnsvLotesPatrimonio;
use Illuminate\Validation\Rule;

use App\AnsvDescartes;
use App\SysMultivalue;
use App\User;
use App\SysUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;


class ReportesController2 extends Controller
{

    public function __construct()
    {
        $this->middleware(['permission:view_insumos|view_insumos_all']);
    }

    public function ejecutarAccion(Request $request)
    {
        $accion = $request->input('accion');
        $deshabilitarBotonAcciones = false;

        if ($accion === 'agregar_lote') {

            $deshabilitarBotonAcciones = true;
            // Obtener datos para la vista
            $sucursaless = SysMultivalue::where('type', 'SUCU')->get();
            $Todassucursales = SysMultivalue::where('type', 'SUCU')->get();
            $sucursalSeleccionada = $request->sucursal;
            return view('reportes.crear_lote', compact('sucursaless', 'Todassucursales', 'sucursalSeleccionada',));


        } elseif ($accion === 'habilitar_lote') {
            $selectedItems = $request->input('selectedItems', []);
            if (empty($selectedItems)) {
                $request->session()->flash('warning', 'Por favor, selecciona al menos un lote antes de realizar la acción.');
                return redirect()->route('reporte.control.insumos');
            }
            $affectedRows = AnsvLotes::whereIn('lote_id', $selectedItems)->update([
                'habilitado' => true,
                'modification_date' => now()
            ]);
            return redirect()->back();


        } 
        
        elseif ($accion === 'deshabilitar_lote') {     

            $selectedItems = $request->input('selectedItems', []);
            if (empty($selectedItems)) {
                $request->session()->flash('warning', 'Por favor, selecciona al menos un lote antes de realizar la acción.');
                return redirect()->route('reporte.control.insumos');
            }

            $rangosControl = AnsvLotes::whereIn('lote_id', $selectedItems)
            ->select('control_desde', 'control_hasta')
            ->first();

      
            $numerosControlEnUso = AnsvControl::whereBetween('nro_control', [$rangosControl->control_desde, $rangosControl-> control_hasta])->exists();
            $numerosControlDescartados = AnsvDescartes::whereBetween('control', [$rangosControl->control_desde, $rangosControl-> control_hasta])->exists();
    
            if ($numerosControlEnUso ||   $numerosControlDescartados) {
                $request->session()->flash('error', 'No se pueden deshabilitar los lotes en uso.');
                return redirect()->route('reporte.control.insumos');
            }
        
            $affectedRows = AnsvLotes::whereIn('lote_id', $selectedItems)->update([
                'habilitado' => false,
                'modification_date' => now()
            ]);
    
            return redirect()->back();
        
        } elseif ($accion === 'eliminar_lote') {
            $selectedItems = $request->input('selectedItems', []);

            if (empty($selectedItems)) {
                $request->session()->flash('warning', 'Por favor, selecciona al menos un lote antes de realizar la acción.');
                return redirect()->route('reporte.control.insumos');
            }


            $rangosControl = AnsvLotes::whereIn('lote_id', $selectedItems)
            ->select('control_desde', 'control_hasta')
            ->first();

          
           $numerosControlEnUso = AnsvControl::whereBetween('nro_control', [$rangosControl->control_desde, $rangosControl-> control_hasta])->exists();
           $numerosControlDescartados = AnsvDescartes::whereBetween('control', [$rangosControl->control_desde, $rangosControl-> control_hasta])->exists();

        
            if ($numerosControlEnUso ||  $numerosControlDescartados) {
                $request->session()->flash('error', 'No se pueden eliminar los lotes en uso.');  
                return redirect()->route('reporte.control.insumos');
            } else{
                $affectedRows = AnsvLotes::whereIn('lote_id', $selectedItems)->delete();
                return redirect()->back();
            }         

        } elseif ($accion === 'editar_lote') {

            $selectedItems = $request->input('selectedItems', []);
            if (empty($selectedItems)) {
                $request->session()->flash('warning', 'Por favor, selecciona al menos un lote antes de realizar la acción.');
                return redirect()->route('reporte.control.insumos');
            }
            $lote_id = $selectedItems[0];
            return redirect()->route('editar.lote', ['lote_id' => $lote_id]);

        } elseif ($accion == 'Elegir accion') {

        return back()->withErrors(['Por favor elige una acción'])->withInput();

        }
    } 
 

    public function guardarLote(Request $request)
    {
    
        $controlDesde = $request->input('nro_control_desde');
        $controlHasta = $request->input('nro_control_hasta');

        $query = "
            SELECT *
            FROM ansv_lotes
            WHERE (
                (control_desde <= ? AND control_hasta >= ?)
                OR (control_desde <= ? AND control_hasta >= ?)
            )
            AND (end_date IS NULL)
            OR (
                control_desde = ? AND control_hasta = ?
                AND end_date IS NULL
            )
        ";

        $existingLote = DB::select($query, [$controlHasta, $controlDesde, $controlHasta, $controlHasta, $controlDesde, $controlHasta]);
    
        if ($existingLote) {
            $sucursaless = SysMultivalue::where('type', 'SUCU')->get();
            $Todassucursales = SysMultivalue::where('type', 'SUCU')->get();
            $sucursalSeleccionada = $request->sucursal;
            $error2 = 'El rango de control ya se superpone con un lote existente.';
            return view('reportes.crear_lote', compact('sucursaless', 'Todassucursales', 'sucursalSeleccionada', 'error2'));
        }
    
        $nextLoteId = DB::table('ansv_lotes')->max('lote_id') + 1;
    
        $data = [
            'lote_id' => $nextLoteId,
            'sucursal_id' => $request->input('sucursal_id'), 
            'control_desde' => $controlDesde,
            'control_hasta' => $controlHasta,
            'habilitado' => false,
            'created_by' => 1,
            'creation_date' => now(),
            'modified_by' => null,
            'modification_date' => null,
            'end_date' => null,
            'nro_kit' => $request->input('nro_kit'),
            'nro_caja' => $request->input('nro_caja'),
        ];
    
        DB::table('ansv_lotes')->insert($data);
    
        return redirect()->route('reporte.control.insumos')->with('success', 'Lote creado correctamente');
    }


    public function editarLote($lote_id)
    {
        $lote = AnsvLotes::find($lote_id);
        return view('reportes.editar_lote', compact('lote'));
    }

    public function actualizarLote(Request $request, $lote_id)
    {
        $lote = AnsvLotes::find($lote_id);
    
        $lote->control_desde = $request->input('nro_control_desde');
        $lote->control_hasta = $request->input('nro_control_hasta');
        $lote->nro_kit = $request->input('nro_kit');
    
        $lote->save();
    
        return redirect()->route('reporte.control.insumos')->with('success', 'Lote creado correctamente');
    }
    

    function reporteControlInsumos(Request $request)
    {
        $sucursaless = SysMultivalue::where('type', 'SUCU'); 
        $request->sucursal = auth()->user()->can('view_insumos_all') ? $request->sucursal : auth()->user()->sucursal;

        if ($request->sucursal) {
            $sucursaless = $sucursaless->where('id', $request->sucursal);
        }

        $sucursaless = $sucursaless->get();


        $sucursalSeleccionada = $request->sucursal;
        $ids = [1,10,190,194,195,140,60,50,70,40,160,120,110,192,130,103,100,150,105,180,106,41,131,197];
        $Todassucursales = SysMultivalue::where('type', 'SUCU')
        ->whereIn('id', $ids)
        ->get();

        $lotesImpresos = [];
        $control_desde = $request->input('control_desde');    
        $controlBuscado = $request->input('nro_control');

        $lotesSucursalQuery = AnsvLotes::noEliminados()
            ->when($sucursalSeleccionada, function ($query) use ($sucursalSeleccionada) {
                return $query->where('sucursal_id', $sucursalSeleccionada);
            })
            ->when($controlBuscado, function ($query) use ($controlBuscado) {
                return $query->where(function ($query) use ($controlBuscado) {
                    $query->where('control_desde', '<=', $controlBuscado)
                        ->where('control_hasta', '>=', $controlBuscado);
                });
            })
            ->orderByDesc('lote_id');


        if ($request->numero_kit) {
            $numeroKit = $request->numero_kit;
            $lotesSucursalQuery->where('nro_kit', $numeroKit);
        }

        $lotesSucursal = $lotesSucursalQuery->paginate(15)->appends(['sucursal' => $sucursalSeleccionada, 'numero_kit' => $request->numero_kit]);

        foreach ($lotesSucursal as $lote) {
            
            $descartados = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
                ->distinct()
                ->get(['control']);

            $cantidadDescartados = count($descartados);

            $cantidadLote = $lote->control_hasta - $lote->control_desde + 1;

            $cantidadImpresos = AnsvControl::whereBetween('nro_control', [$lote->control_desde, $lote->control_hasta])
                ->where('liberado', 'false')
                ->whereNotIn('nro_control', $descartados->pluck('control')) 
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
                'cantidadDescartados' => $cantidadDescartados,
                'habilitado' => $lote->habilitado
            ];
        }


        $request->session()->put('lotesImpresos', $lotesImpresos);
        $request->session()->put('sucursalSeleccionada', $sucursalSeleccionada);


        return view('reportes.reportesControlInsumos2', [
            'sucursales' => $sucursaless,
            'sucursalSeleccionada' => $sucursalSeleccionada,
            'Todassucursales' => $Todassucursales,
            'lotesImpresos' => $lotesImpresos,
            'lotesSucursal' => $lotesSucursal,
        ]);
    }



    public function reporteLotesPatrimonio(Request $request) {
        $query = AnsvLotesPatrimonio::orderBy('id', 'desc');
    
        // Aplicar filtro si se envía el parámetro de búsqueda
        if ($request->filled('kit')) {
            $kit = $request->input('kit');
            $query->where('nro_kit', $kit);
        }
    
        // Obtener los resultados paginados
        $datosLotes = $query->paginate(10);
    
        // Resto del código para obtener los resultados y las sucursales
        $resultados = [];
    
        foreach ($datosLotes as $lote) {
            $nroControlDesde = $lote->nro_control_desde;
            $nroControlHasta = $lote->nro_control_hasta;
    
            $resultado = AnsvLotesPatrimonio::select(
                'ansv_lotes_patrimonio.*',
                'ansv_lotes.*',
                'sys_multivalue.description as sucursal_description',
                'ansv_lotes_patrimonio.nro_kit'
            )
                ->leftJoin('ansv_lotes', function ($join) use ($nroControlDesde, $nroControlHasta) {
                    $join->on('nro_control_desde', '=', 'ansv_lotes.control_desde')
                        ->on('nro_control_hasta', '=', 'ansv_lotes.control_hasta');
                })
                ->leftJoin('sys_multivalue', function ($join) {
                    $join->on('sucursal_id', '=', 'sys_multivalue.id')
                        ->where('sys_multivalue.type', '=', 'SUCU');
                })
                ->where('nro_control_desde', '=', $nroControlDesde)
                ->where('nro_control_hasta', '=', $nroControlHasta)
                ->first();
    
            if ($resultado && $resultado->fecha_habilitado_sede !== null) {
                $resultado->fecha_habilitado_sede = Carbon::parse($resultado->fecha_habilitado_sede)->format('Y-m-d H:i:s');
            }
            $resultados[] = $resultado;
        }
    
        $ids = [1,10,190,194,195,140,60,50,70,40,160,120,110,192,130,103,100,150,105,180,106,41,131,197];
        $todasSucursales = SysMultivalue::where('type', 'SUCU')
            ->whereIn('id', $ids)
            ->get();
    
        return view('patrimonio.reportesControlInsumos2', [
            'resultados' => $resultados,
            'todasSucursales' => $todasSucursales,
            'datosLotes' => $datosLotes,
        ]);
    }
    


    

    // public function reporteLotesPatrimonio(Request $request) {

        

    //     $datosLotes = AnsvLotesPatrimonio::orderBy('id', 'desc')->paginate(10); 

    
    //     $resultados = [];

    //     foreach ($datosLotes as $lote) {
    //         $nroControlDesde = $lote->nro_control_desde;
    //         $nroControlHasta = $lote->nro_control_hasta;

    //         $resultado = AnsvLotesPatrimonio::select(
    //             'ansv_lotes_patrimonio.*',
    //             'ansv_lotes.*',
    //             'sys_multivalue.description as sucursal_description',
    //             'ansv_lotes_patrimonio.nro_kit'
    //         )
    //             ->leftJoin('ansv_lotes', function ($join) use ($nroControlDesde, $nroControlHasta) {
    //                 $join->on('nro_control_desde', '=', 'ansv_lotes.control_desde')
    //                     ->on('nro_control_hasta', '=', 'ansv_lotes.control_hasta');
    //             })
    //             ->leftJoin('sys_multivalue', function ($join) {
    //                 $join->on('sucursal_id', '=', 'sys_multivalue.id')
    //                     ->where('sys_multivalue.type', '=', 'SUCU');
    //             })
    //             ->where('nro_control_desde', '=', $nroControlDesde)
    //             ->where('nro_control_hasta', '=', $nroControlHasta)
    //             ->first();


    //         if ($resultado && $resultado->fecha_habilitado_sede !== null) {
    //             $resultado->fecha_habilitado_sede = Carbon::parse($resultado->fecha_habilitado_sede)->format('Y-m-d H:i:s');
    //         }
    //         $resultados[] = $resultado;

    //     }

    //     $ids = [1,10,190,194,195,140,60,50,70,40,160,120,110,192,130,103,100,150,105,180,106,41,131,197];
    //     $todasSucursales = SysMultivalue::where('type', 'SUCU')
    //         ->whereIn('id', $ids)
    //         ->get();


    //     return view('patrimonio.reportesControlInsumos2', [
    //         'resultados' => $resultados,
    //         'todasSucursales' => $todasSucursales,
    //         'datosLotes' => $datosLotes,
    //     ]);
    // }

   







    public function acciones(Request $request)
    {
       
        $accion = $request->input('accion');
        $sucursalId = $request->input('sucursal');
        $seleccionLotes = $request->input('seleccion');

        if (empty($seleccionLotes)) {
            return redirect()->back()->with(['custom_error' => 'No se han seleccionado lotes para asignar.'])->withInput();
        }

        if (empty($sucursalId) && !in_array($accion, ['enviarSede', 'enviarNacion'])) {
            return redirect()->back()->with(['custom_error' => 'Debes elegir una sucursal para realizar la acción.'])->withInput();
        }

        if (empty($accion)) {
            return redirect()->back()->with(['custom_error' => 'Debes elegir una acción para realizar.'])->withInput();
        }


        if ($accion === 'asignarLote') {

            $seleccionLotes = $request->input('seleccion');

            if (!empty($seleccionLotes)) {
                $sucursalId = $request->input('sucursal');
        
                foreach ($seleccionLotes as $loteId) {

                    $infoLote = AnsvLotesPatrimonio::select('id', 'nro_control_desde', 'nro_control_hasta', 'nro_kit')
                        ->where('id', $loteId)
                        ->first();

                    if ($infoLote) {
                        $ultimoLote = AnsvLotes::latest('lote_id')->first();

                        $nuevoLoteId = $ultimoLote ? $ultimoLote->lote_id + 1 : 1;
        
                        $existeRango = AnsvLotes::where('control_desde', $infoLote->nro_control_desde)
                            ->where('control_hasta', $infoLote->nro_control_hasta)
                            ->exists();
        
                        if (!$existeRango) {
                            AnsvLotes::create([
                                'lote_id' => $nuevoLoteId,
                                'sucursal_id' => $sucursalId,
                                'control_desde' => $infoLote->nro_control_desde,
                                'control_hasta' => $infoLote->nro_control_hasta,
                                'habilitado' => false,
                                'created_by' => null,
                                'creation_date' => now(),
                                'modified_by' => null,
                                'modification_date' => now(),
                                'end_date' => null,
                                'nro_kit' => $infoLote->nro_kit
                            ]);

                        } else {
                            return redirect()->back()->with(['custom_error' => 'El rango ya está asignado a una sede.'])->withInput();
                        }
                    }
                }
        
                return redirect()->back()->with('success', 'Lotes asignados correctamente')->withInput();
            } else {
                return redirect()->back()->with(['custom_error' => 'No se han seleccionado lotes para asignar.'])->withInput();
            }

        }
        
        if($accion === 'enviarSede'){
            $seleccionLotes = $request->input('seleccion');

            if (!empty($seleccionLotes)) {
            
                $lotesEnviados = AnsvLotesPatrimonio::whereIn('id', $seleccionLotes)
                ->whereNotNull('fecha_enviado_sede')
                ->count();

                if ($lotesEnviados > 0) {
                    return redirect()->back()->with(['custom_error' => 'El lote ya fue enviado a Sede.'])->withInput();
                }

                AnsvLotesPatrimonio::whereIn('id', $seleccionLotes)
                ->whereNull('fecha_enviado_sede') 
                ->update(['fecha_enviado_sede' => Carbon::now()]);

                return redirect()->back()->with('success', 'Lotes enviados a sede correctamente');
            } else {
                return redirect()->back()->with(['custom_error' => 'No se han seleccionado lotes para enviar a Sede .'])->withInput();
            }
        }
    
        if($accion === 'enviarNacion'){
            $seleccionLotes = $request->input('seleccion');

            $descartesPatrimonio1 = AnsvLotesPatrimonio::whereIn('id', $seleccionLotes)
            ->get();
$ansvLotesPatrimonio = $descartesPatrimonio1->first();

$nroControlDesde = $ansvLotesPatrimonio->nro_control_desde;
$nroControlHasta = $ansvLotesPatrimonio->nro_control_hasta;
$nro_kit = $ansvLotesPatrimonio->nro_kit;

       $descartesPatrimonio = DB::table('ansv_descartes')
      ->select('control', 'motivo', 'descripcion', 'creation_date', 'created_by', 'end_date')
    ->whereBetween('control', [$nroControlDesde, $nroControlHasta])
    ->get();

    $descartesBlancosPatrimonio = [];

foreach ($descartesPatrimonio as $descarte) {
    $control = $descarte->control;
    $motivo = $descarte->motivo;
    $descripcion = $descarte->descripcion;

    $cantidadLote = $nroControlHasta - $nroControlDesde + 1;

            
    $cantidadDescartados = $descartesPatrimonio->count();


    $descartados = AnsvDescartes::whereBetween('control', [$nroControlDesde, $nroControlHasta])
    ->distinct()
    ->get(['control']);



    $cantidadImpresos = AnsvControl::whereBetween('nro_control', [$nroControlDesde,$nroControlHasta])
    ->where('liberado', 'false')
    ->whereNotIn('nro_control', $descartados->pluck('control')) 
    ->count('nro_control');




   $codificados = AnsvControl::whereBetween('nro_control', [$nroControlDesde, $nroControlHasta])
    ->where('liberado', false)
    ->pluck('nro_control');

  


   $descartesBlancosPatrimonio[] = [
        
        'controlDescartado' => $control,
        'motivoDescartado' => $motivo,
        'descripcionDescartado' => $descripcion,
        
        
    ];




}

$blancos = [];

$descartados2 = AnsvDescartes::whereBetween('control', [$nroControlDesde, $nroControlHasta])
->distinct()
->get(['control']);


$codificados = AnsvControl::whereBetween('nro_control', [$nroControlDesde, $nroControlHasta])
->where('liberado', false)
->pluck('nro_control');


for ($i = $nroControlDesde; $i <= $nroControlHasta; $i++) {
    if (!$descartados2->contains('control', $i) && !$codificados->contains($i)) {
        
        $blancos[] = $i;
    }else{
        $blancos = [];

    }
}
 



         
            return $this->mostrarVista3($descartesBlancosPatrimonio,$blancos, $nro_kit);




         

        
        }
    }


    public function mostrarVista3($descartesBlancosPatrimonio,$blancos, $nro_kit)

    {

        $controlesGuardados = DB::table('patrimonioenviadonacion')->pluck('control')->toArray();


session(['controlesGuardados' => $controlesGuardados]);


        return Redirect::route('patrimonioBlancosDescartes')->with([
            'descartesBlancosPatrimonio' => $descartesBlancosPatrimonio,
             'blancos' => $blancos,

            'nro_kit' => $nro_kit,
        ]);
    }














public function accionesDescartesBlancos(Request $request) {
    $nroKit = $request->input('nro_kit');

    $seleccionDescartes = (array) $request->input('seleccion_descartes');
    $seleccionBlancos = (array) $request->input('seleccion_blancos');

    // Insertar registros de descartes
    foreach ($seleccionDescartes as $controlDescarte) {
        DB::table('patrimonioenviadonacion')->insert([
            'control' => $controlDescarte,
            'blanco_descarte' => 1,
            'nro_kit' => $nroKit, 
        ]);

        $this->registrarLog($nroKit);
    }

    foreach ($seleccionBlancos as $controlBlanco) {
        DB::table('patrimonioenviadonacion')->insert([
            'control' => $controlBlanco,
            'blanco_descarte' => 2, 
            'nro_kit' => $nroKit,
        ]);

        $this->registrarLog($nroKit);
    }

    $lotesEnviados = AnsvLotesPatrimonio::where('nro_kit', $nroKit)
        ->whereNotNull('fecha_enviado_nacion')
        ->count();

    if ($lotesEnviados === 0) {
        AnsvLotesPatrimonio::where('nro_kit', $nroKit)
            ->whereNull('fecha_enviado_nacion')
            ->update(['fecha_enviado_nacion' => Carbon::now()]);
    }

    return redirect()->route('reporteLotesPatrminio')->with('success', 'Se guardo exitosamente los descartes/blancos');
}

private function registrarLog( $nroKit) {
    DB::table('ansv_lotes_patrimonio_log')->insert([
        'nro_kit' => $nroKit,
        'fecha_registro' => Carbon::now(),
    ]);
}






public function mostrarDatos(Request $request)
{
    $nroKit = $request->input('nro_kit');

    $descartes = DB::table('patrimonioenviadonacion')
        ->where('blanco_descarte', 1)
        ->where('nro_kit', $nroKit)
        ->get();

    $blancos = DB::table('patrimonioenviadonacion')
        ->where('blanco_descarte', 2)
        ->where('nro_kit', $nroKit)
        ->get();

    $resultados = DB::table('patrimonioenviadonacion as pe')
        ->join('ansv_lotes as al', 'pe.nro_kit', '=', 'al.nro_kit')
        ->where('pe.nro_kit', '=', $nroKit)
        ->select('pe.nro_kit', 'al.control_desde', 'al.control_hasta')
        ->distinct()
        ->get();

    $rangoNumerosControl = [];
    if ($resultados->isNotEmpty()) {
        foreach ($resultados as $resultado) {
            $rangoNumerosControl = array_merge(
                $rangoNumerosControl,
                range($resultado->control_desde, $resultado->control_hasta)
            );
        }
        $rangoNumerosControl = array_unique($rangoNumerosControl);

        $descartados = AnsvDescartes::whereBetween('control', [$resultados[0]->control_desde, $resultados[0]->control_hasta])
            ->distinct()
            ->get(['control']);

        $desFaltante = [];

        $controlesDescartados = $descartados->pluck('control')->toArray();
        $controlesDescartes = $descartes->pluck('control')->toArray();
        $controlesFaltantes = array_diff($controlesDescartados, $controlesDescartes);

        $descartadosFaltantes = $descartados->filter(function ($descartado) use ($controlesFaltantes) {
            return in_array($descartado->control, $controlesFaltantes);
        });

        foreach ($descartadosFaltantes as $descartadoFaltante) {
            $desFaltante[] = $descartadoFaltante->control;
        }

        $controlesFaltantes2 = array_diff($rangoNumerosControl, $controlesDescartados, $controlesDescartes, $descartados->pluck('control')->toArray(), $desFaltante);

        $controlesFaltantesNoBlancos = array_diff($controlesFaltantes2, $blancos->pluck('control')->toArray());
    } else {
        $rangoNumerosControl = [];
        $desFaltante = [];
        $controlesFaltantesNoBlancos = [];
    }

    return view('patrimonio.mostrar_datos', compact('descartes', 'blancos', 'nroKit', 'desFaltante', 'controlesFaltantesNoBlancos'));
}




    public function patrimonioBlancosDescartes(Request $request) {

        $seleccionLotes = $request->input('seleccion');

        return view('patrimonio.blancosDescartes');
        
        
        
        }
        
        




    public function guardarLotePatrimonio(Request $request) {

        $request->validate([
            'nro_control_desde' => 'required|numeric',
            'nro_control_hasta' => 'required|numeric',
            'fecha_recibido_nacion' => 'required|date',
            'fecha_recibido_sede' => 'required|date',
            'nro_kit' => [
                'required',
                'string',
                Rule::unique('ansv_lotes_patrimonio')->where(function ($query) use ($request) {
                    return $query->where('nro_kit', $request->input('nro_kit'));
                }),
            ],
        ]);

        $userId = Auth::user()->id;

        $nuevoDesde = $request->input('nro_control_desde');
        $nuevoHasta = $request->input('nro_control_hasta');
        $nuevoNroKit = $request->input('nro_kit');

        $existeSolapamiento = DB::table('ansv_lotes_patrimonio')
            ->where(function ($query) use ($nuevoDesde, $nuevoHasta) {
                $query->whereBetween('nro_control_desde', [$nuevoDesde, $nuevoHasta])
                    ->orWhereBetween('nro_control_hasta', [$nuevoDesde, $nuevoHasta]);
            })
            ->orWhere(function ($query) use ($nuevoDesde, $nuevoHasta) {
                $query->where('nro_control_desde', '<=', $nuevoDesde)
                    ->where('nro_control_hasta', '>=', $nuevoHasta);
            })
            ->orWhere('nro_kit', $nuevoNroKit)
            ->exists();

        if ($existeSolapamiento) {
           
            return redirect()->route('cargarLote')->with('custom_error', 'Ya existe un registro con solapamiento en los rangos de control o el mismo número de kit.')->withInput();
        }

        DB::table('ansv_lotes_patrimonio')->insert([
            'nro_control_desde' => $nuevoDesde,
            'nro_control_hasta' => $nuevoHasta,
            'fecha_recibido_nacion' => $request->input('fecha_recibido_nacion'),
            'fecha_habilitado_sede' => $request->input('fecha_habilitado_sede'),
            'fecha_recibido_sede' => $request->input('fecha_recibido_sede'),
            'creation_by' => $userId,
            'modification_date' => $request->input('modification_date'),
            'nro_kit' => $nuevoNroKit,
        ]);

        $request->session()->flash('success', 'Lote creado correctamente.');

        return redirect()->route('cargarLote');
    }


    public function CargarLotePatrimonio (Request $request) {
        return view('patrimonio.cargar_lote');
    }




  


















    public function obtenerCodificados(Request $request)
    {
        $loteId = $request->input('loteId');

        $lote = AnsvLotes::where('lote_id', $loteId)->first();

        $descartados = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
            ->distinct()
            ->get(['control']);

        if (!$lote) {
            return response()->json(['error' => 'Lote no encontrado'], 404);
        }

        $controlDesde = $lote->control_desde;
        $controlHasta = $lote->control_hasta;

        $codificados = AnsvControl::whereBetween('nro_control', [$controlDesde, $controlHasta])
            ->where('liberado', false)
            ->whereNotIn('nro_control', $descartados->pluck('control'))
            ->get(['tramite_id', 'nro_control', 'created_by']);

        $createdByIDs = $codificados->pluck('created_by');

        $usuarios = SysUsers::whereIn('id', $createdByIDs)->get(['id', 'first_name', 'last_name']);

        $tramiteIDs = $codificados->pluck('tramite_id');


        $tramites = Tramites::whereIn('tramite_id', $tramiteIDs)->get(['tramite_id', 'nro_doc','sexo']);

        $codificados = $codificados->map(function ($codificado) use ($usuarios, $tramites) {
          

            $usuario = $usuarios->where('id', $codificado->created_by)->first();
            if ($usuario) {
                $nombre = $usuario->first_name; 
                $apellido = $usuario->last_name; 
                $codificado->created_by = $nombre . ' ' . $apellido; 
            } else {
                $codificado->created_by = 'Desconocido'; 
            }

            $tramite = $tramites->where('tramite_id', $codificado->tramite_id)->first();
            if ($tramite) {
                $nro_doc = $tramite->nro_doc; 
                $sexo = $tramite->sexo;
                $codificado->nro_doc = $nro_doc;
                $codificado->sexo = $sexo;
            } else {
                $codificado->nro_doc = 'N.C'; 
            }

            return $codificado;
        });

        $numeroKit = $lote->nro_kit;

        return response()->json([
            'codificados' => $codificados,
            'numeroKit' => $numeroKit
        ]);
    }


















    public function obtenerDescartes(Request $request)
    {
        $loteId = $request->input('loteId');

        $lote = AnsvLotes::where('lote_id', $loteId)->first();

        $descartes = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
            ->distinct()
            ->get(['control', 'descripcion', 'created_by']);

        $createdByIDs = $descartes->pluck('created_by')->unique();

     
        $usuarios = SysUsers::whereIn('id', $createdByIDs)->get(['id', 'first_name', 'last_name']);

        // Obtener los datos de la tabla AnsvControl
        $codificados = AnsvControl::whereIn('nro_control', $descartes->pluck('control'))->get(['nro_control', 'tramite_id']);

        // Obtener los datos de la tabla Tramites
        $tramites = Tramites::whereIn('tramite_id', $codificados->pluck('tramite_id'))->get(['tramite_id', 'nro_doc']);

        // Combinar los datos de descartes, usuarios, codificados y tramites
        $descartes = $descartes->map(function ($descarte) use ($usuarios, $codificados, $tramites) {
            $nombre = 'Desconocido';
            $apellido = '';

            if ($descarte->created_by) {
                $usuario = $usuarios->where('id', $descarte->created_by)->first();

            if ($usuario) {
                $nombre = $usuario->first_name; // Obtener el nombre del usuario
                $apellido = $usuario->last_name; // Obtener el apellido del usuario
            }
        }

        $codificado = $codificados->where('nro_control', $descarte->control)->first();
        $tramite = $tramites->firstWhere('tramite_id', optional($codificado)->tramite_id);

        return [
            'control' => $descarte->control,
            'created_by' => $nombre . ' ' . $apellido, // Combinar nombre y apellido
            'descripcion' => $descarte->descripcion,
            'nro_doc' => optional($tramite)->nro_doc ?: 'N.C',
            'tramite_id' => optional($codificado)->tramite_id ?: 'N.C',
        ];
    });

    $numeroKit = $lote->nro_kit;

    return response()->json([
        'descartes' => $descartes,
        'numeroKit' => $numeroKit,
    ]);
}


    public function obtenerDescartes23(Request $request)
    {
        $loteId = $request->input('loteId');

        // Obtener el rango de control_desde y control_hasta del lote
        $lote = AnsvLotes::where('lote_id', $loteId)->first();

        // Obtener los descartes dentro del rango de control_desde y control_hasta
        $descartes = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
            ->distinct()
            ->get(['control', 'descripcion', 'created_by']);

        $descartes = $descartes->map(function ($descarte) {
            $nombre = 'Desconocido';
            $apellido = '';
            $tramiteId = 'N.C';

            if ($descarte->created_by) {
                $usuario = SysUsers::find($descarte->created_by);

                if ($usuario) {
                    $nombre = $usuario->first_name; // Obtener el nombre del usuario
                    $apellido = $usuario->last_name; // Obtener el apellido del usuario
                }
            }

            $codificado = AnsvControl::where('nro_control', $descarte->control)
                ->first();

            if ($codificado) {
                $tramiteId = $codificado->tramite_id;
            }

            return [
                'control' => $descarte->control,
                'created_by' => $nombre . ' ' . $apellido, // Combinar nombre y apellido
                'descripcion' => $descarte->descripcion,
                'nro_doc' => 'N.C',
                'tramite_id' => $tramiteId,
            ];
        });

        $numeroKit = $lote->nro_kit;

        return response()->json([
            'descartes' => $descartes,
            'numeroKit' => $numeroKit,
        ]);
    }


    public function obtenerDescartes22(Request $request)
    {
        $loteId = $request->input('loteId');

        // Obtener el rango de control_desde y control_hasta del lote
        $lote = AnsvLotes::where('lote_id', $loteId)->first();

        // Obtener los descartes dentro del rango de control_desde y control_hasta
        $descartes = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
            ->distinct()
            ->get(['control','descripcion']);

        // Obtener los codificados asociados a los descartes
        $codificados = AnsvControl::whereIn('nro_control', $descartes->pluck('control'))
        ->with('tramite') // Cargar la relación 'tramite'
        ->get(['nro_control', 'tramite_id', 'created_by']);

        $descartes = $descartes->map(function ($descarte) use ($codificados) {
            $codificado = $codificados->where('nro_control', $descarte->control)->first();

            $nro_doc = 'N.C';

            if ($codificado && $codificado->tramite) {
                $nro_doc = $codificado->tramite->nro_doc;
            }

            return [
                'tramite_id' => $codificado ? $codificado->tramite_id : 'N.C',
                'control' => $descarte->control,
                'created_by' => $codificado ? $codificado->created_by : 'N.C',
                'descripcion' => $descarte->descripcion,
                'nro_doc' => $nro_doc,
            ];
        });

        $numeroKit = $lote->nro_kit;

        return response()->json([
            'descartes' => $descartes,
            'numeroKit' => $numeroKit,
        ]);
    }



    public function obtenerBlancos(Request $request)
    {
        $loteId = $request->input('loteId');

        $lote = AnsvLotes::where('lote_id', $loteId)->first();


        if (!$lote) {
            return response()->json(['error' => 'Lote no encontrado'], 404);
        }

        $descartados = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
            ->distinct()
            ->pluck('control');

        $codificados = AnsvControl::whereBetween('nro_control', [$lote->control_desde, $lote->control_hasta])
            ->where('liberado', false)
            ->pluck('nro_control');

        $blancos = [];

        for ($i = $lote->control_desde; $i <= $lote->control_hasta; $i++) {
            if (!$descartados->contains($i) && !$codificados->contains($i)) {
                $blancos[] = $i;
            }
        }

        $cantidadBlancos = count($blancos);
        // dd($cantidadBlancos);

        return response()->json([
            'cantidadBlancos' => $cantidadBlancos,
            'blancos' => $blancos,
            'numeroKit' => $lote->nro_kit
        ]);
    }


    public function exportarExcel(Request $request)
    {
        $lotesImpresos = $request->session()->get('lotesImpresos', []);
        $sucursalSeleccionada = $request->input('sucursal');
        $sucursal = SysMultivalue::where('type', 'SUCU')
                ->where('id', $sucursalSeleccionada)
                ->first();

        $sucursalDescripcion = $sucursal ? $sucursal->description : 'TODOS';

        $fechaActual = date('d/m/Y');
        $paginaActual = $request->input('page', 1);



        $tempFile = tmpfile();
        $csvHeaders = ['lote_id', 'sucursal', 'control_desde', 'control_hasta', 'cantidadLote', 'cantidadImpresos','cantidadDescartados', 'cantidadBlancos', 'nroKit'];
        fputcsv($tempFile, $csvHeaders);

        // Escribir los datos en el archivo temporal
        foreach ($lotesImpresos as $loteImpreso) {
            $rowData = [
                $loteImpreso['lote_id'],
                $loteImpreso['sucursal'],
                $loteImpreso['control_desde'],
                $loteImpreso['control_hasta'],
                $loteImpreso['cantidadLote'],
                $loteImpreso['cantidadImpresos'],
                $loteImpreso['cantidadDescartados'],
                $loteImpreso['cantidadBlancos'],
                $loteImpreso['nroKit'],
                // $loteImpreso['nroCaja'],
            ];
            fputcsv($tempFile, $rowData);
        }

        // Obtener el contenido del archivo temporal
        fseek($tempFile, 0);
        $fileContent = stream_get_contents($tempFile);
        $fileName = "{Sucursal:{$sucursalDescripcion}}_{Fecha:{$fechaActual}}_{Pagina:{$paginaActual}}.csv";

        // Crear la respuesta con el archivo CSV adjunto
        $response = response($fileContent, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"$fileName\"");

        // Cerrar y eliminar el archivo temporal
        fclose($tempFile);

        return $response;
    }


    public function exportarExcel2(Request $request)
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

            $lotesSucursal = AnsvLotes::noEliminados()
        ->when($sucursalSeleccionada, function ($query) use ($sucursalSeleccionada) {
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

        $tempFile = tmpfile();
        $csvHeaders = ['lote_id', 'sucursal', 'control_desde','control_hasta', 'cantidadLote', 'cantidadBlancos', 'cantidadDescartados', 'cantidadImpresos', 'nroKit', 'nroCaja'];
        fputcsv($tempFile, $csvHeaders);

        // Escribir los datos en el archivo temporal
        foreach ($lotesImpresos as $loteImpreso) {
            $rowData = [
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
            fputcsv($tempFile, $rowData);
        }

        // Obtener el contenido del archivo temporal
        fseek($tempFile, 0);
        $fileContent = stream_get_contents($tempFile);

        // Crear la respuesta con el archivo CSV adjunto
        $response = Response::make($fileContent, 200);
        $response->header('Content-Type', 'text/csv');
        $response->header('Content-Disposition', 'attachment; filename="Lotes.csv"');

        // Cerrar y eliminar el archivo temporal
        fclose($tempFile);

        return $response;

    }

    public function descargarCSV(Request $request)
    {
        $loteId = $request->input('loteId');

        $lote = AnsvLotes::where('lote_id', $loteId)->first();

        $descartados = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
            ->distinct()
            ->get(['control']);

        if (!$lote) {
            return response()->json(['error' => 'Lote no encontrado'], 404);
        }

        $controlDesde = $lote->control_desde;
        $controlHasta = $lote->control_hasta;

        $codificados = AnsvControl::whereBetween('nro_control', [$controlDesde, $controlHasta])
            ->where('liberado', false)
            ->whereNotIn('nro_control', $descartados->pluck('control'))
            ->get(['tramite_id', 'nro_control', 'created_by']);

        // Obtener los nombres y apellidos de las personas asociadas a los IDs de 'created_by'
        $createdByIDs = $codificados->pluck('created_by');
        $usuarios = SysUsers::whereIn('id', $createdByIDs)->get(['id', 'first_name', 'last_name']);

        // Obtener las descripciones y nros de doc asociados a los tramite_id
        $tramites = Tramites::whereIn('tramite_id', $codificados->pluck('tramite_id'))->get(['tramite_id', 'nro_doc', 'sexo']);
        $descartes = AnsvDescartes::whereIn('control', $codificados->pluck('nro_control'))->get(['control', 'descripcion']);

        // Combinar los datos de codificados, tramites y descartes
        $codificados = $codificados->map(function ($codificado) use ($usuarios, $tramites, $descartes) {
            $usuario = $usuarios->where('id', $codificado->created_by)->first();
            $tramite = $tramites->where('tramite_id', $codificado->tramite_id)->first();
            $descarte = $descartes->where('control', $codificado->nro_control)->first();

            $nombre = $usuario ? $usuario->first_name : 'Desconocido';
            $apellido = $usuario ? $usuario->last_name : '';
            $nro_doc = $tramite ? $tramite->nro_doc : 'N.C';

            return [
                'tramite_id' => $codificado->tramite_id,
                'nro_control' => $codificado->nro_control,
                'created_by' => $nombre . ' ' . $apellido,
                'nro_doc' => $nro_doc,
                'sexo' => $tramite ? $tramite->sexo : 'N.C'
            ];
        });

        $nro_kit =$lote->nro_kit;
        
        // Crear un archivo temporal
        $tempFile = tmpfile();
        $csvHeaders = ['Trámite ID', 'Número de Control', 'Creado por','Nro. Doc', 'Sexo'];
        fputcsv($tempFile, $csvHeaders);

        // Escribir los datos en el archivo temporal
        foreach ($codificados as $codificado) {
            $rowData = [
                $codificado['tramite_id'],
                $codificado['nro_control'],
                $codificado['created_by'],
                $codificado['nro_doc'],
                $codificado['sexo']
            ];
            fputcsv($tempFile, $rowData);
        }

        // Obtener el contenido del archivo temporal
        fseek($tempFile, 0);
        $fileContent = stream_get_contents($tempFile);

        // Crear la respuesta con el archivo CSV adjunto
        $response = Response::make($fileContent, 200);
        $response->header('Content-Type', 'text/csv');
        $response->header('Content-Disposition', 'attachment; filename="' . $nro_kit . '.csv"');

        // Cerrar y eliminar el archivo temporal
        fclose($tempFile);

        return $response;
    }

    public function descargarCSV2(Request $request)
    {
        $loteId = $request->input('loteId');

        // Obtener el rango de control_desde y control_hasta del lote
        $lote = AnsvLotes::where('lote_id', $loteId)->first();

        // Obtener los descartes dentro del rango de control_desde y control_hasta
        $descartes = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
            ->distinct()
            ->get(['control', 'descripcion', 'created_by']);

        $createdByIDs = $descartes->pluck('created_by')->unique();

        // Obtener los nombres de los usuarios correspondientes al campo created_by
        $usuarios = SysUsers::whereIn('id', $createdByIDs)->get(['id', 'first_name', 'last_name']);

        // Obtener los datos de la tabla AnsvControl
        $codificados = AnsvControl::whereIn('nro_control', $descartes->pluck('control'))->get(['nro_control', 'tramite_id']);

        // Obtener los datos de la tabla Tramites
        $tramites = Tramites::whereIn('tramite_id', $codificados->pluck('tramite_id'))->get(['tramite_id', 'nro_doc']);

        // Combinar los datos de descartes, usuarios, codificados y tramites
        $descartes = $descartes->map(function ($descarte) use ($usuarios, $codificados, $tramites) {
            $nombre = 'Desconocido';

            if ($descarte->created_by) {
                $usuario = $usuarios->where('id', $descarte->created_by)->first();

                if ($usuario) {
                    $nombre = $usuario->first_name . ' ' . $usuario->last_name; // Combinar nombre y apellido
                }
            }

            $codificado = $codificados->where('nro_control', $descarte->control)->first();
            $tramite = $tramites->firstWhere('tramite_id', optional($codificado)->tramite_id);

            return [
                'tramite_id' => optional($codificado)->tramite_id ?: 'N.C',
                'control' => $descarte->control,
                'created_by' => $nombre,
                'descripcion' => $descarte->descripcion,
                'nro_doc' => optional($tramite)->nro_doc ?:'N.C',
            ];
        });

        $tempFile = tmpfile();

        $csvHeaders = ['Trámite ID', 'Número de Control', 'Creado por', 'Descripción', 'Nro. Doc'];
        fputcsv($tempFile, $csvHeaders);

        foreach ($descartes as $descarte) {
            $rowData = [
                $descarte['tramite_id'],
                $descarte['control'],
                $descarte['created_by'],
                $descarte['descripcion'],
                $descarte['nro_doc'],
            ];

            fputcsv($tempFile, $rowData);
        }

        fseek($tempFile, 0);
        $fileContent = stream_get_contents($tempFile);

        $response = response($fileContent, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="codificados.csv"');

        fclose($tempFile);

        return $response;
    }


    public function descargarCSV3(Request $request)
    {
        $loteId = (int)$request->input('loteId');

        $lote = AnsvLotes::where('lote_id', $loteId)->first();

        if (!$lote) {
            return response()->json(['error' => 'Lote no encontrado'], 404);
        }

        $descartados = AnsvDescartes::whereBetween('control', [$lote->control_desde, $lote->control_hasta])
            ->distinct()
            ->pluck('control');

        $codificados = AnsvControl::whereBetween('nro_control', [$lote->control_desde, $lote->control_hasta])
            ->where('liberado', false)
            ->pluck('nro_control');

        $blancos = [];

        for ($i = $lote->control_desde; $i <= $lote->control_hasta; $i++) {
            if (!$descartados->contains($i) && !$codificados->contains($i)) {
                $blancos[] = $i;
            }
        }

        $csvContent = implode("\n", $blancos);

        $fileName = 'blancos_' . $loteId . '_' . date('Y-m-d') . '.csv';

        // Crear la respuesta con el archivo CSV adjunto
        $response = Response::make($csvContent, 200);
        $response->header('Content-Type', 'text/csv');
        $response->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

}