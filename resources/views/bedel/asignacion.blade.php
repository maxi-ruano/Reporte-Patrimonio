@extends('layouts.templeate')

@section('content')
<!-- page content -->
<div class="row">
  <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">

      <div class="x_content">

                {!! Form::open(['route' => 'bedel.index', 'id'=>'formCategory', 'method' => 'GET', 'class' => 'form-horizontal', 'role' => 'form', 'files' => true ]) !!}
                <input type="text" name="op" id="op" value="find" class="hide">
                <div class="form-group">
                    <div class="col-md-1 col-sm-1">
                      <select name="pais" class="form-control" required place-holder="asd">
                        <option value="" disabled selected>Nac.</option>
                        @foreach($default['paises'] as $pais)
                        @if($pais->id == 1)
                        <option value="{{ $pais->id }}" >{{ $pais->description }}</option>
                        @else
                        <option value="{{ $pais->id }}">{{ $pais->description }}</option>
                        @endif
                        @endforeach
                      </select>
                    </div>

                    <div class="col-md-1 col-sm-1">
                      <select name ="tipo_doc" class="form-control" required>
                        @foreach($default['tdoc'] as $tdoc)
                        @if($tdoc->id == 1)
                        <option value="{{ $tdoc->id }}" selected>{{ $tdoc->description }}</option>
                        @else
                        <option value="{{ $tdoc->id }}">{{ $tdoc->description }}</option>
                        @endif
                        @endforeach
                      </select>
                    </div>

                    <div class="col-md-2 col-sm-2">
                      <input name="doc" type="text" class="form-control" placeholder="Documento" required>
                    </div>

                    <div class="col-md-1 col-sm-1">
                      <select name="sexo" class="form-control" required>
                        @foreach($default['sexo'] as $sex)
                        @if($sex->id == 0)
                        <option value="" selected disabled>Sexo</option>
                        @else
                        <option value="{{ strtolower($sex->description) }}">{{ $sex->description }}</option>
                        @endif
                        @endforeach
                      </select>
                    </div>


                  <!--<div class="ln_solid"></div>-->

                    <!--<div class="col-md-2 col-sm-2">-->
                      <input id="send" type="submit" class="btn btn-success col-md-1 col-sm-1" value="Enviar">
                    </div>


                {!! Form::close() !!}
                @if( $categorias[0] != false )
                <button  type="button" class="btn btn-primary" data-toggle="modal" data-target=".bs-example-modal-lg">Large modal</button>

                <div id="modalCliente" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content text-center">
                      <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                        </button>
                        <h4 class="modal-title" id="myModalLabel"><strong>Datos Personales</strong></h4>
                      </div>
                      <div class="modal-body text-center">
                        @if($categorias[0] != false)
                        <div class="profile_details">
                        <div class="well profile_view">
                          <div class="col-sm-12">
                            <div class="right col-xs-5 text-center">
                              <img  class="img-pregunta img-responsive" onerror="this.src=\'http://192.168.76.215/deve_teorico/public/production/images/user.png\'"  style = "height: 150px; width: auto;" src="{{ $datos[2] }}" alt="Generic placeholder thumbnail">

                            </div>
                            <div class="left col-xs-7">
                              @if($categorias[0] != false)
                              <h2> Nombre:<strong>  {{ $datos[1]->nombre }} </strong></h2>
                              <h2> Apellido:<strong>  {{ $datos[1]->apellido }} </strong></h2>
                              <h2><p>DNI: <strong> {{  $datos[1]->nro_doc }}</strong> </p></h2>
                              @endif
                            </div>

                          </div>
                        </div>
                        </div>
                        {!! Form::open(['route' => 'bedel.index', 'id'=>'formCategory', 'method' => 'GET', 'class' => 'form-horizontal', 'role' => 'form', 'files' => true ]) !!}

                        <div class="form-group">
                          <div class="row">
                              <div class="col-lg-6 col-lg-offset-3">
                                <div class="col-md-4 col-sm-6">
                                  <select name ="categorias" class="form-control" required>
                                    <option value="" selected>Categoria</option>
                                    @foreach($categorias[1]->tramite as $cat)
                                    <option value="{{ $cat->clase }}">{{ $cat->clase }}</option>
                                    @endforeach
                                  </select>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                  <select name ="computadoras" class="form-control">
                                    <option value="" selected>Computadora</option>
                                    @if($computadoras[0] != false)
                                      @foreach($computadoras[1] as $computadora)
                                      <option value="{{ $computadora->id }}">{{ $computadora->id }}</option>
                                      @endforeach
                                    @endif
                                  </select>

                                </div>
                                <div class="col-md-4 col-sm-6">
                                  <button type="button" class="btn btn-primary">ASIGNAR</button>
                                </div>
                            </div>
                          </div>

                        </div>
                        {!! Form::close() !!}
                        @else
                          <!--<div class="form-group">
                            <div class="panel panel-default">
                              <div class="panel-body"><h3> $peticion[1]->disponibilidadMensaje </h3></div>
                            </div>
                          </div>-->
                        @endif
                      </div>
                      <div class="modal-footer">
                        <div class="text-center">
                          <button type="button" aling="center" class="btn btn-default" data-dismiss="modal">CERRAR</button>

                        </div>

                      </div>

                    </div>
                  </div>
                </div>
                @endif
      </div>
      @include('bedel.monitoreo')
    </div>
  </div>
</div>
<!-- /page content -->

@endsection('content')

@push('scripts')

    <script>
    $( document ).ready(function() {
      if ('{{ !empty($peticion[1]->tramite_id) }}')
        $('#modalCliente').modal('show');

    });
    </script>
@endpush
