<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SoapController;
use SoapClient;
use App\ModoAutonomoLog;
use App\SysConfig;

class WsClienteSafitController extends Controller
{
  private $url;
  private $uswID;
  private $uswPassword;
  private $uswHash;
  private $munID;
  var $ingID = null;
  var $cliente = null;
  var $sesID = null;
  
  public function __construct(){
    $this->crearConstantes();
    $this->url = SafitWS_ws_url;
    $this->uswID = SafitWS_userName;
    $this->uswPassword = SafitWS_userPass;
    $this->uswHash = SafitWS_userHash;
    $this->munID = SafitWS_munID;
  }

  public function iniciarSesion(){
    $response = null;
    try {

      $conexion = $this->verificarSwSafit();
      if($conexion->success){

        $res = $this->cliente->abrir_sesion( $this->uswID, $this->uswPassword, $this->uswHash);
        if( isset($res->sesID) && isset($res->ingID) ){
          $this->sesID = $res->sesID;
          $this->ingID = $res->ingID;

          $response = array(
            'success' => true,
            'request' => $this->cliente,
            'response' => $res
          );

        }else{
          $response = array(
            'success' => false,
            'error' => $res->rspDescrip,
            'request' => 'abrir_sesion',
            'response' => $res
          );
        }
      }else{
        $response = $conexion;
      }

    }catch(\Exception $e) {
      ModoAutonomoLog::create(array('ws' => 'safit-abrir_sesion', 'description' => $e->getMessage()));
      $response = array(
          'success' => false,
          'error' => "Error iniciar sesion en el web service de Safit",
          'request' => $this->url,
          'response' => $e->getMessage()
      );
    }

    return (object) $response;
  }

  public function cerrarSesion(){
    $this->cliente->cerrar_sesion($this->uswID, $this->ingID);
  }

  public function getBoletas($persona){
    try {
      $response = $this->cliente->consultar_boleta_pago_persona($this->uswID,
                                                   $this->ingID,
                                                   $this->munID,
                                                   $persona->nro_doc,
                                                   $persona->tipoDocSafit());
    }
    catch(\Exception $e) {
      ModoAutonomoLog::create(array('ws' => 'safit-consultar_boleta_pago_persona', 'description' => $e->getMessage()));
      $response = $e->getMessage();
    }
    $res = array('request' => array(
                              'url' => parse_url($this->url),
                              'method' => 'consultar_boleta_pago_persona',
                              'nro_doc' => $persona->nro_doc,
                              'tipo_doc' => $persona->tipoDocSafit()),
                 'response' => $response);
    return (object) $res;
  }

  public function existeClienteSoap(){
    return $this->cliente != null;
  }

  public function createClienteSoap(){
    $res = null;
    try {
        $context = stream_context_create(array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
            )
        ));
        $soapClientOptions = array(
                'stream_context' => $context,
                'soap_version' => SOAP_1_1,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => 1,
                'exceptions' => true
        );
        $res = $this->cliente = new SoapClient($this->url, $soapClientOptions);
            
      }catch(\Exception $e) {
        ModoAutonomoLog::create(array('ws' => 'safit-SoapClient', 'description' => $e->getMessage()));
      }
    return $res;
  }

  public function emitirBoletaVirtualPago($tramiteAIniciar){
    $datosComprobante = array("nroDoc" => $tramiteAIniciar->nro_doc,
                              "tdcID" => $tramiteAIniciar->tipo_doc,
                              "sexo" => $tramiteAIniciar->sexo,
                              "nombre" => $tramiteAIniciar->nombre,
                              "apellido" => $tramiteAIniciar->apellido,
                              "fechaNac" => $tramiteAIniciar->fecha_nacimiento,
                              "nacionalidad" => $tramiteAIniciar->nacionalidad,// revisar se le esta mandando texto y deberia se
                              "nombre_materno" => "",
                              "apellido_materno" => "",
                              "nombre_paterno" => "",
                              "apellido_paterno" => "");

      $datosPago = array("codigo" => $tramiteAIniciar->bop_cb,
                         "importe" => $tramiteAIniciar->bop_monto,
                         "fechaPago" => $tramiteAIniciar->bop_fec_pag,
                         "codigoComprobante" => $tramiteAIniciar->bop_id);
    $res = null;
    try {
      $res = $this->cliente->obtener_certificado_virtual_pago($this->uswID,
                                                              $this->ingID,
                                                              $this->munID,
                                                              $tramiteAIniciar->cem_id,
                                                              $datosComprobante,
                                                              $datosPago);
    }catch(\Exception $e) {
        ModoAutonomoLog::create(array('ws' => 'safit-obtener_certificado_virtual_pago', 'description' => $e->getMessage()));
        $res = $e->getMessage();
    }
    return $res;
  }

  public function consultarBoletaPago($bopCB, $cemID){
    try {
      $res = $this->cliente->consultar_boleta_pago( $this->uswID,
                                                    $this->ingID,
                                                    $this->munID,
                                                    $cemID,
                                                    $bopCB);
    }catch(\Exception $e) {
        ModoAutonomoLog::create(array('ws' => 'safit-consultar_boleta_pago', 'description' => $e->getMessage()));
        $res = $e->getMessage();
    }
    return $res;
  }

  public function verificarSwSafit(){
    $conexion = false;
    $message = "";
    $clienteSoap = $this->cliente;
    if(!$this->existeClienteSoap())
      $clienteSoap = $this->createClienteSoap();

    if(!is_null($clienteSoap)){
      $cliente = $clienteSoap->eco();
      if(isset($cliente->safDisponible))
        if($cliente->safDisponible == 1)
          $conexion = true;
    }

    if(!$conexion)
      $message = "El web service de Safit no se encuentra disponible";

    $response = array(
      'success' => $conexion,
      'error' => $message,
      'request' => parse_url($this->url), 
      'response' => $clienteSoap 
    );
    
    return (object) $response;
  }

}
