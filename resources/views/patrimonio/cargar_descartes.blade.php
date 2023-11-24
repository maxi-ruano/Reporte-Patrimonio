



 
 @extends('layouts.templeate')
 @section('titlePage', )
 @section('content')
 
 <h1>Crear Descartes Patrimonio</h1>
 
 
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <!-- ... (resto del contenido) ... -->
            </div>
            <div class="x_content">
                <form action="{{ route('guardarDescarte') }}" method="POST"> 
                    @csrf

                    <div class="form-group">
                        <label for="lote_id">Lote_id</label>
                        <input type="text" name="lote_id" id="lote_id" class="form-control" value="{{ $loteId }}" disabled>
                    </div>
                
                
 
                    <div class="form-group">
                        <label for="nro_control_desde">Nro Control </label>
                        <input type="number" name="nro_control_desde" id="nro_control_desde" class="form-control" required>
                     
                    </div>

                    <div class="form-group">
                        <label for="tipo_lote">Descripcion </label>
                        <select name="descripcion" id="descripcion" class="form-control" required>
                            <option value="descartes">Descartes</option>
                            <option value="blancos">Blancos</option>
                        </select>
                    </div>
                  
                  

                
                    
                    <button type="submit" class="btn btn-primary">Crear </button>
                </form>
            </div>
        </div>
    </div>
</div>
 
 @endsection
 
 
 <script>
  
 </script>
 