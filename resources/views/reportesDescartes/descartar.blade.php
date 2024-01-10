
   



 --}}
 @extends('layouts.templeate')
 @section('titlePage', )
 @section('content')
 
 <h1>Descartar Insumo</h1>
 <br><br>
 
 
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <!-- ... (resto del contenido) ... -->
            </div>
            <div class="x_content">
                <form action="{{ route('insertarDescarte') }}" method="POST" id="miFormulario" > 
                    @csrf

               

 
                    <div class="form-group">
                        <label for="nro_control">Nro Control </label>
                        <input type="number" name="nro_control" id="nro_control" class="form-control" required >
                     
                    </div>

                    <div class="form-group">
                        <label for="nro_control">Descripcion </label>
                        <input type="text" name="descripcion" id="descripciob" class="form-control" required >
                     
                    </div>
                   




                    <div class="modal fade" id="miModal" tabindex="-1" role="dialog" aria-labelledby="miModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="miModalLabel">Detalles del Usuario</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p > <strong>Descartado:</strong> <span id="descartado"></span></p>
                                    <p > <strong>  En uso :</strong> <span id="enuso"></span></p>
                                    <p><strong>Tramite_id:</strong> <span id="tramite_id"></span></p>
                                    <p><strong>Sucursal_id:</strong> <span id="sucursal_id"></span></p>
                                    <p><strong>Nombre: </strong> <span id="nombre"></span></p>
                                    <p><strong>Apellido:</strong> <span id="apellido"></span></p>
                                    <p><strong>Género:</strong> <span id="genero"></span></p>
                                    <p><strong>DNI:</strong> <span id="documento"></span></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                    <button type="button" class="btn btn-primary" id="confirmarDatos">Confirmar y Enviar</button>
                                </div>
                                {{-- <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                    



                    
                    <button type="submit" class="btn btn-primary">Aceptar</button>
                </form>
            </div>
        </div>
    </div>
</div>




 



<!-- Agrega jQuery -->
{{-- <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- Agrega el archivo JavaScript de Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.min.js"></script> --}}



<!-- Agrega jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- Agrega el archivo JavaScript de Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.min.js"></script>

<!-- Agrega este script para mostrar el modal cuando la página se cargue -->




 <script>
    $(document).ready(function() {
        // Captura el clic en el botón "Aceptar"
        $('#miFormulario').submit(function(e) {
            e.preventDefault(); // Evita que el formulario se envíe

            // Recupera los datos del formulario
            var nroControl = $('#nro_control').val();
            var descripcion = $('#descripcion').val();

            // Realiza una solicitud AJAX para obtener los datos de data2
            $.ajax({
                type: 'POST',
                url: '{{ route('consultarDatos') }}', // Nueva ruta para consultar datos
                data: {
                    _token: '{{ csrf_token() }}',
                    nro_control: nroControl
                },
                success: function(response) {
                    // Llena el modal con los datos consultados

                    $('#descartado').text(response.descartado);
                    $('#enuso').text(response.enuso);
                    $('#tramite_id').text(response.tramite_id);
                    $('#sucursal_id').text(response.sucursal_id);
                    $('#nombre').text(response.nombre);
                    $('#apellido').text(response.apellido);
                    $('#genero').text(response.genero);
                    $('#documento').text(response.documento);

                    // Muestra el modal
                    $('#miModal').modal('show');
                },
                error: function(error) {
                    // Maneja errores si es necesario
                    console.error('Error en la solicitud AJAX: ' + error.statusText);
                }
            });
        });

        // Captura el clic en el botón "Confirmar y Enviar" en el modal
        $('#confirmarDatos').click(function() {
            // Envía el formulario después de confirmar en el modal
            $('#miFormulario').unbind('submit').submit();
        });
    });
</script> 




 
<script>
    $(document).ready(function () {
        // Evento que se ejecuta cuando el modal se muestra
        $('#miModal').on('show.bs.modal', function (event) {
            // Obtén el valor de descartado
            var descartado = $('#descartado').text().trim().toLowerCase();

            // Deshabilita el botón si descartado es 'si'
            if (descartado === 'si') {
                $('#confirmarDatos').prop('disabled', true);
            } else {
                $('#confirmarDatos').prop('disabled', false);
            }
        });
    });
</script>






  


<a href="{{ route('informe-descartes') }}"> Volver</a>

 
 @endsection
 
 


 
   


