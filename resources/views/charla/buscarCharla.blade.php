@extends('layouts.app')
@section('content')
<!-- page content -->
<div class="container">

 <div class="wrapper-inner-tab-backgrounds-b">
    <div class="wrapper-inner-tab-backgrounds-first">
        <a target="_blank" href="{{ url('buscarBoletaPagoPersona') }}"><div class="sim-button-b button30"><span> Consultar CENAT </span></div></a>
    </div>

    <div class="wrapper-inner-tab-backgrounds-second">
        <a target="_blank" href="{{ url('checkPreCheck') }}"><div class="sim-button-b button30"><span> PRECHECK </span></div></a>
    </div>
 </div>

 <div class="row">
  <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_title">
          <h3>Consultar CHARLA</h3>
          <div class="clearfix"></div>
        </div>
      <div class="x_content">
        <form id="formulario" method="POST" class="form-horizontal form-label-left">
            <div class="form-group" >
                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Sexo<span class="required">*</span>
                </label>
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <select name="sexo" id="sexo" class="form-control">
                        <option value="f">F</option>
                        <option value="m">M</option>
                        <option value="x">X</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
              <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Documento<span class="required">*</span>
              </label>
              <div class="col-md-6 col-sm-6 col-xs-12">
                <input type="number" class="form-control" id="nro_doc" name="nro_doc" aria-describedby="nroDocumento" required = 'true' placeholder="Ejem ... 34125452">
              </div>
            </div>
        </form>

 <div class="form-group">
              <label class="control-label col-md-3 col-sm-3 col-xs-12"></label>
              <div class="col-md-6 col-sm-6 col-xs-12">
                <button id="enviar" name="enviar" onClick="ajaxCall()" class="btn btn-primary btn-block">Buscar Charla</button>
              </div>
<br>
<br>
            </div>
        <div class="clearfix"></div>
              </div>
    </div>
  </div>
 </div>


<div id="response" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true"></span></button>
                <strong></strong>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script>


        function ajaxCall() {

                $.ajaxSetup({
                        headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
                }
                });

            $.ajax({

                url: 'buscarCharlaPost',
                type: "POST",
                data: $("#formulario").serialize()
            }).done(function(res){
                let obj = JSON.parse(res);
                var clase = $('#response').attr('class');
                if(obj.encontrado == true){
                        console.log(obj.mensaje);
                        $('#response').html(obj.mensaje + "<br/>" + "Nombre: " + obj.nombre + "<br/>" + "Apellido: " + obj.apellido + "<br/>" + "Codigo: " + obj.codigo + "<br/>" + "Categoria: " + obj.categoria + "<br/>" + "Vencimiento de charla: " + obj.fechaVencimiento );
                        $('#response').removeClass('alert alert-danger alert-dismissible fade in');
                        $('#response').addClass('alert alert-success alert-dismissible fade in');
                }

                if(obj.encontrado == false){
                        $('#response').html(obj.mensaje)
                        $('#response').removeClass('alert alert-success alert-dismissible fade in');
                        $('#response').addClass('alert alert-danger alert-dismissible fade in');
                }
            });
        }
    </script>
</body>
<!-- /page content -->
@endsection

@push('scripts')
  <script src="{{ asset('vendors/jquery/dist/jquery.min.js')}}"></script>
  <!-- Bootstrap -->
  <script src="{{ asset('vendors/bootstrap/dist/js/bootstrap.min.js')}}"></script>
  <!-- Custom Theme Scripts -->
  <script src="{{ asset('build/js/custom.min.js')}}"></script>
@endpush

