{{-- 



 
 @extends('layouts.templeate')
 @section('titlePage', )
 @section('content')
 
 


 @if(session('success'))
 <div class="alert alert-success">
     {{ session('success') }}
 </div>
@endif




 
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">

            

                @if (session('descartesBlancosPatrimonio'))
                <p >Número de Kit: {{ session('nro_kit') }}</p>

    <form action="{{ route('accionesDescartesBlancos') }}"  method="POST">


        @csrf
        <input type="hidden" name="nro_kit" value="{{ session('nro_kit') }}">

        <table class="table">
            <h1>Descartes </h1>
            <thead class="thead-dark">
                <tr>
                    <th>Seleccionar</th>
                    <th>Control Descartado</th>
                    <th>Motivo Descartado</th>
                    <th>Descripcion Descartado</th>
                </tr>
            </thead>
            <tbody>
               

                @foreach (session('descartesBlancosPatrimonio') as $descarte)
                    <tr>

                            <td>
                                <input type="checkbox" name="seleccion_descartes[]"
                                       value="{{ $descarte['controlDescartado'] }}"
                                       {{ in_array($descarte['controlDescartado'], session('controlesGuardados', [])) ? 'disabled' : '' }}>
                            </td>
                        <td>{{ $descarte['controlDescartado'] }}</td>
                        <td>{{ $descarte['motivoDescartado'] }}</td>
                        <td>{{ $descarte['descripcionDescartado'] }}</td>
                        <td>
                            @if (in_array($descarte['controlDescartado'], session('controlesGuardados', [])))
                            <span style="color: red;">Enviado a Nación</span>
                            @endif
                        </td>

                    </tr>
                @endforeach


            </tbody>
        </table>
        <br><br>
        <table class="table">
            <h1> Blancos </h1>

            <thead class="thead-dark">
                <tr>
                    <th>Seleccionar</th>
                    <th>Control </th>
                   
                </tr>
            </thead>
            <tbody>
                @foreach (session('patrimonioBlancos') as $blanco)
                    <tr>
                        <td>
                            <input type="checkbox" name="seleccion_blancos[]" value="{{ $blanco }}">
                        </td>
                        <td>
                            <input type="checkbox" name="seleccion_blancos[]"
                                   value="{{ $blanco }}"
                                   {{ in_array($blanco, session('controlesGuardados', [])) ? 'disabled' : '' }}>
                        </td>
                        <td>{{ $blanco }}</td>
                        <td>
                            @if (in_array($blanco, session('controlesGuardados', [])))
                                <span style="color: red;">Enviado a Nación</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <button type="submit" class="btn btn-primary">Enviar Seleccionados</button>
    </form>
@endif

        </div>
    </div>
</div>
 
 @endsection
 
 
 <script>
  
 </script>
  --}}

  @extends('layouts.templeate')
@section('titlePage', )
@section('content')

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">

            {{-- @if (session('descartesBlancosPatrimonio')) --}}
            <p >Número de Kit: {{ session('nro_kit') }}</p>

            <form action="{{ route('accionesDescartesBlancos') }}"  method="POST">
                @csrf
                <input type="hidden" name="nro_kit" value="{{ session('nro_kit') }}">

                <table class="table">
                    <h1>Descartes </h1>
                    <thead class="thead-dark">
                        <tr>
                            <th>Seleccionar</th>
                            <th>Control Descartado</th>
                            <th>Motivo Descartado</th>
                            <th>Descripcion Descartado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(session('descartesBlancosPatrimonio') && is_array(session('descartesBlancosPatrimonio')))
                            @foreach (session('descartesBlancosPatrimonio') as $descarte)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="seleccion_descartes[]"
                                               value="{{ $descarte['controlDescartado'] }}"
                                               {{ in_array($descarte['controlDescartado'], session('controlesGuardados', [])) ? 'disabled' : '' }}>
                                    </td>
                                    <td>{{ $descarte['controlDescartado'] }}</td>
                                    <td>{{ $descarte['motivoDescartado'] }}</td>
                                    <td>{{ $descarte['descripcionDescartado'] }}</td>
                                    <td>
                                        @if (in_array($descarte['controlDescartado'], session('controlesGuardados', [])))
                                            <span style="color: red;">Enviado a Nación</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </table>
            <br><br>
            <table class="table">
                <h1> Blancos </h1>
    
                <thead class="thead-dark">
                    <tr>
                        <th>Seleccionar</th>
                        <th>Control </th>
                       
                    </tr>
                </thead>
                <tbody>
                    @if(session('blancos') && is_array(session('blancos')))

                    @foreach (session('blancos') as $blanco)
                        <tr>
                            
                            <td>
                                <input type="checkbox" name="seleccion_blancos[]"
                                       value="{{ $blanco }}"
                                       {{ in_array($blanco, session('controlesGuardados', [])) ? 'disabled' : '' }}>
                            </td>
                            <td>{{ $blanco }}</td>
                            <td>
                                @if (in_array($blanco, session('controlesGuardados', [])))
                                    <span style="color: red;">Enviado a Nación</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @endif

                </tbody>
            </table>

                <button type="submit" class="btn btn-primary">Enviar Seleccionados</button>
            </form>
            {{-- @endif --}}

        </div>
    </div>
</div>

@endsection

<script>
    // Tu script aquí
</script>
