@extends('layouts.templeate')
@section('titlePage', 'Dashboard')
@section('content')

<!-- page content -->

  <div class="right_col" role="main">
  
    {!! Form::open(['route' => 'consultaDashboard', 'id'=>'consultaDashboard', 'method' => 'get', 'class' => 'form-horizontal form-label-left', 'role' => 'form', 'files' => true ]) !!}
      <!-- /DatePicker -->
      <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12">
          <div class="input-group">
            <input name="fecha" id="fecha" type="text" data-date-format='yy-mm-dd' value='{{ $fecha }}' class="form-control has-feedback-left" >
            <span class="input-group-btn">
                <button id="btnConsultar" type="submit" class="btn btn-primary">Consultar</button>
            </span>
          </div>
        </div>
      </div>
      <!-- /end datePicker -->
    {{ Form::close() }}


    <!-- top Totales -->
    <div class="row tile_count">
      @foreach ($datos_precheck as $datos)
        <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
          <span class="count_top"><i class="{{ $datos['ico'] }}"></i> Total {{ $datos['titulo'] }} </span>
          <div class="count"> {{ $datos['total'] }} </div>
          <span class="count_bottom"> <i class="green">{{ $datos['porc'] }} % </i> {{ $datos['subtitulo'] }} </span>
        </div>
      @endforeach
    </div>

    <div class="row tile_count">
      @foreach ($datos_tramites as $datos)
        <div class="col-md-2 col-sm-4 col-xs-6 tile_stats_count">
          <span class="count_top"><i class="{{ $datos['ico'] }}"></i> Total {{ $datos['titulo'] }} </span>
          <div class="count"> {{ $datos['total'] }} </div>
          <span class="count_bottom"> <i class="green">{{ $datos['porc'] }} % </i> {{ $datos['subtitulo'] }} </span>
        </div>
      @endforeach
    </div>
    <!-- /top totales -->

    

    <!-- /Grafica - echart_donut -->
    <div class="col-md-5 col-sm-12 col-xs-12">
      <div class="x_panel">
        <div class="x_title">
          <h2>Personas en Espera</h2>
          <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
            <li><a class="close-link"><i class="fa fa-close"></i></a>
            </li>
          </ul>
          <div class="clearfix"></div>
        </div>
        <div class="x_content">
          <div id="echart_sedeRoca" style="height:600px;"></div>
        </div>
      </div>
    </div>

    <div class="col-md-7 col-sm-12 col-xs-12">
      <div class="x_panel">
        <div class="x_title">
          <h2>Por Sucursal</h2>
          <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
            <li><a class="close-link"><i class="fa fa-close"></i></a>
            </li>
          </ul>
          <div class="clearfix"></div>
        </div>
        <div id="echart_sedes" style="height:750px;"> </div> <br><br>
      </div>
    </div>
    <!-- /Grafica -->



    <!-- /Widget Summary - ESTADISTICAS -->
    <div class="col-md-4 col-sm-4 col-xs-12">
        <div class="x_panel tile fixed_height_350">
          <div class="x_title">
            <h2>ESTADÍSTICAS PreCheck </h2>
            <ul class="nav navbar-right panel_toolbox">
              <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
            </ul>
            <div class="clearfix"></div>
          </div>
          <div class="x_content">
            @foreach ($datos_precheck as $datos)
              <div class="widget_summary">
                <div class="w_left w_25">
                <span>{{ $datos['titulo'] }}</span>
                </div>
                <div class="w_center w_55">
                  <div class="progress">
                      <div class="progress-bar bg-green" role="progressbar" style="width: {{ $datos['porc'] }}%;">
                    </div>
                  </div>
                </div>
                <div class="w_right w_20">
                  <span> {{ $datos['porc'] }} %</span>
                </div>
                <div class="clearfix"></div>
              </div>
              @endforeach
          </div>
        </div>
      </div>
    <!-- /end Widget Summary -->

  </div>
<!-- /page content -->

@endsection

@push('scripts')
  <script src="{{ asset('vendors/jquery/dist/jquery.min.js')}}"></script>
  <!-- Bootstrap -->
  <script src="{{ asset('vendors/bootstrap/dist/js/bootstrap.min.js')}}"></script>

  <!-- bootstrap-daterangepicker -->
  <script src="{{ asset('vendors/moment/min/moment.min.js')}}"></script>
  <script src="{{ asset('vendors/bootstrap-daterangepicker/daterangepicker.js')}}"></script>

  <!-- Template Gentelella ECharts - Graph -->
  <script src="{{ asset('vendors/echarts/echarts.min.js')}}"></script>
  <script src="{{ asset('js/dashboard.js')}}"></script>
@endpush

@section('css')
    <!-- bootstrap-daterangepicker -->
    <link href="{{ asset('vendors/bootstrap-daterangepicker/daterangepicker.css')}}" rel="stylesheet">
@endsection 