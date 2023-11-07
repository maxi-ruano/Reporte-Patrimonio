<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TramitesAIniciar;
use App\AnsvPaises;
use App\SysMultivalue;
use App\SigeciPaises;
use App\TramitesAIniciarErrores;
use App\TramitesAIniciarCheckprecheck;
use App\AnsvCelExpedidor;
use App\Http\Controllers\TramitesController;
use App\Http\Controllers\TramitesAInicarController;
use App\Sigeci;
use App\BoletaBui;
use App\CharlaVirtual;

class PreCheckController extends Controller
{
  
  public $centrosEmisores = null;

  public function __construct(){
    $this->centrosEmisores = new AnsvCelExpedidor();
    $this->crearConstantes(); //Esta funcion deberia ejecutarse desde Controllers pero se vuelve a ajecutar porq no reconocia las constants
  }

  /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
  public function index(Request $request)
  {
      $tramites = '';
      $precheck = '';
      $tramite_a_iniciar_id = ''; 

      if(isset($request->nro_doc)){
        //Buscar en tramites a iniciar y mostrar toda la informacion basica 
        $tramites = TramitesAIniciar::selectRaw('tramites_a_iniciar.*, sigeci.fecha as sigeci_fecha, tramites_habilitados.fecha as sath_fecha, tramites.fec_emision, tramites.fec_vencimiento')
                    ->leftjoin('tramites_habilitados','tramites_habilitados.tramites_a_iniciar_id','tramites_a_iniciar.id')
                    ->leftjoin('sigeci','sigeci.idcita','tramites_a_iniciar.sigeci_idcita')
                    ->leftjoin('tramites','tramites.tramite_id','tramites_a_iniciar.tramite_dgevyl_id')
                    ->where('tramites_a_iniciar.nro_doc',$request->nro_doc)
                    ->where('tramites_a_iniciar.estado','!=',TURNO_VENCIDO)
                    ->orderBy('tramites_a_iniciar.id','DESC')
                    ->get();

        if(count($tramites)){
          foreach ($tramites as $key => $value) {
            $buscar = TramitesAIniciar::find($value->id);
            $value->nacionalidad = $buscar->nacionalidadTexto();
            $value->tipo_doc = $buscar->tipoDocText();
            $value->sigeci_fecha = ($value->sigeci_fecha) ? date('d-m-Y', strtotime($value->sigeci_fecha)) : '';
            $value->sath_fecha = ($value->sath_fecha) ? date('d-m-Y', strtotime($value->sath_fecha)) : '';
            $value->fec_emision = ($value->fec_emision) ? date('d-m-Y', strtotime($value->fec_emision)) : '';
            $value->fec_vencimiento = ($value->fec_vencimiento) ? date('d-m-Y', strtotime($value->fec_vencimiento)) : '';

            //Si contiene un solo Precheck seleccionamos para visualizar validaciones
            if(count($tramites) == 1)
              $tramite_a_iniciar_id = $value->id;
          }
        }
        
      }

      //Si existe en el request  un tramite a iniciar seleccionado para mostrar sus validaciones
      if(isset($request->id))
        $tramite_a_iniciar_id = $request->id;

      //Buscar en validaciones_precheck
      if($tramite_a_iniciar_id)
        $precheck =  \DB::table('validaciones_precheck')
                            ->selectRaw('validaciones_precheck.*, sys_multivalue.description, tramites_a_iniciar.tramite_dgevyl_id')
                            ->join('tramites_a_iniciar', 'tramites_a_iniciar.id', 'validaciones_precheck.tramite_a_iniciar_id')
                            ->join('sys_multivalue', 'sys_multivalue.id', 'validaciones_precheck.validation_id')
                            ->where('sys_multivalue.type', 'VALP')
                            ->where('tramite_a_iniciar_id',$tramite_a_iniciar_id)
                            ->get();
      
      $centrosEmisores = $this->centrosEmisores->getCentrosEmisores();
      //Se envia listado d elas Sucursales para el select del buscar
      $SysMultivalue = new SysMultivalue();        
      $sucursales = $SysMultivalue->sucursales();

      return view('precheck.index', compact('tramites','precheck','centrosEmisores','sucursales'));
  }

  /**
     * Remove the specified resource from storage.
     * Si se desea anular un precheck, borrando el comprobante para que en Licta lo coloquen manual
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function anularPreCheckComprobante(Request $request)
    {
        try{
            $anular = \DB::table('validaciones_precheck')->where('id',$request->id)
                            ->update(array('validado' => false, 'comprobante' => null));
            return $anular;
        }
        catch(Exception $e){   
            return "Fatal error - ".$e->getMessage();
        }
    }

    public function anular_sinalic(Request $request)
    {
        try{
          $tramitesAIniciar = TramitesAIniciar::find($request->id);

          if($tramitesAIniciar->tramite_sinalic_id){

            $controller = new TramitesAInicarController();
            $controller->anularTramiteSinalic($tramitesAIniciar->tramite_sinalic_id,'6','microservicio');

            $tramitesAIniciar->tramite_sinalic_id = null;
            $tramitesAIniciar->tipo_tramite = null;
            $tramitesAIniciar->save();

            $anular = \DB::table('validaciones_precheck')
                            ->where('tramite_a_iniciar_id',$request->id)
                            ->where('validation_id',SINALIC)
                            ->update(array('validado' => false, 'comprobante' => null));
          }
       }
        catch(Exception $e){   
            return "Fatal error - ".$e->getMessage();
        }
    }



  public function checkPreCheck(){
    $paises = SysMultivalue::where('type','PAIS')->orderBy('description', 'asc')->pluck('description', 'id');
    $tdoc = SysMultivalue::where('type','TDOC')->orderBy('id', 'asc')->pluck('description', 'id');
    $sexo = SysMultivalue::where('type','SEXO')->where('id','<>',0)->orderBy('id', 'asc')->pluck('description', 'description');
    

    return View('safit.checkModoAutonomo')->with('paises', $paises)
                                          ->with('tdoc', $tdoc)
                                          ->with('sexo', $sexo)
                                          ->with('centrosEmisores', $this->centrosEmisores->getCentrosEmisores());
  }

  public function consultarPreCheck(Request $request){
    $tramiteAIniciar = TramitesAIniciar::find($request->id);
                           
    if($tramiteAIniciar){
      $tramiteAIniciar->nacionalidad = $tramiteAIniciar->nacionalidadTexto();
      $tramiteAIniciar->motivo = $tramiteAIniciar->motivo();
      $tramiteAIniciar->fecha_turno = $tramiteAIniciar->fechaTurno();

      $precheck =  \DB::table('validaciones_precheck as v')
                      ->select('v.tramite_a_iniciar_id', 'v.validado', 's.description', 'v.validation_id','v.comprobante', 'v.updated_at')
                      ->join('sys_multivalue as s', 's.id', '=', 'v.validation_id')
                      ->where('s.type', 'VALP')
		      ->whereNotIn('v.validation_id',['7','5']) //6 para charla
		      ->where('v.tramite_a_iniciar_id', $tramiteAIniciar->id)
		      ->orderby('s.id','desc')
		      ->get();
                      
      if(count($precheck)){
        $precheck = $this->getErroresTramite($precheck);
        $precheck = $this->getComprobantes($precheck);
        return response()->json(['datosPersona' => $tramiteAIniciar, 'precheck' => $precheck]);
      }else{
        //Tramites antiguos que no tenian registros en validaciones_precheck
        return response()->json(['error'=> "El tramite no cuenta con validaciones en Pre-checkeo"]);
      } 

    }else{
      return response()->json(['error' => "El examen no se ha sido iniciado en el Pre-checkeo"]);
    }
  }

  public function  getErroresTramite($precheck){
    foreach ($precheck as $key => $value) {
      if($value->validado == false){
        $estado = ($value->validation_id == 3)?[2,3]:[$value->validation_id];
        $value->error = TramitesAIniciarErrores::whereIn('estado_error', $estado)
                             ->where('tramites_a_iniciar_id', $value->tramite_a_iniciar_id)
                             ->where('response_ws', '!=','""')
                             ->select('description', 'id', 'created_at','response_ws')
                             ->orderBy('id', 'desc')
                             ->first();
      }
    }
    return $precheck;
  }

  public function getComprobantes($precheck){
    foreach ($precheck as $key => $value) {
      if($value->validation_id == BUI){
        $value->boleta = BoletaBui::where('tramite_a_iniciar_id', $value->tramite_a_iniciar_id)->first();
      }elseif($value->validation_id == CHARLA_VIRTUAL){
	 $value->charla = CharlaVirtual::where('codigo', $value->comprobante )->first();
	/* if(isset($value->charla->fecha_vencimiento)){
	 	$value->charla->fecha_vencimiento_txt = date('d-m-Y', strtotime($value->charla->fecha_vencimiento));
	 }*/
      }
    } 
    return $precheck;
  }

  public function buscarTramitesPrecheck(Request $request){
    
    //Buscar las personas asociadas a un mismo nro de documento con diferente tipo documento y/o nacionalidad
    $personas = TramitesAIniciar::where('nro_doc', $request->nro_doc)
                  ->select('nro_doc','tipo_doc','nacionalidad')
                  ->groupBy('nro_doc','tipo_doc','nacionalidad')
                  ->orderBy('tipo_doc', 'asc')
                  ->get();
    
    $tramites = [];
    //Seleccionar solo un tramite por cada persona tomando en cuenta que exista en validaciones_precheck validado
    foreach ($personas as $key => $persona){
      $busqueda = TramitesAIniciar::select('tramites_a_iniciar.*','sigeci.fecha','sigeci.hora','sigeci.sucroca as sucursal')
                                  ->where([
                                    'nro_doc' => $persona->nro_doc,
                                    'tipo_doc'=> $persona->tipo_doc,
                                    'nacionalidad'=> $persona->nacionalidad
                                    ])
                                  ->join('sigeci','sigeci.idcita','=','tramites_a_iniciar.sigeci_idcita')
                                  ->where('tramites_a_iniciar.estado','!=', TURNO_VENCIDO)
                                  ->orderBy('sigeci.idcita', 'desc')
                                  ->first();
      if($busqueda)
        $tramites [] = $busqueda;
      
    } 
   
   if(count($tramites)){
      foreach ($tramites as $key => $value) {
        $buscar = TramitesAIniciar::find($value->id);
        $value->nacionalidad = $buscar->nacionalidadTexto();
        $value->tipo_doc = $buscar->tipoDocText();
        $value->sucursal = $buscar->sucursalTexto($value->sucursal);
      }
      return response()->json(['res' => $tramites]);
    }else{
      return response()->json(['error' => "El examen no se ha sido iniciado en el Pre-checkeo"]);
    }
  }

  //function get para API listar los tramites iniciados con estado on ó off 
  public function get_tramites_precheck(Request $request){
    
    $fecha = ($request->fecha=='')?date("Y-m-d"):$request->fecha;

    $tramiteController = new TramitesController();
    $consulta = $tramiteController->consultarTramitesPrecheck($fecha, $request->estado);

    if($request->export) 
      $this->exportFile($consulta, $request->export, 'tramitesPrecheck'.$fecha);

    return $consulta;
  }

  //function get para API listar los errores generados en el precheck
  public function get_errores_precheck(Request $request){

    $fecha = ($request->fecha=='')?date("Y-m-d"):$request->fecha;
    
    //No limitar el limit Memory de PHP
    ini_set('memory_limit', '-1');

    $errores = $this->consultar_errores_precheck($fecha);

    //Solo si existe el parametro para export en: xls, xlsx, txt, csv, entre otros.
    $consulta = json_decode(json_encode($errores), true);
    if($request->export) 
      $this->exportFile($consulta, $request->export, 'erroresPrecheck'.$fecha);
    
    //Preparar array de array - agrupando por idcita con sus errores
    $datos = [];
    if(count($errores)){
      foreach ($errores as $key => $persona){
        $datos[$persona->idcita] = ['idcita' => $persona->idcita, 'tipo_doc' => $persona->tipo_doc, 'nro_doc' => $persona->nro_doc, 'nombre' => $persona->nombre, 'apellido' => $persona->apellido, 'sexo' => $persona->sexo];

        foreach ($errores as $key => $error){
          if($persona->idcita == $error->idcita){
            $response_ws = json_decode($error->response_ws);
            
            //Se asigna ID error para el clasificar e informar al vecino correctamente (solo uso externo)
            $rspID = ($error->description == 'No existe ninguna boleta valida para esta persona')? '2':isset($response_ws->rspID)?$response_ws->rspID:null;            
            //Libre Deuda
            if($error->validation_id == '4'){
              $rspID = null;
              if(strpos($error->description,'Existen actas pendientes'))
                $rspID = '1';
              if(strpos($error->description,'documento erróneo'))
                $rspID = '2';
              if(strpos($error->description,'plan de pagos'))
                $rspID = '3';
            }

            $datos[$persona->idcita]['error'][] = array(
                          'validation_id' => $error->validation_id, 
                          'validation_nombre' => $error->validation_nombre, 
                          'description' => $error->description, 
                          'error_id' => $rspID, 
                          'created_at' => $error->created_at);
          }
        }
      }
    }else{
      $datos['mensaje']= "No existen registros de errores con fecha ".$fecha; 
    }
    
    return response()->json($datos);
  }

  public function consultar_errores_precheck($fecha){

      $campos = "SELECT 
                  sigeci_idcita as idcita, 
                  tramites_a_iniciar.nro_doc,
                  MAX(sys_multivalue_tdoc.description) as tipo_doc,
                  MAX(tramites_a_iniciar.nombre) as nombre,
                  MAX(tramites_a_iniciar.apellido) AS apellido,
                  MAX(tramites_a_iniciar.sexo) AS sexo,
                  tramites_a_iniciar_errores.tramites_a_iniciar_id,
                  validaciones_precheck.validation_id,
                  MAX(sys_multivalue.description) as validation_nombre,
                  MAX(tramites_a_iniciar_errores.description) as description, 
                  MAX(tramites_a_iniciar_errores.response_ws) as response_ws, 
                  MAX(tramites_a_iniciar_errores.created_at) as created_at ";

      $table = "FROM tramites_a_iniciar_errores 
                INNER JOIN tramites_a_iniciar ON tramites_a_iniciar.id = tramites_a_iniciar_errores.tramites_a_iniciar_id
                INNER JOIN sigeci ON sigeci.idcita = tramites_a_iniciar.sigeci_idcita
                LEFT JOIN sys_multivalue sys_multivalue_tdoc ON sys_multivalue_tdoc.id = tramites_a_iniciar.tipo_doc AND sys_multivalue_tdoc.type = 'TDOC'
                 ";
     
      //Consulta para errores de Safit con estado_errores (2,3) y validaciones precheck (3)(false)
      $errores_safit = \DB::select($campos.$table."
                        INNER JOIN validaciones_precheck ON validaciones_precheck.tramite_a_iniciar_id = tramites_a_iniciar.id and 
                          validaciones_precheck.validation_id = 3 and validaciones_precheck.validado = false
                        LEFT JOIN sys_multivalue ON sys_multivalue.id = validaciones_precheck.validation_id AND sys_multivalue.type = 'VALP'
                      WHERE sigeci.fecha = '".$fecha."' and tramites_a_iniciar_errores.estado_error IN('2','3') and tramites_a_iniciar_errores.response_ws <> '\"\"'
                        AND tramites_a_iniciar_errores.response_ws like '%rspID%'
                        AND tramites_a_iniciar_errores.description not like '%demorado%'
                      GROUP BY tramites_a_iniciar.tipo_doc, tramites_a_iniciar.nro_doc, sigeci_idcita,  tramites_a_iniciar_errores.tramites_a_iniciar_id, validaciones_precheck.validation_id
                      ORDER BY sigeci_idcita ASC ");

      //Consulta para errores de Libre Deuda y BUI con estado_errores (4,5) y validaciones precheck (4,5)(false)
      $errores_ldbui = \DB::select($campos.$table."                      
                        INNER JOIN validaciones_precheck ON validaciones_precheck.tramite_a_iniciar_id = tramites_a_iniciar.id and 
                          validaciones_precheck.validation_id = tramites_a_iniciar_errores.estado_error and validaciones_precheck.validado = false
                        LEFT JOIN sys_multivalue ON sys_multivalue.id = validaciones_precheck.validation_id AND sys_multivalue.type = 'VALP'
                      WHERE sigeci.fecha = '".$fecha."' and tramites_a_iniciar_errores.estado_error IN('4') and tramites_a_iniciar_errores.response_ws <> '\"\"'
                        AND tramites_a_iniciar_errores.description not like 'ERROR%'
                      GROUP BY tramites_a_iniciar.tipo_doc, tramites_a_iniciar.nro_doc, sigeci_idcita, tramites_a_iniciar_errores.tramites_a_iniciar_id, validaciones_precheck.validation_id, tramites_a_iniciar_errores.estado_error
                      ORDER BY sigeci_idcita ASC, tramites_a_iniciar_errores.estado_error ASC ");

      $consulta = array_merge($errores_safit,$errores_ldbui);

    return $consulta;
  }


  public function actualizarPaseATurno(Request $request) {
    $fecha = date("Y-m-d H:i:s");
    return TramitesAIniciar::where('id',$request->id)->update(['fecha_paseturno' => $fecha]);
  }

  public function get_precheck_comprobantes(Request $request) {
    
    $fecha = isset($request->fecha)?date('Y-m-d', strtotime($request->fecha)):date('Y-m-d');

    $sql  = "SELECT
              tramites_a_iniciar.id, 
              sigeci.tipodoc, 
              tramites_a_iniciar.nro_doc, 
              tramites_a_iniciar.nombre, 
              tramites_a_iniciar.apellido, 
              tramites_a_iniciar.sexo, 
              (CASE WHEN validaciones_precheck.validation_id = 3 THEN validaciones_precheck.comprobante ELSE null END) as SAFIT, 
              (CASE WHEN validaciones_precheck.validation_id = 4 THEN validaciones_precheck.comprobante ELSE null END) as LIBRE_DEUDA, 
              (CASE WHEN validaciones_precheck.validation_id = 5 THEN validaciones_precheck.comprobante ELSE null END) as BUI
            FROM tramites_a_iniciar
              INNER JOIN sigeci ON  sigeci.idcita =  tramites_a_iniciar.sigeci_idcita
              INNER JOIN validaciones_precheck ON  validaciones_precheck.tramite_a_iniciar_id =  tramites_a_iniciar.id
            WHERE sigeci.fecha = '".$fecha."' ";
    

    $consulta = \DB::table(\DB::raw('('.$sql.')  as precheck'))
                    ->selectRaw("id, tipodoc, nro_doc, MAX(nombre) as nombre, MAX(apellido) as apellido, MAX(sexo) as sexo, MAX(SAFIT) as safit, MAX(LIBRE_DEUDA) as libredeuda, MAX(BUI) as bui")
                    ->groupBy("id","tipodoc", "nro_doc")
                    ->orderBy('nro_doc','ASC')          
                    ->get(); 

    $data= json_decode( json_encode($consulta), true);
    $this->exportFile($data, 'xls', 'consultaPrecheck');

    return $data;
  }
}
