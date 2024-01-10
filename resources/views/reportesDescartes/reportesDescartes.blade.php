
@extends('layouts.templeate')
@section('titlePage', 'Reporte Descartes')
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


.btn-descartar {
    display: flex;
    position: relative;
    bottom :255px;
    left : 0px;
    z-index : 2;
    font-size : 0px;
/* Coloca el contenedor a la derecha */
    
     /* Ajusta el margen superior según sea necesario */
}
.error-control {
    max-width: 500px; /* Ajusta el ancho máximo según tus necesidades */
   ; /* Centra el cuadro de error horizontalmente */
}
.error-mensaje-control{
    font-size: 15px;
}

#datatable-responsive {
    margin-top: 10px; /* Ajusta el margen superior según sea necesario */
}

#datatable-responsive th,
#datatable-responsive td {
    padding: 5px; /* Ajusta el espaciado en las celdas según sea necesario */
     font-size: 14px; */
}

.custom-select-width {
    width: 300px; /* Ajusta el ancho según sea necesario */
}

/* Estilo para mostrar "Todos" en el desplegable */
.custom-select-width option {
    font-size: 14px; /* Ajusta el tamaño de la fuente según sea necesario */
}

.pagination{
display : flex ; 
position : absolute;  
left : 120px;
bottom : 250px;
z-index; 3
}

.registros{
display : flex ;
position : relative; 
margin-right : 500px;
color : red;
}



.div-paginacion{
display:flex;
position : relative ;
top : 600px;
right : 110px;

}

.boton-descartar{

font-size : 16px;



}
</style>

<br><br>

   


    <form action="{{ route('informe-descartes') }}"  method="GET">

      
        
      
        <div class="col-md-6">
            <div class="form-group custom-select-width">
                <label for="control_desde">CONTROL </label>
                <br><br>

                
                <input type="text" class="form-control" name="numero_control" id="numero_control" placeholder="Ingrese nro control">


                <br>

                <button type="submit" class="btn btn-primary btn-m">Buscar</button>
                <br>
        

            </div>
        </div>

        {{-- <input type="hidden" name="selectedItems" id="selectedItems"> --}}

      
     </form> 






<br>



<br>



<div class= "regitros">

@if(request()->has('numero_control'))
    @if(isset($controlBuscado) && $controlBuscado->count() > 0)

        <h4>Registros encontrados:</h4>
        <br>
        <table id="datatable-responsive"   style="width: 820px" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" >
            <!-- Encabezados de la tabla -->
            <thead>
                <tr>
                    <th>Nro Insumo</th>
                    <th>Descripción</th>
                    <!-- Agrega más encabezados según tu tabla -->
                </tr>
            </thead>
            <tbody>
                @foreach($controlBuscado as $registro)
                    <tr>
                        <td>{{ $registro->control }}</td>
                        <td>{{ $registro->descripcion }}</td>
                        <!-- Agrega más celdas según tu tabla -->
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-danger error-control" role="alert">
       <p class="error-mensaje-control"> No se encontraron registros para el número de control: {{ request('numero_control') }} </p>
    </div>
    @endif
@endif


</div>

<div class ="div-paginacion">

    <div class="pagination">
        {{ $descartes->links() }}
 <div class="form-group btn-descartar" >
    <label for="acciones"> </label>
    <br><br>


    <a href="{{ route('descartarInsumo') }}" class="btn btn-primary btn-m boton-descartar">Descartar</a>


</div>


    </div>

</div>

<br><br><br><br><br>


<br>
   


    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
               
            <div class="x_content">
                    <table id="datatable-responsive"      style="width: 800px" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" >
                        <thead>
                            <tr>
                                {{-- <th>Checkbox</th> --}}
                                <th style="width: 50px;"  >Nro Insumo</th>
                                <th style="width: 50px;">Descripcion</th>
                                {{-- <th>Documento</th>
                                <th>Tramite </th>
                                 --}}

                                

                            </tr>
                        </thead>
                        
                            
                            <tbody>
                                @foreach ($descartes as $descarte)
                                    <tr>
                                        {{-- <td>Checkbox</td> --}}
                                        <td>{{ $descarte->control}}</td>
                                        <td>{{ $descarte->descripcion}}</td>
                                        <!-- Agrega más celdas según tu tabla -->
                                    </tr>
                                @endforeach
                            </tbody>

                        
                    
                    </table>
                </div>
            </div>
        </div>
    </div>




    <p>
    </p>




{{-- <button> VOLVER </button> --}}

@endsection








<script>









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


