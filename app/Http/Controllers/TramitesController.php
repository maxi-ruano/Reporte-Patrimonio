<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Tramites;
use App\Http\Controllers\SigeciController;

class TramitesController extends Controller
{
    //Ignore los Estado Borrado o Cancelado
    private $estadosIgnore = ['93','94']; 
 
    public function __construct() {
      $this->Sigeci = new SigeciController();
    }

    public function buscarTramite(Request $request){
      $tramite = Tramites::where('nro_doc',$request->nro_doc)
                          ->where('tipo_doc',$request->tipo_doc)
                          ->where('sexo',$request->sexo)
                          ->where('pais',$request->pais);
      return $tramite;
    }

    public function consultarTramite(Request $request){
      $consulta = Tramites::where("tramites.tramite_id",$request->tramite_id)->first();
      //Se reemplaza id por texto de cada tabla relacionada
      if(isset($consulta->tramite_id)){
        if($consulta->tramite_id){
          $consulta->tipo_doc = $consulta->tipoDocTexto();
          $consulta->sucursal = $consulta->sucursalTexto();
          $consulta->estado_description = $consulta->estadoTexto();
          $consulta->fec_inicio = date('Y-m-d', strtotime($consulta->fec_inicio));
        }
      }
      return $consulta;
    }

    //Consulta general de los tramites iniciados con parametros en fecha o/y estado (on/off)
    public function consultarTramitesPrecheck($fecha = '', $estado = ''){
      
      $fecha = ($fecha=='')?date("Y-m-d"):$fecha;

      $tramites =  Tramites::selectRaw('tramites.nro_doc,tramites_a_iniciar.nombre,tramites_a_iniciar.apellido,tramites.tramite_id')
                          ->join('tramites_a_iniciar','tramites_a_iniciar.tramite_dgevyl_id','tramites.tramite_id')
                          ->whereIn('tramites_a_iniciar.sigeci_idcita',$this->Sigeci->getTurnos($fecha)->pluck('idcita')->toArray())
                          ->orderby('tramites.nro_doc');

      if($estado == 'on')
        $tramites->whereIn('tramites_a_iniciar.id',$this->TramitesAIniciarCompletados($fecha)->pluck('id')->toArray());
      
      if($estado == 'off') 
        $tramites->whereNotIn('tramites_a_iniciar.id',$this->TramitesAIniciarCompletados($fecha)->pluck('id')->toArray());

      $consulta = $tramites->get();

      return $consulta;
    }

    //function get para API listar los tramites con licencias emitidas
    public function get_licencias_emitidas(Request $request){
      $ip = $request->ip();
      //IP permitidas para realizar la consulta (Roca): Daniela / Yonibel / Guido
      $autorizadas = array('192.168.76.136','192.168.76.215','192.168.76.230','192.168.76.206');
      ///Secretaria de Atencion Ciudadana autorizados:
      array_push($autorizadas, '10.67.51.55','10.67.51.58','10.67.51.59','10.67.51.60','10.10.14.37', '10.10.5.95');
      ///Gerencia de SACTA
      //array_push($autorizadas,'10.209.73.19','10.209.73.23','10.209.73.24','10.209.73.26','10.209.73.38','10.209.73.39','10.209.73.40','10.209.73.54','10.209.73.54','10.209.73.123','10.209.73.166');
      array_push($autorizadas, '10.170.7.190','172.30.113.35');
      //BOTI
      array_push($autorizadas, '10.0.1.2');

      $consulta = [];

      //if(in_array($ip, $autorizadas)){
        if((isset($request->desde) && isset($request->hasta)) || isset($request->nrodoc)){

          $estado_finalizado = '95';
          $estado_completado = '14';

          //No limitar el limit Memory de PHP
          ini_set('memory_limit', '-1');

          if(isset($request->nrodoc)){
            //Mostrar solo la ultima licencia otrogada - Consulta para la Gerencia de Taxista
            $campos = " tramites.nro_doc,
                        datos_personales.apellido,
                        datos_personales.nombre,
                        CAST(tramites.fec_emision AS DATE),
                        CAST(tramites.fec_vencimiento AS DATE),
                        licencias_otorgadas.clase AS categoria,
                        (CASE WHEN CAST(tramites.fec_vencimiento AS DATE) < current_date THEN 'Si' ELSE 'No' END) as vencida";
          }else{
            //Mostrar listado de licencia otrogada - Consulta para la Atención al Ciudadano
            $campos = ' tramites.nro_doc,
                        datos_personales.apellido,
                        datos_personales.nombre,
                        datos_personales.sexo,
                        licencias_otorgadas.nacionalidad,
                        datos_personales.fec_nacimiento,
                        datos_personales.correo,
                        datos_personales.calle as calle,
                        datos_personales.numero as altura,
                        tramites.sucursal,
                        tipo_tramites.descripcion AS tipo_tramite,
                        tramites.estado,
                        CAST(tramites.fec_inicio AS DATE),
                        CAST(tramites.fec_inicio AS TIME(0)) AS hora_inicio,
                        CAST(tramites_log.modification_date AS DATE) as fec_finalizacion,
                        CAST(tramites_log.modification_date AS TIME(0)) as hora_finalizacion,
                        CAST(tramites.fec_emision AS DATE),
                        CAST(tramites.fec_vencimiento AS DATE),
                        licencias_otorgadas.clase AS categoria';
          }

          //Consulta de licencias otorgadas
          $tramites =  Tramites::selectRaw($campos)
                          ->join('licencias_otorgadas','licencias_otorgadas.tramite_id','tramites.tramite_id')
                          ->join('tipo_tramites','tipo_tramites.tipo_tramite_id','tramites.tipo_tramite_id')
                          ->join('datos_personales',function($join) {
                              $join->on('datos_personales.nro_doc', '=', 'tramites.nro_doc');
                              $join->on('datos_personales.tipo_doc', '=', 'tramites.tipo_doc');
                              $join->on('datos_personales.sexo', '=', 'tramites.sexo');
                          })
                          ->join('tramites_log',function($join) use($estado_finalizado) {
                            $join->on('tramites_log.tramite_id', 'tramites.tramite_id');
                            $join->where('tramites_log.estado', $estado_finalizado);
                          })
                          ->where('tramites.estado',$estado_completado)
                          ->orderby('tramites.fec_inicio','DESC');

          //Comrpobar si existe el filtro por Nro. Documento
          if(isset($request->nrodoc)){
            $tramites->where('tramites.nro_doc',$request->nrodoc);
            $consulta = $tramites->first();
            if($consulta == NULL)
              $consulta['message'] = "No se encontraron resultados de los datos ingresados.";
          }else{
            //validar si existen los parametros de busqueda por fecha (desde, hasta)
            if(isset($request->desde) && isset($request->hasta)){
              $fecha_desde = explode('-',$request->desde);
              $fecha_hasta = explode('-',$request->hasta);
              if(checkdate($fecha_desde[1], $fecha_desde[2], $fecha_desde[0]) && checkdate($fecha_hasta[1], $fecha_hasta[2], $fecha_hasta[0])){
                //Comprobar si este el parametro de vencida para poder hacer el filtro
                if($request->vencida)
                  $tramites->whereBetween('tramites.fec_vencimiento',[$request->desde,$request->hasta]);
                else
                  $tramites->whereBetween('tramites.fec_emision',[$request->desde,$request->hasta]);

                //Se ejecuta la consulta final obtenida
                $consulta = $tramites->get();

                if(count($consulta)){
                  if($request->export) //Solo si existe el parametro para export en: xls, xlsx, txt, csv, entre otros.
                    $this->exportFile($consulta, $request->export, 'licenciasEmitidas');
                }else{
                  $consulta['message'] = "No se encontraron resultados de los datos ingresados.";
                }
      
              }else{
                $consulta['message'] = "Las fechas ingresadas son incorrectas!";
              }
            }
          }
        }else{
          $consulta['error'] = "Los parametros ingresados son incorrectos.";
        }
      /*}else{
        $consulta['error'] = "Acceso denegado: IP ".$ip." no permitida!..";
	}*/
      return $consulta;
    }

    public function TramitesAIniciarCompletados($fecha) {
      $consulta = \DB::table('tramites_a_iniciar')
                        ->join('sigeci','sigeci.idcita','tramites_a_iniciar.sigeci_idcita')
                        ->where('sigeci.fecha',$fecha)
                        ->whereNotIn('tramites_a_iniciar.id', function($query) use($fecha) {
                          $query->select('validaciones_precheck.tramite_a_iniciar_id')
                                ->from("validaciones_precheck")
                                ->join('tramites_a_iniciar','tramites_a_iniciar.id','validaciones_precheck.tramite_a_iniciar_id')
                                ->join('sigeci','sigeci.idcita','tramites_a_iniciar.sigeci_idcita')
                                ->where('sigeci.fecha',$fecha)
                                ->whereNotIn('validaciones_precheck.validation_id',[SINALIC])
                                ->where('validaciones_precheck.validado','false')
                                ->groupBy('validaciones_precheck.tramite_a_iniciar_id');  
                        })->get();
      return $consulta;
      
    }

    //API CORRESPONDE TRAMITE
    public function get_corresponde_tramite(Request $request){
	if($request->sexo != "f" && $request->sexo != 'm' && $request->sexo != 'x'){

		$consulta['error'] = "Los parametros ingresados son incorrectos.";

	}else if(isset($request->nrodoc)&&isset($request->sexo)/*&&isset($request->tipodoc)*/){
		$nro_doc = $request->nrodoc;
		$sexo = $request->sexo;
		//$tipo_doc = $request->tipodoc;

		//Limite de fechas emision
		$fecha_emi = '2020-03-17';
		//Limite de fecha opcionales
		$fecha_ini_op = '2020-02-15';
		$fecha_fin_op = '2021-12-31';
		// Limites de fecha Obligatorios
		$fecha_ini_ob = '2022-01-01';
		$fecha_fin_ob = '2025-02-15';

		//LOGICA REIMPRESION
		/*
				Respuesta | Tramite
				    1	  | Otorgamiento
				    2	  | Renovación
				    3	  | Reimpresión Obligatoria
				    4	  | Reimpresión Opcional
				    5	  | Licencia Vigente
				    6	  | Licencia Vigente (pero debe reimprimir en el futuro)

		*/
		$ultimo_tramite = DB::select("SELECT * FROM  ultimo_tramite('$nro_doc','$sexo')");

		if(!$ultimo_tramite){ //Otorgamiento: no existe licencia
			$corresponde = 1;
		}else{

			$ultimo_tramite = $ultimo_tramite[0]; //para tomar el tramite
                        $fec_emision_licencia = $ultimo_tramite->fec_emision;
                        $fec_vencimiento_licencia = $ultimo_tramite->fec_vencimiento;

			$reimpresion = DB::select("SELECT * FROM std_validacion_reimpresiones('$nro_doc','$sexo')");

			if($reimpresion){
				$reimpresion_opcional = DB::select("SELECT * FROM std_validacion_reimpresiones_opcional('$nro_doc','$sexo')");

				if($reimpresion_opcional){
					$corresponde = 4;
				}else{
					$corresponde = 3;
				}

			}else{

				if($fec_emision_licencia >= $fecha_emi){ //despues de decreto
                                        if($fec_vencimiento_licencia < date("Y-m-d",strtotime(date('Y-m-d')."-12 month"))){
                                                $corresponde = 1;
                                        }else{
                                                if($fec_vencimiento_licencia > date("Y-m-d",strtotime(date('Y-m-d')."+ 2 month"))){
                                                                $corresponde = 5;
						}else{
								$corresponde = 2;
						}
                                        }

                                }else{ //antes del decreto
                                        if($fec_vencimiento_licencia >= $fecha_fin_ob){ //si venció después del decreto, reimpresiones obligatorias
                                                if($fec_vencimiento_licencia > date("Y-m-d",strtotime(date("Y-m-d")."-12 month"))){
							$corresponde = 1;
                                                }else{
                                                        $corresponde = 2;
                                                }
                                        }else if ($fec_vencimiento_licencia <= $fecha_ini_op){ //venció antes del decreto, reimpresiones opcionales
                                                $corresponde = 1;
                                        }else{
						if($fec_vencimiento_licencia >= $fecha_ini_op && $fec_vencimiento_licencia <= $fecha_fin_ob){
							if($fec_vencimiento_licencia > date("Y-m-d",strtotime(date('Y-m-d')."+ 2 month"))){
								$corresponde = 6; //reimpresion obligatoria pero licencia vigente
							}else if ($fec_vencimiento_licencia >= $fecha_ini_op && $fec_vencimiento_licencia <= $fecha_fin_op){
								if($fec_vencimiento_licencia <= date("Y-m-d",strtotime(date('Y-m-d')."- 22 month"))){
									$corresponde = 2;
								}else if ($fec_vencimiento_licencia <= date("Y-m-d",strtotime(date('Y-m-d')."- 36 month"))){
									$corresponde = 1;
								}else{
									$corresponde = 4; //esto es porque esta inhabilitado
								}
							}else if (($fec_vencimiento_licencia >= $fecha_ini_ob && $fec_vencimiento_licencia <= $fecha_fin_ob) && $fec_vencimiento_licencia <= date("Y-m-d",strtotime(date('Y-m-d')."-12 month"))){
								$corresponde = 1;
							}else{
								$corresponde = 3; //esto es porque esta inhabilitado
							}
						}
					}
        	                }
			} //fin else reimpre
		} //fin else ultimo tramite

		$consulta = [
			'nrodoc' => $nro_doc,
			'sexo' => $sexo,
			//'fec_emision_ultima_licencia' => isset($fec_emision_licencia) ? $fec_emision_licencia : "no hay datos",
			//'fec_vencimiento_ultima_licencia' => isset($fec_vencimiento_licencia) ? $fec_vencimiento_licencia : "no hay datos",
			'tramite_a_realizar' => $corresponde
		];
	}else{
		$consulta['error'] = "Los parametros ingresados son incorrectos.";
	}

	return response()->json($consulta);

    } //fin funcion api get_tramite_corresponde

    public function get_inhabilitacion(Request $request){
	if($request->sexo != "f" && $request->sexo != 'm' && $request->sexo != 'x'){

	        $consulta['error'] = "Los parametros ingresados son incorrectos.";

        }else if(isset($request->nrodoc)&&isset($request->sexo)/*&&isset($request->tipodoc)*/){
		$consulta = [
			'nrodoc' => $request->nrodoc,
                        'sexo' => $request->sexo,
		];

		$inhabilitado = DB::table('inhabilitados')
					->select('inhabilitados.motivo')
					//->join('motivos_inhabilitacion','motivos_inhabilitacion.motivo_id','inhabilitados.motivo') en caso de querer mandar la descripcion del motivo
					->where('doc_num',$request->nrodoc)
					->where('sexo',$request->sexo)
					->where(function($query){
						$query->where('rehabilitado',false)
							->orWhereNull('rehabilitado');
					})
					->whereNotIn('motivo',['14','15','26','27','28','29','45','46','57','58','99','100']) //retenidos y otras cosas que tratan de ser retenidos
				->get();
		if($inhabilitado->isNotEmpty()){
			$consulta['inhabilitado'] = true;
			$inhabilitado->each(function ($array) {
				switch($array->motivo){
					//Legales
					case 1:
					case 5:
					case 6:
					case 7:
					case 8:
					case 17:
					case 18:
					case 19:
					case 20:
					case 22:
					case 23:
					case 24:
					case 25:
					case 31:
					case 32:
					case 33:
					case 35:
					case 37:
					case 38:
					case 39:
					case 40:
					case 41:
					case 42:
					case 49:
					case 50:
					case 52:
					case 55:
					case 56:
					case 98:
					   $mensaje = 'tu trámite se encuentra inhabilitado.' . "\n\n";
					   $mensaje .= "Para continuar, *mandá un mail a legalesdghc@buenosaires.gob.ar* aclarando tu *nombre, apellido y número de documento*." . "\n\n";
					   $mensaje .= "Alguien del equipo te va a responder en 72 horas hábiles.";
					   $array->derivacion = $mensaje;
					   $mensaje_web = '<p><strong>Mandar un correo eléctronico</strong> a';
					   $mensaje_web .= '  <a href="mailto:legalesdghc@buenosaires.gob.ar" target="_blank;">legalesdghc@buenosaires.gob.ar</a> aclarando tu <ul>';
					   $mensaje_web .= '<li><strong>Nombre y Apellido</strong></li> <li><strong>Número de documento</strong></li>';
					   $mensaje_web .= '</ul> Te responderemos en 72 horas hábiles.</p>';
					   $array->web = $mensaje_web;
					   break;
					//DGAI
					case 2:
					case 9:
					case 10:
					case 51:
					case 53:
					   $mensaje = 'tu trámite se encuentra inhabilitado por un Controlador de la DGAI.'  . "\n\n";
					   $mensaje .= "Para resolver tu situación, tenés que *presentarte personalmente en la Sede Central de la DGAI*." . "\n";
					   $mensaje .= "Regimiento de Patricios N° 65, de 8 a 19 hs." . "\n\n";
					   $mensaje .= "Para más información, entrá a https://www.buenosaires.gob.ar/tramites/resolver-infracciones-de-transito-con-un-controlador-en-forma-presencial";
					   $array->derivacion = $mensaje;
					   $mensaje_web = '<p>Presentarte personalmente en la';
					   $mensaje_web .= ' <a href="https://www.buenosaires.gob.ar/justiciayseguridad/infracciones">Sede Central de la Dirección General de Administración de Infracciones (DGAI)</a> ubicada en: ';
					   $mensaje_web .= '<a href="https://mapa.buenosaires.gob.ar/comollego/?lat=-34.630348&lng=-58.370683&zl=15&modo=transporte&desde=Regimiento+De+Patricios+Av.+65&dir=Yrigoyen%2C+Hipolito+2346">';
					   $mensaje_web .= 'Regimiento de Patricios 65</a>, de 8 a 19 hs, para resolver las infracciones de tránsito con un controlador de faltas. <br><br>';
					   $mensaje_web .= 'Si necesitás más información ingresá a: <a href="https://www.buenosaires.gob.ar/tramites/resolver-infracciones-de-transito-con-un-controlador-en-forma-presencial">';
					   $mensaje_web .= 'resolver tus infracciones de tránsito.</a> </p>';
					   $array->web = $mensaje_web;
					   break;
					//Medico
					case 4:
					case 12:
					case 13:
					case 16:
					case 54:
					   $mensaje = "*tu trámite se encuentra inhabilitado*." . "\n\n";
					   $mensaje .= "Entrá a https://www.buenosaires.gob.ar/tramites/reconsideracion-para-licencia-de-conducir para resolverlo.";
					   $array->derivacion = $mensaje;
					   $mensaje_web = '<p> Realizar el <a href="https://www.buenosaires.gob.ar/tramites/reconsideracion-para-licencia-de-conducir">trámite de Reconsideración</a>, ';
					   $mensaje_web .= 'para ser reevaluado en tu aptitud psicofísica una vez que hayan transcurrido 180 días desde la Inhabilitación. </p>';
                                           $array->web = $mensaje_web;
					   break;
					case 3:
					case 21:
					case 30:
					case 34:
					case 43:
					case 47:
					case 48:
					   $mensaje = "tu trámite se encuentra inhabilitado." . "\n\n";
					   $mensaje .= "Para continuar, *mandá un mail a subgerenciaapfis@buenosaires.gob.ar* aclarando tu *nombre, apellido, número de documento y nacionalidad.*" . "\n\n";
					   $mensaje .= "Alguien del equipo te va a responder en 72 horas hábiles.";
					   $array->derivacion = $mensaje;
					   $mensaje_web = '<p><strong>Mandar un correo eléctronico</strong> a <a href="mailto:subgerenciaapfis@buenosaires.gob.ar" target="_blank;">subgerenciaapfis@buenosaires.gob.ar</a> aclarando tu';
					   $mensaje_web .= '<ul><li><strong>Nombre y Apellido</strong></li> <li><strong>Número de documento</strong></li> <li><strong>Nacionalidad</strong></li>';
					   $mensaje_web .= '</ul>Te responderemos en 72 horas hábiles.</p>';
					   $array->web = $mensaje_web;
					   break;
					default:
					   $mensaje = "tu trámite se encuentra inhabilitado." . "\n\n";
                                           $mensaje .= "Para continuar, *mandá un mail a legalesdghc@buenosaires.gob.ar* aclarando tu *nombre, apellido y número de documento*." . "\n\n";
                                           $mensaje .= "Alguien del equipo te va a responder en 72 horas hábiles.";
					   $array->derivacion = $mensaje;
					   $mensaje_web = '<p><strong>Mandar un correo eléctronico</strong> a';
                                           $mensaje_web .= '  <a href="mailto:legalesdghc@buenosaires.gob.ar" target="_blank;">legalesdghc@buenosaires.gob.ar</a> aclarando tu <ul>';
                                           $mensaje_web .= '<li><strong>Nombre y Apellido</strong></li> <li><strong>Número de documento</strong></li>';
                                           $mensaje_web .= '</ul> Te responderemos en 72 horas hábiles.</p>';
                                           $array->web = $mensaje_web;
					   break;
				}
			});
			//dd($inhabilitado);
			$consulta['inhabilitaciones'] = $inhabilitado;
		}else{
			$consulta['inhabilitado'] = false;
		}
//		dd($consulta);

	}else{
		$consulta['error'] = "Los parametros ingresados son incorrectos.";
	}
	return response()->json($consulta);
    }

    public function get_retencion(Request $request){
	if($request->sexo != "f" && $request->sexo != 'm' && $request->sexo != 'x'){

                $consulta['error'] = "Los parametros ingresados son incorrectos.";

        } else if(isset($request->nrodoc)&&isset($request->sexo)/*&&isset($request->tipodoc)*/){
                $consulta = [
                        'nrodoc' => $request->nrodoc,
                        'sexo' => $request->sexo,
                ];

                $retenido = DB::table('inhabilitados')
                                        ->select('inhabilitados.motivo','descripcion')
                                        ->join('motivos_inhabilitacion','motivos_inhabilitacion.motivo_id','inhabilitados.motivo')
                                        ->where('doc_num',$request->nrodoc)
                                        ->where('sexo',$request->sexo)
                                        ->where(function($query){
                                                $query->where('rehabilitado',false)
                                                        ->orWhereNull('rehabilitado');
                                        })
                                        ->whereIn('motivo',['26','27','28','29','45','57','58','100']) //retenidos y otras cosas que tratan de ser retenidos
					->get();

		if($retenido->isNotEmpty()){
			$consulta['retenido'] = true;
			$retenido->each(function($array){
				switch($array->motivo){
					case 26:
						$array->derivacion = "";
						break;
					case 27:
						$array->derivacion = "";
						break;
					case 28:
						$array->derivacion = "";
						break;
					case 29:
						$array->derivacion = "";
						break;
					case 45:
						$array->derivacion = "";
						break;
					case 57:
						$array->derivacion = "";
						break;
					case 58:
						$array->derivacion = "";
						break;
					case 100:
						$array->derivacion = "";
						break;
				}
			});
			$consulta['retencion'] = $retenido;
		}else{
			$consulta['retenido']= false;
		}
	}
	return response()->json($consulta);
    }
}
