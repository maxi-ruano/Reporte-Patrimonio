<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use App\Sigeci;
use App\TramitesAIniciar;
use App\Http\Controllers\SoapController;
use App\Http\Controllers\WsClienteSafitController;
use App\Http\Controllers\WsClienteSinalicController;
use App\Http\Controllers\WsCharlaVirtualController;
use App\AnsvPaises;
use App\SysMultivalue;
use App\SigeciPaises;
use App\TramitesAIniciarErrores;
use App\LibreDeudaLns;
use App\LibreDeudaHdr;
use App\BoletaBui;
use App\AnsvCelExpedidor;
use App\EmisionBoletaSafit;
use App\ValidacionesPrecheck;
use App\TramitesHabilitados;
use App\Http\Controllers\UserController;

class TramitesAInicarController extends Controller
{
  private $localhost = '192.168.76.33';
  private $diasEnAdelante = 1;
  private $cantidadDias = 2;
  private $fecha_inicio = '';
  private $fecha_fin = '';
  private $munID = 1;
  private $estID = "A";
  private $estadoBoletaNoUtilizada = "N";
  private $estado_final = 6;

  //LIBRE deuda
  private $userLibreDeuda;
  private $passwordLibreDeuda;
  private $urlLibreDeuda;

  //BUI
  private $motivoTurnoEnElDia = '25';
  private $motivoReimpresion  = '29'; 
  private $userBui;
  private $passwordBui;
  private $urlVerificacionBui;

  //SINALIC
  private $wsSinalic = null;

  //SAFIT
  public $wsSafit = null;

  public $centrosEmisores = null;

  public function __construct(){

    $this->centrosEmisores = new AnsvCelExpedidor();
    //WS SAFIT
    $this->wsSafit = new WsClienteSafitController();
    //WS SINALIC
    $this->wsSinalic = new WsClienteSinalicController();
    ini_set('default_socket_timeout', 600);
    $this->calcularFechas();
    
    $this->userLibreDeuda = LibreDeudaWS_userName;
    $this->passwordLibreDeuda = LibreDeudaWS_userPass;
    $this->urlLibreDeuda = LibreDeudaWS_wsdl_url;

    $this->userBui = BuiWS_userName;
    $this->passwordBui = BuiWS_userPass;
    $this->urlVerificacionBui = BuiWS_ws_url;
  }

  public function getTramitesAIniciar($estado, $fecha_inicio, $fecha_fin){
    $personas = \DB::table('tramites_a_iniciar')
                    ->select('tramites_a_iniciar.*')
                    ->join('sigeci', 'sigeci.idcita', '=', 'tramites_a_iniciar.sigeci_idcita')
                    ->whereBetween('sigeci.fecha', [$fecha_inicio, $fecha_fin])
                    ->where('tramites_a_iniciar.estado', $estado)
                    ->get();
    return $personas;
  }

  public function getTramitesAIniciarValidaciones($estado, $estadoValidacion, $fecha_inicio, $fecha_fin){
    $personas = \DB::table('tramites_a_iniciar')
                    ->select('tramites_a_iniciar.*')
                    ->join('sigeci', 'sigeci.idcita', '=', 'tramites_a_iniciar.sigeci_idcita')
                    ->join('validaciones_precheck', 'validaciones_precheck.tramite_a_iniciar_id', '=', 'tramites_a_iniciar.id')
                    ->whereBetween('sigeci.fecha', [$fecha_inicio, $fecha_fin])
                    ->where('tramites_a_iniciar.estado', '!=', TURNO_VENCIDO)
                    ->where('validaciones_precheck.validation_id', $estadoValidacion)
                    ->where('validaciones_precheck.validado', false)
                    ->get();
    return $personas;
  }

  public function guardarError($res, $estado, $tramite){
    TramitesAIniciarErrores::create(['description' => $res->error,
                                      'request_ws' => json_encode($res->request),
                                      'response_ws' => json_encode($res->response),
                                      'estado_error' => $estado,
                                      'tramites_a_iniciar_id' => $tramite]);
  }

  public function guardarDatosBoleta($persona, $boleta, $siguienteEstado){
    $persona->bop_cb = (isset($boleta->bopCB)?$boleta->bopCB:$boleta->bop_cb);
    $persona->bop_monto = (isset($boleta->bopMonto)?$boleta->bopMonto:$boleta->bop_monto);
    $persona->bop_fec_pag = (isset($boleta->bopFecPag)?$boleta->bopFecPag:$boleta->bop_fec_pag);
    $persona->bop_id = (isset($boleta->bopID)?$boleta->bopID:$boleta->bop_id);
    $persona->cem_id = (isset($boleta->cemID)?$boleta->cemID:$boleta->cem_id);
    $persona->estado = $siguienteEstado;
    return $persona->save();
  }

  //Proceso utilizado en segundo plano mediante Queue
  public function iniciarTramiteEnPrecheck($t){
    
    $turno = TramitesHabilitados::find($t->id);
    $tramiteAIniciar = TramitesAIniciar::find($turno->tramites_a_iniciar_id);

    \Log::info('['.date('h:i:s').'] '.'se procede iniciarTramiteEnPrecheck(), '.$turno->id);
    
      /**Se ejecuta de forma Async el usuario no debe esperar el tiempo de respuesta */
      \Log::info('['.date('h:i:s').'] '.'inicio validaciones precheck con tramiteAIniciar ID: '.$tramiteAIniciar->id);

        //if(!$this->estaValidadoEnValidacionesPrecheck($tramiteAIniciar,LIBRE_DEUDA)){
          \Log::info('['.date('h:i:s').'] '.'> > > gestionarLibreDeuda() tramites_habilitado ID = '.$turno->id);
          $this->gestionarLibreDeuda($tramiteAIniciar, LIBRE_DEUDA, VALIDACIONES);
        //}

        if($this->estaValidadoEnValidacionesPrecheck($tramiteAIniciar,BUI) != true){
          \Log::info('['.date('h:i:s').'] '.'> > > gestionarBui('.BUI.') tramites_habilitado ID = '.$turno->id);
          $this->gestionarBui($tramiteAIniciar, BUI, VALIDACIONES);        
        }

        \Log::info('['.date('h:i:s').'] '.' comprobando SAFIT '.EMISION_BOLETA_SAFIT);
        if($this->estaValidadoEnValidacionesPrecheck($tramiteAIniciar,EMISION_BOLETA_SAFIT) != true){
          \Log::info('['.date('h:i:s').'] '.'> > > buscarBoletaSafit() tramites_habilitado ID = '.$turno->id);
          if($this->buscarBoletaSafit($tramiteAIniciar, SAFIT)){
            //Ejecutar gestionarBoletaSafit() solo si encuenctra datos en buscarBoloetaSafit()
            \Log::info('['.date('h:i:s').'] '.'> > > gestionarBoletaSafit() tramites_habilitado ID = '.$turno->id);
            $this->gestionarBoletaSafit($tramiteAIniciar, EMISION_BOLETA_SAFIT, VALIDACIONES);
          }
        }

      \Log::info('['.date('h:i:s').'] '.'fin validaciones precheck de '.$turno->id);
      
    return true;
  }

  //Cambiar el concepto de BUI solo si motivo es TURNO EN EL DIA o REIMPRESION
  public function obtenerConceptosBui($tramite) {
    $turno = TramitesHabilitados::where('tramites_a_iniciar_id',$tramite->id)->where('deleted', false)->orderby('id','DESC')->first();
    $motivo = isset($turno->motivo_id)?$turno->motivo_id:'';
    switch ($motivo) {
	 // case $this->motivoTurnoEnElDia:
      	//	$conceptos = [["07.02.30"]];
         // break;
	  case $this->motivoReimpresion:
		      $conceptos = [["07.02.49"]];
          break;
	  default:
		$conceptos = [["07.02.28"], ["07.02.31"], ["07.02.32"], ["07.02.33"], ["07.02.34"], ["07.02.36"]];
    }
    return $conceptos;
  }

  public function existeTramiteAIniciarConPrecheck($nro_doc, $tipo_doc, $sexo, $nacionalidad){
    $existe = TramitesAIniciar::orderBy('id','desc')
                    ->where('nacionalidad',$nacionalidad)
                    ->where('nro_doc',$nro_doc)
                    ->where('tipo_doc',$tipo_doc)
                    ->where('sexo',strtoupper($sexo))
                    ->where('estado', '!=', TURNO_VENCIDO)
                    ->whereNull('tramite_dgevyl_id')
		    ->whereNull('std_solicitud_id') //No tome precheck de reimpresion
                    ->first();
    return $existe;
  }

  public function eliminarVinculosEnTramitesHabilitados($turno_id, $tramite_id){
    TramitesHabilitados::where('tramites_a_iniciar_id',$tramite_id)
                        ->where('id','!=',$turno_id)
                        ->update(['tramites_a_iniciar_id'=> null]);
  }

  public function estaValidadoEnValidacionesPrecheck($tramiteAIniciar, $validation_id){    
    $consulta = ValidacionesPrecheck::select('validado')
                    ->where('tramite_a_iniciar_id',$tramiteAIniciar->id)
                    ->where('validation_id',$validation_id)
                    ->first();
    return $consulta->validado;
  }

  /**
   * MicroservicioController: 1) Metodos asociados para completarTurnosEnTramitesAIniciar
   */
  public function completarTurnosEnTramitesAIniciar($siguienteEstado){
    $turnos = $this->getTurnos($this->fecha_inicio, $this->fecha_fin);    
    foreach ($turnos as $key => $turno) {
      try{

        if(empty(TramitesAIniciar::where('sigeci_idcita', $turno->idcita)->first()))
          if(!$this->asignarTurnoEnTramitesAIniciar($turno))
            $this->guardarTurnoEnTramitesAInicar($turno, $siguienteEstado);
            
		  }catch(\Exception $e){
        $array = array('error' => $e->getMessage()." IDCITA: ".$turno->idcita, 'request' => "",'response' => "");
        $this->guardarError((object)$array, $siguienteEstado, 1);
		  }
    }
  }

  public function asignarTurnoEnTramitesAIniciar($turno){
    $nacionalidad = $this->getIdPais($turno->nacionalidad());
    $tipo_doc = $turno->tipoDocLicta();
    $sexo = $turno->getSexo();
    $tramiteAIniciar = $this->existeTramiteAIniciarConPrecheck($turno->numdoc, $tipo_doc, $sexo, $nacionalidad);

    if($tramiteAIniciar){
      $tramiteAIniciar->sigeci_idcita = $turno->idcita;
      $tramiteAIniciar->save();
      $turno->tramite_a_iniciar_id = $tramiteAIniciar->id;
      $turno->save();
      return true;
    }
    return false;
  }

  public function getTurnos($fecha_inicio, $fecha_fin){
    $res = Sigeci::whereBetween('fecha', [$fecha_inicio, $fecha_fin])
                 ->whereNull('tramite_a_iniciar_id')
                 ->whereNotIn('idprestacion', $this->prestacionesCursos)
                 ->get();
    return $res;
  }

  public function guardarTurnoEnTramitesAInicar($turno, $siguienteEstado){
      $tramiteAIniciar = new TramitesAIniciar();
      $tramiteAIniciar->apellido = $turno->apellido;
      $tramiteAIniciar->nombre = $turno->nombre;
      $tramiteAIniciar->tipo_doc = $turno->tipoDocLicta();
      //$tramiteAIniciar->nro_doc = $turno->numdoc;
      $tramiteAIniciar->nro_doc = $turno->docOriginal();
      $tramiteAIniciar->nacionalidad = $this->getIdPais($turno->nacionalidad());
      $tramiteAIniciar->fecha_nacimiento = $turno->fechanac;
      $tramiteAIniciar->estado = $siguienteEstado;
      $tramiteAIniciar->sigeci_idcita = $turno->idcita;
      $tramiteAIniciar->sexo = $turno->getSexo();
      $saved = $tramiteAIniciar->save();
      $turno->tramite_a_iniciar_id = $tramiteAIniciar->id;
      $turno->save();
      if($saved)
        $validaciones = $this->crearValidacionesPrecheck($tramiteAIniciar->id);
      
      return $tramiteAIniciar;
    
  }

  public function crearValidacionesPrecheck($id){
    $valp = SysMultivalue::where('type','VALP')->get();
    foreach ($valp as $key => $value) {
      $existe = ValidacionesPrecheck::where('tramite_a_iniciar_id',$id)->where('validation_id',$value->id)->count();
      if(!$existe){
        $validaciones = new ValidacionesPrecheck();
        $validaciones->tramite_a_iniciar_id = $id;
        $validaciones->validation_id = $value->id;
        $validaciones->validado = false;
        $validaciones->save();
      }
    }
    return true;
  }

  /**
  * MicroservicioController: 2) Metodos asociados para verificarLibreDeudaDeTramites
  */
  public function verificarLibreDeudaDeTramites($estadoActual, $estadoValidacion, $siguienteEstado){
    $tramites = $this->getTramitesAIniciarValidaciones($estadoActual, $estadoValidacion, $this->fecha_inicio, $this->fecha_fin);
    foreach ($tramites as $key => $tramite) {
      try{
        $this->gestionarLibreDeuda($tramite, $estadoValidacion, $siguienteEstado);
      }catch(\Exception $e){
        $array = array('error' => $e->getMessage(), 'request' => "",'response' => "");
        $this->guardarError((object)$array, $siguienteEstado, $tramite->id);
      }
    }
  }

  public function gestionarLibreDeuda($tramite, $estadoValidacion, $siguienteEstado){
    $res = $this->verificarLibreDeuda($tramite);
    if(!$res['res']){
      $this->guardarError((object)$res, $estadoValidacion, $tramite->id);
    }else {
      $this->guardarValidacion($tramite, true, $estadoValidacion, $res['comprobante']);
      $this->actualizarEstado($tramite, $siguienteEstado);
    }
  }

  public function verificarLibreDeuda($tramite){	  
    $tramite = TramitesAIniciar::find($tramite->id);
    $res = array();
    $url = $this->urlLibreDeuda; 

    if($this->file_contents_exist($url)){

	$datos = "method=getLibreDeuda".
             	"&tipoDoc=".$tramite->tipoDocLibreDeuda().
             	"&numeroDoc=".$tramite->nro_doc.
             	"&userName=".$this->userLibreDeuda.
	     	"&userPass=".$this->passwordLibreDeuda;
    	$options=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false));
    	$wsresult = file_get_contents($url.$datos, false, stream_context_create($options));
	
	if ($wsresult == FALSE){
      		$res['res'] = false;
      		$res['error'] = 'Error en el Ws de Libre Deuda';
      		$res['request'] = $datos;
      		$res['response'] = null;
    	}else{
      		$p = xml_parser_create();
      		xml_parse_into_struct($p, $wsresult, $vals, $index);
      		xml_parser_free($p);
      		$json = json_encode($vals);
      		$array = json_decode($json,TRUE);
      		$persona = null;
      		$libreDeuda = null;
      
      		foreach ($array as $key => $value) {
        	    if($value['tag'] == 'ERROR' ){
          		$res['res'] = false;
          		$res['error'] = ( isset($value['value'])? $value['value'] : "" );
          		$res['request'] = $datos;
          		$res['response'] = $array;
			return $res;
        	    }else{
          		if($value['tag'] == 'PERSONA' )
            		   $persona = $value['attributes'];
          		if($value['tag'] == 'LIBREDEUDA' )
            		   $libreDeuda = $value['attributes'];
        	    }
      		}
		
		if(isset($libreDeuda['NUMEROLD'])){
			$libreDeudaHdr = $this->guardarDatosPersonaLibreDeuda($persona, $tramite);
        		$this->guardarDatosLibreDeuda($libreDeuda, $libreDeudaHdr);
        		$res['res'] = true;
        		$res['comprobante'] = $libreDeuda['NUMEROLD'];
		}
    	}
    }else{
    	$res['res'] = false;
        $res['error'] = 'El web service no se enuentra disponible';
        $res['request'] = $url;
        $res['response'] = null;
    }	
    return $res;
  }

  public function guardarDatosPersonaLibreDeuda($datos, $tramite){
    $libreDeudaHdr = LibreDeudaHdr::where('tipo_doc', $tramite->tipo_doc)
                                  ->where('sexo', $tramite->sexo)
                                  ->where('pais', $tramite->nacionalidad)
                                  ->first();
    if(!$libreDeudaHdr)
      $libreDeudaHdr = new libreDeudaHdr();
    $libreDeudaHdr->nro_doc = $datos['DOCUMENTO'] ? $datos['DOCUMENTO'] : $tramite->nro_doc;
    $libreDeudaHdr->tipo_doc = $tramite->tipo_doc;
    $libreDeudaHdr->sexo = $tramite->sexo;
    $libreDeudaHdr->pais = $tramite->nacionalidad;
    $libreDeudaHdr->nombre = $datos['NOMBRE'] ? $datos['NOMBRE'] : "";
    $libreDeudaHdr->apellido = $datos['APELLIDO'] ? $datos['APELLIDO'] : "";
    $libreDeudaHdr->tipo_doc_text = $datos['TIPODOC'] ? $datos['TIPODOC'] : "";
    $libreDeudaHdr->calle = $datos['CALLE'] ? $datos['CALLE'] : "";
    $libreDeudaHdr->numero = $datos['NUMERO'] ? $datos['NUMERO'] : "";
    $libreDeudaHdr->piso = $datos['PISO'] ? $datos['PISO'] : "";
    $libreDeudaHdr->depto = $datos['DEPTO'] ? $datos['DEPTO'] : "";
    $libreDeudaHdr->telefono = $datos['TELEFONO'] ? $datos['TELEFONO'] : "";
    $libreDeudaHdr->localidad = $datos['LOCALIDAD'] ? $datos['LOCALIDAD'] : "";
    if($datos['PROVINCIA'])  $libreDeudaHdr->provincia = $datos['PROVINCIA'];
    $libreDeudaHdr->provincia_text = $datos['DESCPROVINCIA'] ? $datos['DESCPROVINCIA'] : "";
    $libreDeudaHdr->codigo_postal = $datos['CODIGOPOSTAL'] ? $datos['CODIGOPOSTAL'] : "";
    if($datos['SALDOPUNTOS']) $libreDeudaHdr->saldopuntos = $datos['SALDOPUNTOS'];
    if($datos['CANTIDADVECESLLEGOA0']) $libreDeudaHdr->cantidadvecesllegoa0 = $datos['CANTIDADVECESLLEGOA0'] ;
    $libreDeudaHdr->save();
    return $libreDeudaHdr;
  }

  public function guardarDatosLibreDeuda($datos, $libreDeudaHdr){
    $LibreDeudaLns = LibreDeudaLns::where('libredeuda_hdr_id', $libreDeudaHdr->libredeuda_hdr_id)->first();
    if(!$LibreDeudaLns)
      $LibreDeudaLns = new LibreDeudaLns();
    $LibreDeudaLns->libredeuda_hdr_id = $libreDeudaHdr->libredeuda_hdr_id;
    $LibreDeudaLns->numero_completo = $datos['NUMEROCOMPLETO'];
    $LibreDeudaLns->numero_id = $datos['NUMEROLD'];
    $LibreDeudaLns->digito = $datos['DIGITO'];
    $LibreDeudaLns->codigo_barras = $datos['CODIGOBARRAS'];
    $LibreDeudaLns->codigo_barras_encriptado = $datos['CODIGOBARRASENCRIPTADO'];
    $LibreDeudaLns->username = $datos['USERNAME'];
    $LibreDeudaLns->importe = $datos['IMPORTE'];
    $LibreDeudaLns->clavesb = $datos['CLAVESB'];
    $LibreDeudaLns->fecha_emision_completa = $datos['FECHAEMISIONCOMPLETA'];
    $LibreDeudaLns->hora_emision = $datos['HORAEMISION'];
    $LibreDeudaLns->fecha_emision = $datos['FECHAEMISION'];
    $LibreDeudaLns->fecha_vencimiento_completa = $datos['FECHAVENCIMIENTOCOMPLETA'];
    $LibreDeudaLns->fecha_vencimiento = $datos['FECHAVENCIMIENTO'];
    $LibreDeudaLns->save();
  }

  //API expuesta para se use desde LICTA - por problemas de permiso al servidor 
  public function api_getLibreDeuda(Request $request){
  	
	if( isset($request->nrodoc) && isset($request->tipodoc) && isset($request->nu) && isset($request->pu) )
	{
	    $username = $request->nu;
	    $password = $request->pu;

	    $userdb = new UserController();
	    if($userdb->validateCredentialsLICTA($username, $password))
	    {
	    	$url = $this->urlLibreDeuda;
    		if($this->file_contents_exist($url)){
		
	    	   $params = "method=getLibreDeuda".
             	  	"&tipoDoc=".$request->tipodoc.
             	  	"&numeroDoc=".$request->nrodoc.
             	  	"&userName=".$this->userLibreDeuda.
             	  	"&userPass=".$this->passwordLibreDeuda;
    	    	   $options  = array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false));
      	    	   $wsresult = file_get_contents($url.$params, false, stream_context_create($options));
		
		   /*
		    * Convertir el resultado en JSON
		   $response = $wsresult;
		   if($respose == false){
			 $wsresult = array( 'error'=> true, 'message' => 'Error de conexion con el WS');
		   }else{
			$p = xml_parser_create();
      			xml_parse_into_struct($p, $response, $vals, $index);
      			xml_parser_free($p);
      			$json = json_encode($vals);
      			$info = json_decode($json,TRUE);
	    		$wsresult = array( 'error'=> false, 'message' => 'Acceso verificado', 'response' => $info);
		   } */
		}else{
			$wsresult = array( 'error'=> true, 'message' => 'El web service no se encuentra disponible.');
		}
	    }else{
		$wsresult = array( 'error'=> true, 'message' => 'Acceso denegado por credenciales incorrectas.');
	    }
	}else{
		$wsresult = array( 'error'=> true, 'message' => 'Los parametros ingresados son incorrectos.');
	}
	
	return $wsresult;
  }

  /**
  * MicroservicioController: 3) Metodos asociados para completarBoletasEnTramitesAIniciar
  */
  public function completarBoletasEnTramitesAIniciar($estadoActual, $siguienteEstado){
    $personas = $this->getTramitesAIniciar($estadoActual, $this->fecha_inicio, $this->fecha_fin);
    foreach ($personas as $key => $persona)  {
      try {
        $this->buscarBoletaSafit($persona, $siguienteEstado);
      }catch(\Exception $e){
        $array = array('error' => $e->getMessage(), 'request' => "",'response' => "");
        $this->guardarError((object)$array, $siguienteEstado, $persona->id);
      }
    }
  }

  public function buscarBoletaSafit($persona, $siguienteEstado){
      $persona = TramitesAIniciar::find($persona->id);
      $res = $this->getBoleta($persona);
      if(empty($res->error)){
        $this->guardarDatosBoleta($persona, $res, $siguienteEstado);
        return 1;
      }else {
        $this->guardarError($res, $siguienteEstado, $persona->id);
        return 0;
      }
  }

  public function getBoleta($persona){
    $res = array('error' => "", 'request' => "",'response' => "");
    $boleta = null;

    //$prorroga_cuarentena = $this->obtener_prorroga_cuarentena($persona); //Ya no se usa mas

    $conexion = $this->wsSafit->iniciarSesion();
    if($conexion->success){
      $consulta = $this->wsSafit->getBoletas($persona);
      $this->wsSafit->cerrarSesion();

      $res['request'] = $consulta->request;
      $res['response'] = $consulta->response;
      $boletas = $consulta->response;

      if(isset($boletas->rspID)){
        if($boletas->rspID == 1){
          foreach ($boletas->datosBoletaPago->datosBoletaPagoParaPersona as $key => $boletaI) {
            if($this->esBoletaValida($boletaI)){ //Se saca el parametro $prorroga_cuarentena
              if(!is_null($boleta)){
                if( date($boletaI->bopFecPag) >= date($boleta->bopFecPag)) // para obtener la boleta mas reciente
                  $boleta = $boletaI;
              }else{
                $boleta = $boletaI;
              }
            }else{
              $res['error'] = "No existe ninguna boleta vigente acreditada sin utilizar";
            }
          }
        }else{
          $res['error'] = $boletas->rspDescrip;
        }
      }else{
        $res['error'] = "Ha ocurrido un error inesperado";
      }

      if(!is_null($boleta)){
        $persona = TramitesAIniciar::find($persona->id);
        $persona->sexo = $boletas->datosBoletaPago->datosPersonaBoletaPago->oprSexo;
        $persona->save();
        $res = $boleta;
      }else{
        if($boleta = $this->buscarBoletaSafitEnTurnosVencidos($persona)){
          $res['error'] = '';
          $res = $boleta;
        }
      }

    }else{
      $res = $conexion;
    }

    return (object) $res;
  }

  public function esBoletaValida($boleta, $prorroga = 0){
    $res = false;
    $dias = DIAS_VALIDEZ_BOLETA_CENAT + $prorroga;
    //verificamos que la boleta no esta utilizada, este acreditada y su fecha de pago cumpla con los meses de vigencia   
    if($boleta->bopEstado == $this->estadoBoletaNoUtilizada)
      if($boleta->munID == $this->munID)
        if($boleta->estID == $this->estID)
          if($this->fechaDeVencimientoValida($boleta->bopFecPag, $dias))
            $res = true;
    return $res;
  }

  public function fechaDeVencimientoValida($fecha, $dias){
    $nuevaFecha = strtotime ( '+'.$dias.' days' , strtotime ( $fecha ) ) ;
    if (date('Y-m-d') < date('Y-m-d', $nuevaFecha))
      $res = true;
    else
      $res = false;
    return $res;  
  }


  /**
  * MicroservicioController: 4) Metodos asociados para emitirBoletasVirtualPago
  */
  public function emitirBoletasVirtualPago($estadoActual, $estadoValidacion, $siguienteEstado){
    $tramitesAIniciar = $this->getTramitesAIniciar($estadoActual, $this->fecha_inicio, $this->fecha_fin);
    foreach ($tramitesAIniciar as $key => $tramiteAIniciar) {
      try{
        $this->gestionarBoletaSafit($tramiteAIniciar, $estadoValidacion, $siguienteEstado);
      }catch(\Exception $e){
        $array = array('error' => $e->getMessage(), 'request' => "",'response' => "");
        $this->guardarError((object)$array, $siguienteEstado, $tramiteAIniciar->id);
      }
    }
    $this->wsSafit->cerrarSesion();
  }

  public function gestionarBoletaSafit($tramiteAIniciar, $estadoValidacion, $siguienteEstado) {
    $demorado = false;
    $tramiteAIniciar = TramitesAIniciar::find($tramiteAIniciar->id);
    $tramiteAIniciar->tipo_doc = $tramiteAIniciar->tipoDocSafit();
    
    $conexion = $this->wsSafit->iniciarSesion();
    if($conexion->success){
      $res = $this->wsSafit->emitirBoletaVirtualPago($tramiteAIniciar);
      $this->wsSafit->cerrarSesion();
      if(isset($res->rspID)){
        if($res->rspID == 1){
          if(isset($res->reincidencias->rspReincidente)){
            if($res->reincidencias->rspReincidente == "P"){
              $array = array('error' => "El Cenat esta demorado",
                            'request' => $tramiteAIniciar,
                            'response' => $res);
              $this->guardarError((object)$array, $estadoValidacion, $tramiteAIniciar->id);
              $demorado = true;
            }
          }
          if(!$demorado){
            $this->guardarValidacion($tramiteAIniciar, true, $estadoValidacion, $tramiteAIniciar->bop_id);
            $this->actualizarEstado($tramiteAIniciar, $siguienteEstado);
            $this->guardarEmisionBoleta($tramiteAIniciar, $this->localhost);
          }
        }else{
          $array = array('error' => $res->rspDescrip,
                        'request' => $tramiteAIniciar,
                        'response' => $res);
          $this->guardarError((object)$array, $estadoValidacion, $tramiteAIniciar->id);
        }
      }else{
        $array = array('error' => $res,
                      'request' => $tramiteAIniciar,
                      'response' => $res);
        $this->guardarError((object)$array, $estadoValidacion, $tramiteAIniciar->id);
      }
    }else{
      $array = $conexion;
      $this->guardarError((object)$array, $estadoValidacion, $tramiteAIniciar->id);
    }
  }

  public function guardarEmisionBoleta($boleta, $ip){
    $emision = new EmisionBoletaSafit();
    $emision->numero_boleta = $boleta->bop_id;
    $emision->tipo_doc = $boleta->tipo_doc;
    $emision->nro_doc = $boleta->nro_doc;
    $emision->sexo = $boleta->sexo;
    $emision->ip = $ip;
    $emision->save();
  }

  public function getIdPais($pais){
    $pais = SigeciPaises::where('pais', $pais)->first();
    return $pais->paisAnsv->id_ansv;
  }


  /**
  * MicroservicioController: 5) Metodos asociados para verificarBuiTramites
  */
  public function verificarBuiTramites($estadoActual, $estadoValidacion, $siguienteEstado){
    $tramites = $this->getTramitesAIniciarValidaciones($estadoActual, $estadoValidacion, $this->fecha_inicio, $this->fecha_fin);
    foreach ($tramites as $key => $tramite) {
      try{
        $this->gestionarBui($tramite, $estadoValidacion, $siguienteEstado);
      }catch(\Exception $e){
        $array = array('error' => $e->getMessage(), 'request' => "",'response' => "");
        $this->guardarError((object)$array, $siguienteEstado, $tramite->id);
      }
    }
    return true;
  }

  public function gestionarBui($tramite, $estadoValidacion, $siguienteEstado){
    $conceptos = $this->obtenerConceptosBui($tramite);
    $error = 'ninguno';
      foreach ($conceptos as $key => $value) {
        $res = $this->verificarBui($tramite, $value);
        if( !empty($res['error']) ){
          if($res['error'] != $error){
            $this->guardarError((object)$res, $estadoValidacion, $tramite->id);
            $error = $res['error'];
          }
        }else {
          $this->guardarValidacion($tramite, true, $estadoValidacion, $res['comprobante']);
          $this->actualizarEstado($tramite, $siguienteEstado);
          break;
        }
      }
    return $error;
  }

  public function verificarBui($tramite, $concepto){

    \Log::info('['.date('h:i:s').'] Se inicia verificarBui() tramiteAIniciar ID: '.$tramite->id); 

   // $prorroga_cuarentena = $this->obtener_prorroga_cuarentena($tramite); //Ya no se usa
    
    $nro_boleta = null;
    $tramite = TramitesAIniciar::find($tramite->id);
    $data = array("TipoDocumento" => $tramite->tipoDocBui(),
                  "NroDocumento" => $tramite->nro_doc,
                  "ListaConceptos" => $concepto,
                  "Ultima" => "false");
    $res = $this->peticionCurl($data, $this->urlVerificacionBui, "POST", $this->userBui, $this->passwordBui);
    $mensaje = "ha ocurrido un error inesperado";

    if(empty($res->boletas)){
      if(isset($res->mensaje))
        $mensaje = $res->mensaje;
    } else {
      if($boleta = $this->existeBoletaHabilitada($res->boletas)){ //Se deja de pasar $prorroga_cuarentena
        if(!$this->boletaUtilizada($boleta)){
          $boletaBui = BoletaBui::create(array(
          'id_boleta'=>$boleta->IDBoleta,
          'nro_boleta'=>$boleta->NroBoleta,
          'cod_barras'=>$boleta->CodBarras,
          'importe_total'=>$boleta->ImporteTotal,
          'fecha_pago'=>$boleta->FechaPago,
          'lugar_pago'=>$boleta->LugarPago,
          'medio_pago'=>$boleta->MedioPago,
          'tramite_a_iniciar_id'=>$tramite->id));
          $nro_boleta = $boleta->NroBoleta;
        }else{
          $mensaje = "La boleta habilitada ya a sido utilizado en el sistema de la direccion general de licencias";
        }
      }else{
        $mensaje = "No dispone de ninguna boleta habilitada";
      }
    }
    if($nro_boleta){
      $res = array('comprobante' => $nro_boleta);
      $mensaje = 'Numero de Comprobante: ' . $res['comprobante'];
    }else{
      $res = array('error' => $mensaje, 'request' => $data, 'response' => $res);
    }
    \Log::info('['.date('h:i:s').'] '.' se ejecuto peticionCurl(), concepto: '.$concepto[0].' mensaje: '.$mensaje); 

    return $res;
  }

  public function peticionCurl($data, $url, $metodo, $user, $password){
    $data_string = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$password");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );

    $result = curl_exec($ch);
    curl_close($ch);
    $result = (object)json_decode($result, true);
    return $result;
  }

  public function existeBoletaHabilitada($boletas, $prorroga = 0){
    $res = false;
    $dias = DIAS_VALIDEZ_BOLETA_BUI + $prorroga;
    foreach ($boletas as $key => $boleta) {
      $boleta = (object)$boleta;
      $vto = substr($boleta->FechaPago,1,10);
      $nuevaFecha = strtotime ( '+'.$dias.' days' , strtotime ( $vto ) ) ;
      if (date('Y-m-d') < date('Y-m-d',$nuevaFecha)){
          $res = $boleta;
          break;
      }
    }
    return $res;
  }

  public function boletaUtilizada($boleta){
    $res = false;
    //Buscar si existe un tramite que ya uso la boleta en s_requisitos
    $requisito =  \DB::table('s_requisitos')
                  ->where('requisito_id','53')
                  ->where('valor_varchar', $boleta->NroBoleta)
                  ->orWhere('valor_varchar',$boleta->CodBarras)
                  ->count();
    if($requisito){
      $res = true;
    }else{
      //verificar si existe la boleta ya asignado a un tramite_a_iniciar
      $boleta = BoletaBui::join('tramites_a_iniciar','tramites_a_iniciar.id','boletas_bui.tramite_a_iniciar_id')
                    ->where('boletas_bui.nro_boleta', $boleta->NroBoleta)
                    ->whereNotNull('boletas_bui.tramite_a_iniciar_id')
                    ->where('tramites_a_iniciar.estado','!=',TURNO_VENCIDO)
                    ->count();
      if($boleta)
        $res = true;
    }
    
    return $res;
  }

  /**
   * MicroservicioController: 6) Metodos asociados para revisarValidaciones
   */
  public function revisarValidaciones($siguienteEstado){
    $validaciones = TramitesAIniciar::select('tramites_a_iniciar.id','tramites_a_iniciar.estado')
                  ->join('sigeci', 'sigeci.idcita', '=', 'tramites_a_iniciar.sigeci_idcita')
                  ->whereBetween('sigeci.fecha', [$this->fecha_inicio, $this->fecha_fin])
                  ->whereNotIn('tramites_a_iniciar.estado',[VALIDACIONES_COMPLETAS, INICIO_EN_SINALIC, TURNO_VENCIDO])
                  ->get();
    foreach ($validaciones as $key => $validacion) {
      if($this->validacionesTerminadas($validacion->id)){
        $tramite = TramitesAIniciar::find($validacion->id);
        $tramite->estado = $siguienteEstado; 
        $tramite->save();
      }
    }   
  }

  public function validacionesTerminadas($id){
      $res = ValidacionesPrecheck::where("tramite_a_iniciar_id", $id)->whereNotIn('validation_id',[SINALIC,CHARLA_VIRTUAL])->get();
      foreach($res as $key => $validacion)
        if (!$validacion->validado)
          return false;
      return true;
  }


  /**
   * Otras Funciones
   */

  public function guardarValidacion($tramitesAIniciar, $estado, $validation, $comprobante){
    $validacion = ValidacionesPrecheck::where('validation_id', $validation)
                                      ->where('tramite_a_iniciar_id', $tramitesAIniciar->id)
                                      ->first();
    $validacion->validado = $estado;
    $validacion->comprobante = $comprobante;
    return $validacion->save();
  }

  public function actualizarEstado($tramiteAIniciar, $siguienteEstado){
    $validaciones = ValidacionesPrecheck::where('tramite_a_iniciar_id', $tramiteAIniciar->id)
                                        ->where('validado', false)
                                        ->get();
    if(count($validaciones)==0){
      $tramiteAIniciar = TramitesAIniciar::find($tramiteAIniciar->id);
      $tramiteAIniciar->estado = $siguienteEstado;
      $tramiteAIniciar->save();
    }
  }

  public function enviarTramitesASinalic($estadoActual, $siguienteEstado){

    $this->wsSinalic->iniciarSesion();

    if(is_null($this->wsSinalic->cliente))
      return "El Ws de Sinalic no responde, por favor revise la conexion, o contactese con Nacion";

    $SinalicWS_id_sede = explode(",",SinalicWS_id_sede);
    $tramites = TramitesAIniciar::selectRaw("tramites_a_iniciar.*, sigeci.sucroca as sucursal")
                                  ->join("sigeci","sigeci.idcita","tramites_a_iniciar.sigeci_idcita")
                                  ->whereIn('sigeci.sucroca',$SinalicWS_id_sede)
                                  ->whereNull('tramites_a_iniciar.tramite_dgevyl_id')
                                  ->whereNull('tramites_a_iniciar.tramite_sinalic_id')
                                  ->where('tramites_a_iniciar.estado', $estadoActual)
                                  ->whereBetween('sigeci.fecha', [$this->fecha_inicio, $this->fecha_fin])
                                  ->get();
    foreach ($tramites as $key => $tramite) {
      $this->asignarTipoTramiteAIniciar($tramite);
      $response_ws = null;
      $res = null;
      $datos = $this->wsSinalic->parseTramiteParaSinalic($tramite);
      switch ($tramite->tipo_tramite) {
        case 2: //RENOVACION
          $response_ws = $this->wsSinalic->IniciarTramiteRenovarLicencia($datos);
          $res = (isset($response_ws->IniciarTramiteRenovarLicenciaResult))?$response_ws->IniciarTramiteRenovarLicenciaResult:$response_ws;
        break;
        case 1: //OTORGAMIENTO
          $response_ws = $this->wsSinalic->IniciarTramiteNuevaLicencia($datos);
          $res = (isset($response_ws->IniciarTramiteNuevaLicenciaResult))?$response_ws->IniciarTramiteNuevaLicenciaResult:$response_ws;
        break;
        case 6: //RENOVACION CON AMPLIACION
          $response_ws = $this->wsSinalic->IniciarTramiteRenovacionConAmpliacion($datos);
          $res = (isset($response_ws->IniciarTramiteRenovacionConAmpliacionResult))?$response_ws->IniciarTramiteRenovacionConAmpliacionResult:$response_ws;
        break;
        default:
          # code...
          break;
      }

      if($response_ws){
        /*//Corregimos casos que se requiere un renovacion pero iniciaron como nueva licencia
        $tramiteNuevaLicencia = strpos($response_ws, 'IniciarTramiteNuevaLicencia');
        if($tramiteNuevaLicencia == true && $tramite->tipo_tramite != 1){
          $tramite->tipo_tramite = 1;
          $tramite->save();
        }*/

        $resultado = $this->interpretarResultado($datos, $response_ws, $res);
        if(!empty($resultado->error)){
          $this->guardarError($resultado, $siguienteEstado, $tramite->id);
        }else {
          $tramite->estado = $siguienteEstado;
          $tramite->tramite_sinalic_id = $resultado->tramite_sinalic_id;
          $tramite->response_ws = json_encode($response_ws);
          $tramite->save();
          //ACTUALIZAMOS en validaciones_precheck
          $this->guardarValidacion($tramite, true, SINALIC, $tramite->tramite_sinalic_id);
        }
      }
    }
  }

  public function interpretarResultado($datos, $response_ws, $res){
    if(isset($res->exception)){
      $resultado = array('error' => 'El servidor no puede procesar la solicitud',
                         'request' => $datos,
                         'response' => $response_ws);
    }else{
      if(intval($res->CantidadErrores) > 0){
        $resultado = array('error' => $this->getErrores($res->MensajesRespuesta),
                           'request' => $datos,
                           'response' => $response_ws);
      }else{
        $resultado = array('mensaje' => $this->getErrores($res->MensajesRespuesta), 
                           'tramite_sinalic_id' => $res->NumeroTramite);
      }
    }
    return (object)$resultado;
  }

  public function getErrores($lista){
    $res = '';
    foreach ($lista as $key => $value)
      $res.= $value.' - ';
    return $res;
  }

  public function validarInhabilitacion($res){
    return "validarInhabilitacion";
  }

  public function asignarTipoTramiteAIniciar($tramiteAInicar){
    $ultimaLicencia = $this->getUltimaLicencia($tramiteAInicar);
    $tramiteAInicar->tipo_tramite = $this->getTipoTramite($ultimaLicencia);
    $tramiteAInicar->save();
  }

  public function getUltimaLicencia($tramiteAInicar){
    $licencias = $this->getLicencias($tramiteAInicar);
    $ultimaLicencia = null;

    if(isset($licencias->ConsultarLicenciasResult->LicenciaDTO)){
      $licencias = $licencias->ConsultarLicenciasResult->LicenciaDTO;

      if(!is_array($licencias))
        return $licencias; //Retorna Una sola licencia

      foreach ($licencias as $key => $value) {
        if(!$ultimaLicencia){
          $ultimaLicencia = $value;
          if(count($licencias) == 1)
            break;
        } else {
          $fecha = str_replace("/","-",$ultimaLicencia->FechaVencimiento);
          $fecha2 = str_replace("/","-",$value->FechaVencimiento);
          if(strtotime($fecha) < strtotime($fecha2))
            $ultimaLicencia = $value;
        }
      }
    }
    return $ultimaLicencia;
  }

  public function getLicencias($tramiteAInicar){
    $res = $this->wsSinalic->ConsultarLicencias(array(
             "nroDocumento" => $tramiteAInicar->nro_doc,
             "sexo" => $tramiteAInicar->sexo,
             "tipoDocumento" => $tramiteAInicar->tipo_doc
           ));
    return $res;
  }

  public function anularTramiteSinalic($nro_tramite,$motivo,$usuario){
      $this->wsSinalic->iniciarSesion();
      if(is_null($this->wsSinalic->cliente)){
        return "El Ws de Sinalic no responde, por favor revise la conexion, o contactese con Nacion";
        return false;
      }else{

        $res = $this->wsSinalic->AnularTramite(array(
                "nroTramite" => $nro_tramite,
                "motivo" => $motivo,
                "usuario" => $usuario
              ));

        if($res->AnularTramiteResult->CantidadErrores > 0)
          return false;
      }

    return true;
  }

  public function getTipoTramite($ultimaLicencia){
    /*****
     * tipo_tramite_id_ansv
        1 "OTORGAMIENTO"
        2 "RENOVACION"
        3 "DUPLICADO"
        4 "AMPLIACION"
        5 "REVALIDA"
        6 "RENOVACION_AMPLIACION"
    */

    if(!$ultimaLicencia || $this->licenciaVencidaMasDeUnAnio($ultimaLicencia))
      $res = 1;// OTORGAMIENTO
    else{
      if($this->estaEnJurisdiccion($ultimaLicencia, 'C.A.B.A.')){
          $res = 2;
      }else{
          if($this->esNecesarioAmpliacion($ultimaLicencia))
            $res = 6; //RENOVACION CON AMPLIACION
          else
            $res = 2; //RENOVACION
      }
    }
    return $res;
  }

  public function estaEnJurisdiccion($ultimaLicencia, $jurisdiccionTexto){
    $pos = strpos($ultimaLicencia->Domicilio->NombreLocalidad, $jurisdiccionTexto);
    return $pos !== false;
  }

  public function licenciaVencidaMasDeUnAnio($ultimaLicencia){
    $elAnioPasado = strtotime('-1 year');
    $fecha = str_replace("/","-",$ultimaLicencia->FechaVencimiento);
    $fechaVencimiento = strtotime($fecha); //se toma para m/d/YYYY de sinalic viene en d/m/y

    return $fechaVencimiento < $elAnioPasado;
  }

  public function esNecesarioAmpliacion($ultimaLicencia){
    if(isset($ultimaLicencia->Clases)){
      $clases = strtolower($ultimaLicencia->Clases);
      $clases = str_replace("a","", $clases);
      $clases = str_replace("b","", $clases);
      return preg_match("/[a-z]/i", $clases);
    }else{
      return false;
    }
  }

  public function permisoParaGenerarCenat(){
    $ip = \Request::ip();
    $clientIP = substr($ip, 0, strrpos($ip, '.'));

    $sucursales = \DB::table('sys_rpt_servers')
                    ->join('emision_boleta_safit_permisos', 'emision_boleta_safit_permisos.sucursal_id', 'sys_rpt_servers.sucursal_id')
                    ->where('emision_boleta_safit_permisos.activo', true)
                    ->get();
    $permiso=false;
    foreach($sucursales as $sucursal){
      $ip = gethostbyname($sucursal->ip);
      $sucursalIp = substr($ip, 0, strrpos($ip, '.'));
      if($sucursalIp == $clientIP)
        $permiso = true;
    }
    return $permiso;
  }

  //Generar Cenat desde buscarBoletaPago
  public function buscarBoletaPago(Request $request){
    //if($this->permisoParaGenerarCenat()){
      return View('safit.buscarBoletaPago')->with('centrosEmisores', $this->centrosEmisores->getCentrosEmisores());
    /*}else{
      Flash::warning('La sede no tiene permisos para Generar Cenat, intente desde el Precheck o Tramites Habilitados!');
      return redirect('/login');
    }*/

  }

  public function consultarCenat(Request $request){
    $boleta = $this->consultarBoletaPagoSafit($request->bop_cb, $request->cem_id);
    return View('safit.buscarBoletaPago')->with('centrosEmisores', $this->centrosEmisores->getCentrosEmisores())
                                          ->with($boleta[0], $boleta[1]);
  }

  public function generarCenat(Request $request){
    $boleta = $this->obtenerCertificadoVirtualPago($request);
    return View('safit.buscarBoletaPago')->with('centrosEmisores', $this->centrosEmisores->getCentrosEmisores())
                                          ->with('boleta', $request)
                                          ->with($boleta[0], $boleta[1]);
  }

  //Generar Cenat desde el PreCheck
  public function generarCenatPrecheck(Request $request){

    $tramiteAIniciar = TramitesAIniciar::find($request->id);
    $tramiteAIniciar->tipo_doc = $tramiteAIniciar->tipoDocSafit();

    $consulta = $this->consultarBoletaPagoSafit($request->bop_cb, $request->cem_id);

    if($consulta[0] == 'boleta'){
      $boleta = $consulta[1];
      //verificamos que los datos esten correctos para guardar en tramitesAniciar
      if($boleta->nro_doc == $tramiteAIniciar->nro_doc && $boleta->tipo_doc == $tramiteAIniciar->tipo_doc && $boleta->sexo == $tramiteAIniciar->sexo){        

        if($this->guardarDatosBoleta($tramiteAIniciar, $boleta, SAFIT))
          $consulta = $this->obtenerCertificadoVirtualPago($tramiteAIniciar);
        else
          $consulta = ['error', 'No se guardaron los datos en TramitesAIniciar'];
        
      }else{
        $consulta = ['error', 'Los datos del titular no coinciden'];
      }

    }

    //Si emitio el certificado o el Cenat ya fue emitido actualizamos en validaciones_precheck 
    if($consulta[0] == 'success')
      if($this->actualizarEnValidacionesPrecheck($request->bop_cb, $tramiteAIniciar, EMISION_BOLETA_SAFIT))
        $consulta = ['success', 'actualizado satisfactoriamente'];

    return $consulta;
  }

  //1) Consultar Boleta de Pago al WS Safit
  public function consultarBoletaPagoSafit($bop_cb, $cem_id){
    $emision = null;
    if($bop_cb < 999999999)
      $emision = EmisionBoletaSafit::where('numero_boleta', $bop_cb)->first();
    
    if ($emision === null) {
      $conexion = $this->wsSafit->iniciarSesion();
      if($conexion->success){
        $res = $this->wsSafit->consultarBoletaPago($bop_cb, $cem_id);
        $this->wsSafit->cerrarSesion();
        if(isset($res->rspID)){
          if($res->rspID == 1){
            $boleta = (object) array('nro_doc' => $res->datosBoletaPago->datosPersonaBoletaPago->oprDocumento,
                                  'tipo_doc' => $res->datosBoletaPago->datosPersonaBoletaPago->tdcID,
                                  'sexo' => $res->datosBoletaPago->datosPersonaBoletaPago->oprSexo,
                                  'nombre' => $res->datosBoletaPago->datosPersonaBoletaPago->oprNombre,
                                  'apellido' => $res->datosBoletaPago->datosPersonaBoletaPago->oprApellido,
                                  'bop_id' => $res->datosBoletaPago->bopID,
                                  'bop_cb' => $res->datosBoletaPago->bopCB,
                                  'bop_monto' => $res->datosBoletaPago->bopMonto,
                                  'bop_fec_pag' => $res->datosBoletaPago->bopFecPag,
                                  'cem_id' => $cem_id);
            $resultado = ['boleta', $boleta];
          }else{
            $resultado = ['error', $res->rspDescrip];
          }
        }else{
          $resultado = ['error',  'Ha ocurrido un error inesperado: '.$res];
        }
      }else{
        $resultado = ['error',  $conexion->error];
      }
    }else{
      $resultado = ['success', 'El Cenat ya fue emitido'];
    }

    return $resultado;
  }

  public function actualizarEnValidacionesPrecheck($bop_cb,$tramiteAIniciar, $validation_id){ 
    $actualizo = false;

    //Solo si existe en emision_boleta_safit y en tramites_a_iniciar
    $emision = EmisionBoletaSafit::whereRaw(" CAST(numero_boleta AS text) IN(SELECT bop_id from tramites_a_iniciar where bop_id = '".$bop_cb."' and id = ".$tramiteAIniciar->id.") ")->first();

    if($emision){
      if($emision->tipo_doc == $tramiteAIniciar->tipo_doc && $emision->nro_doc == $tramiteAIniciar->nro_doc && $emision->sexo == $tramiteAIniciar->sexo){
          $this->guardarValidacion($tramiteAIniciar, true, $validation_id, $emision->numero_boleta);
          $actualizo = true;
      }
    }
    return $actualizo;
  }

  //2) Emitir Certificado Virtual de Pago - WS Safit
  public function obtenerCertificadoVirtualPago($request){
    $clientIP = \Request::ip();
    $tramiteAInicar = (object) array('nro_doc' => $request->nro_doc,
                                      'tipo_doc' => $request->tipo_doc,
                                      'sexo' => $request->sexo,
                                      'nombre' => $request->nombre,
                                      'apellido' => $request->apellido,
                                      'fecha_nacimiento' => $request->fecha_nacimiento,
                                      'nacionalidad' => $request->nacionalidad,
                                      'bop_cb' => $request->bop_cb,
                                      'bop_monto' => $request->bop_monto,
                                      'bop_fec_pag' => $request->bop_fec_pag,
                                      'bop_id' => $request->bop_id,
                                      'cem_id' => $request->cem_id);
    $emision = EmisionBoletaSafit::where('numero_boleta', $request->bop_id)->first();
    if ($emision === null) {
      $conexion = $this->wsSafit->iniciarSesion();
      if($conexion->success){
        $res = $this->wsSafit->emitirBoletaVirtualPago($tramiteAInicar);
        $this->wsSafit->cerrarSesion();
        $demorado = false;
        if(isset($res->rspID)){
          if($res->rspID == 1){
            if(isset($res->reincidencias->rspReincidente))
              if($res->reincidencias->rspReincidente == "P"){
                $resultado = ['error', 'El Cenat se encuentra Demorado'];
                $demorado = true;
              }
              if(!$demorado){
                //revertimos el tipo_doc a como se usa en licta
                $sigeci = new Sigeci();
                $sigeci->idtipodoc = $request->tipo_doc;
                $request->tipo_doc = $sigeci->tipoDocLicta();
                $this->guardarEmisionBoleta($request, $clientIP);
                $resultado = ['success', $res->rspDescrip];
              }
          }else{
            $resultado = ['error', $res->rspDescrip];
          }

        }else{
          $resultado = ['error', 'Ha ocurrido un error inesperado: '.$res];
        }
      }else{
        $resultado = ['error', $conexion->error];
      }
    }else{
      $resultado = ['success', 'El Cenat ya fue emitido'];
    }
    return $resultado;
  }

  public function buscarBoletaPagoPersona(Request $request){
    $SysMultivalue = new SysMultivalue();
    $tipodocs = $SysMultivalue->tipodocs();
    $error = '';
    $boletas = null;

    if($request->nro_doc){
      $persona = TramitesAIniciar::selectRaw($request->tipo_doc.' as tipo_doc, '.$request->nro_doc.' as nro_doc')->first();
      $conexion = $this->wsSafit->iniciarSesion();
      if($conexion->success){
        $consulta = $this->wsSafit->getBoletas($persona);
        $this->wsSafit->cerrarSesion();
        $boletas = $consulta->response;
        $error = (isset($boletas->rspDescrip))?$boletas->rspDescrip:'';
      }else{        
        $error = $conexion->error;
      }
    }
    return View('safit.buscarBoletaPagoPersona')->with('tipodocs', $tipodocs)
                                                ->with('boletas', $boletas)
                                                ->with('error', $error);
  }

  public function calcularFechas(){
    $this->fecha_inicio = new \DateTime(date("Y-m-d").' + ' . $this->diasEnAdelante . ' day');
    $this->fecha_inicio = $this->fecha_inicio->format('Y-m-d');
    $xmasDay = new \DateTime($this->fecha_inicio.' + ' . $this->cantidadDias . ' day');
    $this->fecha_fin = $xmasDay->format('Y-m-d');
  }

  public function testCheckBoletas(Request $request){
    $tramite = TramitesAIniciar::find($request->id);
    $boleta = $this->getBoleta($tramite);
    dd($boleta);
  }

  public function parametros($nroDocumento, $tipoDocumento, $sexo){
    $parametros = array();
    $parametros['nroDocumento'] = $nroDocumento;
    $parametros['tipoDocumento'] = $tipoDocumento;
    $parametros['Sexo'] = $sexo;
    return $parametros;
  }

  //FUNCIONES PARA TURNOS VENCIDOS
  public function revisarTurnosVencidos(){
    try{
      //16 dias atras - no aplica para el precheck, pasara a vencido en la tarde si no asiste a su turno
      //$last_date = date('Y-m-d', strtotime('-'.(DIAS_VALIDEZ_TURNO).' days', strtotime(date('Y-m-d'))));
      
      $hoy = date('Y-m-d');

      $precheck = TramitesAIniciar::whereNull('tramite_dgevyl_id')
                                  ->where('estado', '!=', TURNO_VENCIDO)
                                  ->whereNotIn('id',  function($query) use($hoy) {
                                    $query->select('tramites_a_iniciar.id')
                                      ->from('tramites_a_iniciar')
                                      ->leftjoin('sigeci', 'sigeci.idcita', '=', 'tramites_a_iniciar.sigeci_idcita')
                                      ->leftjoin('tramites_habilitados', 'tramites_habilitados.tramites_a_iniciar_id', '=', 'tramites_a_iniciar.id')
       

//                               ->whereRaw("sigeci.fecha > '".$hoy."' OR tramites_habilitados.fecha > '".$hoy."' ");

->whereRaw("sigeci.fecha::DATE > '".$hoy."' OR tramites_habilitados.fecha::DATE > '".$hoy."' OR (tramites_habilitados.fecha::DATE >= '2021-11-25' AND tramites_habilitados.motivo_id = '29') OR (tramites_habilitados.fecha::DATE >= '2023-06-28' AND tramites_habilitados.motivo_id = '12')");

                                  })
                                  ->update(['estado' => TURNO_VENCIDO]);

      \Log::info('['.date('h:i:s').'] revisarTurnosVencidos - Se da por TURNO_VENCIDO a los precheck menores igual a : '.$hoy);

      //PENDIENTE: anular los tramites iniciados en SINALIC que pasaron a VENCIDOS  y actualizar en tramites_a_iniciar
      $tramites = TramitesAIniciar::leftjoin("ansv_tramite","ansv_tramite.numero_tramite_ansv","tramites_a_iniciar.tramite_sinalic_id")
                    ->where('tramites_a_iniciar.estado', TURNO_VENCIDO)
                    ->whereNotNull('tramites_a_iniciar.tramite_sinalic_id')
                    ->whereNull('tramites_a_iniciar.tramite_dgevyl_id')
                    ->whereNull('ansv_tramite.numero_tramite_ansv')
                    ->get();

      foreach ($tramites as $tramite){
        if($this->anularTramiteSinalic($tramite->tramite_sinalic_id,'5','microservicio_turno_vencido')){
          $actualizar = TramitesAIniciar::find($tramite->id)->update([ 'tramite_sinalic_id' => null ]);
        }
      }

    }catch(\Exception $e){
        \Log::warning('['.date('h:i:s').'] revisarTurnosVencidos Error: '.$e->getMessage()); 
    }                    
  }

  public function corregirAnsvTramite(){

      $tramites = \DB::table('ansv_tramite')->whereBetween("fecha_reporte",['2019-09-01','2019-09-01'])->whereNull('numero_tramite_ansv')->get();
      echo count($tramites); die();
      echo '<br> ';
      foreach ($tramites as $key => $tramite) {
        $numero_tramite_ansv = null;
        $ansv = \DB::table('ansv_success_msgs')->where('tramite_id',$tramite->tramite_id)->whereRaw(" numero_tramite_ansv > 0 ")->first();
        if($ansv){
          $numero_tramite_ansv = $ansv->numero_tramite_ansv;
        }else{
            $ansv = \DB::table('ansv_success_msgs')->where('tramite_id',$tramite->tramite_id)->whereRaw("metodo_llamado like 'Iniciar%' ")->first();
            if($ansv){
              $response = $ansv->response;
              $posicion = strpos($response,'NumeroTramite');

                if($posicion >0){

                  if( strpos($response,'NumeroTramite";i:') ){
                    $posicion+=17;
                  }else{
                    $posicion+=20;
                  }

                  $numero = substr($response,$posicion,8);

                  if($numero>0){
                    $numero_tramite_ansv = $numero;
                  }
                }
          }
        }

        if($numero_tramite_ansv > 0 ){
          $actualizar = \DB::table('ansv_tramite')->where('tramite_id',$tramite->tramite_id)->update([ 'numero_tramite_ansv' => $numero_tramite_ansv ]);
        }
      }
  }

  public function buscarBoletaSafitEnTurnosVencidos($tramiteAIniciar){
    $fecha_minima_pago = date('Y-m-d', strtotime('-'.(DIAS_VALIDEZ_BOLETA_CENAT).' days', strtotime(date('Y-m-d'))));
    $encontrado = null;
    $res = TramitesAIniciar::where('estado', TURNO_VENCIDO)
                    ->where('bop_fec_pag', '>', $fecha_minima_pago)
                    ->where('nacionalidad', $tramiteAIniciar->nacionalidad)
                    ->where('nro_doc', $tramiteAIniciar->nro_doc)
                    ->where('tipo_doc', $tramiteAIniciar->tipo_doc)
                    //->where('sexo', $tramiteAIniciar->sexo)
                    ->first();
    if($res)
      $encontrado = (object) array( 'bopCB' => $res->bop_cb,
                                    'bopMonto' => $res->bop_monto,
                                    'bopFecPag' => $res->bop_fec_pag,
                                    'bopID' => $res->bop_id,
                                    'cemID' => $res->cem_id);
    return  $encontrado;
  }

  public function getCharlaVirtual($tramite, $estadoValidacion){

    $WsCharlaVirtual = new WsCharlaVirtualController();
    $consulta = $WsCharlaVirtual->consultar($tramite);
    if($consulta->success){
	$codigo = $WsCharlaVirtual->guardar($consulta->response);
	$this->guardarValidacion($tramite, true, $estadoValidacion, $codigo);
    }else {
      $this->guardarError($consulta, $estadoValidacion, $tramite->id);
    }
  }

  public function obtener_prorroga_cuarentena($persona){
	  $prorroga = 0;
	  $cuarentena = \DB::table('t_cuarentena')
		 		->where('nro_doc',$persona->nro_doc)
				->where('tipo_doc',$persona->tipo_doc)
				->where('sexo',strtoupper($persona->sexo))
				->where('nacionalidad',$persona->nacionalidad)
				->count();
	  if($cuarentena){
	  	$prorroga = DIAS_PRORROGA_CUARENTENA;
	  }
	  return $prorroga;
  }

  //Generar charla
  /**
  * MicroservicioController: 1) Metodos asociados para verificarCharlaDeTramites
  */
  public function verificarCharlaDeTramites($estadoActual, $estadoValidacion, $siguienteEstado){
    $tramites = $this->getTramitesAIniciarValidaciones($estadoActual, $estadoValidacion, $this->fecha_inicio, $this->fecha_fin);
    foreach ($tramites as $key => $tramite) {
      try{
        $this->getCharlaVirtual($tramite, $estadoValidacion);
      }catch(\Exception $e){
        $array = array('error' => $e->getMessage(), 'request' => "",'response' => "");
        $this->guardarError((object)$array, $siguienteEstado, $tramite->id);
      }
    }
  }

}
