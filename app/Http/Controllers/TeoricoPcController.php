<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DatosPersonales;
use App\TeoricoPc;
use App\EtlExamen;

class TeoricoPcController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $teoricopc = TeoricoPc::all();
        dd($teoricopc);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return View('pc.template');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $teoricopc = new TeoricoPc($request->all());
        if (!filter_var($request->ip, FILTER_VALIDATE_IP) === false) {
            $teoricopc->ip = ip2long($teoricopc->ip);
            $teoricopc->save();
            var_dump($teoricopc->ip);
            echo("Ok");
            dd($request->ip);
        } else {
            echo("Ip invalida");
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $teoricopc = TeoricoPc::find($id);
        dd($teoricopc);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $teoricopc = TeoricoPc::find($id);
        $teoricopc->ip = long2ip($teoricopc->ip);
        return View('pc.template')->with('teoricopc', $teoricopc);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $teoricopc = TeoricoPc::find($id);
        $teoricopc->fill($request->all());
            if (!filter_var($request->ip, FILTER_VALIDATE_IP) === false) {
                $teoricopc->ip = ip2long($teoricopc->ip);
                $teoricopc->save();
                dd($teoricopc->ip);
                echo("Ok");
            } else {
                echo("Ip invalida");
            }
        $teoricopc->save();
        //return redirect()->route('');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function isActive(Request $request) {
      $ip = ip2long($request->ip());
      $teoricopc = TeoricoPc::where('ip', '=', $ip)->first();
      $error_array = array();
      $response_array = array();
      if ($teoricopc->examen_id == NULL OR $teoricopc->examen_id == '') {
          array_push($error_array, 'No hay examen asignado en esta IP');
      }
      if ($teoricopc->activo != true) {
          array_push($error_array, 'Esta IP no esta habilitada');
      }
      //print_r($error_array);
      if (count($error_array) > 0) {
          array_push($response_array, false);
          array_push($response_array, $error_array);
          return $response_array;
      }
      else {
        array_push($response_array, true);
        array_push($response_array, $teoricopc->examen_id);
          return $response_array;
      }
    }

    public function estadoComputadoras(Request $request) {
      return view('bedel.monitoreo');
    }

    public function listarDisponibles($suc_id){
      $response = array();
      $teoricopc = TeoricoPc::where('sucursal_id', $suc_id)
      ->where('activo', false)
      ->get();
      if ($teoricopc != NULL) {
        return array(true, $teoricopc);
      }
      return array(false);

    }

    public function computadorasMonitor(){
      $computadoras = TeoricoPc::all();
      foreach ($computadoras as $key => $computadora) {
        $examen = EtlExamen::find($computadora->examen_id);
        $nro_doc = $examen->tramite->nro_doc;
        $tipo_doc = $examen->tramite->tipo_doc;
        $pais = $examen->tramite->pais;
        $sexo = $examen->tramite->sexo;
        $sucursal = $examen->tramite->sucursal;

        if($sucursal == 1 || $sucursal == 2)
          $ip = "192.168.76.200";
        else
          $ip = $computadora->examen->tramite->SysRptServer->ip;

        $computadora->pathFoto = "http://". $ip ."/data/fotos/" .
                      str_pad($pais, 3, "0", STR_PAD_LEFT) .
                      $tipo_doc .
                      $nro_doc .
                      strtoupper($sexo) .
                      ".JPG";

        $datosPersona = DatosPersonales::where('nro_doc', $nro_doc)
                                 ->where('pais', $pais)
                                 ->where('tipo_doc', $tipo_doc)->first();

        $computadora->nro_doc = $nro_doc;
        $computadora->nombre = $datosPersona->nombre;
        $computadora->apellido = $datosPersona->apellido;
        $computadora->estadoExamen = '<span class="label label-default">NO ASIGNADO';
        if($examen->fecha_inicio){
          $computadora->estadoExamen = '<span class="label label-warning">EN PROCESO';
          if($examen->fecha_fin)
            if($examen->aprobado)
              $computadora->estadoExamen = '<span class="label label-success">APROBADO';
            else
              $computadora->estadoExamen = '<span class="label label-danger">REPROBADO';
        }

        }
        return response()->json(['computadoras' => $computadoras]);

  }

  public function verificarAsignacion(Request $request){
    $teorico = TeoricoPc::find($request->computadora_id);
    if($teorico->examen_id == $request->examen_id)
      return response()->json(['res' => 'false']);
    else
      return response()->json(['res' => 'true']);
  }

  public function asignarPc($pc, $examen_id){
    $teorico = TeoricoPc::where('id', $pc)
    ->update(['examen_id' => $examen_id, 'activo' => true]);
  }

}
