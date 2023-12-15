
@extends('layouts.templeate')
@section('titlePage', 'Control Insumos')
@section('content')

<style>

.text-right {
    text-align: right;
}

.table{
    font-size: 14px;
}

.col-md-6{
    font-size: 14px;
}

.flex{
    display: flex;
}

.content-between{
    justify-content: space-between;
}

.mb-0{
    margin-bottom: 0;
}

.mb-1{
    margin-bottom: 1em;
}

.fsize{
   font-size: 15px;
   bottom: 108px;
}

</style>

<br>
    <br>


    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif




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

                    {{-- <button type="submit" class="btn btn-primary btn-sm">Buscar</button> --}}
                    {{-- <button type="submit" class="btn btn-primary btn-m">Buscar</button> --}}


                </div>

             
            </div>

            

        </div>
<br>
        <div class="col-md-6">
            <div class="form-group">
                <label for="control_desde">Control </label>

                
                {{-- <input type="text" class="form-control" name="control_desde" id="control_desde" placeholder="Ingrese nro control "> --}}
                <input type="text" class="form-control" name="nro_control" id="nro_control" placeholder="Ingrese nro control">


                <br>

                <button type="submit" class="btn btn-primary btn-m">Buscar</button>
                <br>
        

            </div>
        </div>

        <input type="hidden" name="selectedItems" id="selectedItems">

      
     </form> 




      <form action="{{ route('accionLote') }}" method="POST"> 
        @csrf 
        
        <div class="form-group">
            <label for="acciones">Acciones:</label>
            <select name="accion" id="acciones" class="form-control">



                <option >Elegir accion</option>




                @can('view_action_add')

                <option value="agregar_lote">Agregar Lote</option>

                @endcan

                {{-- @can('view_action_delete') --}}

                <option value="eliminar_lote">Eliminar Lote</option>

                {{-- @endcan --}}

                {{-- @can('view_action_edit') --}}

                <option value="editar_lote">Editar Lote</option>

                {{-- @endcan --}}


                {{-- @can('view_action_enable') --}}

                <option value="habilitar_lote">Habilitar Lote</option>

                
                {{-- @endcan --}}

                {{-- @can('view_action_disable') --}}

                <option value="deshabilitar_lote">Deshabilitar Lote</option>

                <option value="recibido">Recibido</option>

                <option value="enviado">Enviado</option>


                {{-- @endcan --}}

            </select>

        </div>

     
        {{-- @can('view_action_accept') --}}


        <button type="submit"  id="btn-acciones"  class="btn btn-primary acciones-btn" >Aceptar</button> <!-- Agrega un botón de envío para enviar el formulario -->
   
    
        {{-- @endcan --}}

        @if($errors->has('accion'))
        <div class="alert alert-danger">{{ $errors->first('accion') }}</div>
    @endif


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
                @if (session('warning'))
    <div class="alert alert-warning">
        {{ session('warning') }}
    </div>
@endif
                <div class="x_content">
                    <table id="datatable-responsive" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Checkbox</th>
                                <th>Lote ID</th>
                                <th>Sucursal</th>
                                <th>Control desde</th>
                                <th>Control hasta</th>
                                <th>Cant. Insumos</th>
                                <th>Cant. Codificados</th>
                                <th>Descartes</th>
                                <th>Blancos</th>
                                <th>N° Kit </th>
                                <th>Habilitado</th>

                                

                            </tr>
                        </thead>
                        <tbody>
                            
                            @foreach ($lotesImpresos as $index => $insumo)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selectedItems[]" value="{{ $insumo['lote_id'] }}">

                                    </td>

                                   

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
                                        @if($insumo['habilitado'])
                                            true
                                        @else
                                            false
                                        @endif
                                    </td>
                                    <td align="center">
                                      <button class="btn btn-primary btn-codificados btn-sm" data-lote-id="{{ $insumo['lote_id'] }}" data-toggle="modal" data-target="#codificadosModal">Codificados</button>
                                    </td>

                                    <td align="center">
                                    	<button class="btn btn-primary btn-descartes btn-sm" data-lote-id="{{ $insumo['lote_id'] }}" data-toggle="modal" data-target="#descartesModal">Descartes</button>
                                    </td>

                                   <td align="center">
                                    <button class="btn btn-primary btn-blancos btn-sm" data-lote-id="{{ $insumo['lote_id'] }}" data-toggle="modal" data-target="#blancosModal">Blancos</button>
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
       <a class="btn btn-primary btn-sm" href="{{ route('exportar.insumos', ['sucursal' => $sucursalSeleccionada, 'page' => $lotesSucursal->currentPage()]) }}">Descargar Excel</a>
    </p>


<div class="modal fade" id="codificadosModal" tabindex="-1" role="dialog" aria-labelledby="codificadosModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header flex content-between" style="flex-direction: row-reverse;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin: 0 12px 0 auto;">
                    <span aria-hidden="true">&times;</span>
                </button><br>

                <a href="#" id="btnDescargarExcel" class="btn btn-primary btn-sm mb-0">Descargar Excel</a> <!-- Agregado: Botón de descarga -->

            </div>
            <div class="modal-body">
		<div class="flex content-between mb-1">
	                <p class="mb-0 fsize" id="codificadosModalLabel">Codificados del Lote : <span id="loteIdPlaceholder"></span> </p>
	                <p class="mb-0 fsize" id='nroKit'>Número de Kit: <span id="numeroKit"></span></p>
		</div>

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
            <div class="modal-header flex content-between" style="flex-direction: row-reverse;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin: 0 12px 0 auto;">
                    <span aria-hidden="true">&times;</span>
                </button><br>
	      	<button id="btnDescargarExcel2" class="btn btn-primary btn-sm mb-0">Descargar Excel</button>
            </div>
            <div class="modal-body">
		<div class="flex content-between mb-1">
	                <p class="mb-0 fsize" id="descartesModalLabel">Descartes del Lote: <span id="loteIdPlaceholder2"></span></p>
			<p class="mb-0 fsize" id='nroKit'>Número de Kit: <span id="numeroKitPlaceholder"></span></p>
		</div>
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
            <div class="modal-header flex content-between" style="flex-direction: row-reverse;">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin: 0 12px 0 auto;">
                   <span aria-hidden="true">&times;</span>
              </button>
              <button id="btnDescargarExcel3" class="btn btn-primary btn-sm mb-0">Descargar Excel</button>
            </div>
            <div class="modal-body">
		<div class="flex content-between mb-1">
			<p class="mb-0 fsize">
	                    Blancos del Lote: <span id="loteIdPlaceholder3"></span>
        	        </p>
                  	<p class="mb-0 fsize" id='nroKit'>Número de Kit: <span id="numeroKitPlaceholder2"></span></p>
		</div>
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



      
      </form>

{{-- <button> VOLVER </button> --}}
<a href="{{ route('reporte.control.insumos') }}"> Volver</a>

@endsection


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>






<script>
//  $(document).ready(function() {
//         $('.btn-codificados').click(function() {

   

//             var loteId = $(this).data('lote-id');
//             $('#loteIdPlaceholder').text(loteId);
//            // console.log(loteId);
//             // Realiza una petición AJAX para obtener loscodificados del lote seleccionado
//             $.ajax({
//                 url: '/obtener-codificados', // Reemplaza esto con la ruta adecuada en tu aplicación
//                 type: 'GET',
//                 data: { loteId: loteId },

//                 success: function(response) {
//                 numeroKit = response.numeroKit;
//                     console.log(response);
//                     // Limpia la tabla de codificados
//                     $('#codificadosTableBody').empty();

//                     // Agrega cada codificado a la tabla en el modal
//                     response.codificados.forEach(function(codificado) {
//                         var row = '<tr>' +
//                             '<td>' + codificado.tramite_id + '</td>' +
//                             '<td>' + codificado.nro_control + '</td>' +
//                             '<td>' + codificado.created_by + '</td>' +
//                             '<td>' + codificado.nro_doc + '</td>' +
//                             '<td>' + codificado.sexo + '</td>' +
//                             '</tr>';

//                         $('#codificadosTableBody').append(row);
//                     });

//                     // Asigna el número de kit al elemento correspondiente
//                     $('#numeroKit').text(response.numeroKit);

//                     // Abre el modal
//                     $('#codificadosModal').modal('show');

              
//                 },
//                 error: function(xhr, status, error) {
//                     // Maneja el error de la petición AJAX
//                     console.log(error);
              
//                 }
//             });
//         });
//       $('#btnDescargarExcel').click(function() {
//     var loteId = $('#loteIdPlaceholder').text();

//     // Realiza una petición AJAX para obtener los datos en formato CSV
//     $.ajax({
//         url: '/precheck/descargar-csv', // Reemplaza esto con la ruta adecuada en tu aplicación
//         type: 'GET',
//         data: { loteId: loteId },
//         xhrFields: {
//             responseType: 'blob' // Indica que la respuesta será un archivo binario (Blob)
//         },
//         success: function(response) {
//             // Crea un enlace temporal y lo simula como un clic para descargar el archivo
//             var url = window.URL.createObjectURL(new Blob([response]));
//             var a = document.createElement('a');
//             a.href = url;
//             a.download = 'Nro_kit_'+ numeroKit +'_Codificados.csv';
//             document.body.appendChild(a);
//             a.click();
//             document.body.removeChild(a);
//             window.URL.revokeObjectURL(url);
//         },
//         error: function(xhr, status, error) {
//             // Maneja el error de la petición AJAX
//             console.log(error);
//         }
//     });
// });
//     });




$(document).ready(function() {
    var isActionRunning = false;

    $('.btn-codificados').click(function() {
        event.preventDefault();
        event.stopPropagation();
        if (isActionRunning) {
            return;
        }

        isActionRunning = true;
        $('.acciones-btn').prop('disabled', true);

        var loteId = $(this).data('lote-id');
        $('#loteIdPlaceholder').text(loteId);
        
        // Realiza una petición AJAX para obtener los codificados del lote seleccionado
        $.ajax({
            url: '/obtener-codificados', // Reemplaza esto con la ruta adecuada en tu aplicación
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
            },
            complete: function() {
                isActionRunning = false;
                $('.acciones-btn').prop('disabled', false);
            }
        });
    });

    // Agrega aquí tus otras funciones de botones (btn-descartes y btn-blancos)

    $('#btnDescargarExcel').click(function() {
        event.preventDefault();
        event.stopPropagation();
        var loteId = $('#loteIdPlaceholder').text();

        // Realiza una petición AJAX para obtener los datos en formato CSV
        $.ajax({
            url: '/descargar-csv', // Reemplaza esto con la ruta adecuada en tu aplicación
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
                a.download = 'Nro_kit_' + numeroKit + '_Codificados.csv';
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

    // Resto de tus funciones de botones

});





$(document).ready(function() {
    $('.btn-descartes').click(function(event) {
        event.preventDefault();
        event.stopPropagation();

        $('.acciones-btn').prop('disabled', true);

        var loteId = $(this).data('lote-id');
        $('#loteIdPlaceholder2').text(loteId);

        // Realiza una petición AJAX para obtener los descartes del lote seleccionado
        $.ajax({
            url: '/obtener-descartes', // Reemplaza esto con la ruta adecuada en tu aplicación
            type: 'GET',
            data: { loteId: loteId },
            success: function(response) {
                $('#numeroKitPlaceholder').text(response.numeroKit);
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

        // Abre el modal
        $('#descartesModal').modal('show');

        $('.acciones-btn').prop('disabled', false);
    });

    $('#btnDescargarExcel2').click(function(event) {
        event.preventDefault();

        var loteId = $('#loteIdPlaceholder2').text();

        // Realiza una petición AJAX para obtener los datos en formato CSV
        $.ajax({
            url: '/descargar-csv2', // Reemplaza esto con la ruta adecuada en tu aplicación
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









//     $(document).ready(function() {
        
//         $('.btn-descartes').click(function() {
//             var loteId = $(this).data('lote-id');
//             $('#loteIdPlaceholder2').text(loteId);

//             // Realiza una petición AJAX para obtener los descartes del lote seleccionado
//             $.ajax({
//                 url: '/obtener-descartes', // Reemplaza esto con la ruta adecuada en tu aplicación
//                 type: 'GET',
//                 data: { loteId: loteId },
//                 success: function(response) {
// 		    $('#numeroKitPlaceholder').text(response.numeroKit);
//                     numeroKit = response.numeroKit;
//                     // Limpia la tabla de descartes
//                     $('#descartesTableBody').empty();

//                     // Agrega cada descarte a la tabla en el modal
//                     response.descartes.forEach(function(descarte) {
//                         var row = '<tr>' +
//                             '<td>' + descarte.tramite_id + '</td>' +
//                             '<td>' + descarte.control + '</td>' +
//                             '<td>' + descarte.created_by + '</td>' +
//                             '<td>' + descarte.descripcion + '</td>' +
//                              '<td>' + descarte.nro_doc + '</td>' +

//                             '</tr>';

//                         $('#descartesTableBody').append(row);
//                     });

//                    $('#numeroKit').text(response.numeroKit);
//                 },
//                 error: function(xhr, status, error) {
//                     // Maneja el error de la petición AJAX
//                     console.log(error);
//                 }
//             });
//         });

//     $('#btnDescargarExcel2').click(function() {
//     var loteId = $('#loteIdPlaceholder2').text();

//     // Realiza una petición AJAX para obtener los datos en formato CSV
//     $.ajax({
//         url: '/precheck/descargar-csv2', // Reemplaza esto con la ruta adecuada en tu aplicación
//         type: 'GET',
//         data: { loteId: loteId },
//         xhrFields: {
//             responseType: 'blob' // Indica que la respuesta será un archivo binario (Blob)
//         },
//         success: function(response) {
//           // console.log(response);
//             // Crea un enlace temporal y lo simula como un clic para descargar el archivo
//             var url = window.URL.createObjectURL(new Blob([response]));
//             var a = document.createElement('a');
//             a.href = url;
//            // a.download = 'descartes.csv';
//             a.download = 'Nro_Kit_'+  numeroKit + '_Descartes.csv';

//             document.body.appendChild(a);
//             a.click();
//             document.body.removeChild(a);
//             window.URL.revokeObjectURL(url);
//         },
//         error: function(xhr, status, error) {
//             // Maneja el error de la petición AJAX
//             console.log(error);
//         }
//     });
// });

//     });





$(document).ready(function() {
    $('.btn-blancos').click(function(event) {
        event.preventDefault();
        event.stopPropagation();

        $('.acciones-btn').prop('disabled', true);

        var loteId = $(this).data('lote-id');
        $('#loteIdPlaceholder3').text(loteId);

        $.ajax({
            url: '/obtener-blancos',
            type: 'GET',
            data: { loteId: loteId },
            success: function(response) {
                numeroKit = response.numeroKit;
                $('#numeroKitPlaceholder2').text(response.numeroKit);
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

        // Abre el modal
        $('#blancosModal').modal('show');

        $('.acciones-btn').prop('disabled', false);
    });

    $('#btnDescargarExcel3').click(function(event) {
        event.preventDefault();

        var loteId = $('#loteIdPlaceholder3').text();

        // Realiza una petición AJAX para obtener los datos en formato Excel
        $.ajax({
            url: '/descargar-csv3', // Reemplaza esto con la ruta adecuada en tu aplicación
            type: 'GET',
            data: { loteId: loteId },
            xhrFields: {
                responseType: 'blob' // Indica que la respuesta será un archivo binario (Blob)
            },
            success: function(response) {
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











// $(document).ready(function() {
//     $('.btn-blancos').click(function() {
//         var loteId = $(this).data('lote-id');
//         $('#loteIdPlaceholder3').text(loteId);

//         $.ajax({
//             url: '/obtener-blancos',
//             type: 'GET',
//             data: { loteId: loteId },
//             success: function(response) {

// 	        numeroKit = response.numeroKit;
// 		$('#numeroKitPlaceholder2').text(response.numeroKit);
//                 $('#cantidadBlancos').text(response.cantidadBlancos);
//                 $('#numeroKit').text(response.numeroKit);

//                 var blancos = response.blancos;
//                 var blancosTableBody = $('#blancosTableBody');
//                 blancosTableBody.empty();


//                 if (blancos.length > 0) {
//                     for (var i = 0; i < blancos.length; i++) {
//                         var numeroControl = blancos[i];
//                         var row = '<tr><td>' + numeroControl + '</td></tr>';
//                         blancosTableBody.append(row);
//                     }
//                 } else {
//                     var noBlancosRow = '<tr><td colspan="1">No hay blancos disponibles</td></tr>';
//                     blancosTableBody.append(noBlancosRow);
//                 }
//             },
//             error: function(xhr, status, error) {
//                 console.log(error);
//             }
//         });
//     });

//   $('#btnDescargarExcel3').click(function() {
//         var loteId = $('#loteIdPlaceholder3').text();
//         // Realiza una petición AJAX para obtener los datos en formato Excel
//         $.ajax({
//             url: '/precheck/descargar-csv3', // Reemplaza esto con la ruta adecuada en tu aplicación
//             type: 'GET',
//             data: { loteId: loteId },
//             xhrFields: {
//                 responseType: 'blob' // Indica que la respuesta será un archivo binario (Blob)
//             },
//             success: function(response) {
//                 console.log(response);
//                 // Crea un enlace temporal y lo simula como un clic para descargar el archivo
//                 var url = window.URL.createObjectURL(response);

//                 var a = document.createElement('a');
//                 a.href = url;
//                 a.download ='Nro_Kit_'+ numeroKit + '_Blancos.csv';
//                 document.body.appendChild(a);
//                 a.click();
//                 document.body.removeChild(a);
//                 window.URL.revokeObjectURL(url);
//             },
//             error: function(xhr, status, error) {
//                 // Maneja el error de la petición AJAX
//                 console.log(error);
//             }
//         });
//    });

// });


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

