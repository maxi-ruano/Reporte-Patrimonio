@extends('layouts.templeate')
@section('titlePage', 'Editar Lote')
@section('content')

<h1>Editar Lote</h1>

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <!-- ... (resto del contenido) ... -->
            </div>
            <div class="x_content">
                <form  id="editar-lote-form" action="{{ route('actualizarLote', ['lote_id' => $lote->lote_id]) }}" method="POST">
                    {{-- <form id="editar-lote-form" action="{{ route('actualizarLote', ['lote_id' => $selectedItems[0]]) }}" method="POST"> --}}

                    @csrf
                    @method('PUT') <!-- Agrega el método HTTP PUT para indicar que se usará la actualización -->

                    {{-- <div class="form-group">
                        <label for="nro_control_desde">Lote id</label>
                        <input type="text" name="lote_id" id="lote_id" class="form-control" value="{{ $lote->lote_id }}" readonly>
                    </div> --}}

                    <div class="form-group">
                        <label for="nro_control_desde">Nro Control Desde:</label>
                        <input type="number" name="nro_control_desde" id="nro_control_desde" class="form-control" value="{{ $lote->control_desde }}">
                    </div>

                    <div class="form-group">
                        <label for="nro_control_hasta">Nro Control Hasta:</label>
                        <input type="number" name="nro_control_hasta" id="nro_control_hasta" class="form-control" value="{{ $lote->control_hasta }}">
                    </div>

                    <div class="form-group">
                        <label for="nro_kit">Nro Kit:</label>
                        <input type="number" name="nro_kit" id="nro_kit" class="form-control" value="{{ $lote->nro_kit }}">
                    </div>

                

                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection


<script>
    document.getElementById('editar-lote-form').addEventListener('submit', function(event) {
        // Obtiene la lista de checkboxes seleccionados
        var checkboxes = document.querySelectorAll('input[name="lote_seleccionado[]"]:checked');

        // Verifica si al menos un checkbox está seleccionado
        if (checkboxes.length === 0) {
            // Evita que el formulario se envíe
            event.preventDefault();

            // Muestra un mensaje de advertencia o usa alguna biblioteca para mostrar un modal, alerta, etc.
            alert('Por favor, selecciona al menos un lote antes de realizar la acción.');
        }
    });
</script>
