<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SysMultivalue;
use App\User;
use App\TramitesHabilitados;
use App\AnsvPaises;
use App\AnsvCelExpedidor;
use App\DatosPersonales;
use Laracasts\Flash\Flash;
use Illuminate\Support\Facades\Auth;
use App\TramitesAIniciar;
use App\ValidacionesPrecheck;
use App\Jobs\ProcessPrecheck;
use App\Sigeci;
use App\TramitesHabilitadosMotivos;
use App\Http\Controllers\TramitesAInicarController;
use Carbon\Carbon;
use App\Tramites;


class TramitesHabilitadosController extends Controller
{
    private $path = 'tramiteshabilitados';
    public $centrosEmisores = null;

    public function __construct(){
        $this->centrosEmisores = new AnsvCelExpedidor();
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $fecha = isset($_GET['fecha'])?$_GET['fecha']:'';

        $user = Auth::user();
        $admin = $user->roles->where('name','Admin')->count();
        $administrador = $user->roles->where('name','Administrador Tramites Habilitados')->count();
        $auditor = $user->roles->where('name','Auditoria')->count();
        
        $data = TramitesHabilitados::selectRaw('tramites_habilitados.*, tramites_habilitados_observaciones.observacion, tramites_a_iniciar.tramite_dgevyl_id')
                    ->leftjoin('tramites_a_iniciar','tramites_a_iniciar.id','tramites_habilitados.tramites_a_iniciar_id')
                    ->leftjoin('tramites_habilitados_observaciones','tramites_habilitados_observaciones.tramite_habilitado_id','tramites_habilitados.id')
                    ->where(function($query) use ($request) {
                        $query->where('tramites_habilitados.nombre', 'iLIKE', '%'. $request->search .'%')
                            ->orWhere('tramites_habilitados.apellido', 'iLIKE', '%'. $request->search .'%')
                            ->orWhereRaw("CAST(tramites_habilitados.nro_doc AS text) iLIKE '%$request->search%' ");
                    })
                    ->orderBy('tramites_habilitados.updated_at','desc');
        if($fecha)
            $data = $data->where('fecha',$fecha);
        
        if(isset($request->sucursal))
            $data = $data->where('sucursal',$request->sucursal);
        
        if(!$auditor && !$admin && !$administrador)
            $data = $data->whereIn('tramites_habilitados.motivo_id', $this->getRoleMotivos('role_motivos_lis'));
                    
        //Verificar si tiene permisos para filtrar solo los que registro
        $user = Auth::user();
        if($user->hasPermissionTo('view_self_tramites_habilitados'))
            $data = $data->where('user_id',$user->id);
        
        if($user->hasPermissionTo('view_sede_tramites_habilitados'))
            $data = $data->where('sucursal',$user->sucursal);
        
        //Finalizar Query con el Paginador
        $data = $data->paginate(10);
        
        //Se reemplaza id por texto de cada tabla relacionada
        if(count($data)){
            foreach ($data as $key => $value) {
                $buscar = TramitesHabilitados::find($value->id);
                $value->tipo_doc = $buscar->tipoDocText();
		        $value->pais = $buscar->paisTexto();
		        $value->rol = $buscar->rolTexto();
                $value->user_id = $buscar->userTexto($value->user_id);
                $value->habilitado_user_id = $buscar->userTexto($value->habilitado_user_id);
                $value->motivo_id = $buscar->motivoTexto();
                $value->sucursal = $buscar->sucursalTexto();
                $value->fecha = date('d-m-Y', strtotime($value->fecha));
                $value->deleted_by = $buscar->userTexto($value->deleted_by);
            }
        }

        //Se envia listado de las Sucursales para el select del buscar
        $SysMultivalue = new SysMultivalue();        
        $sucursales = $SysMultivalue->sucursales();

        return view($this->path.'.index')->with('data', $data)
                                         ->with('centrosEmisores', $this->centrosEmisores->getCentrosEmisores())
                                         ->with('sucursales', $sucursales);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $fecha_actual = date('Y-m-d');
        $fecha_max = $this->calcularFecha();

        //MOSTRAR CONVENIOS SOLO A ABOGADOS, ACA LIBERTADOR Y ECONOMICAS  -temporal mientras se normaliza en la DB
        $sucursal = Auth::user()->sucursal;
        $no_include = 27;
        if(in_array($sucursal, array(10,100,103))){
            $no_include = 0;
        }

        //Se cargan motivos segun los permisos asignados en roles_motivos_sel
        $motivos = TramitesHabilitadosMotivos::whereNull('deleted_at')
                        ->where('activo',true)
                        ->whereIn('id',$this->getRoleMotivos('role_motivos_sel'))
                        ->where(function($query) use($sucursal){
                            $query->where('sucursal_id',0)->orwhere('sucursal_id',$sucursal);
                        })
                        ->where('id','<>',$no_include)
                        ->orderBy('description', 'asc')
                        ->pluck('description','id');
        
        $SysMultivalue = new SysMultivalue();
        $sucursales = $SysMultivalue->sucursales();
        $tdocs = $SysMultivalue->tipodocs(); 
        $paises = $SysMultivalue->paises();
        
        return view($this->path.'.form')->with('fecha_actual',$fecha_actual)
                                        ->with('fecha_max',$fecha_max)
                                        ->with('sucursales',$sucursales)
                                        ->with('tdocs',$tdocs)
                                        ->with('paises',$paises)
                                        ->with('motivos',$motivos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        try{
            if($this->verificarLimite($request->sucursal, $request->motivo_id, $request->fecha, $user)){

                $tipo_doc   = $request->tipo_doc;
                $nro_doc    = strtoupper($request->nro_doc);
		        $sexo 	    = $request->sexo;
                $pais       = $request->pais;
                $fecha      = $request->fecha;
                $motivo_id  = $request->motivo_id;

                //validar PASAPORTE acepte letras y numeros de lo contrario solo numeros
                
                if ($user->id != '318') {
                    if($tipo_doc == '4')
                    {
                        $this->validate($request, ['nro_doc' => 'required|min:0|max:10|regex:/^[0-9a-zA-Z]+$/']);
                    }
                    else
                    {
                    $this->validate($request, ['nro_doc' => 'required|min:0|max:10|regex:/(^(\d+)?$)/u']);
                    }
                }

                //Validar si existe en tramites habilitados
                $existe = TramitesHabilitados::where('tipo_doc',$tipo_doc)
                                                ->where('nro_doc',$nro_doc)
                                                ->where('pais',$pais)
                                                ->where('fecha',$fecha)
                                                ->where('deleted',false)
                                                ->count();
                if($existe){
                    flash('El Documento Nro. '.$nro_doc.' tiene un turno asignado para el día '.$fecha.' por tramites habilitados')->error()->important();
                    return back();
                }
		        //Validar si existe una licencia vigente para Duplicados
		        if ($motivo_id == '12') {
                    $existe = \DB::table('tramites')
                        ->where('nro_doc', $nro_doc)
                        ->where('tipo_doc', $tipo_doc)
                        ->where('pais', $pais)
                        ->where('sexo', strtolower($sexo))
                        ->whereRaw('estado IN(14, 95)')
                        ->whereRaw('fec_vencimiento >= current_date')
                        ->first();

                    if (!$existe) {
                            flash('El Documento Nro. ' . $nro_doc . ' no tiene una licencia VIGENTE.')->warning()->important();
                    return back();
                    }
                }
                //Validar si tiene turno en sigeci si el motivo es diferente de ERROR EN TURNO
                if($motivo_id != '13'){
                    $existeturno = $this->existeTurnoSigeci($tipo_doc, $nro_doc, $fecha);
                    if($existeturno){
                        flash('El Documento Nro. '.$nro_doc.' tiene un turno por SIGECI para el día '.$fecha)->warning()->important();
                        return back();
                    }
                }
                //Validar si tiene turno en LICTA, si el motivo es diferente a REINICIA TRAMITE
                if($motivo_id != '14'){
                    $tramite = $this->existeTramiteEnCurso($tipo_doc, $nro_doc, $pais, $fecha, $sexo);
                    if($tramite){
                        flash('El Documento Nro. '.$nro_doc.' tiene un turno iniciado en LICTA '.$tramite->tramite_id.' Por favor agregar por REINICIA TRAMITE')->warning()->important();
                        return back();
                    }
                }

		//Validar motivo CUARENTENA exista en la tabla t_cuarentena
                if($motivo_id == '28'){
                    $existe = $this->existePersonaEnCuarentena($tipo_doc, $nro_doc, $sexo, $pais);
                    if(!$existe){
			flash('El Documento Nro. '.$nro_doc.' no se encuentra habilitado para ingresar como CUARENTENA.')->warning()->important();
			return back();
                    }
                }

		//Validar motivo REIMPRESION no existe en LICTA un trámite inicado o finalizado
		// if($motivo_id == '29'){
		//   $existe = \DB::table('tramites')
		// 		->where('nro_doc',$nro_doc)
		// 		->where('tipo_doc',$tipo_doc)
		// 		// ->where('pais',$pais)
		// 		->where('sexo',strtolower($sexo))
		// 		->where('tipo_tramite_id', 1030)
		// 		->where('estado','<>',93)
		// 		->count();
		  //Se comenta validadcion por cambio decreto enero 2021 donde permite hacer otro tramite de REIMPRESION
		  /*if($existe){
		  	flash('El Documento Nro. '.$nro_doc.' ya tiene en LICTA un trámite como REIMPRESION.')->warning()->important();
                        return back();	
			} */
		// }

                //Si no existe ninguna restriccion entonces creamos el registro
                $tramiteshabilitados = new TramitesHabilitados();
                $tramiteshabilitados->fecha         = $fecha;
                $tramiteshabilitados->apellido      = strtoupper($request->apellido);
                $tramiteshabilitados->nombre        = strtoupper($request->nombre);
                $tramiteshabilitados->tipo_doc      = $tipo_doc;
                $tramiteshabilitados->nro_doc       = $nro_doc;
                $tramiteshabilitados->sexo          = $sexo;
                $tramiteshabilitados->fecha_nacimiento     = $request->fecha_nacimiento;
                $tramiteshabilitados->pais          = $pais;
                $tramiteshabilitados->user_id       = $request->user_id;
                $tramiteshabilitados->sucursal      = $request->sucursal;
                $tramiteshabilitados->motivo_id     = $motivo_id;
                $tramiteshabilitados->habilitado = false;               
		$tramiteshabilitados->std_solicitud_id = $request->std_solicitud_id;
                $saved = $tramiteshabilitados->save();

                $this->guardarObservacion($tramiteshabilitados->id, $request->observacion);

                //ASIGNAR O GENERAR PRECHECK
                $asignarPrecheck = $this->asignarPrecheck($tramiteshabilitados->id);

                if($asignarPrecheck && $user->id === '318')
                {
                    return true;
                }

                Flash::success('El Tramite se ha creado correctamente');
                return redirect()->route('tramitesHabilitados.create');

            }else{
                //CUANDO SE VALIDA EL LIMITE POR ROL, SUCURSAL Y MOTIVO
                //Flash::error('LIMITE DIARIO PERMITIDO para la sucursal según el motivo seleccionado.!!');
                return back();  
            }
        }
        catch(Exception $e){
            return "Fatal error - ".$e->getMessage();
        }
    }

    public function asignarPrecheck($id) {

        $tramiteshabilitados = TramitesHabilitados::find($id);
        $nacionalidad = AnsvPaises::where('id_dgevyl', $tramiteshabilitados->pais)->first()->id_ansv;
        $motivo = TramitesHabilitadosMotivos::where('id', $tramiteshabilitados->motivo_id)->first()->description;
        $precheck = null;

        $tramiteAIniciarController = new TramitesAInicarController();
        $precheck_disponible = $tramiteAIniciarController->existeTramiteAIniciarConPrecheck($tramiteshabilitados->nro_doc, $tramiteshabilitados->tipo_doc, $tramiteshabilitados->sexo, $nacionalidad);

        switch ($motivo) {
            case "REINICIA TRAMITE":
                //Buscamos el precheck que tenia asociado el tramite de LICTA
                $tramite_id = $tramiteshabilitados->observacion();
                $precheck = TramitesAIniciar::where('tramite_dgevyl_id',$tramite_id)->first();

                //En caso de no encontrar el precheck asociado con tramite_id de LICTA buscamos uno disponible
                if(!$precheck)
                    $precheck = $precheck_disponible;

                break;

            case "ERROR EN TURNO":
                //Buscamos el precheck que tenia asociado el turno de SIGECI
                $idcita = $tramiteshabilitados->observacion();
                $precheck = TramitesAIniciar::where('sigeci_idcita',$idcita)->where('estado', '!=', TURNO_VENCIDO)->orderby('id','desc')->first();

                if($precheck){
                    $precheck = TramitesAIniciar::find($precheck->id);
                    if($precheck->nro_doc == $tramiteshabilitados->nro_doc && $precheck->tipo_doc == $tramiteshabilitados->tipo_doc && $precheck->sexo == $tramiteshabilitados->sexo){

                        $tramiteshabilitados->tramites_a_iniciar_id = $precheck->id;
                        $tramiteshabilitados->save();

                        //Corregimos datos incorrectos al tomar el turno en Sigeci
                        if($precheck->nacionalidad != $nacionalidad){
                            $precheck->nacionalidad = $nacionalidad;
                            $precheck->save();
                        }
                        if($precheck->fecha_nacimiento != $tramiteshabilitados->fecha_nacimiento){
                            $precheck->fecha_nacimiento = $tramiteshabilitados->fecha_nacimiento;
                            $precheck->save();
                        }
                    }else{
                        $precheck = $precheck_disponible;
                    }
                }
                break;

            case "RETOMA TURNO":
                //Buscamos el precheck que tenia asociado el turno de SIGECI
                $idcita = $tramiteshabilitados->observacion();
                $precheck = TramitesAIniciar::where('sigeci_idcita',$idcita)->where('estado', '!=', TURNO_VENCIDO)->orderby('id','desc')->first();
                break;

            case "TURNO EN EL DIA":
                $precheck = null;
                break;

            default:
                $precheck = $precheck_disponible;

        }
        //ASOCIAR PRECHECK A TRAMITES HABILITADOS
        if($precheck){
            $tramiteAIniciar = TramitesAIniciar::find($precheck->id);
            $tramiteshabilitados->tramites_a_iniciar_id = $tramiteAIniciar->id;
            $tramiteshabilitados->save();
        }else{
            //CREAR UN PRECHECK EN TRAMITES A INICIAR
            $tramiteAIniciar = new TramitesAIniciar();
            $tramiteAIniciar->apellido          = $tramiteshabilitados->apellido;
            $tramiteAIniciar->nombre            = $tramiteshabilitados->nombre;
            $tramiteAIniciar->tipo_doc          = $tramiteshabilitados->tipo_doc;
            $tramiteAIniciar->nro_doc           = $tramiteshabilitados->nro_doc;
            $tramiteAIniciar->sexo              = $tramiteshabilitados->sexo;
            $tramiteAIniciar->nacionalidad      = $nacionalidad;
            $tramiteAIniciar->fecha_nacimiento  = $tramiteshabilitados->fecha_nacimiento;
            $tramiteAIniciar->estado            = '1';
	    $tramiteAIniciar->std_solicitud_id  = $tramiteshabilitados->std_solicitud_id;
            $tramiteAIniciar->save();

            $tramiteshabilitados->tramites_a_iniciar_id = $tramiteAIniciar->id;
            $tramiteshabilitados->save();

            $validaciones = $tramiteAIniciarController->crearValidacionesPrecheck($tramiteAIniciar->id);
        }

        //Enviamos al QUEUE para procesar las validaciones Precheck en segundo plano
        if ($tramiteAIniciar->tramite_dgevyl_id == null){
            ProcessPrecheck::dispatch($tramiteshabilitados);
        }
        return true;
    }




    public function tramitesReimpresionStd($ws_fecDes, $ws_fecHas,$ws_estado,$ws_esquema,$ws_metodo)
    {
        $data = $this->solicitudDatosStd($ws_fecDes, $ws_fecHas,$ws_estado,$ws_esquema,$ws_metodo);
        $paises = AnsvPaises::all();


      foreach ($data as $tramite) {

          \Log::info("Tramite numero: " . $tramite['numeroTramite']);

	   $fecha_nacimiento = $tramite['datosFormulario']['fecha_nacimiento']['valor'];

	   /*  Estas líneas se usan para determinar si existe el codigo pais y si corresponde al valor que necesitamos
	   if(isset($tramite['datosFormulario']['codigo_pais']['valor'])){   
	   	$pais = $tramite['datosFormulario']['codigo_pais']['valor'];
	   }else{
	   	$pais = $tramite['datosFormulario']['nacionalidad']['valor'];
	   }*/

           $pais = $tramite['datosFormulario']['nacionalidad']['valor'];

	   if ($pais === 'ARG'){ $pais = 'Argentina';}
	  //if (strlen($pais)>3) continue;	//Esta línea es para el codigo pais
            $request = new Request();

            $request->fecha = date('Y-m-d');
            $request->nombre = $tramite['nombreCiudadano'];
            $request->apellido = $tramite['apellidoCiudadano'];

            $request->nro_doc = $tramite['numeroDocumentoCiudadano'];
            $request->sexo = $tramite['generoCiudadano'];
            $request->fecha_nacimiento = implode('-',array_reverse(explode("/",$fecha_nacimiento)));
            // Usuario tramites a distancia
            $request->user_id = '318';
            //$request->user_id = '261';
            /*// Sucursal de reimpresiones
            $request->sucursal= '180';
            // Motivo tramite: reimpresiones
            $request->motivo_id = 29;*/
            switch ($ws_metodo) {
                case 'Duplicadolicencias':
                    $request->sucursal= '150';
                    $request->motivo_id = 12;
                    break;
                case 'ReimpresiondeCredenciales':
                    $request->sucursal= '180';
                    $request->motivo_id = 29;
                    break;
            }

	    $request->std_solicitud_id = $tramite['numeroTramite'];

            /*
                En BD existe tres tipos de nacionaonalidades argentinas:
                Opcional: id_dgevyl=83, naturalizado: id_dgevyl=75 y de nacimiento: id_dgevyl= 1

            */
            if ($pais === 'Argentina') {
                $request->pais = '1';

            }else{
               //$request->pais = $paises->where('iso_alfa_3',$pais)->first()->id_dgevyl;
               $request->pais = $paises->where('nombre',$pais)->first()->id_dgevyl;
            }
            // Recibimos DNI o PASAPORTE, en nuestra DB usamos números para el tipo_doc
            if($tramite['tipoDocumentoCiudadano'] === 'DNI'){
                $request->tipo_doc = '1';

            }elseif( (trim($tramite['tipoDocumentoCiudadano']) === 'DE') || (trim($tramite['tipoDocumentoCiudadano']) === 'CRP' ) ){
                $request->tipo_doc = '1';

	    }elseif($tramite['tipoDocumentoCiudadano'] === 'PAS'){
                $request->tipo_doc = '4';
            }

            //Si retorna true, cambia el estado del esquema en al tabla std_solicitudes y del lado de STD   
            if($this->store($request)){
                $this->cambioEstadoDeEsquema($tramite['numeroTramite'],"Listo para trabajar");
            };

        }
        return back();

    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            
            $t = TramitesHabilitados::find($id);
                    
            $inicio_tramite = ($t->tramites_a_iniciar_id)?TramitesAIniciar::find($t->tramites_a_iniciar_id)->tramite_dgevyl_id:'';
            //No realizar ninguna modificacion si el tramiteAIniciar inicio en Fotografia
            if($inicio_tramite){
                Flash::error('El Tramite ya se inicio en LICTA no se puede anular!');
                return redirect()->route('tramitesHabilitados.index');
            }else{
            
                $tramiteshabilitados = TramitesHabilitados::find($id);
                $tramiteshabilitados->deleted = true;
                $tramiteshabilitados->deleted_by = Auth::user()->id;
		$tramiteshabilitados->save();
		
		// No me quiso aceptar la constante TURNO_VENCIDO
		$vencido = 8;
		$anular_precheck = TramitesAIniciar::where("id",$t->tramites_a_iniciar_id)->update(['estado' => $vencido]);
            
                Flash::success('El Tramite se ha anulado correctamente');
                return redirect()->route('tramitesHabilitados.index');
            }
        }
        catch(Exception $e){   
            return "Fatal error - ".$e->getMessage();
        }
    }

    public function habilitar(Request $request)
    {
        $sql = TramitesHabilitados::where("id",$request->id)
                ->update(array('habilitado' => $request->valor, 'habilitado_user_id' => Auth::user()->id));
        return $sql;
    }

    public function guardarObservacion($tramite_habilitado_id, $observacion)
    {
        \DB::table('tramites_habilitados_observaciones')->where('tramite_habilitado_id',$tramite_habilitado_id)->delete();
        $sql =\DB::insert("INSERT INTO tramites_habilitados_observaciones (tramite_habilitado_id, observacion) 
                   VALUES (".$tramite_habilitado_id.", '".$observacion."' )");
        return $sql;
    }


    public function buscarDatosPersonales(Request $request)
    {
        $encontrado = null;
        $encontrado = DatosPersonales::selectRaw('nombre, apellido, UPPER(sexo) as sexo, fec_nacimiento as fecha_nacimiento, pais')
                                        ->where("tipo_doc",$request->tipo_doc)
                                        ->where("nro_doc",$request->nro_doc)
                                        ->where("sexo",strtolower($request->sexo))
                                        ->orderBy('modification_date','DESC')
                                        ->first();
        if(!$encontrado){
            $encontrado = TramitesHabilitados::where("tipo_doc",$request->tipo_doc)->where("nro_doc",$request->nro_doc)->where("sexo",$request->sexo)->orderBy('id','DESC')->first();
            if(!$encontrado){
                $encontrado = TramitesAIniciar::selectRaw("tramites_a_iniciar.*, ansv_paises.id_dgevyl as pais")
                            ->join('ansv_paises','ansv_paises.id_ansv','tramites_a_iniciar.nacionalidad')
                            ->where("tipo_doc",$request->tipo_doc)
                            ->where("nro_doc",$request->nro_doc)
                            ->where("sexo",$request->sexo)
                            ->orderBy('id','DESC')
                            ->first();
            }
        }
        return json_encode($encontrado);
    }

    public function consultarUniversoReimpresion(Request $request){
        $consulta =  \DB::table("universo_reimpresiones_v")
			->where('tipo_doc',$request->tipo_doc)
			->where('nro_doc',$request->nro_doc)
			->where('sexo','ilike',$request->sexo)
			->where('pais',$request->pais)
			->get();
	foreach ($consulta as $row){
		$row->fec_emision = date('d-m-Y', strtotime($row->fec_emision));
		$row->fec_vencimiento = date('d-m-Y', strtotime($row->fec_vencimiento));
	}
        return $consulta;
    }

    public function consultarTurnoSigeci(Request $request){
        $consulta = Sigeci::selectRaw("sigeci.*, tramites_a_iniciar.tramite_dgevyl_id")
                        ->leftjoin('tramites_a_iniciar','tramites_a_iniciar.sigeci_idcita','sigeci.idcita')
                        ->where("sigeci.idcita",$request->idcita)
                        ->whereNotIn('sigeci.idprestacion', $this->prestacionesCursos)
                        ->first();
        return $consulta;
    }
    public function consultarUltimoTurno(Request $request){
        $consulta = Sigeci::selectRaw("sigeci.*")
                        ->join("tipo_doc","tipo_doc.id_sigeci","sigeci.idtipodoc")
                        ->leftjoin('tramites_a_iniciar','tramites_a_iniciar.sigeci_idcita','sigeci.idcita')
                        ->where("tipo_doc.id_dgevyl",$request->tipo_doc)
                        ->where("sigeci.numdoc",$request->nro_doc)
                        ->whereNull('tramites_a_iniciar.tramite_dgevyl_id')
                        ->whereNotIn('sigeci.idprestacion', $this->prestacionesCursos)
                        ->orderBy('sigeci.idcita','DESC')
                        ->first();
        if($consulta){
            $turno = Sigeci::where('idcita',$consulta->idcita)->first();
            $sexo = $turno->getSexo();
            if(strtoupper($sexo) != strtoupper($request->sexo))
                $consulta = null;
        }
        return $consulta;
    }
    public function existeTurnoSigeci($tipo_doc, $nro_doc, $fecha){
        $sigeci = Sigeci::join("tipo_doc","tipo_doc.id_sigeci","sigeci.idtipodoc")
                        ->where("tipo_doc.id_dgevyl",$tipo_doc)
                        ->where("sigeci.numdoc",$nro_doc)
                        ->where("sigeci.fecha",$fecha)
                        ->whereNotIn('sigeci.idprestacion', $this->prestacionesCursos)
                        ->count();
        return $sigeci;
    }
    public function existeTramiteEnCurso($tipo_doc, $nro_doc, $pais, $fecha, $sexo){
        $tramite = \DB::table('tramites')
                        ->whereRaw("estado <= '13'")
                        ->where("tipo_doc",$tipo_doc)
                        ->where("nro_doc",$nro_doc)
                        ->where("pais",$pais)
			->where("sexo",strtolower($sexo))
                        ->whereRaw("fec_inicio > current_date - interval '3 month' ")
                        ->first();
        return $tramite;
    }
    public function existePersonaEnCuarentena($tipo_doc, $nro_doc, $sexo, $pais){
	$cuarentena = \DB::table('t_cuarentena')
                        ->where("tipo_doc",$tipo_doc)
                        ->where("nro_doc",$nro_doc)
                        ->where("sexo",strtoupper($sexo))
			->where("pais",$pais)
			->count();
	return $cuarentena;
    } 
    public function calcularFecha(){
        $dia_semana = date('w');
        //Si es Jueves o viernes sumar 5, por incluir fin de semana, de lo contrario sumar 3
        //$sumar_dias = ($dia_semana == 4 || $dia_semana == 5)?'5':'3'; 
        $sumar_dias = 3;
        $fecha = date('Y-m-d', strtotime('+'.$sumar_dias.' days', strtotime(date('Y-m-d'))));  
        return $fecha;
    }
    public function verificarLimite($sucursal, $motivo, $fecha, $user){
        $acceso= false;

        $role_id = $user->roles->pluck('id')->first();
        $mensaje = 'LIMITE DIARIO PERMITIDO para la sucursal según el motivo seleccionado.!!';

        //VERIFICAR LIMITE ESTABLECIDO EN LA TABLA tramites_habilitados_motivos
        $th_motivos = TramitesHabilitadosMotivos::where('id',$motivo)->first();
        if($th_motivos->limite){
            if($th_motivos->sucursal_id == $sucursal){
                $total = TramitesHabilitados::where('motivo_id',$motivo)->where('sucursal',$sucursal)->where('fecha',$fecha)->where('deleted',false)->count();
                if($total < $th_motivos->limite)
                    $acceso = true;
            }else{
                $mensaje = 'SUCURSAL NO HABILITADA para el motivo seleccionado.';
            }
        }else{
            
            //VERIFICAR LIMITE ESTABLECIDO EN LA TABLA roles_motivos
            $roles_limites = \DB::table('roles_limites')->where('role_id',$role_id)->where('sucursal',$sucursal)->where('motivo_id',$motivo)->where('activo', true)->first();
            //Encontrar todas las posibilidades establecidas en roles_limites
            if($roles_limites == null)
                $roles_limites = \DB::table('roles_limites')->where('role_id',$role_id)->where('sucursal',$sucursal)->whereNull('motivo_id')->where('activo', true)->first();
            
            if($roles_limites == null)
                $roles_limites = \DB::table('roles_limites')->where('role_id',$role_id)->where('motivo_id',$motivo)->whereNull('sucursal')->where('activo', true)->first();
            
            if($roles_limites == null)
                $roles_limites = \DB::table('roles_limites')->where('role_id',$role_id)->whereNull('sucursal')->whereNull('motivo_id')->where('activo', true)->first();

            if($roles_limites){
                $consulta = TramitesHabilitados::where('fecha',$fecha)
                                ->whereIn('user_id',function($query) use($role_id){
                                    $query->select('model_id')->from('model_has_roles')->where('role_id',$role_id);
                                });
                
                if($roles_limites->sucursal)
                    $consulta = $consulta->where('sucursal',$roles_limites->sucursal);
                
                if($roles_limites->motivo_id)
                    $consulta = $consulta->where('motivo_id',$roles_limites->motivo_id);
                            
                $consulta = $consulta->count();

                if($consulta >= $roles_limites->limite)
                    $acceso = false;
                else
                    $acceso = true;
            }else{
                $acceso = true;
            }
        }

        if(!$acceso)
            Flash::error($mensaje);

        return $acceso;  
    }
    public function getRoleMotivos($tabla){
        $user = Auth::user();
        $roles = $user->roles->pluck('id')->toArray();

        $motivos = \DB::table($tabla)->whereIn('role_id',$roles)->pluck('motivo_id')->toArray();
        return $motivos;
    }

    //Se envia masivamente los turnos que no ha inciado a procesar Precheck con el queque (Demonio)- PAUSADO
    public function verificarPrecheckHabilitados(){
        $turnos =  TramitesHabilitados::whereNull('tramites_a_iniciar_id')->where('fecha',date('Y-m-d'))->get();
        foreach ($turnos as $key => $turno) {
            //Crear registro en tramitesAIniciar y procesar el Precheck
            ProcessPrecheck::dispatch($turno);
        }
    }
    private function obtenerTokenStd()
    {
        $curl = curl_init();
        //Homologacion
        curl_setopt_array($curl, array(
            CURLOPT_URL => env('URL_AUTH_STD'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>"{\r\n\"usuario\": \"".env('USER_STD')."\",\r\n\"password\": \"".env('PASS_USER_STD')."\"\r\n}",
            CURLOPT_HTTPHEADER => array("Content-Type: application/json",
                                    "client_id:".env('CLIENT_ID'),
                                    "client_secret:".env('CLIENT_SECRET')),)
        );
    
         $response = curl_exec($curl);
         curl_close($curl);
         $array = json_decode($response,TRUE);
        //  $fp = fopen('credenciales_std_hml.txt', 'w');
        //  fwrite($fp, serialize($array));
        //  fclose($fp);

        $token = $array["authHeader"];

        return $token;
    }

    private function obtenerDatosStd($token,$fecDes,$fecHas,$estado,$esquema,$metodo)
    {
        //$para = "fechaDesde=".$fecDes."&estadoGeneral=".$estado."&estadoDelEsquema=".$esquema; 
            //echo $para.PHP_EOL;
        $para = "fechaDesde=".$fecDes."&fechaHasta=".$fecHas."&estadogeneral=".$estado."&estadoDelEsquema=".$esquema;
            //echo $para.PHP_EOL;
                $curl = curl_init();
                //Homologacion
                curl_setopt_array($curl, array(
                CURLOPT_URL => env('URL_CONSULTA_STD').$metodo."?".$para,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array("Content-Type: application/json",
                                        "client_id:".env('CLIENT_ID'),
                                        "client_secret:".env('CLIENT_SECRET'),
                                        "Authorization:".$token))
                ); 
        $response = curl_exec($curl);
        curl_close($curl);
        $array = json_decode($response,TRUE);
        // $fp = fopen('res_ws_std_hml_'.uniqid().'_'.$metodo.'.txt', 'w');
        // fwrite($fp, serialize($array));
        // fclose($fp);
        return $array;   
    }

    private function solicitudDatosStd($ws_fecDes,$ws_fecHas,$ws_estado,$ws_esquema,$ws_metodo)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        // if (!file_exists('credenciales_std_hml.txt'))
        // {
        $token = $this->obtenerTokenStd();
        // }else{
        //         $fp = fopen('credenciales_std_hml.txt','rb');
        //        $array = unserialize(fread($fp, filesize('credenciales_std_hml.txt')));
        //        fclose($fp);
        // }
        // $actual_time = date('Y-m-d\TH:i:sO');
        // $expiration_time = date('Y-m-d\TH:i:sO', strtotime(substr($array["jwtclaimsSet"]["expirationTime"], 0, 19)));
        // if ($actual_time < $expiration_time){
            //El Token Aun No Expiro Reutilizar del Archivo de Texto
        //         echo "Token Valido: ".$token."\n";
        // }else{
        //         //El Token Expiro Solicitar Nuevamente
        //         $array = $this->getTokenStd();
        //         $token = $array["authHeader"];
        //         echo "Token Expirado, Solicitado: ".$token."\n";
        // }
        
        return $this->obtenerDatosStd($token,$ws_fecDes,$ws_fecHas,$ws_estado,$ws_esquema,$ws_metodo);

    }
    private function transicionEstadoEsquema($token, $tramite, $data)
    {
        $curl = curl_init();
        //Homologacion
        curl_setopt_array($curl, array(
        CURLOPT_URL => env('URL_TRANSICION_STD').$tramite.'/transicion',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "UTF-8",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array("Content-Type: application/json",
                                "client_id:".env('CLIENT_ID'),
                                "client_secret:".env('CLIENT_SECRET'),
                                "Authorization:".$token),));

        $response = curl_exec($curl);
        $array = json_decode($response,TRUE);
        curl_close($curl);
        return $array;
    }
    private function cambioEstadoDeEsquema($numTramiteStd,$estado_a_enviar,$idmotivo='',$observaciones='')
    {
        $token = $this->obtenerTokenStd();

        //$data="{".'"estado":"'.$estado_a_enviar.'","motivo":"'.$idmotivo.'","observaciones": "'.$observaciones.'"'.',"archivos":[]'."}";
	//$tramitestdEjemplo = "00002117/20";
	$data = array('estado'=>$estado_a_enviar,'motivo'=>$idmotivo,'observaciones'=>$observaciones,'archivos'=>[]);
        
        $tramite = str_replace("/","",$numTramiteStd);
        $array = $this->transicionEstadoEsquema($token,$tramite,json_encode($data));
	$array = [];

        if (array_key_exists('idError', $array) || array_key_exists('message', $array)){
            \Log::warning('['.date('h:i:s').'] '." Error en el tramite numero: $numTramiteStd. Error WS Detalle, message: {$array['message']}.");
            return false;
        }else{
            try {
                \DB::table('std_solicitudes')
                ->where('numero_tramite',$numTramiteStd)
                ->update(['estado_esquema'=>$estado_a_enviar]);    
            
                \Log::info("Cambio de esquema, realizado con exito para el tramite de STD: $numTramiteStd, al estado de esquema: $estado_a_enviar");  
                return true;

            } catch (Exception $e) {
                return "Fatal error - ".$e->getMessage();
            }
        }   
    }

    public function reimpresionesLicenciaEmitida()
    {
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->endOfDay();

	//$startDate = Carbon::create(2022,7,9,0,0,0); 
	//$endDate = Carbon::create(2022,7,9,23,59,59); 

	$expiration_time = Carbon::now()->addHours(2);
	$expiration_time_copy = $expiration_time->copy()->subMinute();

	$fecDes = '2022-01-01';
	$metodo = 'ReimpresiondeCredenciales';
	$token = $this->obtenerTokenStd();

        $tipo_id_reimpresion = 1030;
        $estado_completado = 14;

        $tramites =  Tramites::
                        join('tramites_a_iniciar','tramites.tramite_id','tramites_a_iniciar.tramite_dgevyl_id')
			->join('std_solicitudes','std_solicitudes.numero_tramite','tramites_a_iniciar.std_solicitud_id')
                        ->where('tramites.estado',$estado_completado)
                        ->whereBetween('modification_date',[$startDate,$endDate])
                        ->where('tipo_tramite_id',$tipo_id_reimpresion)
			->whereNotNull('std_solicitud_id')
                        ->where('estado_esquema','!=','Licencia Emitida')
                        ->get();



        foreach($tramites as $tramite){
            
	    $tramite_id = $tramite->std_solicitud_id;

	   if(Carbon::now() >= $expiration_time || Carbon::now() >= $expiration_time_copy){
		\Log::info("Renuevo token");
		$expiration_time = Carbon::now()->addHours(2);
		$expiration_time_copy = $expiration_time->copy()->subMinute();
		$token = $this->obtenerTokenStd();
	    }

            $std_tramite = $this->reimpresion_tramite($tramite_id,$fecDes,$metodo,$token);

            if ($std_tramite[0]["estadoDelEsquema"] == "Verificando documentación"){
                   $this->cambioEstadoDeEsquema($tramite_id,"Licencia Emitida");
            }

        }
	return true;
    }

    public function reimpresion_tramite($numTramite,$fecDes,$metodo,$token){

        $para = "fechaDesde=".$fecDes."&numeroTramite=".$numTramite;

        $curl = curl_init();
        
	//
        curl_setopt_array($curl, array(
                CURLOPT_URL => env('URL_CONSULTA_STD').$metodo."?".$para,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
               	CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array("Content-Type: application/json",
                        "client_id:".env('CLIENT_ID'),
                        "client_secret:".env('CLIENT_SECRET'),
                        "Authorization:".$token))
        ); 
        $response = curl_exec($curl);

        \Log::info($response);

        curl_close($curl);
        $array = json_decode($response,TRUE); 
        return $array;
    }
}
