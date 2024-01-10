<?php

namespace App\Http\Controllers;

use App\AnsvControl;
use App\AnsvDescartes;
use Illuminate\Support\Facades\DB;
use App\AnsvLotes;
use App\AnsvTramite;
use App\DatosPersonales;
use App\SysMultivalue;
use App\Tramites;
use Illuminate\Http\Request;


class ReportesControlDescartes extends Controller
{

    public function mostrarInformeDescartes(Request $request)
    {
        // Lógica para obtener datos y mostrar la vista del informe
        $control= $request -> input('numero_control');

        $controlBuscado = AnsvDescartes::where('control', $control)
            ->orderBy('creation_date', 'desc')
            ->get();

        $descartes = AnsvDescartes::orderBy('control', 'desc')
        ->paginate(20);

        return view('reportesDescartes.reportesDescartes' , [
            'descartes' => $descartes,
            'controlBuscado' => $controlBuscado,
        ]);
    }

    public function descartarInsumo(Request $request)
    {
        return view('reportesDescartes.descartar');
    }


    public function insertarDescarte(Request $request)
    {
        $request->validate([
            'nro_control' => 'required|numeric',
            'descripcion' => 'required|string',

        ]);

        $nroControl = $request->input('nro_control');
        $fechaActual = now();

        $controlNoEnRango = !AnsvLotes::where(function($query) use ($nroControl) {
            $query->where('control_desde', '=', $nroControl)
                ->orWhere('control_hasta', '=', $nroControl)
                ->orWhere(function($innerQuery) use ($nroControl) {
                    $innerQuery->where('control_desde', '<=', $nroControl)
                                ->where('control_hasta', '>=', $nroControl);
                });
        })->exists();

        if ($controlNoEnRango) {
            // El número de control no existe en ningún lote
            return redirect()->back()
                ->withErrors(['nro_control' => 'El número de control no existe en ningún lote.'])
                ->withInput();
        }

        $descartesExiste = AnsvDescartes::where('control', $nroControl)->get();
        $descartesEnUso = AnsvControl::where('nro_control', $nroControl)->get();

        $user = auth()->user();
        $sucursalUsuario =  $user->sucursal;

        $descartesActivos = AnsvDescartes::where('control', $nroControl)
            ->whereNull('end_date')
            ->get();

        if ($descartesActivos->count() > 0) {
            return redirect()->route('descartarInsumo')->withErrors(['nro_control' => 'El número de control ya está descartado.']);
        }

        $data = [
            'control' => $nroControl,
            'motivo' => 97,
            'creation_date' => $fechaActual,
            'created_by' => auth()->user()->id,
            'descripcion' => $request->input('descripcion'),
        ];

        $loteSucursal = DB::table('ansv_lotes')
        ->select('sucursal_id')
        ->where('control_desde', '<=', $nroControl)
        ->where('control_hasta', '>=', $nroControl)
        ->whereNull('end_date')
        ->orderByDesc('creation_date')
        ->value('sucursal_id');
        
        if ($descartesEnUso->count() > 0 && $sucursalUsuario === $loteSucursal) {
            DB::table('ansv_descartes')->insert($data);
            $liberadoTrue = AnsvControl::where('nro_control', $nroControl)->update([
                'liberado' => true,
            ]);
        } 
        
        elseif ($sucursalUsuario === $loteSucursal) {

            DB::table('ansv_descartes')->insert($data);

        }
        else {
            return redirect()->back()
                ->withErrors(['nro_control' => 'No puedes descartar insumos de otra sucursal.'])
                ->withInput();
        }

        return redirect()->route('informe-descartes')->with('success', 'Lote creado correctamente');
    }
    

    public function consultarDatos(Request $request)
    {
        $nroControl = $request->input('nro_control');

        $descartesExiste = AnsvDescartes::where('control', $nroControl)->get();
        $descartesEnUso = AnsvControl::where('nro_control', $nroControl)->get();

        if($descartesEnUso->count() > 0){

            $primerElemento = $descartesEnUso->first();
            $tramiteId = $primerElemento->tramite_id;
            $tramites = Tramites::where('tramite_id', $tramiteId)->get();

            $a = $tramites->first();
            $nro_doc = $a->nro_doc;
            
            $datosUsuario = DatosPersonales::where('nro_doc', $nro_doc)->get();
            $usuarioDatos = $datosUsuario->first();
            
            $sexo = $usuarioDatos->sexo;
            $nombre = $usuarioDatos->nombre;
            $apellido = $usuarioDatos->apellido;
            $nro_doc = $usuarioDatos->nro_doc;
            
            $loteSucursal = DB::table('ansv_lotes')
            ->select('sucursal_id')
            ->where('control_desde', '<=', $nroControl)
            ->where('control_hasta', '>=', $nroControl)
            ->whereNull('end_date')
            ->orderByDesc('creation_date')
            ->value('sucursal_id');

            $nombreSucursal = DB::table('sys_multivalue')
            ->select('type', 'id', 'description', 'text_id', 'rowid')
            ->where('type', 'SUCU')
            ->where('id', $loteSucursal)
            ->get();
        
            $descripcion = $nombreSucursal->first()->description;
                
            $enuso = 'si';
            $descartado = 'no';

            if ($descartesExiste->count() > 0) {
                $descartado = 'si';
            }
        
            $datos = [
                'descartado' => $descartado,
                'enuso' => $enuso,
                'tramite_id' => $tramiteId,
                'sucursal_id' => $descripcion,
                'genero' => $sexo,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'documento' => $nro_doc
            ];
        
            return response()->json($datos);  

        } else {

            $enuso = 'no';
            $descartado = 'no';
            
            if ($descartesExiste->count() > 0) {
                $descartado = 'si';
            }

            $datos = [
                'descartado' => $descartado,
                'enuso' => $enuso,
                'tramite_id' => 'no esta asociado a un tramite ',
                'genero' => 'no esta asociado a un tramite',
                'nombre' => 'no esta asociado a un tramite',
                'apellido' => 'no esta asociado a un tramite',
                'documento' => 'no esta asociado a un tramite'
            ];
        
            return response()->json($datos);
        }
        
    }

}
