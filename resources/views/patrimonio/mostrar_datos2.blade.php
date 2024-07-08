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