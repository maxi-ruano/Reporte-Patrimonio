{{-- 

@extends('layouts.templeate')
@section('titlePage')
@endsection
@section('content')

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="container">
    <div class="row">
        <div class="col-md-6">
            <p style="font-size: 20px;">Número de Kit: {{ $nroKit }}</p>
            <p style="font-size: 20px;">Número de Control Desde: {{ $nroControlDesde }}</p>
            <p style="font-size: 20px;">Número de Control Hasta: {{ $nroControlHasta }}</p>


            <br>

            <h1>Descartes:</h1>
            <br>
            <form>
                @csrf
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Seleccionar</th>
                            <th>Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($descartes as $descarte)
                            <tr>
                                <td>
                                    <input type="hidden" name="seleccion_descartes[]" value="{{ $descarte->control }}">
                                    <i class="fas fa-check" style="color: green;"></i>
                                </td>
                                <td>
                                    {{ $descarte->control }}
                                </td>
                            </tr>
                        @endforeach

                        <!-- Mostrar registros de $desFaltante en rojo si está definido -->
                        @if (isset($desFaltante))
                            @foreach ($desFaltante as $controlFaltante)
                                <tr>
                                    <td>
                                        <!-- Puedes usar un ícono diferente si lo deseas -->
                                        <i class="fas fa-times" style="color: red;"></i>
                                    </td>
                                    <td style="color: red;">
                                        {{ $controlFaltante }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </form>
        </div>

        <div class="col-md-6" style="margin-top: 139px;"> <!-- Ajusta el valor según sea necesario -->
            <h1>Blancos:</h1>
            <br>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Seleccionar</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($blancos as $blanco)
                        <tr>
                            <td>
                                <input type="hidden" name="seleccion_blancos[]" value="{{ $blanco->control }}">
                                <i class="fas fa-check" style="color: green;"></i>
                            </td>
                            <td>
                                {{ $blanco->control }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Mostrar registros de $controlesFaltantesNoBlancos en rojo si está definido -->
                    @if (isset($controlesFaltantesNoBlancos))
                        @foreach ($controlesFaltantesNoBlancos as $controlFaltante)
                            <tr>
                                <td>
                                    <!-- Puedes usar un ícono diferente si lo deseas -->
                                    <i class="fas fa-times" style="color: red;"></i>
                                </td>
                                <td style="color: red;">
                                    {{ $controlFaltante }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<a href="{{ route('reporteLotesPatrminio') }}">Volver</a>

@endsection
 --}}


 {{-- @extends('layouts.templeate')
 @section('titlePage')
 @endsection
 @section('content')
 
 @if(session('success'))
     <div class="alert alert-success">
         {{ session('success') }}
     </div>
 @endif
 
 <div class="container">
     <div class="row">
         <div class="col-md-6">
             <p style="font-size: 20px;">Número de Kit: {{ $nroKit }}</p>
             <p style="font-size: 20px;">Número de Control Desde: {{ $nroControlDesde }}</p>
             <p style="font-size: 20px;">Número de Control Hasta: {{ $nroControlHasta }}</p>
 
 
             <br>
 
             <h1>Descartes:</h1>
             <br>
             <form>
                 @csrf
                 <table class="table table-striped">
                     <thead>
                         <tr>
                             <th>Seleccionar</th>
                             <th>Control</th>
                         </tr>
                     </thead>
                     <tbody>
                         @foreach ($descartes as $descarte)
                             <tr>
                                 <td>
                                     <input type="hidden" name="seleccion_descartes[]" value="{{ $descarte->control }}">
                                     <i class="fas fa-check" style="color: green;"></i>
                                 </td>
                                 <td>
                                     {{ $descarte->control }}
                                 </td>
                             </tr>
                         @endforeach
 
                         <!-- Mostrar registros de $desFaltante en rojo si está definido -->
                        
                     </tbody>
                 </table>
             </form>
         </div>
 
         <div class="col-md-6" style="margin-top: 139px;"> <!-- Ajusta el valor según sea necesario -->
             <h1>Blancos:</h1>
             <br>
             <table class="table table-striped">
                 <thead>
                     <tr>
                         <th>Seleccionar</th>
                         <th>Control</th>
                     </tr>
                 </thead>
                 <tbody>
                     @foreach ($blancos as $blanco)
                         <tr>
                             <td>
                                 <input type="hidden" name="seleccion_blancos[]" value="{{ $blanco}}">
                                 <i class="fas fa-check" style="color: green;"></i>
                             </td>
                             <td>
                                 {{ $blanco }}
                             </td>
                         </tr>
                     @endforeach
 
                    
                 </tbody>
             </table>
         </div>
     </div>
 </div>
 
 <a href="{{ route('reporteLotesPatrminio') }}">Volver</a>
 
 @endsection
  --}}
  @extends('layouts.templeate')
  @section('titlePage')
  @endsection
  @section('content')
  
  @if(session('success'))
      <div class="alert alert-success">
          {{ session('success') }}
      </div>
  @endif
  
  <div class="container">
      <div class="row">
          <div class="col-md-6">
              <p style="font-size: 20px;">Número de Kit: {{ $nroKit }}</p>
              <p style="font-size: 20px;">Número de Control Desde: {{ $nroControlDesde }}</p>
              <p style="font-size: 20px;">Número de Control Hasta: {{ $nroControlHasta }}</p>
  
              <br>
  
              <h1>Descartes:</h1>
              <br>
              {{-- <form method="POST" action="{{ route('ruta.a.tu.controlador') }}"> --}}
                {{-- <form method="POST" > --}}
                    {{-- <form method="POST" action="{{ route('accionesDescartesBlancos') }}">
                  @csrf
                  <input type="hidden" name="nro_kit" value="{{ $nroKit }}">
                  <table class="table table-striped">
                      <thead>
                          <tr>
                              <th>Seleccionar</th>
                              <th>Control</th>
                          </tr>
                      </thead>
                      <tbody>
                          @foreach ($descartes as $descarte)
                              <tr>
                                  <td>
                                      <input type="checkbox" name="seleccion_descartes[]" value="{{ $descarte }}">
                                  </td>
                                  <td>
                                      {{ $descarte}}
                                  </td>
                              </tr>
                          @endforeach
                      </tbody>
                  </table>
          </div>
  
          <div class="col-md-6" style="margin-top: 139px;"> <!-- Ajusta el valor según sea necesario -->
              <h1>Blancos:</h1>
              <br>
                  <table class="table table-striped">
                      <thead>
                          <tr>
                              <th>Seleccionar</th>
                              <th>Control</th>
                          </tr>
                      </thead>
                      <tbody>
                          @foreach ($blancos as $blanco)
                              <tr>
                                  <td>
                                      <input type="checkbox" name="seleccion_blancos[]" value="{{ $blanco }}">
                                  </td>
                                  <td>
                                      {{ $blanco }}
                                  </td>
                              </tr>
                          @endforeach
                      </tbody>
                  </table>
                  <button type="submit" class="btn btn-primary">Enviar Selección</button>
              </form> --}}
              <form method="POST" action="{{ route('accionesDescartesBlancos') }}">
                @csrf
                <input type="hidden" name="nro_kit" value="{{ $nroKit }}">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Seleccionar</th>
                            <th>Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($descartes as $descarte)
                            <tr>
                                <td>
                                    <input type="checkbox" name="seleccion_descartes[]" value="{{ $descarte }}"
                                        {{ in_array($descarte, $seleccionadosDescartes) ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    {{ $descarte }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
        </div>

        <div class="col-md-6" style="margin-top: 139px;">
            <h1>Blancos:</h1>
            <br>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Seleccionar</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($blancos as $blanco)
                        <tr>
                            <td>
                                <input type="checkbox" name="seleccion_blancos[]" value="{{ $blanco }}"
                                    {{ in_array($blanco, $seleccionadosBlancos) ? 'disabled' : '' }}>
                            </td>
                            <td>
                                {{ $blanco }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Enviar Selección</button>
            </form>
          </div>
      </div>
  </div>
  
  <a href="{{ route('reporteLotesPatrminio') }}">Volver</a>
  
  @endsection
  