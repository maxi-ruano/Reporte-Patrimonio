<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\AnsvLotes;
use App\Tramites;
use App\AnsvControl;
use App\AnsvDescartes;
use App\SysMultivalue;
use App\User;
use App\SysUsers;


class ReportesController2 extends Controller
{

    public function __construct()
    {
        $this->middleware(['permission:view_insumos|view_insumos_all']);
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
    $Todassucursales = SysMultivalue::where('type', 'SUCU')->get();
    $lotesImpresos = [];


    $lotesSucursalQuery = AnsvLotes::noEliminados()->when($sucursalSeleccionada, function ($query) use ($sucursalSeleccionada) {
        return $query->where('sucursal_id', $sucursalSeleccionada);
    })->orderByDesc('lote_id');

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


    $request->session()->put('lotesImpresos', $lotesImpresos);


    return view('reportes.reportesControlInsumos2', [
        'sucursales' => $sucursaless,
        'sucursalSeleccionada' => $sucursalSeleccionada,
        'Todassucursales' => $Todassucursales,
        'lotesImpresos' => $lotesImpresos,
        'lotesSucursal' => $lotesSucursal,
    ]);
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


