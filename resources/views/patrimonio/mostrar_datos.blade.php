{{-- 

  @extends('layouts.templeate')
@section('titlePage', )
@section('content')

<div class="container">
    <p>Número de Kit: {{ $nroKit }}</p>
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

                @foreach ($desFaltante as $controlFaltante)
                    <tr>
                        <td>
                            <i class="fas fa-times" style="color: red;"></i>
                        </td>
                        <td style="color: red;">
                            {{ $controlFaltante }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <br>

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
                @foreach ($controlesFaltantesNoBlancos as $controlFaltante)
                    <tr>
                        <td>
                            <i class="fas fa-times" style="color: red;"></i>
                        </td>
                        <td style="color: red;">
                            {{ $controlFaltante}}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </form>
</div>

@endsection --}}

@extends('layouts.templeate')
@section('titlePage', )
@section('content')

<div class="container">
    <p>Número de Kit: {{ $nroKit }}</p>
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

        <br>

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
                                {{ $controlFaltante}}
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

    </form>
</div>

@endsection
