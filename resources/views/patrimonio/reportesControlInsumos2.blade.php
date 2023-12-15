
@extends('layouts.templeate')
@section('titlePage', 'Patrimonio')
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


 

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    
   


<form action="{{ route('cargarLote') }}" method="POST"> 
    @csrf 
    
    <div class="form-group">
        <label for="acciones">CARGAR LOTE :  </label> <br><br>



    


            <a href="{{ route('cargarLote') }}"  class="btn btn-primary acciones-btn">  cargar</a>


         


        

       


    </div>

 
</form>










    <br><br>

 


    {{-- <form   action="{{ route('asignarLotePatrimonio') }}" method="POST">  --}}
        <form action="{{ route('acciones') }}" method="POST">

        @csrf 
        



        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sucursal">Sucursal:</label>
                    <select class="form-control" name="sucursal" id="sucursal">
                        <option value="">Todos</option>
                        @foreach($todasSucursales as $sucursal)
                            <option value="{{ $sucursal->id }}">
                                {{ $sucursal->description }}
    
                            
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    
        <br><br>


        {{-- <div class="form-group">
            <label for="acciones">Asignar lote  a Sede :  </label> <br><br>
    
    
    
        
    
    
    
    
                   
         <button  class="btn btn-primary acciones-btn" > Asignar a sede  </button>
    
            
    
           
    
    
        </div> --}}
    
        <div class="form-group">
            <label for="acciones">Acciones:</label>
            <select class="form-control" name="accion" id="accion">
                <option value="">Seleccionar acción</option>
                <option value="asignarLote">Asignar Lote</option>
                <option value="enviarNacion">Enviar Nacion</option>
                <option value="enviarSede">Enviar Sede</option>


                <!-- Agrega más opciones según las acciones que desees -->
            </select>
        </div>
    
        <div class="form-group">
            <button class="btn btn-primary acciones-btn">Ejecutar Acción</button>
        </div>
    
      
    
      

 


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
                                <th>
                                    Checkbox> 
                                </th>
                                <th>ID </th>
                                <th>Sucursal</th>
                                <th>Control_desde</th>
                                <th>Control_hasta</th>
                                <th>Fecha_recibido_nacion</th>
                                <th>Fecha_recibido_sede</th>
                                <th>Fecha_habilitado_sede</th>
                                <th>Fecha_enviado_sede</th>
                                <th>Fecha_enviado_nacion</th>

                               {{-- <th> Modification_date </th> --}}
                               <th> Nro_Kit</th>
                         


                             

                                

                            </tr>
                        </thead>
                        <tbody>
                            <tbody>
                                @foreach($resultados as $resultado)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="seleccion[]" value="{{ $resultado->id }}" class="checkbox">
                                    </td>
                                    <td>{{ $resultado->id }}</td>
                                    <td>{{ $resultado->sucursal_description }}</td>
                                    <td>{{ $resultado->nro_control_desde }}</td>
                                    <td>{{ $resultado->nro_control_hasta }}</td>
                                    <td>{{ $resultado->fecha_recibido_nacion }}</td>
                                    <td>{{ $resultado->fecha_recibido_sede }}</td>
                                    <td>{{ $resultado->fecha_habilitado_sede }}</td>
                                    <td>{{ $resultado->fecha_enviado_sede }}</td>
                                    <td>{{ $resultado->fecha_enviado_nacion }}</td>
                                    <td>{{ $resultado->nro_kit }}</td>
                                </tr>
                            @endforeach
                            
                            </tbody>
                            
                        
                        </tbody>
                           
                    </table>
                </div>
            </div>
        </div>
    </div>

</form>



  


      


@endsection


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>















{{-- <script>
    $(document).ready(function () {
        // Configuración del botón Descartes
        $('.btn-descartes').on('click', function () {
            
            // Aquí debes obtener la información específica para Descartes y mostrarla en el modal
            var loteId = $(this).closest('tr').find('td:eq(0)').text();
            var patrimonio = " ";
            var descripcion = "Patrimonio Descartes ";
          
            var cantidad = ""; // obtener el valor correspondiente;
            var creationBy = ""; // obtener el valor correspondiente;

            // Actualizar el contenido del modal Descartes
            $('#descartesModal .modal-body').html(
                `<p><strong>Lote ID:</strong> ${loteId}</p>
                 <p><strong>Patrimonio:</strong> ${patrimonio}</p>
                 <p><strong>Descripción:</strong> ${descripcion}</p>
                 <p><strong>Cantidad:</strong> ${cantidad}</p>
                 <p><strong>Creation By:</strong> ${creationBy}</p>`
            );

            // Mostrar el modal Descartes
            $('#descartesModal').modal('show');
        });

        // Configuración del botón Blancos
        $('.btn-blancos').on('click', function () {
            // Aquí debes obtener la información específica para Blancos y mostrarla en el modal
            var loteId = $(this).closest('tr').find('td:eq(0)').text();
            var patrimonio = "Patrimonio Blancos"; // obtener el valor correspondiente;
            var descripcion = "Patrimonio Blancos"; // obtener el valor correspondiente;
            var cantidad = ""; // obtener el valor correspondiente;
            var creationBy = ""; // obtener el valor correspondiente;

            // Actualizar el contenido del modal Blancos
            $('#blancosModal .modal-body').html(
                `<p><strong>Lote ID:</strong> ${loteId}</p>
                 <p><strong>Patrimonio:</strong> ${patrimonio}</p>
                 <p><strong>Descripción:</strong> ${descripcion}</p>
                 <p><strong>Cantidad:</strong> ${cantidad}</p>
                 <p><strong>Creation By:</strong> ${creationBy}</p>`
            );

            // Mostrar el modal Blancos
            $('#blancosModal').modal('show');
        });
    });
</script> --}}
{{-- <script>
    $(document).ready(function () {
        // Asociando el evento click al botón de carga
        $('#cargarDescartesBtn').click(function (e) {
            // Obtener los elementos seleccionados
            var elementosSeleccionados = $('input.checkbox:checked');
            console.log(elementosSeleccionados);

            // Iterar sobre los elementos seleccionados y redirigir a la página de guardar lote para cada uno
            elementosSeleccionados.each(function () {
                var loteId = $(this).val();
                var redirectUrl = "{{ route('cargarDescartes') }}" + "?lote_id=" + loteId;
                window.location.href = redirectUrl;
            });
        });
        $('input.checkbox').prop('checked', false);
        $(window).on('pageshow', function () {
            $('input.checkbox').prop('checked', false);
        });
    });
</script> --}}


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
    





   
@endsection

@section('css')
    <!-- Datatables -->
    <link href="{{ asset('vendors/datatables.net-bs/css/dataTables.bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css')}}" rel="stylesheet">
@endsection

