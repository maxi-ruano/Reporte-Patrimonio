<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TramitesAIniciar;
use App\Http\Controllers\TramitesAIniciarController;
use App\Http\Controllers;
use App\Tramites;
use Log;

class MicroservicioController extends Controller
{
    public function __construct(){
      ini_set('default_socket_timeout', 600);
    }

    public function revisarTurnosVencidos(){
      \Log::warning('['.date('h:i:s').'] inicio revisarTurnosVencidos()');
      $tramitesAIniciar = new TramitesAInicarController();
      $tramitesAIniciar->revisarTurnosVencidos();
    }

    public function completarTurnosEnTramitesAIniciar(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: completarTurnosEnTramitesAIniciar()');
      $tramitesAIniciar = new TramitesAInicarController();
      $tramitesAIniciar->completarTurnosEnTramitesAIniciar( INICIO );
    }

    public function verificarLibreDeudaDeTramites(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: verificarLibreDeudaDeTramites()');
      $tramitesAIniciar = new TramitesAInicarController();
      $tramitesAIniciar->verificarLibreDeudaDeTramites(INICIO, LIBRE_DEUDA, VALIDACIONES);
    }

    public function completarBoletasEnTramitesAIniciar(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: completarBoletasEnTramitesAIniciar()');
      $tramitesAIniciar = new TramitesAInicarController();
      $tramitesAIniciar->completarBoletasEnTramitesAIniciar( INICIO, SAFIT);
    }

    public function emitirBoletasVirtualPago(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: emitirBoletasVirtualPago()');
      $tramitesAIniciar = new TramitesAInicarController();
      $tramitesAIniciar->emitirBoletasVirtualPago( SAFIT, EMISION_BOLETA_SAFIT,   VALIDACIONES);
    }

    public function verificarBuiTramites(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: verificarBuiTramites()');
      $tramitesAIniciar = new TramitesAInicarController();
      $tramitesAIniciar->verificarBuiTramites( INICIO, BUI, VALIDACIONES);
    }

    public function revisarValidaciones(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: revisarValidaciones()');
      $tramitesAIniciar = new TramitesAInicarController();
      $tramitesAIniciar->revisarValidaciones(VALIDACIONES_COMPLETAS);
    }

    public function enviarTramitesASinalic(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: enviarTramitesASinalic()');
      $tramitesAIniciar = new TramitesAInicarController();
      $tramitesAIniciar->enviarTramitesASinalic( VALIDACIONES_COMPLETAS, INICIO_EN_SINALIC);
    }

    public function tramitesReimpresionStd(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: tramitesReimpresionStd()');
      $tramitesHabilitadosController = new TramitesHabilitadosController();

     $ws_fecDes =  date('Y-m-d', mktime(0,0,0,date('m'),date('d')-1,date('Y')));
     $ws_fecHas =  date('Y-m-d');


     $ws_fecDes = '2022-07-27';
     $ws_fecHas = '2022-07-27';



      $ws_estado = 'Abierto';
      $ws_esquema = 'Verificada';
      $ws_metodo = 'ReimpresiondeCredenciales';

      $tramitesHabilitadosController->tramitesReimpresionStd($ws_fecDes,$ws_fecHas,$ws_estado,$ws_esquema,$ws_metodo);
    }

    public function reimpresionesLicenciaEmitida()
    {
      \Log::info('['.date('h:i:s').'] '.'se inicio: reimpresionesLicenciaEmitida()');
      $tramitesHabilitadosController = new TramitesHabilitadosController();
      $tramitesHabilitadosController->reimpresionesLicenciaEmitida(); 
      \Log::info('['.date('h:i:s').'] '.'finalizo: reimpresionesLicenciaEmitida()');

    }

    public function tramitesDuplicadosStd(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: tramitesDuplicadosStd()');
      $tramitesHabilitadosController = new TramitesHabilitadosController();

     $ws_fecDes =  date('Y-m-d', mktime(0,0,0,date('m'),date('d')-1,date('Y')));
     $ws_fecHas =  date('Y-m-d');


     $ws_fecDes = '2022-07-27';
     $ws_fecHas = '2023-07-27';


      $ws_estado = 'Abierto';
      $ws_esquema = 'Verificada';
      $ws_metodo = 'Duplicadolicencias';

      $tramitesHabilitadosController->tramitesReimpresionStd($ws_fecDes,$ws_fecHas,$ws_estado,$ws_esquema,$ws_metodo);
      \Log::info('['.date('h:i:s').'] '.'finalizó: tramitesDuplicadosStd()');
    }

    public function verificarCharlaTramites(){
      \Log::info('['.date('h:i:s').'] '.'se inicio: verificarCharlaTramites()');
      $tramitesAIniciar = new TramitesAInicarController();
      $tramitesAIniciar->verificarCharlaDeTramites( INICIO, CHARLA_VIRTUAL, VALIDACIONES);
      \Log::info('['.date('h:i:s').'] '.'finalizo: verificarCharlaTramites()');
    }

    public function run(){
      ini_set('default_socket_timeout', 600);
      $tramitesAIniciar = new TramitesAInicarController();
      //dd('entro a Microservicio');
      //$tramitesAIniciar->revisarTurnosVencidos();
      //  pasa a estarunPrecheckdo 1
      //$tramitesAIniciar->completarTurnosEnTramitesAIniciar( INICIO );
      // Verificar LirunPrecheckbre deuda, pasa a estado 4 en validaciones precheck
      $tramitesAIniciar->verificarLibreDeudaDeTramites(INICIO, LIBRE_DEUDA, VALIDACIONES); //ID validacion 4
      // pasa de estarunPrecheckdo 1 a 2 los tramites
      $tramitesAIniciar->completarBoletasEnTramitesAIniciar( INICIO, SAFIT);
      // Emitir cenatrunPrecheck solo si estado 2
      $tramitesAIniciar->emitirBoletasVirtualPago( SAFIT, EMISION_BOLETA_SAFIT,   VALIDACIONES); //ID validacion 3
      // Emitir cenat solo si estado 1 ya actualiza validaciones_precheck
      $tramitesAIniciar->verificarBuiTramites( INICIO, BUI, VALIDACIONES); //ID validacion 5
      // Si bui, cenat y infracciones pasa de estado 2 a 6
      $tramitesAIniciar->revisarValidaciones(VALIDACIONES_COMPLETAS);
      // pasa de estado 6 a 7 los tramites
      //$tramitesAIniciar->enviarTramitesASinalic( VALIDACIONES_COMPLETAS, INICIO_EN_SINALIC);
      //
    }

    public function runPrecheck(Request $request){

      ini_set('default_socket_timeout', 600);

      $tramitesAIniciar = new TramitesAInicarController();
      $tramite = TramitesAIniciar::find($request->id);
      $precheck='';
      
      switch ($request->validation) {
        case EMISION_BOLETA_SAFIT:
          if($tramitesAIniciar->buscarBoletaSafit($tramite, SAFIT))
            $precheck = $tramitesAIniciar->gestionarBoletaSafit($tramite, EMISION_BOLETA_SAFIT, VALIDACIONES);
          else
            $precheck = 'No se encontro la Boleta Safit';
        break;
        case LIBRE_DEUDA:
          $precheck = $tramitesAIniciar->gestionarLibreDeuda($tramite, LIBRE_DEUDA, VALIDACIONES);
        break;
        case BUI:
          $precheck = $tramitesAIniciar->gestionarBui($tramite, BUI, VALIDACIONES);
        break;
        case CHARLA_VIRTUAL:
          $precheck = $tramitesAIniciar->getCharlaVirtual($tramite, CHARLA_VIRTUAL);
        break;
        default:
          $precheck = 'No se realizo ninguna operacion, validation incorrecta';
          break;
      }
      return $precheck;
    }
}
