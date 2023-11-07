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

// dd($control);


// $controlBuscado = AnsvDescartes::where('control', $control)->first(); // encuentra la primer coincidencia y la idea es que muestre todos los descartes referidos a ese nro control 

$controlBuscado = AnsvDescartes::where('control', $control)->get(); // con esto traigo todos los registros y me lo devuelve en una coleecion la  cual la recorro con un

// dd($controlBuscado);





//     $descartes = AnsvDescartes::all(); // recuperamos una coleccion con todos los descartes de la base de datos . 
$descartes = AnsvDescartes::paginate(20);
    // foreach ($descartes as $descarte) {
    //     $descarte->descripcion;
    //     var_dump($descarte->descripcion);                // recorremos para verificar el contenido 
    //  }






    return view('reportesDescartes.reportesDescartes' , [
        'descartes' => $descartes,
        'controlBuscado' => $controlBuscado,
    ]);
}





public function descartarInsumo(Request $request)
    {
        // $accion = $request->input('accion');

        // if ($accion === 'descartar_insumo') {

            return view('reportesDescartes.descartar');

        // } 
    }





// public function insertarDescarte(Request $request)
// {


// $nroControl = $request->input('nro_control');

// $fechaActual = now();






// $descartesExiste = AnsvDescartes::where('control', $nroControl)->get();

// $descartesEnUso= AnsvControl::where('nro_control', $nroControl)->get();


//  $primerElemento = $descartesEnUso->first();

// // // Acceder al valor de tramite_id

//  $tramiteId = $primerElemento->tramite_id;



// $tramites = Tramites::where('tramite_id', $tramiteId)->get();


//  $a = $tramites->first();

//  $nro_doc = $a -> nro_doc;


//  $datosUsuario = DatosPersonales::where('nro_doc', $nro_doc)->get();


// // // dd($datosUsuario);

// $usuarioDatos = $datosUsuario->first();


//  $sexo = $usuarioDatos -> sexo;
//  $nombre = $usuarioDatos -> nombre;
//  $apellido = $usuarioDatos -> apellido;
//  $nro_doc = $usuarioDatos -> nro_doc;

// // // dd($nro_doc);

//  $data2 = [
//      'tramite_id' => $tramiteId,
//     'genero' => $sexo,
//     'nombre' => $nombre,
//     'apellido' => $apellido,
//     'documento' => $nro_doc
// ];


// $request->session()->put('confirmacion_descarte', $data2);



// dd($data2);




// $user= auth()->user();

//  //dd($user -> sucursal );

//  //dd($user );

// $sucursalUsuario =  $user->sucursal;










// // dd($sucursalUsuario);


// if ($descartesExiste->count() > 0) {
//     // El número de control ya existe, mostrar un mensaje de error
//     return redirect()->back()
//         ->withErrors(['nro_control' => 'El número de control ya esta descartado.'])
//         ->withInput();
// }



// $data = [
//     'control' => $nroControl,
//     'motivo' => 97,
//     'creation_date' => $fechaActual,
//     'created_by' => auth()->user()->id,
//     'descripcion' => $request->input('descripcion'),
// ];



// $loteSucursal = DB::table('ansv_lotes')
//     ->select('sucursal_id')
//     ->where('control_desde', '<=', $nroControl)
//     ->where('control_hasta', '>=', $nroControl)
//     ->value('sucursal_id');



// // dd($loteSucursal);





// if ($descartesEnUso->count() > 0 && $sucursalUsuario === $loteSucursal) {

//     DB::table('ansv_descartes')->insert($data);   
    
//     $liberadoTrue = AnsvControl::where ('nro_control', $nroControl)->update([
//         'liberado' => true,
//     ]);

// } elseif  ($sucursalUsuario === $loteSucursal)  {

//     DB::table('ansv_descartes')->insert($data);   

    

// }else {
//     return redirect()->back()
//     ->withErrors(['nro_control' => 'No podes descartar insumos de otra sucursal.'])
//     ->withInput();
// }












// return redirect()->route('reporte.control.insumos')->with('success', 'Lote creado correctamente');




// }

public function insertarDescarte(Request $request)
{
    $nroControl = $request->input('nro_control');
    $fechaActual = now();
    $descartesExiste = AnsvDescartes::where('control', $nroControl)->get();
    $descartesEnUso = AnsvControl::where('nro_control', $nroControl)->get();

    $user = auth()->user();
    $sucursalUsuario =  $user->sucursal;

    if ($descartesExiste->count() > 0) {
        // El número de control ya existe, mostrar un mensaje de error
        return redirect()->back()
            ->withErrors(['nro_control' => 'El número de control ya está descartado.'])
            ->withInput();
    }

    $data = [
        'control' => $nroControl,
        'motivo' => 97,
        'creation_date' => $fechaActual,
        'created_by' => auth()->user()->id,
        'descripcion' => $request->input('descripcion'),
    ];

    // $loteSucursal = DB::table('ansv_lotes')
    //     ->select('sucursal_id')
    //     ->where('control_desde', '<=', $nroControl)
    //     ->where('control_hasta', '>=', $nroControl)
    //     ->value('sucursal_id');



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
        
       
       
        


    } elseif ($sucursalUsuario === $loteSucursal) {

        DB::table('ansv_descartes')->insert($data);

    } else {
        return redirect()->back()
            ->withErrors(['nro_control' => 'No puedes descartar insumos de otra sucursal.'])
            ->withInput();
    }

    return redirect()->route('reporte.control.insumos')->with('success', 'Lote creado correctamente');
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
