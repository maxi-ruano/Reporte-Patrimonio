
@extends('layouts.templeate')
@section('titlePage', 'Descartes y Blancos Patrimonio')
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
            {{-- <label for="acciones">CARGAR LOTE :  </label> <br><br> --}}



        


                <a href="{{ route('cargarLote') }}"  class="btn btn-primary acciones-btn">  cargar</a>


             


            

           


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
                                <th>Lote_id_Patrimonio </th>
                                <th>Descripcion </th>
                                <th>Cantidad</th>
                               <th>Creation_by </th> 

                                {{-- <th>Control_desde</th>
                                <th>Control_hasta</th>
                                <th>Fecha_recibido_nacion</th>
                                <th>Fecha_habilitado_sede</th>
                                <th>Fecha_recibido_sede</th>
                               <th> Modification_date </th>
                               <th> Nro_Kit</th>

                              --}}

                                

                            </tr>
                        </thead>
                        <tbody>
                            <tbody>
                            
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

