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
								}
							}else if (($fec_vencimiento_licencia >= $fecha_ini_op && $fec_vencimiento_licencia <= $fecha_fin_op) && $fec_vencimiento_licencia >= date("Y-m-d",strtotime(date('Y-m-d')."-12 month"))){
								$corresponde = 1;
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
					->select('inhabilitados.motivo','descripcion')
					->join('motivos_inhabilitacion','motivos_inhabilitacion.motivo_id','inhabilitados.motivo')
					->where('doc_num',$request->nrodoc)
					->where('sexo',$request->sexo)
					->where(function($query){
						$query->where('rehabilitado',false)
							->orWhereNull('rehabilitado');
					})
					->whereNotIn('motivo',['14','15','26','27','28','29','45','46','57','58','100']) //retenidos y otras cosas que tratan de ser retenidos
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
					case 55:
					case 56:
					case 98:
					   $array->derivacion = "legalesdghc@buenosaires.gob.ar";
					   $array->requisitos = "En el asunto del mail indicar: nombre, apellido y número de documento. En el cuerpo del mail indicar el motivo de inhabilitación";
					   break;
					//DGAI
					case 0:
					case 2:
					case 9:
					case 10:
					case 51:
					case 53:
					   $array->derivacion = "https://www.buenosaires.gob.ar/tramites/resolver-infracciones-de-transito-con-un-controlador-en-forma-presencial";
					   $array->requisitos = "";
					   break;
					//Medico
					case 4:
					case 12:
					case 13:
					case 16:
					case 54:
					   $array->derivacion = "https://www.buenosaires.gob.ar/tramites/reconsideracion-para-licencia-de-conducir";
					   $array->requisitos = "";
					   break;
					case 3:
					case 21:
					case 30:
					case 34:
					case 43:
					case 47:
					case 48:
					   $array->derivacion = "subgerenciaapfis@buenosaires.gob.ar";
					   $array->requisitos = "En el asunto del mail indicar el motivo de inhabilitación. En el cuerpo del mail indicar: nombre, apellido, numero de documento y nacionalidad";
					   break;
					default:
					   $array->derivacion = "";
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
}
