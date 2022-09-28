@extends('layouts.templeate')
@section('titlePage', "Tramites Habilitados")

@section('content')
<!-- page content -->
<div class="container">
    @can('view_all_tramites_habilitados','view_self_tramites_habilitados')
        <div class="col-md-2 col-xs-12">
            <a href="{{route('tramitesHabilitados.index')}}?fecha={{date('Y-m-d')}}" class="btn btn-info btn-group-justified"> <i class="fa fa-list"></i> Mostrar listado </a>
        </div>
        <br><br>
    @endcan
    
    <h4>Crear Turno </h4>   
        {!! Form::open(['route' => 'tramitesHabilitados.store', 'id'=>'formTramitesHabilitados', 'method' => 'POST', 'class' => 'form-horizontal', 'role' => 'form', 'files' => true ]) !!}
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">

	<div class="form-group">
	    <div class="col-md-8 col-xs-12">
                {!! Form::label('sucursal', ' Sucursal') !!}
                @can('enable_sede_tramites_habilitados')
                    {!! Form::select('sucursal', $sucursales, isset($edit) ? $edit->sucursal : Auth::user()->sucursal , ['class' => 'form-control' ]) !!}
                @else
                    {!! Form::select('sucursal', $sucursales, isset($edit) ? $edit->sucursal : Auth::user()->sucursal , ['class' => 'form-control', 'readonly' => 'readonly', 'disabled' ]) !!}
                    <input type="hidden" name="sucursal" value="{{ isset($edit) ? $edit->sucursal : Auth::user()->sucursal }}">
                @endcan
            </div>

            <div class="col-md-4 col-xs-12">
                {!! Form::label('fecha', ' Fecha') !!}
                <div class="input-group">
                    <div class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </div>
                    @can('enable_fecha_tramites_habilitados')
                        <input type="date" name="fecha" value="{{ isset($edit) ? $edit->fecha : $fecha_actual }}" class="form-control" required="required" min="{{ $fecha_actual }}" max="{{ $fecha_max }}">    
                    @else
                        <input type="date" name="fecha" value="{{ isset($edit) ? $edit->fecha : $fecha_actual }}" class="form-control" required="required" readonly="readonly" >
                    @endcan
                </div>
            </div>
        </div>

        <div class="form-group">
	    <div class="col-md-6 col-xs-12">
		{!! Form::label('tipo_doc', ' Documento') !!}
            	{!! Form::select('tipo_doc', $tdocs, isset($edit) ? $edit->tipo_doc : null, ['class' => 'form-control']) !!}
            	{!! Form::text('nro_doc', isset($edit) ? $edit->nro_doc : null, ['class' => 'form-control', 'placeholder' => 'Nro. Documento', 'maxlength' => 10, 'required' => 'required' ]) !!}
	    </div> 
	    <div class="col-md-6 col-xs-12">
                {!! Form::label('sexo', ' Sexo') !!}
		{!! Form::select('sexo', ['F' => 'Femenino', 'M' => 'Masculino' , 'X' =>  'No Binario'], isset($edit) ? $edit->sexo : 'M' , ['class' => 'form-control', 'required' => 'required']) !!} 
		<i>(*) Debes seleccionar el sexo para autocompletar los datos.</i>
            </div>

	</div>

	<div class="form-group">
	     <div class="col-md-6 col-xs-12">
                {!! Form::label('nombre', ' Nombres') !!}
                {!! Form::text('nombre', isset($edit) ? $edit->nombre : null, ['class' => 'form-control', 'placeholder' => 'Nombres', 'required' => 'required']) !!}
            </div>
	    <div class="col-md-6 col-xs-12">
            	{!! Form::label('apellido', ' Apellidos') !!}
            	{!! Form::text('apellido', isset($edit) ? $edit->apellido : null, ['class' => 'form-control', 'placeholder' => 'Apellidos', 'required' => 'required']) !!}
	    </div>
        </div>

        <div class="form-group">
            <div class="col-md-4 col-xs-12">
                {!! Form::label('fecha_nacimiento', ' Fecha de Nacimiento') !!}
                <input type="date" name="fecha_nacimiento" value="{{ isset($edit) ? $edit->fecha_nacimiento : NULL }}" class="form-control" required="required" >
            </div>

            <div class="col-md-8 col-xs-12">
                {!! Form::label('pais', ' País') !!}
                {!! Form::select('pais', $paises, isset($edit) ? $edit->pais : 1 , ['class' => 'form-control', 'placeholder' => 'Seleccione', 'required' => 'required']) !!}
            </div>
        </div>

	<div class="form-group"> 
	    <div class="col-md-4 col-xs-12" >	       
            	{!! Form::label('motivo_id', ' Motivo') !!}
            	@if (count($motivos)==1)
                   {!! Form::select('motivo_id', $motivos, isset($edit) ? $edit->motivo_id : null , ['class' => 'form-control', 'required' => 'required']) !!}
            	@else
                    {!! Form::select('motivo_id', $motivos, isset($edit) ? $edit->motivo_id : null , ['class' => 'form-control', 'placeholder' => 'Seleccione', 'required' => 'required']) !!}
            	@endif
	    </div>
	    <div class="col-md-8 col-xs-12">
		<div id="div_observacion">    
            	     {!! Form::label('observacion', ' Observación: ') !!}
             	     {!! Form::text('observacion', isset($edit) ? $edit->observacion : null, ['class' => 'form-control', 'required' => 'required']) !!}
            	     <input type="hidden" name="precheck_id" id="precheck_id" value="">
		</div>
            </div>
	</div> 
        <div class="form-group">
            <div class="col-md-4 col-xs-12">
                <div id="div_std">
                     {!! Form::label('std_solicitud_id', ' Numero de tramite STD: ') !!}
                     {!! Form::text('std_solicitud_id', isset($edit) ? $edit->std_solicitud_id : null, ['class' => 'form-control']) !!}
                     <input type="hidden" name="precheck_id" id="precheck_id" value="">
                </div>
            </div>
        </div>
        <div id="ultimo_turno"> </div>
        <hr>
        
        @can('add_tramites_habilitados')
        <div class="col-md-2 col-xs-12">
            <button type="submit" class="btn btn-primary btn-group-justified"> <i class="fa fa-check-square-o"></i> Enviar </button>                
        </div>
        @endcan
       
    {!! Form::close() !!}
</div>

<!-- /page content -->
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $("select[name=pais]").val(1);
            validarMotivos();

            @if(!isset($edit)) 
                //Autocompletar datos solo si nuevo registro, si esta editando no realizara la buscqueda
                $("select[name=tipo_doc], input[name=nro_doc], select[name=sexo]").change(function(){
                    var nro_doc = $("input[name=nro_doc]").val();
                    var tipo_doc = $("select[name=tipo_doc]").val();
                    var sexo = $("select[name=sexo]").val();
                
                    $.ajax({
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        url: './../buscarDatosPersonales',
                        data: {tipo_doc: tipo_doc, nro_doc: nro_doc, sexo:sexo },
                        type: "GET", dataType: "json", Async: false,
                        success: function(ret){
                            console.log(ret);
                            if(ret){
                                $("input[name=nombre]").val(ret.nombre);
                                $("input[name=apellido]").val(ret.apellido);
                                $("input[name=fecha_nacimiento]").val(ret.fecha_nacimiento);
                                $("select[name=sexo]").val(ret.sexo);
                                $("select[name=pais]").val(ret.pais);
                                validarMotivos();
                            }else{
                                $("input[name=nombre], input[name=apellido], input[name=fecha_nacimiento]").val('');
                            }                        
                        }
                    });
                });
            @endif

            $("#formTramitesHabilitados input, #formTramitesHabilitados select").change(function(){
                validarMotivos();
            });

            //Segun el motivo solicitar mas informacion 
            function validarMotivos(){
                var motivo = $("select[name=motivo_id] option:selected").text();

                $("#div_observacion input").removeAttr('required minlength maxlength');
                $("#div_observacion label").html('Observación: ');
                $("#div_observacion input").attr('placeholder','');                
                $("#ultimo_turno").empty();
                $("#precheck_id").val('');
                $('button[type=submit]').attr("disabled",false);
		$("#std_solicitud_id").removeAttr('required');
		$("#std_solicitud_id").attr('disabled',true);

                switch(motivo) {
                    case 'DIRECCIÓN':
                        //Campo Obligatorio dejar constancia de quien viene (Obligatorio)
                        $("#div_observacion input").attr('placeholder','Ingrese el responsable de la solictud').attr('required','required');
                        break;
                    case 'LEGALES':
                        //Nro. de Expediente (Obligatorio)
                        $("#div_observacion label").html('Nro. de Expediente / Carpeta: ');
                        $("#div_observacion input").attr('placeholder','Ingrese el Nro. del Expediente').attr('required','required');
                        break;
                    case 'ERROR EN TURNO':
                        //Nro. de Cita (Obligatorio)
                        $("#div_observacion label").html('Nro. de Cita: ');
                        $("#div_observacion input").attr('placeholder','Ingrese el Nro. de la Cita').attr('required','required').attr('minlength','8').attr('maxlength','8');
                        $('button[type=submit]').attr("disabled",true);
                        validarErrorEnTurno();
                        break;
                    case 'REINICIA TRAMITE':
                        //ID del Tramite (Obligatorio)
                        $("#div_observacion label").html('ID del Tramite: ');
                        $("#div_observacion input").attr('placeholder','Ingrese el ID del Tramite').attr('required','required').attr('minlength','7').attr('maxlength','7');;
                        $('button[type=submit]').attr("disabled",true);
                        validarReiniciaTramite();
                        break;
                    case 'RETOMA TURNO':
                        //Nro. de Cita (Obligatorio)
                        $("#div_observacion label").html('Nro. de Cita: ');
                        $("#div_observacion input").attr('placeholder','Ingrese el Nro. de la Cita').attr('required','required').attr('minlength','8').attr('maxlength','8');
                        validarRetomaTurno();
                        break;
                    case 'MAYOR DE 65':
                        var fecha = $('input[name=fecha_nacimiento]').val();
                        var edad = calcularEdad(fecha);
                        if(edad >= 65 && edad < 100){
                            $('button[type=submit]').attr("disabled",false);
                        }else{
                            $('button[type=submit]').attr("disabled",true);
                            $("#ultimo_turno").html('<h4 class="red"> <i class="fa fa-user-times" style="font-size:20px;"></i> Esta persona no cuenta con la edad permitida! tiene: '+edad+' años </h4>');
                        }
                        break;
                    case 'EMBARAZADAS':
                        var sexo = $("select[name=sexo]").val();
                        if(sexo == 'F'){
                            $('button[type=submit]').attr("disabled",false);
                        }else{
                            $('button[type=submit]').attr("disabled",true);
                            $("#ultimo_turno").html('<h4 class="red"> <i class="fa fa-user-times" style="font-size:20px;"></i> Solo se permite para este motivo el genero Femenino. </h4>');
                        }
                        break;
		    case 'REIMPRESION':
			validarReimpresion();
			break;
                    default:
                        //
                }
                
            }

	    function validarReimpresion(){
		var tipo_doc = $("select[name=tipo_doc").val();
		var nro_doc = $("input[name=nro_doc]").val();
		var sexo = $("select[name=sexo]").val();
		var pais = $("select[name=pais]").val();
		
		$('button[type=submit]').attr("disabled",true);
		if(nro_doc != ''){
		    $.ajax({
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        url: './../consultarUniversoReimpresion',
                        data: {nro_doc:nro_doc, tipo_doc:tipo_doc, sexo:sexo, pais:pais },
                        type: "GET", dataType: "json",
                        success: function(data){
			     if(data.length){
				console.log(data[0]);
				var v = data[0];
				var inhabilitado = (v.inhab_desde)?true:false;
				var info_inhab 	 = (inhabilitado)?'<b class="red">Inhabilitado</b> desde '+v.inhab_desde+' hasta '+v.inhab_hasta:' <b class="green">No se detecto ninguna inhabilitación asociada a esta persona.</b>';
				var cod_causa 	 = (v.inhab_causa)?v.inhab_causa+'-':'';
				var causa 	 = (inhabilitado)?'Causa: '+cod_causa+''+v.inhab_observaciones:'';
				var rehabilitado = (inhabilitado)?'Rehabilitado: '+v.inhab_fec_rehabilitado:'';
				var check_on  	 = '<i class="fa fa-check-circle" style="font-size:26px;color:green"></i>';
				var check_off 	 = '<i class="fa fa-times-circle" style="font-size:26px;color:red"></i>';

				$("#ultimo_turno").html('Información de su último trámite: <table class="table table-striped jambo_table"><tr><td>Trámite Nº '+v.tramite_id+'</td><td> Otorgamiento: '+v.fec_emision+'</td><td> Vencimiento: '+v.fec_vencimiento+'</td><td>'+check_on+'</td></tr><tr><td>'+info_inhab+'</td><td>'+causa+'</td><td>'+rehabilitado+'</td><td class="icono"></td></tr></table>');

				if ( inhabilitado == false || v.inhab_rehabilitado == true){
				    $("#ultimo_turno .icono").html(check_on);
                               	    $('button[type=submit]').attr("disabled",false);
				    $('#std_solicitud_id').attr('required','required');
				    $("#std_solicitud_id").attr('disabled',false);
				}else{
				    $("#ultimo_turno .icono").html(check_off);
				    $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> La persona se encuentra INHABILITADA!.</h4>');
                                    $('button[type=submit]').attr("disabled",true);
				}
			     }else{
				$("#ultimo_turno").html('<h4 class="red"><i class="fa fa-user-times" style="font-size:30px;"></i>Esta persona no se encuentra en el universo de REIMPRESIONES! </h4>');
			     }
			}
		    });
		}
	    }

             function validarErrorEnTurno(){
                var idcita  = $("#div_observacion input").val();
                var nro_doc = $("input[name=nro_doc]").val();
                var nombre  = $("input[name=nombre]").val();
                var apellido = $("input[name=apellido]").val();
                var sucursal = $("select[name=sucursal]").val();

                $('button[type=submit]').attr("disabled",true);
                
                if(idcita !='' && nro_doc != ''){
                    $.ajax({
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        url: './../consultarTurnoSigeci',
                        data: {idcita: idcita },
                        type: "GET", dataType: "json",
                        success: function(ret){
                            var precheck_id = ret.tramite_a_iniciar_id;
                            //Calcular los dias entre la dos fecha
                            var fechaini = new Date(ret.fecha);
                            var fechafin = new Date($("input[name=fecha]").val());
                            var diasdif= fechafin.getTime()-fechaini.getTime();
                            var dias = Math.round(diasdif/(1000*60*60*24));

                            var f = ret.fecha.split('-');
                            var fecha = f[2]+'/'+f[1]+'/'+f[0];

                            $("#ultimo_turno").html('Información de su turno: <table class="table table-striped jambo_table"><tr><td>'+fecha+'</td><td>'+ret.hora+'</td><td>'+ret.tipodoc+'</td><td>'+ret.numdoc+'</td><td>'+ret.nombre+' '+ret.apellido+'</td><td>'+ret.descsede+'</td><td>'+ret.descprestacion+'</td><td class="red"> '+dias+' días</td><td class="icono"></td></tr></table>');
                            console.log(nro_doc+" | "+ret.numdoc+" | "+nombre+" | "+ret.nombre+" | "+apellido+" | "+ret.apellido);
                            if (nro_doc == ret.numdoc || nombre == ret.nombre || apellido == ret.apellido){                             

                                if(ret.tramite_dgevyl_id){
                                    $('button[type=submit]').attr("disabled",true);
                                    $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> El número de cita ingresado ya cuenta con un tramite en LICTA: '+ret.tramite_dgevyl_id+'</h4>');
                                }else{
                                   // if(sucursal == ret.sucroca){
                                        if(dias >= 0 && dias <= 15){
                                            $("#ultimo_turno .icono").html('<i class="fa fa-check-circle" style="font-size:26px;color:green"></i>');
                                            $('button[type=submit]').attr("disabled",false);
                                            $("#precheck_id").val(precheck_id);
                                            $("#ultimo_turno").append('<h4 class="green"> <i class="fa fa-user-circle" style="font-size:30px;"></i> Usted certifica que los datos del TURNO son incorrectos!.</h4>');
                                        }else{
                                            $("#ultimo_turno .icono").html('<i class="fa fa-times-circle" style="font-size:26px;color:red"></i>');
                                            $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> El turno no cumple con los 15 días correspondientes para poder TOMAR TURNO!.</h4>');
                                        }
                                   /* }else{
                                        $("#ultimo_turno .icono").html('<i class="fa fa-times-circle" style="font-size:26px;color:red"></i>');
                                        $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> El turno ingresado no corresponde con la Sucursal que intenta registrar!.</h4>');
                                    }*/
                                }
                            }else{
                                $('button[type=submit]').attr("disabled",true);
                                $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> El número de cita ingresado no corresponde a la persona que intenta TOMAR TURNO!.</h4>');
                            }
                        },
                        error: function(err) {
                            $("#ultimo_turno").html('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> Esta persona no cuenta con turno previo, debe contar con turno entre los 15 días para poder TOMAR TURNO.</h4>');
                         }
                    });
                }
            }

             function validarReiniciaTramite(){
                var tramite_id = $("#div_observacion input").val();
                var nro_doc = $("input[name=nro_doc]").val();

                $('button[type=submit]').attr("disabled",true);

                if(tramite_id !='' && nro_doc != ''){
                    $.ajax({
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        url: './../consultarTramite',
                        data: {tramite_id: tramite_id},
                        type: "GET", dataType: "json",
                        success: function(ret){
                            
                            //Calcular los dias entre las dos fechas
                            var fechaini = new Date(ret.fec_inicio);
                            var fechafin = new Date($("input[name=fecha]").val());
                            var diasdif= fechafin.getTime()-fechaini.getTime();
                            var dias = Math.round(diasdif/(1000*60*60*24));
                            
                            var f = ret.fec_inicio.split('-');
                            var fecha = f[2]+'/'+f[1]+'/'+f[0];

                            $("#ultimo_turno").html('Información del Tramite: <table class="table table-striped jambo_table"><tr><td>'+ret.tramite_id+'</td><td>'+ret.tipo_doc+'</td><td>'+ret.nro_doc+'</td><td>'+fecha+'</td><td>'+ret.sucursal+'</td><td>'+ret.estado_description+'</td><td class="red"> '+dias+' días</td><td class="icono"></td></tr></table>');


                            if (nro_doc != ret.nro_doc){
                                $('button[type=submit]').attr("disabled",true);
                                $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> El número de Tramite ID ingresado no corresponde a la persona que intenta REINICIAR TRAMITE!.</h4>');    
                            }else{
                                if(ret.estado >= 95 || ret.estado == 14){
                                    $('button[type=submit]').attr("disabled",true);
                                    $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> El númerop de tramite de LICTA ingresado se encuentra en estado: '+ret.estado_description+'</h4>');
                                }else{
                                    if(dias >= 0 && dias <= 90){
                                        $("#ultimo_turno .icono").html('<i class="fa fa-check-circle" style="font-size:26px;color:green"></i>');
                                        $('button[type=submit]').attr("disabled",false);
                                    }else{
                                        $("#ultimo_turno .icono").html('<i class="fa fa-times-circle" style="font-size:26px;color:red"></i>');
                                        $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> El turno no cumple con los 90 días correspondientes para poder REINICIAR TRAMITE!.</h4>');
                                    }
                                }
                            }
                        },
                        error: function(err) {
                            $("#ultimo_turno").html('<h4 class="red"> <i class="fa fa-user-times" style="font-size:20px;"></i> Existe un error en el sistema, comuniquese con el administrador</h4>');
                        }
                    });
                }
            }

            function validarRetomaTurno(){
                var motivo = $("select[name=motivo_id] option:selected").text();
                var tipo_doc = $("select[name=tipo_doc]").val();
                var nro_doc = $("input[name=nro_doc]").val();
                var sexo =  $("select[name=sexo]").val();
                var sucursal = $("select[name=sucursal]").val();

                $('button[type=submit]').attr("disabled",true);
                
                if(nro_doc != ''){
                    $.ajax({
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        url: './../consultarUltimoTurno',
                        data: {tipo_doc: tipo_doc, nro_doc: nro_doc, sexo:sexo },
                        type: "GET", dataType: "json",
                        success: function(ret){
                            
                            console.log(ret);
                            //var precheck_id = ret.tramite_a_iniciar_id;
                            $("#div_observacion input").val(ret.idcita);

                            //Calcular los dias entre la dos fecha
                            var fechaini = new Date(ret.fecha);
                            var fechafin = new Date($("input[name=fecha]").val());
                             var diasdif= fechafin.getTime()-fechaini.getTime();
                            var dias = Math.round(diasdif/(1000*60*60*24));

                            var f = ret.fecha.split('-');
                            var fecha = f[2]+'/'+f[1]+'/'+f[0];

                            $("#ultimo_turno").html('Información de su último turno: <table class="table table-striped jambo_table"><tr><td>'+fecha+'</td><td>'+ret.hora+'</td><td>'+ret.tipodoc+'</td><td>'+ret.numdoc+'</td><td>'+ret.nombre+' '+ret.apellido+'</td><td>'+ret.descsede+'</td><td>'+ret.descprestacion+'</td><td class="red"> '+dias+' días</td><td class="icono"></td></tr></table>');
                            
                            if(sucursal == ret.sucroca){
                                if(dias >= 0 && dias <= 15){
                                    $("#ultimo_turno .icono").html('<i class="fa fa-check-circle" style="font-size:26px;color:green"></i>');
                                    $('button[type=submit]').attr("disabled",false);
                                }else{
                                    $("#ultimo_turno .icono").html('<i class="fa fa-times-circle" style="font-size:26px;color:red"></i>');
                                    $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> El turno previo ha superado el limite de los 15 días para poder RETOMAR TURNO!.</h4>');
                                }
                            }else{
                                $("#ultimo_turno .icono").html('<i class="fa fa-times-circle" style="font-size:26px;color:red"></i>');
                                $("#ultimo_turno").append('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> El turno ingresado no corresponde con la Sucursal que intenta registrar!.</h4>');
                            }
                        },
                        error: function(err) {
                            $("#ultimo_turno").html('<h4 class="red"> <i class="fa fa-user-times" style="font-size:30px;"></i> Esta persona no cuenta con turno previo, debe contar con turno entre los 15 días para poder RETOMAR TURNO.</h4>');
                        }
                    });
                }

                //Si es Administrador permitir guardar
                @role('Administrador Tramites Habilitados')
                    $('button[type=submit]').attr("disabled",false);
                @endrole
            }

            function calcularEdad(fecha) {
                var hoy = new Date();
                var cumpleanos = new Date(fecha);
                var edad = hoy.getFullYear() - cumpleanos.getFullYear();
                var m = hoy.getMonth() - cumpleanos.getMonth();
                if (m < 0 || (m === 0 && hoy.getDate() < cumpleanos.getDate())) {
                    edad--;
                }
                return edad;
            }

        });
    </script>
@endpush
