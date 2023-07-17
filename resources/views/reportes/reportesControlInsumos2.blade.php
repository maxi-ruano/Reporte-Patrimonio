
@extends('layouts.templeate')
@section('titlePage', 'Control Insumos')
@section('content')
    <!-- page content -->
    <br>
    <br>

    <form action="{{ route('reporte.control.insumos') }}" method="GET">
        <input type="hidden" name="page" value="{{ $lotesSucursal->currentPage() }}">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sucursal">Sucursal:</label>
                    <select class="form-control" name="sucursal" id="sucursal">
			@if(auth()->user()->can('view_insumos_all'))
                        	<option value="">Todos</option>
	                        @foreach ($Todassucursales as $sucursal)
        	                    <option value="{{ $sucursal->id }}" {{ $sucursal->id == $sucursalSeleccionada ? 'selected' : '' }}>
                	                {{ $sucursal->description }}
                        	    </option>
	                        @endforeach
			@else
				<option value="{{auth()->user()->sucursal}}">{{$Todassucursales->where('id',auth()->user()->sucursal)->first()->description}}</option>
			@endif
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="numero_kit">Número de Kit:</label>
                    <input type="text" class="form-control" name="numero_kit" id="numero_kit" placeholder="Ingrese el número de kit">
                </div>
            </div>

        </div>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>

    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="#">Ajustes 1</a></li>
                                <li><a href="#">Ajustes 2</a></li>
                            </ul>
                        </li>
                        <li><a class="close-link"><i class="fa fa-close"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <table id="datatable-responsive" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
                        <thead>
                            <tr>

                                <th>Lote_id</th>
                                <th>Sucursal</th>
                                <th>Control desde</th>
                                <th>Control hasta</th>
                                <th>Cant. Insumos</th>
                                <th>Cant. Codificados</th>
                                <th>Descartes</th>
                                <th>Blancos</th>
                                <th>N° Kit </th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lotesImpresos as $index => $insumo)
                                <tr>

                                    <td>{{ $insumo['lote_id'] }}</td>
                                    <td>{{ $insumo['sucursal'] }}</td>
                                    <td>{{ $insumo['control_desde'] }}</td>
                                    <td>{{ $insumo['control_hasta'] }}</td>
                                    <td>{{ $insumo['cantidadLote'] }}</td>
                                    <td>{{ $insumo['cantidadImpresos'] }}</td>
                                    <td>{{ $insumo['cantidadDescartados'] }}</td>
                                    <td>{{ $insumo['cantidadBlancos'] }}</td>
                                    <td>{{ $insumo['nroKit'] }}</td>
                                    <td>
                                      <button class="btn btn-primary btn-codificados" data-lote-id="{{ $insumo['lote_id'] }}" data-toggle="modal" data-target="#codificadosModal">Codificados</button>
                                    </td>

                                    <td>
                                    <button class="btn btn-primary btn-descartes" data-lote-id="{{ $insumo['lote_id'] }}" data-toggle="modal" data-target="#descartesModal">Descartes</button>
                                   </td>



                                   <td>
                                    <button class="btn btn-primary btn-blancos" data-lote-id="{{ $insumo['lote_id'] }}" data-toggle="modal" data-target="#blancosModal">Blancos</button>
                                </td>



                                </tr>
                            @endforeach
                        </tbody>
                              {{ $lotesSucursal->links() }}
                    </table>
                </div>
            </div>
        </div>
    </div>

    <p>
       <a class="btn btn-primary" href="{{ route('exportar.insumos', ['sucursal' => $sucursalSeleccionada, 'page' => $lotesSucursal->currentPage()]) }}">Descargar Excel</a>

    </p> 


<div class="modal fade" id="codificadosModal" tabindex="-1" role="dialog" aria-labelledby="codificadosModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="codificadosModalLabel">Codificados del Lote : <span id="loteIdPlaceholder"></span> </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>

                <a href="#" id="btnDescargarExcel" class="btn btn-primary">Descargar Excel</a> <!-- Agregado: Botón de descarga -->

            </div>
            <div class="modal-body">
                <p class="text-right">Número de Kit: <span id="numeroKit"></span></p>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Trámite ID</th>
                            <th>Número de Control</th>
                            <th>Creado por</th>
                            <th>Dni</th>
                            <th>Sexo</th>
                        </tr>
                    </thead>
                    <tbody id="codificadosTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="descartesModal" tabindex="-1" role="dialog" aria-labelledby="descartesModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="descartesModalLabel">Descartes del Lote: <span id="loteIdPlaceholder2"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
      <button id="btnDescargarExcel2" class="btn btn-primary">Descargar Excel</button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Trámite ID</th>
                            <th>Número de Control</th>
                            <th>Creado por</th>
                            <th>Descripcion</th>
                            <th>Dni</th>
                        </tr>
                    </thead>
                    <tbody id="descartesTableBody"></tbody>
                </table>
              
            </div>
        </div>
    </div>
</div>
 



<div class="modal fade" id="blancosModal" tabindex="-1" role="dialog" aria-labelledby="blancosModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="blancosModalLabel">
                    Blancos del Lote: <span id="loteIdPlaceholder3"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
              <button id="btnDescargarExcel3" class="btn btn-primary">Descargar Excel</button>
            </div>
            <div class="modal-body">
                <p class="text-right">Número de Kit: <span id="numeroKit"></span></p>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Número de Control</th>
                        </tr>
                    </thead>
                    <tbody id="blancosTableBody"></tbody>
                </table>

            </div>
        </div>
    </div>
</div>








@endsection


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>






<script>
 $(document).ready(function() {
        $('.btn-codificados').click(function() {
            var loteId = $(this).data('lote-id');
            $('#loteIdPlaceholder').text(loteId);
           // console.log(loteId);
            // Realiza una petición AJAX para obtener loscodificados del lote seleccionado
            $.ajax({
                url: '/precheck/obtener-codificados', // Reemplaza esto con la ruta adecuada en tu aplicación
                type: 'GET',
                data: { loteId: loteId },

                success: function(response) {
                numeroKit = response.numeroKit;
                    console.log(response);
                    // Limpia la tabla de codificados
                    $('#codificadosTableBody').empty();

                    // Agrega cada codificado a la tabla en el modal
                    response.codificados.forEach(function(codificado) {
                        var row = '<tr>' +
                            '<td>' + codificado.tramite_id + '</td>' +
                            '<td>' + codificado.nro_control + '</td>' +
                            '<td>' + codificado.created_by + '</td>' +
                            '<td>' + codificado.nro_doc + '</td>' +
                            '<td>' + codificado.sexo + '</td>' +
                            '</tr>';

                        $('#codificadosTableBody').append(row);
                    });

                    // Asigna el número de kit al elemento correspondiente
                    $('#numeroKit').text(response.numeroKit);

                    // Abre el modal
                    $('#codificadosModal').modal('show');
                },
                error: function(xhr, status, error) {
                    // Maneja el error de la petición AJAX
                    console.log(error);
                }
            });
        });
      $('#btnDescargarExcel').click(function() {
    var loteId = $('#loteIdPlaceholder').text();

    // Realiza una petición AJAX para obtener los datos en formato CSV
    $.ajax({
        url: '/precheck/descargar-csv', // Reemplaza esto con la ruta adecuada en tu aplicación
        type: 'GET',
        data: { loteId: loteId },
        xhrFields: {
            responseType: 'blob' // Indica que la respuesta será un archivo binario (Blob)
        },
        success: function(response) {
            // Crea un enlace temporal y lo simula como un clic para descargar el archivo
            var url = window.URL.createObjectURL(new Blob([response]));
            var a = document.createElement('a');
            a.href = url;
            a.download = 'Nro_kit_'+ numeroKit +'_Codificados.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },
        error: function(xhr, status, error) {
            // Maneja el error de la petición AJAX
            console.log(error);
        }
    });
});
    });

</script>


<script>
    $(document).ready(function() {
        $('.btn-descartes').click(function() {
            var loteId = $(this).data('lote-id');
            $('#loteIdPlaceholder2').text(loteId);

            // Realiza una petición AJAX para obtener los descartes del lote seleccionado
            $.ajax({
                url: '/precheck/obtener-descartes', // Reemplaza esto con la ruta adecuada en tu aplicación
                type: 'GET',
                data: { loteId: loteId },
                success: function(response) {

                numeroKit = response.numeroKit;
                    // Limpia la tabla de descartes
                    $('#descartesTableBody').empty();

                    // Agrega cada descarte a la tabla en el modal
                    response.descartes.forEach(function(descarte) {
                        var row = '<tr>' +
                            '<td>' + descarte.tramite_id + '</td>' +
                            '<td>' + descarte.control + '</td>' +
                            '<td>' + descarte.created_by + '</td>' +
                            '<td>' + descarte.descripcion + '</td>' +
                             '<td>' + descarte.nro_doc + '</td>' +

                            '</tr>';

                        $('#descartesTableBody').append(row);
                    });

                   $('#numeroKit').text(response.numeroKit);
                },
                error: function(xhr, status, error) {
                    // Maneja el error de la petición AJAX
                    console.log(error);
                }
            });
        });

    $('#btnDescargarExcel2').click(function() {
    var loteId = $('#loteIdPlaceholder2').text();

    // Realiza una petición AJAX para obtener los datos en formato CSV
    $.ajax({
        url: '/precheck/descargar-csv2', // Reemplaza esto con la ruta adecuada en tu aplicación
        type: 'GET',
        data: { loteId: loteId },
        xhrFields: {
            responseType: 'blob' // Indica que la respuesta será un archivo binario (Blob)
        },
        success: function(response) {
          // console.log(response);
            // Crea un enlace temporal y lo simula como un clic para descargar el archivo
            var url = window.URL.createObjectURL(new Blob([response]));
            var a = document.createElement('a');
            a.href = url;
           // a.download = 'descartes.csv';
            a.download = 'Nro_Kit_'+  numeroKit + '_Descartes.csv';

            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },
        error: function(xhr, status, error) {
            // Maneja el error de la petición AJAX
            console.log(error);
        }
    });
});




    });
</script>




<script>

$(document).ready(function() {
    $('.btn-blancos').click(function() {
        var loteId = $(this).data('lote-id');
        $('#loteIdPlaceholder3').text(loteId);

        $.ajax({
            url: '/precheck/obtener-blancos',
            type: 'GET',
            data: { loteId: loteId },
            success: function(response) {

            numeroKit = response.numeroKit;

                $('#cantidadBlancos').text(response.cantidadBlancos);
                $('#numeroKit').text(response.numeroKit);

                var blancos = response.blancos;
                var blancosTableBody = $('#blancosTableBody');
                blancosTableBody.empty();
                

                if (blancos.length > 0) {
                    for (var i = 0; i < blancos.length; i++) {
                        var numeroControl = blancos[i];
                        var row = '<tr><td>' + numeroControl + '</td></tr>';
                        blancosTableBody.append(row);
                    }
                } else {
                    var noBlancosRow = '<tr><td colspan="1">No hay blancos disponibles</td></tr>';
                    blancosTableBody.append(noBlancosRow);
                }
            },
            error: function(xhr, status, error) {
                console.log(error);
            }
        });
    });

  $('#btnDescargarExcel3').click(function() {
        var loteId = $('#loteIdPlaceholder3').text();
        // Realiza una petición AJAX para obtener los datos en formato Excel
        $.ajax({
            url: '/precheck/descargar-csv3', // Reemplaza esto con la ruta adecuada en tu aplicación
            type: 'GET',
            data: { loteId: loteId },
            xhrFields: {
                responseType: 'blob' // Indica que la respuesta será un archivo binario (Blob)
            },
            success: function(response) {
                console.log(response);
                // Crea un enlace temporal y lo simula como un clic para descargar el archivo
                var url = window.URL.createObjectURL(response);

                var a = document.createElement('a');
                a.href = url;
                a.download ='Nro_Kit_'+ numeroKit + '_Blancos.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            },
            error: function(xhr, status, error) {
                // Maneja el error de la petición AJAX
                console.log(error);
            }
        });
    });







});



</script>











@section('scripts')
    <!-- validator -->
    <script src="{{ asset('vendors/validator/validator.js')}}"></script>
    @include('includes.scriptForms')
    <!-- Datatables -->
    <script src="{{ asset('vendors/datatables.net/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-bs/js/dataTables.bootstrap.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/dataTables.buttons.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/buttons.flash.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/buttons.html5.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/buttons.print.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-keytable/js/dataTables.keyTable.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-responsive/js/dataTables.responsive.min.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js')}}"></script>
    <script src="{{ asset('vendors/datatables.net-scroller/js/dataTables.scroller.min.js')}}"></script>
    <script src="{{ asset('vendors/jszip/dist/jszip.min.js')}}"></script>
    <script src="{{ asset('vendors/pdfmake/build/pdfmake.min.js')}}"></script>
    <script src="{{ asset('vendors/pdfmake/build/vfs_fonts.js')}}"></script>
    {{-- <script>
        $(document).ready(function() {
            $('#datatable-responsive').DataTable({order: [[0, "desc"]]});
        });
    </script> --}}
@endsection

@section('css')
    <!-- Datatables -->
    <link href="{{ asset('vendors/datatables.net-bs/css/dataTables.bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css')}}" rel="stylesheet">
@endsection

