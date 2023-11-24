



 
 @extends('layouts.templeate')
 @section('titlePage', )
 @section('content')
 
 <h1>Crear Lote Patrimonio</h1>
 
 
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <!-- ... (resto del contenido) ... -->
            </div>
            <div class="x_content">
                <form action="{{ route('guardarLote') }}" method="POST"> 
                    @csrf


 
                    <div class="form-group">
                        <label for="nro_control_desde">Nro Control Desde:</label>
                        <input type="number" name="nro_control_desde" id="nro_control_desde" class="form-control" required>
                     
                    </div>

                    <div class="form-group">
                        <label for="nro_control_hasta">Nro Control Hasta:</label>
                        <input type="number" name="nro_control_hasta" id="nro_control_hasta" class="form-control" required>
                     
                    </div>

                    <div class="form-group">
                        <label for="nro_control_hasta"> Fecha Recibido_Nacion</label>
                        <input type="date" name="fecha_recibido_nacion" value="{{ now()->format('Y-m-d') }}" class="form-control" required>
                     
                    </div>

                    <div class="form-group">
                        <label for="nro_control_hasta"> Fecha_Habilitado_Sede</label>
                        <input type="date" name="fecha_habilitado_sede" id="nro_control_hasta" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                     
                    </div>

                    <div class="form-group">
                        <label for="nro_control_hasta">Fecha_Recibido_Sede</label>
                        <input type="date" name="fecha_recibido_sede" id="nro_control_hasta" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                     
                    </div>

                    {{-- <div class="form-group">
                        <label for="nro_control_hasta">Creation_By</label>
                        <input type="number" name="creation_by" id="nro_control_hasta" class="form-control" >
                     
                    </div> --}}

                    <div class="form-group">
                        <label for="nro_control_hasta">Modification_Date </label>
                        <input type="date" name="modification_date" id="nro_control_hasta" class="form-control" required>
                     
                    </div>

                  
                   <div class="form-group">
                        <label for="nro_kit">Nro Kit:</label>
                        <input type="text" name="nro_kit" id="nro_kit" class="form-control" required>
                  
                    </div>

                
                    
                    <button type="submit" class="btn btn-primary">Crear Lote</button>
                </form>
            </div>
        </div>
    </div>
</div>
 
 @endsection
 
 
 <script>
  
 </script>
 