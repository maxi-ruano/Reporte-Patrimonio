<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SysUsers;
use App\Tramites;
use App\SysMultivalue;
use Illuminate\Support\Facades\DB;

class AppMovilController extends Controller
{
    public function auth(Request $request)
    {
	$user = SysUsers::join('sys_user_role','sys_users.id','sys_user_role.user_id')
		->where('username',$request->username)
		->whereIn('role_id',[8,79])
		->where('sector',9)
		->first();

	if($user){
		if(hash('md5',$request->password) == $user->password){
			$response = [
				"login" => true,
				"error" => null,
				"id_user" => $user->id,
			];
			return response()->json($response);
		}else{
			$response = [
                                "login" => false,
				"error" => "Error en credenciales",
                        ];
			return response()->json($response);
		}
	}else{
		$response = [
                        "login" => false,
			"error" => "Error en credenciales",
                ];
		return response()->json($response);
	}
    }

    public function buscarTramite(Request $request)
    {
		$codigopais = SysMultivalue::where('description','ILIKE',"%".$request->description."%")
		->select('id')
		->first();

		$codigoelegido = $codigopais->id;

		$tramite = Tramites::where('tramites.nro_doc',$request->nro_doc)
		->select('tramites.tramite_id', 'tramites.nro_doc', 'tramites.sexo' , 'datos_personales.nombre' ,'datos_personales.apellido','tramites.pais', 'tramites.fec_inicio')
		->where('tramites.tipo_doc',$request->tipo_doc)
		->where('tramites.sexo',$request->sexo)
		->where('tramites.pais',$codigoelegido)
		->where('estado',9)
		->join('datos_personales','tramites.nro_doc','datos_personales.nro_doc')->orderBy('tramite_id','desc')->first();
		//dd($tramite);

		if($tramite)
		{

		    $fec_vencimiento_tramite = date('Y-m-d',strtotime($tramite->fec_inicio."+180 days"));

		    if($fec_vencimiento_tramite < date('Y-m-d')){
			$response = [
				"inicio" => false,
				"estado" => 100, //pusimos 100 como representacion de que esta vencido
			];
		    }else{
			$clases = DB::table('tramites_clases')
			->select('clase','description')
			->join('sys_multivalue','id','clase')
			->where('tramite_id',$tramite->tramite_id)
			->where('type','CLAS')
			->where('otorgada',true)
			->get();

			$patentes = DB::table('tramites_patentes')
			->select('instancia','patente')
			->where('tramite_id',$tramite->tramite_id)
			->get();

			$fechas = DB::table('s_practico')
			->select('fecha1_reprobado','fecha2_reprobado','fecha3_reprobado','aprobado')
			->where('tramite_id',$tramite->tramite_id)
			->get();
			//dd($fechas);

			$patentesYFechas = DB::table('s_practico')
			->select('s_practico.clase','fecha1_reprobado','fecha2_reprobado','fecha3_reprobado','aprobado','instancia','patente')
			->join('tramites_patentes', function($join) {
				$join->on('s_practico.tramite_id','tramites_patentes.tramite_id');
				$join->on('s_practico.clase','tramites_patentes.clase');
			})
                        ->where('s_practico.tramite_id',$tramite->tramite_id)
			->get();

			foreach($clases as $clase){
				$clasenum = $clase->clase;
				$s_practico = DB::table('s_practico')
        	                ->select('fecha1_reprobado','fecha2_reprobado','fecha3_reprobado','aprobado','instancia','patente')
	                        ->join('tramites_patentes', function($join){
                        	        $join->on('s_practico.tramite_id','tramites_patentes.tramite_id');
                                	$join->on('s_practico.clase','tramites_patentes.clase');
                	        })
				->where('s_practico.clase',$clasenum)
        	                ->where('s_practico.tramite_id',$tramite->tramite_id)
	                        ->get();
				$clase->fechas = $s_practico->toArray();
			}


			//dd($clases);

			$response = [
				"inicio" => true,
				"tramite" => $tramite,
				"clases" => $clases,
				//"patentesYFechas" => $patentesYFechas
			];
		    }
		}else{
			$estado = Tramites::where('tramites.nro_doc',$request->nro_doc)
				->select('estado')
			        ->where('tramites.tipo_doc',$request->tipo_doc)
			        ->where('tramites.sexo',$request->sexo)
		        	->where('tramites.pais',$codigoelegido)
			        ->whereNotIn('estado',[9])
			->orderBy('tramite_id','desc')->first();

			$response = [
				"inicio" => false,
				"estado" => $estado->estado,
			];
		}
	return response()->json($response);
    }

	public function postExamen(Request $request)
    {

	$tramite_id = $request->tramiteId;
	$patente = $request->patente;
	$aprobado = $request->estado;
	$fecha = $request->fecha;
	$columna = $request->ubicacion;
	$clase_description = $request->categoriaSeleccionada;
	$user_id = $request->id_user;
	$pasa_estado = true;

	switch($columna){
		case 'fecha1_reprobado':
			$examinador = 'examinador1';
			$columna_patente = 'patente1_reprobado';
			$s_practico = [
                                'aprobado' => null,
                                'fecha2_reprobado' => null,
                                'fecha3_reprobado' => null,
				'examinador2' => null,
                                'examinador3' => null
		        ];
			break;
		case 'aprobado':
			$examinador = 'examinador1';
			$columna_patente = 'patente_aprobado';
			$s_practico = [
                                'fecha1_reprobado' => null,
                                'fecha2_reprobado' => null,
                                'fecha3_reprobado' => null,
				'examinador2' => null,
				'examinador3' => null
                        ];
			break;
		case 'fecha2_reprobado':
			$examinador = 'examinador2';
			$columna_patente = 'patente2_reprobado';
			break;
		case 'fecha3_reprobado':
			$examinador = 'examinador3';
			$columna_patente = 'patente3_reprobado';
			break;
	}

	$clase = DB::table('sys_multivalue')->where('type','CLAS')->where('description',$clase_description)->first();

	if(!$clase){
		$response = [
	                "error" => true,
                	"data" => "La clase no existe"
        	];
        	return response()->json($response);
	}

	$clase = $clase->id;
	$practico = DB::table('s_practico')->where('tramite_id',$tramite_id)->where('clase',$clase)->first();

	if($practico){
		$update_practico = DB::table('s_practico')->where('tramite_id',$tramite_id)->where('clase',$clase)
				->update([
					$columna => $fecha,
					$examinador => $user_id,
					'modified_by' => $user_id,
					'modification_date' => date('Y-m-d H:i:s')
				]);

		$update_patentes = DB::table('tramites_patentes')->insert([
					'tramite_id' => $tramite_id,
					'instancia' => $columna_patente,
					'patente' => $patente,
					'modified_by' => $user_id,
					'modification_date' => date('Y-m-d H:i:s'),
					'clase' => $clase
				]);

		$tramites_clases = DB::table('tramites_clases')->where('tramite_id',$tramite_id)->where('otorgada',true)->get();
         	foreach($tramites_clases as $tramite_clase){
                	$practico = DB::table('s_practico')->where('tramite_id',$tramite_id)->where('clase',$tramite_clase->clase)->first();
                	 if(!$practico || !$practico->aprobado){
                        	$pasa_estado = false;
                         	break;
                 	}
         	}
		if($pasa_estado){
			$tramite = DB::table('tramites')->where('tramite_id',$tramite_id)->update(['estado' => 12,'modified_by' => $user_id,'modification_date' => date('Y-m-d H:i:s')]);
		}
		//dd('existe');

	}else{
		$tramite_log = DB::table('tramites_log')->where('tramite_id',$tramite_id)->where('estado',1)->first(); // se busca en tramites_log porque en tramites trae a la sucursal como null

		$datos = [
			'tramite_id' => $tramite_id,
                        $columna => $fecha,
                        $examinador => $user_id,
                        'created_by' => $user_id,
                        'modified_by' => $user_id,
                        'clase' => $clase,
                        'sucursal' => $tramite_log->sucursal
		];

		$insert = array_merge($datos,$s_practico);
//		dd($insert);
		$update_practico = DB::table('s_practico')->insert($insert);

                $update_patentes = DB::table('tramites_patentes')->insert([
                                        'tramite_id' => $tramite_id,
                                        'instancia' => $columna_patente,
					'patente' => $patente,
                                        'modified_by' => $user_id,
                                        'modification_date' => date('Y-m-d H:i:s'),
                                        'clase' => $clase
                                ]);

		$tramites_clases = DB::table('tramites_clases')->where('tramite_id',$tramite_id)->where('otorgada',true)->get();
	        foreach($tramites_clases as $tramite_clase){
        	        $practico = DB::table('s_practico')->where('tramite_id',$tramite_id)->where('clase',$tramite_clase->clase)->first();
                	if(!$practico || !$practico->aprobado){
                        	$pasa_estado = false;
	                        break;
        	        }
        	}
		if($pasa_estado){
			$tramite = DB::table('tramites')->where('tramite_id',$tramite_id)->update(['estado' => 12,'modified_by' => $user_id,'modification_date' => date('Y-m-d H:i:s')]);
		}
		//dd('no existe');
	}
	$response = [
		"error" => false,
		"data" => "Todo ok"
	];
	return response()->json($response);
    }
}
