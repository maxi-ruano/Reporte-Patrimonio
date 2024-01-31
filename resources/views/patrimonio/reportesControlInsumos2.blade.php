@extends('layouts.templeate')
@section('titlePage', 'Patrimonio')
@section('content')



<style>

    /* .btn-DescBlan{
        margin-left: 300px;
        position: relative;
    }
     */
    
    
    </style>
    
@if (session('custom_error'))
    <div class="alert alert-danger custom-error-message" style="font-size: 15px;">
        {{ session('custom_error') }}
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success" style="font-size: 15px;" >
        {{ session('success') }}
    </div>
@endif
    
{{-- <form action="{{ route('reporteLotesPatrminio') }}" method="GET" style="margin-top:1em"  id="formBuscarKit">
    
    @csrf
    <div class="form-group">
        <label for="kit">Buscar por Nro Kit:</label>
        <input type="text" name="kit" id="inputKit" class="form-control" placeholder="Ingrese el número de kit">
    </div>
    <button type="submit" class="btn btn-primary">Buscar</button>
</form>

<div id="resultadosBusqueda"></div> --}}
<form action="{{ route('reporteLotesPatrminio') }}" method="GET" style="margin-top: 1em">
    @csrf
    <div class="form-group">
        <label for="kit">Buscar por Nro Kit:</label>
        <input type="text" id="inputKit" name="kit" class="form-control" placeholder="Ingrese el número de kit">
    </div>
    <button type="submit" class="btn btn-primary">Buscar</button>
</form>



<form action="{{ route('acciones') }}" method="POST" style="margin-top:1em">
    @csrf 
        
    <div class="row" style="margin-bottom:1em">
        <div class="form-group col-md-4">
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

        <div class="form-group col-md-4">
            <label for="accion"><strong>Acciones:</strong> </label>
            <select class="form-control" name="accion" id="accion">
                <option value="">Seleccionar acción</option>
                <option value="asignarLote">Asignar Lote</option>
                <option value="enviarNacion">Enviar Nacion</option>
                <option value="enviarSede">Enviar Sede</option>
                
                <!-- Agrega más opciones según las acciones que desees -->
            </select>
        </div>
                    
        <div class="form-group col-md-6 col-xs-6">
            <button class="btn btn-primary">Ejecutar Acción</button>
        </div>
    </div>
    
    <div style="margin:1em 0">
        <a href="{{ route('cargarLote') }}"  class="btn btn-primary ">  Cargar Lote </a>
    </div>
   
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <table class="table table-bordered table-hover" style="font-size:1.1em" >
                <thead>
                    <tr>
                        <th>Seleccionar</th>
                        <!-- <th>ID</th> -->
                        <th>Nro Kit</th>
                        <th>Control desde</th>
                        <th>Control hasta</th>
                        <th>Sucursal</th>
                        <th>Recibido nación</th>
                        <th>Recibido sede</th>
                        <th>Habilitado por la sede</th>
                        <th>Enviado de sede</th>
                        <th>Enviado de Descartes y Blancos Nacion</th>
                        <th>Desc/Blanc</th>
                    </tr>
                </thead>
                <tbody>
                    <tbody>
                        @foreach($resultados as $resultado)
                        <tr>
                            <td align="center">
                                <input type="checkbox" name="seleccion[]" value="{{ $resultado->id }}" class="checkbox">
                            </td>
                            <td>{{ $resultado->nro_kit }}</td>
                            <td>{{ $resultado->nro_control_desde }}</td>
                            <td>{{ $resultado->nro_control_hasta }}</td>
                            <td>{{ $resultado->sucursal_description }}</td>
                            <td>{{ $resultado->fecha_recibido_nacion }}</td>
                            <td>{{ $resultado->fecha_recibido_sede }}</td>
                            <td>{{ $resultado->fecha_habilitado_sede }}</td>
                            <td>{{ $resultado->fecha_enviado_sede }}</td>
                            <td>{{ $resultado->fecha_enviado_nacion }}</td>
                            <td>
                                
                                <a href="{{ route('mostrarDatos', ['nro_kit' => $resultado->nro_kit]) }}" class="btn btn-success btn-DescBlan">Ver Descartes y blancos</a>

                            </td>
                            
                        </tr>
                    @endforeach          
                </tbody>       
            </table>
        </div>

        <div class="col-sm-12 col-xs-12 text-center">
            {{ $datosLotes->links() }}
        </div>
    </div>

</form>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
    $(document).ready(function() {
        
        $('#formBuscarKit').submit(function(e) {
            e.preventDefault(); // Evita el envío del formulario por defecto

            var kit = $('#inputKit').val();
            console.log(kit);

            $.ajax({
                url: "{{ route('reporteLotesPatrminio') }}",
                type: "POST",
                data: {
                    kit: kit,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#resultadosBusqueda').html(response);
                },
                error: function(xhr) {
                    console.error(xhr);
                }
            });
        });
    });
</script>
@endsection