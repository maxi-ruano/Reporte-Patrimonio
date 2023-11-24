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

use App\AnsvDescartes;
use App\SysMultivalue;
use App\User;
use App\SysUsers;
use Illuminate\Support\Facades\Auth;

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
    
        // Obtener los rangos de números de control de los lotes seleccionados
        // $rangosControl = AnsvLotes::whereIn('lote_id', $selectedItems)
        //     ->select('control_desde', 'control_hasta')
        //     ->get();
    
          
        //     $numerosControl = [];
        //     foreach ($rangosControl as $rango) {
        //         for ($i = $rango->control_desde; $i <= $rango->control_hasta; $i++) {
        //             $numerosControl[] = $i;
        //         }
        //     }
        
        //     $numerosControlEnUso = AnsvControl::whereIn('nro_control', $numerosControl)->exists();
        //     $numerosControlDescartados = AnsvDescartes::whereIn('control', $numerosControl)->exists();
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

            //  return redirect()->route('reporte.control.insumos')->with('success', 'Lote eliminado correctamente');
             return redirect()->back();
        }




          

        } elseif ($accion === 'editar_lote') {

            $selectedItems = $request->input('selectedItems', []);
            if (empty($selectedItems)) {
                $request->session()->flash('warning', 'Por favor, selecciona al menos un lote antes de realizar la acción.');
                return redirect()->route('reporte.control.insumos');
            }
            // return view('reportes.editar_lote', compact('selectedItems'));
            $lote_id = $selectedItems[0];
            return redirect()->route('editar.lote', ['lote_id' => $lote_id]);

        } elseif ($accion == 'Elegir accion') {

        return back()->withErrors(['Por favor elige una acción'])->withInput();

        }
    }



    




   
 

    public function guardarLote(Request $request)
    {
        // Validar los datos del formulario (opcional, pero recomendado)
    
        $controlDesde = $request->input('nro_control_desde');
        $controlHasta = $request->input('nro_control_hasta');
    
      
    
        // $existingLote = DB::table('ansv_lotes')
        // ->where(function ($query) use ($controlDesde, $controlHasta) {
        //     $query->where(function ($query) use ($controlDesde, $controlHasta) {
        //         $query->where('control_desde', '<=', $controlHasta)
        //             ->where('control_hasta', '>=', $controlDesde);
        //     })
        //     ->orWhere(function ($query) use ($controlDesde, $controlHasta) {
        //         $query->where('control_desde', '<=', $controlHasta)
        //             ->where('control_hasta', '>=', $controlHasta);
        //     });
        // })
        // ->where(function ($query) {
        //     $query->whereNull('end_date'); // Solo registros con end_date nulo
        //     // O puedes agregar otras condiciones para filtrar registros eliminados
        // })
        // ->exists();


    //     $existingLote = DB::table('ansv_lotes')
    // ->where(function ($query) use ($controlDesde, $controlHasta) {
    //     $query->where(function ($query) use ($controlDesde, $controlHasta) {
    //         $query->where('control_desde', '<=', $controlHasta)
    //             ->where('control_hasta', '>=', $controlDesde);
    //     })
    //     ->orWhere(function ($query) use ($controlDesde, $controlHasta) {
    //         $query->where('control_desde', '<=', $controlHasta)
    //             ->where('control_hasta', '>=', $controlHasta);
    //     });
    // })
    // ->where(function ($query) {
    //     $query->whereNull('end_date'); // Solo registros con end_date nulo
    //     // O puedes agregar otras condiciones para filtrar registros eliminados
    // })
    // ->orWhere(function ($query) use ($controlDesde, $controlHasta) {
    //     $query->where('control_desde', $controlDesde)
    //         ->where('control_hasta', $controlHasta)
    //         ->whereNull('end_date'); // Para evitar duplicados exactos
    // })
    // ->exists();


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
    










    //     $nroKitExists = DB::table('ansv_lotes')
    //     ->where('nro_kit', $request->input('nro_kit'))
    //     ->exists();

    // if ($nroKitExists) {
    //     $sucursaless = SysMultivalue::where('type', 'SUCU')->get();
    //     $Todassucursales = SysMultivalue::where('type', 'SUCU')->get();
    //     $sucursalSeleccionada = $request->sucursal;
    //     $error = 'El nro_kit ya está en uso. Debe ser único.';
    //     return view('reportes.crear_lote', compact('sucursaless', 'Todassucursales', 'sucursalSeleccionada', 'error'));
        
    // }
    
        // Continuar con la creación del lote si no hay superposiciones
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
    
        // Insertar los datos en la tabla ansv_lotes
        DB::table('ansv_lotes')->insert($data);
    
        // Redirigir a la página de éxito o a donde desees después de guardar el lote
        return redirect()->route('reporte.control.insumos')->with('success', 'Lote creado correctamente');
    }
    
















    public function editarLote($lote_id)
    {
        // Recupera el lote que se va a editar
        $lote = AnsvLotes::find($lote_id);

        // dd($lote);
    //    dd($lote->control_desde) ;
        return view('reportes.editar_lote', compact('lote'));
    }

     public function actualizarLote(Request $request, $lote_id)
    {
        // Encuentra el lote que se va a actualizar
        $lote = AnsvLotes::find($lote_id);
    
        // Actualiza los campos con los valores enviados desde el formulario
        $lote->control_desde = $request->input('nro_control_desde');
        $lote->control_hasta = $request->input('nro_control_hasta');
        $lote->nro_kit = $request->input('nro_kit');
    
        // Guarda los cambios en la base de datos
        $lote->save();
    
        // Redirige a la vista de edición con un mensaje de éxito
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

    // dd($sucursaless);

    $sucursalSeleccionada = $request->sucursal;
    //$Todassucursales = SysMultivalue::where('type', 'SUCU')->get();

    $ids = [1,10,190,194,195,140,60,50,70,40,160,120,110,192,130,103,100,150,105,180,106,41,131,197];

$Todassucursales = SysMultivalue::where('type', 'SUCU')
    ->whereIn('id', $ids)
    ->get();

    // $Todassucursales = SysMultivalue::where('type', 'SUCU') ->get();

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


    // Filtrar por número de kit si se ha proporcionado
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
            ->whereNotIn('nro_control', $descartados->pluck('control')) // Excluir los descartes de la consulta
            ->count('nro_control');

        $cantidadBlancos = $cantidadLote - ($cantidadImpresos + $cantidadDescartados);

        $nroKit = $lote->getAttribute('nro_kit');
        $nroCaja = $lote->getAttribute('nro_caja');


        // dd($habilitado);

        $lotesImpresos[] = [
            'sucursal' => $sucursaless->where('id', $lote->sucursal_id)->first()->description,
            // 'sucursal' => $sucursaless->where('id', $lote->sucursal_id),

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
    $datosLotes = DB::select('SELECT id, nro_control_desde, nro_control_hasta, fecha_recibido_nacion, fecha_habilitado_sede, fecha_recibido_sede, creation_by, modification_date , nro_kit FROM public.ansv_lotes_patrimonio');

    $ids = [1,10,190,194,195,140,60,50,70,40,160,120,110,192,130,103,100,150,105,180,106,41,131,197];
    $todasSucursales = SysMultivalue::where('type', 'SUCU')
        ->whereIn('id', $ids)
        ->get();

        // dd($todasSucursales);
        return view('patrimonio.reportesControlInsumos2', [
            'datosLotes' => $datosLotes,
            'todasSucursales' => $todasSucursales
        ]);

    // return view('patrimonio.reportesControlInsumos2', ['datosLotes' => $datosLotes]);
}




// public function asignarLotePatrimonio(Request $request) {
//     // Validar los datos del formulario según tus necesidades

//     // Obtener el array de selecciones de lotes
//     $seleccionLotes = $request->input('seleccion');

//     // Obtener la sucursal seleccionada
//     $sucursalId = $request->input('sucursal');

//     foreach ($seleccionLotes as $loteId) {
//         $infoLote = AnsvLotesPatrimonio::select('id', 'nro_control_desde', 'nro_control_hasta', 'nro_kit')
//             ->where('id', $loteId)
//             ->first();

//         if ($infoLote) {
//             // Obtener el último lote_id en la tabla ansv_lotes
//             $ultimoLote = AnsvLotes::latest('lote_id')->first();

//             // Calcular el nuevo lote_id sumándole 1 al último
//             $nuevoLoteId = $ultimoLote ? $ultimoLote->lote_id + 1 : 1;

//             // Verificar si el rango de control desde y hasta ya existe en ansv_lotes
//             $existeRango = AnsvLotes::where('control_desde', $infoLote->nro_control_desde)
//                 ->where('control_hasta', $infoLote->nro_control_hasta)
//                 ->exists();

//             if (!$existeRango) {
//                 AnsvLotes::create([
//                     'lote_id' => $nuevoLoteId,
//                     'sucursal_id' => $sucursalId,
//                     'control_desde' => $infoLote->nro_control_desde,
//                     'control_hasta' => $infoLote->nro_control_hasta,
//                     'habilitado' => false,
//                     'created_by' => null,
//                     'creation_date' => now(),
//                     'modified_by' => null,
//                     'modification_date' => now(),
//                     'end_date' => null,
//                     'nro_kit' => $infoLote->nro_kit
//                 ]);
//             }else {
//                 return redirect()->back()->withErrors(['error' => 'El rango ya está asignado a una sede.']);
//             }
//         }
//     }

//     return redirect()->back()->with('success', 'Lotes asignados correctamente');
// }



public function asignarLotePatrimonio(Request $request) {
    // Validar los datos del formulario según tus necesidades

    // Obtener el array de selecciones de lotes
    $seleccionLotes = $request->input('seleccion');

    // Verificar si se seleccionaron lotes antes de intentar el foreach
    if (!empty($seleccionLotes)) {
        // Obtener la sucursal seleccionada
        $sucursalId = $request->input('sucursal');

        foreach ($seleccionLotes as $loteId) {
            $infoLote = AnsvLotesPatrimonio::select('id', 'nro_control_desde', 'nro_control_hasta', 'nro_kit')
                ->where('id', $loteId)
                ->first();

            if ($infoLote) {
                // Obtener el último lote_id en la tabla ansv_lotes
                $ultimoLote = AnsvLotes::latest('lote_id')->first();

                // Calcular el nuevo lote_id sumándole 1 al último
                $nuevoLoteId = $ultimoLote ? $ultimoLote->lote_id + 1 : 1;

                // Verificar si el rango de control desde y hasta ya existe en ansv_lotes
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
                    return redirect()->back()->withErrors(['error' => 'El rango ya está asignado a una sede.']);
                }
            }
        }

        return redirect()->back()->with('success', 'Lotes asignados correctamente');
    } else {
        // Manejar el caso en que no se seleccionaron lotes
        return redirect()->back()->withErrors(['error' => 'No se han seleccionado lotes para asignar.']);
    }
}
















public function guardarLotePatrimonio(Request $request) {
    // Validar los datos del formulario según sea necesario

    // Obtener el ID del usuario autenticado
    $userId = Auth::user()->id;

    // Obtener el rango del nuevo lote
    $nuevoDesde = $request->input('nro_control_desde');
    $nuevoHasta = $request->input('nro_control_hasta');

    // Verificar si ya existe un registro con solapamiento en los rangos
    $existeSolapamiento = DB::table('ansv_lotes_patrimonio')
        ->where(function ($query) use ($nuevoDesde, $nuevoHasta) {
            $query->whereBetween('nro_control_desde', [$nuevoDesde, $nuevoHasta])
                  ->orWhereBetween('nro_control_hasta', [$nuevoDesde, $nuevoHasta]);
        })
        ->orWhere(function ($query) use ($nuevoDesde, $nuevoHasta) {
            $query->where('nro_control_desde', '<=', $nuevoDesde)
                  ->where('nro_control_hasta', '>=', $nuevoHasta);
        })
        ->exists();

    if ($existeSolapamiento) {
        // Si existe solapamiento, mostrar un mensaje de error y redirigir de nuevo al formulario
        // return redirect()->back()->withErrors(['error' => 'Ya existe un registro con solapamiento en los rangos de control.'])->withInput();
        return redirect()->route('cargarLote')->withErrors(['error' => 'Ya existe un registro con solapamiento en los rangos de control.'])->withInput();

    }

    // Si no existe solapamiento, proceder con la inserción
    DB::table('ansv_lotes_patrimonio')->insert([
        'nro_control_desde' => $nuevoDesde,
        'nro_control_hasta' => $nuevoHasta,
        'fecha_recibido_nacion' => $request->input('fecha_recibido_nacion'),
        'fecha_habilitado_sede' => $request->input('fecha_habilitado_sede'),
        'fecha_recibido_sede' => $request->input('fecha_recibido_sede'),
        'creation_by' => $userId,
        'modification_date' => $request->input('modification_date'),
        'nro_kit' => $request->input('nro_kit'),
    ]);

    // Redirigir a la vista deseada después de una inserción exitosa
    // return view('patrimonio.cargar_lote');
    return redirect()->route('cargarLote')->with('success', 'Lote creado correctamente.');

}



public function sucursalesPatrimonio() {
    // Obtener todas las sucursales desde la base de datos
    $ids = [1,10,190,194,195,140,60,50,70,40,160,120,110,192,130,103,100,150,105,180,106,41,131,197];
    $todasSucursales = SysMultivalue::where('type', 'SUCU')
        ->whereIn('id', $ids)
        ->get();

// dd($todasSucursales);

    // Pasar las sucursales a la vista
    return view('patrimonio.reportesControlInsumos2', ['todasSucursales' => $todasSucursales]);
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

    // Obtener los nombres de las personas asociadas a los IDs de 'created_by'
    $createdByIDs = $codificados->pluck('created_by');

    // $usuarios = User::whereIn('sys_user_id', $createdByIDs)->get(['sys_user_id', 'name']);
    $usuarios = SysUsers::whereIn('id', $createdByIDs)->get(['id', 'first_name', 'last_name']);

    // Obtener los nro_doc de los tramites asociados a los codificados
    $tramiteIDs = $codificados->pluck('tramite_id');


    $tramites = Tramites::whereIn('tramite_id', $tramiteIDs)->get(['tramite_id', 'nro_doc','sexo']);

    // Reemplazar los IDs por los nombres y nro_doc correspondientes en el resultado
    $codificados = $codificados->map(function ($codificado) use ($usuarios, $tramites) {
        // $usuario = $usuarios->where('sys_user_id', $codificado->created_by)->first();
        // if ($usuario) {
        //     $nombre = $usuario->name; // Obtener el nombre del usuario
        //     $codificado->created_by = $nombre;
        // } else {
        //     $created_by =   $codificado->created_by;
        //     $codificado->created_by = $created_by; // Establecer 'Desconocido' si no se encuentra el usuario
        // }

        $usuario = $usuarios->where('id', $codificado->created_by)->first();
if ($usuario) {
    $nombre = $usuario->first_name; // Obtener el nombre del usuario
    $apellido = $usuario->last_name; // Obtener el apellido del usuario
    $codificado->created_by = $nombre . ' ' . $apellido; // Combinar nombre y apellido
} else {
    $codificado->created_by = 'Desconocido'; // Establecer 'Desconocido' si no se encuentra el usuario
}

        $tramite = $tramites->where('tramite_id', $codificado->tramite_id)->first();
        if ($tramite) {
            $nro_doc = $tramite->nro_doc; // Obtener el nro_doc del tramite
            $sexo = $tramite->sexo;
            $codificado->nro_doc = $nro_doc;
            $codificado->sexo = $sexo;
        } else {
            $codificado->nro_doc = 'N.C'; // Establecer 'N.C' si no se encuentra el tramite
        }

        return $codificado;
    });

    // return response()->json($codificados);
    $numeroKit = $lote->nro_kit;

    return response()->json([
        'codificados' => $codificados,
        'numeroKit' => $numeroKit
    ]);
}




public function obtenerDescartes(Request $request)
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


