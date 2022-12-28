@extends('layouts.app')
@section('content')
<!-- page content -->
<div class="container">
 
 <div class="wrapper-inner-tab-backgrounds-b">
    <div class="wrapper-inner-tab-backgrounds-first">
        <a target="_blank" href="{{ url('buscarCharla') }}"><div class="sim-button-b button30"><span> Consultar Charla </span></div></a>
    </div>

    <div class="wrapper-inner-tab-backgrounds-second">
        <a target="_blank" href="{{ url('checkPreCheck') }}"><div class="sim-button-b button30"><span> PRECHECK </span></div></a>
    </div>
 </div>

 <div class="row">
  <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
        <div class="x_title">
          <h3>Consultar CENAT</h3>
          <div class="clearfix"></div>
        </div>
      <div class="x_content">
        {!! Form::open(['route' => 'buscarBoletaPagoPersona', 'id'=>'buscarBoletaPagoPersona', 'method' => 'POST', 'class' => 'form-horizontal form-label-left', 'role' => 'form', 'files' => true ]) !!}
            <div class="form-group">
                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Tipo<span class="required">*</span>
                </label>
                <div class="col-md-6 col-sm-6 col-xs-12">
                    {!! Form::select('tipo_doc', $tipodocs, 1, ['class' => 'form-control']) !!}
                </div>
            </div>    
            <div class="form-group">
              <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Documento<span class="required">*</span>
              </label>
              <div class="col-md-6 col-sm-6 col-xs-12">
                <input type="number" class="form-control" id="nro_doc" name="nro_doc" aria-describedby="nroDocumento" required = 'true' placeholder="Ejem ... 34125452">
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-md-3 col-sm-3 col-xs-12"></label>
              <div class="col-md-6 col-sm-6 col-xs-12">
                <button type="submit" class="btn btn-primary btn-block">Buscar Boleta de Pago</button>
              </div>
            </div>
        {{ Form::close() }}
        <div class="clearfix"></div>
        
        @if ($boletas != null)
            @if (!empty($boletas->datosBoletaPago->datosBoletaPagoParaPersona))
              <div class="x_panel">
                <h2> {{ $boletas->rspDescrip }}</h2>
                <div class="x_content">
                  <div class="flex">
                    <ul class="list-inline count2">
                      <li>
                        <h3>{{ $boletas->datosBoletaPago->datosPersonaBoletaPago->oprDocumento }}</h3>
                        <span>Documento</span>
                      </li>
                      <li>
                          <h3>{{ $boletas->datosBoletaPago->datosPersonaBoletaPago->oprNombre.' '.$boletas->datosBoletaPago->datosPersonaBoletaPago->oprApellido }}</h3>
                          <span>Nombre y Apellido</span>
                      </li>
                      <li>
                        <h3>{{ $boletas->datosBoletaPago->datosPersonaBoletaPago->oprSexo }}</h3>
                        <span>Sexo</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Boleta ID</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>Fecha de Registo</th>
                    <th>Estado</th>                                    
                  </tr>
                </thead>
                <tbody>
                @foreach ($boletas->datosBoletaPago->datosBoletaPagoParaPersona as $key => $boleta)
                  <tr>
                    <td>{{ $boleta->bopID }}</td>
                    <td>{{ $boleta->bopEstadoDescrip }}</td>
                    <td>{{ $boleta->bopMonto }}</td>
                    <td>{{ $boleta->bopFecReg }}</td>
                    <td>
                      @if ($boleta->estDescrip == 'Acreditada')
                        <span style="color:forestgreen"><b> {{ $boleta->estDescrip }} </b></span> 
                      @else
                        <span style="color:red;"> {{ $boleta->estDescrip }} </span>
                      @endif
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            @endif
        @endif

        @if ($error != '')
          <div class="alert alert-danger alert-dismissible fade in" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
            <strong> {{ $error }} </strong>
          </div>
        @endif
      </div>
    </div>
  </div>
 </div>
</div>
<!-- /page content -->
@endsection

@push('scripts')
  <script src="{{ asset('vendors/jquery/dist/jquery.min.js')}}"></script>
  <!-- Bootstrap -->
  <script src="{{ asset('vendors/bootstrap/dist/js/bootstrap.min.js')}}"></script>
  <!-- Custom Theme Scripts -->
  <script src="{{ asset('build/js/custom.min.js')}}"></script>
@endpush
