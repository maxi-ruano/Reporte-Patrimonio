<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\SysMultivalue;

use App\Tramites;

use App\TramitesFull;

use App\EtlExamen;
use App\TeoricoPc;
use App\AnsvAmpliaciones;

class BedelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $mensajeError = '';
      $paises = SysMultivalue::where('type','PAIS')->orderBy('description', 'asc')->get();
      $tdoc = SysMultivalue::where('type','TDOC')->orderBy('id', 'asc')->get();
      $sexo = SysMultivalue::where('type','SEXO')->orderBy('id', 'asc')->get();

      if (isset($request->doc) && $request->doc != '' && isset($request->sexo) && $request->sexo != '' && isset($request->pais) && $request->pais != '' && isset($request->tipo_doc) && $request->tipo_doc != '') {

        $peticion = $this->getTramiteExactly($request->doc, $request->tipo_doc,$request->sexo, $request->pais);

        if($peticion[0]){
          if ($peticion[1]->clase_value == 'NADA' OR $peticion[1]->clase_otorgada_value == 'NADA') {
            $get_class = AnsvAmpliaciones::where('tramite_id', $peticion[1]->tramite_id)->first();
            $peticion[1]->clase_value = $get_class->clases_dif;
            $peticion[1]->clase_otorgada_value = $get_class->clases_dif;
          }

          if ($peticion[1]->detenido == 0) {
            $peticion[1]->motivo_detencion_value = 'NO';
          }

          $disponibilidad = $this->api_get('get', $peticion[1]->tipo_doc, $peticion[1]->nro_doc, $peticion[1]->sexo, $peticion[1]->pais);

          $peticion[1]->disponibilidad = false;
          $peticion[1]->computadoras = false;
          $peticion[1]->categorias = false;

          if($disponibilidad[0] == false){
            $peticion[1]->disponibilidad = $disponibilidad[0];
            $peticion[1]->disponibilidadMensaje = $disponibilidad[1];

          }else{
            $peticion[1]->disponibilidad = $disponibilidad[0];
            $peticion[1]->computadoras = $this->getComputadoras();
            $peticion[1]->categorias = $disponibilidad[1];
          }
        }else{
          $mensajeError = "no existe usuario";
        }

        }
      $peticion = $peticion ?? array(false);
      return view('bedel.asignacion')->with('paises',$paises)->with('tipo_doc',$tdoc)->with('sexo',$sexo)->with('peticion',$peticion)->with('mensajeError',$mensajeError);

    }


    public function habilitado(){

      $res = $this->httpGet('http://192.168.76.233/api_dc.php?function=get&tipo_doc=1&nro_doc=12345&sexo=m&pais=1');
      $res = json_decode($res, false);
      return $res;
    }
    public function getTramiteExactly($nro_doc, $tipo_doc, $sexo, $pais)
    {
      $response_array = array();
      $posibles = TramitesFull::where('nro_doc', $nro_doc)
      ->where('tipo_doc', $tipo_doc)
      ->where('sexo', $sexo)
      ->where('pais', $pais)
      ->where('estado', 8)
      ->orderBy('tramite_id', 'asc')
      ->first();

      if (count($posibles) > 0) {
        array_push($response_array,true);
        array_push($response_array,$posibles);
      }
      else {
        array_push($response_array,false);
      }
      return $response_array;
    }

    public function getExamenByTramite($tramite_id)
    {
      $response_array = array();
      //$posibles = EtlExamen::where('tramite_id',$tramite_id)->where()
    }

    function api_get($function, $tipo_doc, $nro_doc, $sexo, $pais)
{
    $url = "http://192.168.76.233/api_dc.php?function=".$function."&tipo_doc=".$tipo_doc."&nro_doc=".$nro_doc."&sexo=".$sexo."&pais=".$pais;
    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
//  curl_setopt($ch,CURLOPT_HEADER, false);

    $output=curl_exec($ch);

    curl_close($ch);
    $res = json_decode($output, false);
    return $res;
}
    public function getComputadoras()
    {
      return TeoricoPc::where('activo','true')->whereNull('examen_id')->get();
    }
}
