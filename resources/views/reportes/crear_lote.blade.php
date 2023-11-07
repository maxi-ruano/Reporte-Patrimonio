{{-- 
@extends('layouts.templeate')
@section('titlePage', 'Crear Lote')
@section('content')




<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <!-- ... (resto del contenido) ... -->
            </div>
            <div class="x_content">
                <form action="{{ route('guardarLote') }}" method="POST"> 
                    @csrf



 

                    <input type="hidden" name="sucursal_id" value="{{ $sucursalSeleccionada }}">



                    <div class="form-group">
                        <label for="nro_control_desde">Nro Control Desde:</label>
                        <input type="text" name="nro_control_desde" id="nro_control_desde" class="form-control" required>
                        @isset($error2)
                        <span class="text-danger">{{ $error2 }}</span>
                    @endisset
                    </div>

                    <div class="form-group">
                        <label for="nro_control_hasta">Nro Control Hasta:</label>
                        <input type="text" name="nro_control_hasta" id="nro_control_hasta" class="form-control" required>
                        @isset($error2)
                        <span class="text-danger">{{ $error2 }}</span>
                    @endisset
                    </div>

                    <div class="form-group">
                        <label for="nro_kit">Nro Kit:</label>
                        <input type="text" name="nro_kit" id="nro_kit" class="form-control" required>
                        @isset($error)
                        <span class="text-danger">{{ $error }}</span>
                    @endisset
                    </div>

                    <div class="form-group">
                        <label for="sucursal">Sucursal:</label>
                        <select class="form-control" name="sucursal_id" id="sucursal">
             @if(auth()->user()->can('view_insumos_all'))
                       
                            @foreach ($Todassucursales as $sucursal)
                                <option value="{{ $sucursal->id }}">{{ $sucursal->description }}</option>
                            @endforeach
         @else
				<option value="{{auth()->user()->sucursal}}">{{$Todassucursales->where('id',auth()->user()->sucursal)->first()->description}}</option>
			@endif
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Crear Lote</button>
                </form>
            </div>
        </div>
    </div>
</div>


   



 --}}
 @extends('layouts.templeate')
 @section('titlePage', )
 @section('content')
 
 <h1>Crear Lote</h1>
 
 
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <!-- ... (resto del contenido) ... -->
            </div>
            <div class="x_content">
                <form action="{{ route('guardarLote') }}" method="POST"> 
                    @csrf

                    <input type="hidden" name="sucursal_id" value="{{ $sucursalSeleccionada }}">

 
                    <div class="form-group">
                        <label for="nro_control_desde">Nro Control Desde:</label>
                        <input type="number" name="nro_control_desde" id="nro_control_desde" class="form-control" required>
                        @isset($error2)
                        <span class="text-danger">{{ $error2 }}</span>
                    @endisset
                    </div>

                    <div class="form-group">
                        <label for="nro_control_hasta">Nro Control Hasta:</label>
                        <input type="number" name="nro_control_hasta" id="nro_control_hasta" class="form-control" required>
                        @isset($error2)
                        <span class="text-danger">{{ $error2 }}</span>
                    @endisset
                    </div>

                    <div class="form-group">
                        <label for="nro_kit">Nro Kit:</label>
                        <input type="text" name="nro_kit" id="nro_kit" class="form-control" required>
                        @isset($error)
                        <span class="text-danger">{{ $error }}</span>
                    @endisset
                    </div>

                    <div class="form-group">
                        <label for="sucursal">Sucursal:</label>
                        <select class="form-control" name="sucursal_id" id="sucursal">
             @if(auth()->user()->can('view_insumos_all'))
                       
                            @foreach ($Todassucursales as $sucursal)
                                <option value="{{ $sucursal->id }}">{{ $sucursal->description }}</option>
                            @endforeach
         @else
				<option value="{{auth()->user()->sucursal}}">{{$Todassucursales->where('id',auth()->user()->sucursal)->first()->description}}</option>
			@endif
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Crear Lote</button>
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
 