@extends('layouts.templeate')
@section('titlePage', 'Patrimonio')
@section('content')

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
                        <th>Enviado de Descartes y Blancos</th>
                    </tr>
                </thead>
                <tbody>
                    <tbody>
                        @foreach($resultados as $resultado)
                        <tr>
                            <td align="center">
                                <input type="checkbox" name="seleccion[]" value="{{ $resultado->id }}" class="checkbox">
                            </td>
                            <!-- <td>{{ $resultado->id }}</td> -->
                            <td>{{ $resultado->nro_kit }}</td>
                            <td>{{ $resultado->nro_control_desde }}</td>
                            <td>{{ $resultado->nro_control_hasta }}</td>
                            <td>{{ $resultado->sucursal_description }}</td>
                            <td>{{ $resultado->fecha_recibido_nacion }}</td>
                            <td>{{ $resultado->fecha_recibido_sede }}</td>
                            <td>{{ $resultado->fecha_habilitado_sede }}</td>
                            <td>{{ $resultado->fecha_enviado_sede }}</td>
                            <td>{{ $resultado->fecha_enviado_nacion }}</td>
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

@endsection