@extends('layouts.templeate')
@section('titlePage', 'Editar Lote Patrimonio')
@section('content')

<h1>Editar Lote Patrimonio</h1>

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <!-- ... (resto del contenido) ... -->
            </div>
            <div class="x_content">
                {{-- <form action="{{ route('actualizarLotePatrimonio') }} " method="POST"> --}}
                    <form method="POST" action="{{ route('actualizarLotePatrimonio', ['nro_kit' => $nro_kit]) }}"> 

                    @csrf
                    @method('PUT') 

                    <div class="form-group">
                        <label for="nro_control_desde">Nro Control Desde:</label>
                        <input type="number" name="nro_control_desde" id="nro_control_desde" class="form-control" value="{{ $nroControlDesde }}" required>
                    </div>

                    <div class="form-group">
                        <label for="nro_control_hasta">Nro Control Hasta:</label>
                        <input type="number" name="nro_control_hasta" id="nro_control_hasta" class="form-control" value="{{ $nroControlHasta }}" required>
                    </div>

                    <div class="form-group">
                        <label for="fecha_recibido_nacion">Fecha Recibido en Nación:</label>
                        <input type="date" name="fecha_recibido_nacion" id="fecha_recibido_nacion" class="form-control" value="{{ $fecha_recibido_nacion }}" required>
                    </div>

                    <div class="form-group">
                        <label for="fecha_recibido_sede">Fecha Recibido en Sede:</label>
                        <input type="date" name="fecha_recibido_sede" id="fecha_recibido_sede" class="form-control" value="{{ $fecha_recibido_sede }}" required>
                    </div>

                    <div class="form-group">
                        <label for="nro_kit">Nro Kit:</label>
                        <input type="text" name="nro_kit" id="nro_kit" class="form-control" value="{{ $nro_kit }}" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
