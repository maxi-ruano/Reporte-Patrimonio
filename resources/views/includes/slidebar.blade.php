<!-- sidebar menu -->
<div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
  <div class="menu_section">
    <h3>Menú</h3>
    <ul class="nav side-menu">
      @if(Auth::check())
        @if(Auth::user()->can('view_all_tramites_habilitados') || Auth::user()->can('view_self_tramites_habilitados') || Auth::user()->can('view_sede_tramites_habilitados'))
          <li><a href="{{ route('tramitesHabilitados.index') }}?fecha={{date('Y-m-d')}}">
            <i class="fa fa-street-view"></i> Tramites Habilitados
            <span class="fa fa-chevron-right"></span></a>
          </li>
        @else
          @can('add_tramites_habilitados')
            <li><a href="{{ route('tramitesHabilitados.create') }}">
              <i class="fa fa-street-view"></i> Tramites Habilitados
              <span class="fa fa-chevron-right"></span></a>
            </li>
          @endcan
        @endif

        @can('view_dashboard')
        <li><a href="{{ route('consultaDashboard') }}">
          <i class="fa fa-pie-chart"></i> Estadisticas
          <span class="fa fa-chevron-right"></span></a>
        </li>
        @endcan

        @can('view_users')
        <li><a href="{{ route('users.index') }}">
            <i class="fa fa-users"></i> Usuarios
            <span class="fa fa-chevron-right"></span></a>
        </li>
        @endcan

      @endif

      @if(session('usuario_rol_id') == '40' || session('usuario_rol') == 'ROL_ESCUELA' || session('usuario_rol') == 'ROL_ADMIN'
	|| session('usuario_id') == '2722' || session('usuario_id') == '2790' || session('usuario_id') == '2432')
      <li><a href="{{ route('bedel.index') }}">
        <i class="fa fa-users"></i> Bedel
        <span class="fa fa-chevron-down"></span></a>
      </li>
      @endif
      @if( session('usuario_rol') == 'ROL_ADMIN' || session('usuario_rol') == 'ROL_DISPOSICIONES')
      <li>
        <a href="{{ route('disposiciones.index') }}">
          <i class="fa fa-file"></i> Disposiciones
          <span class="fa fa-chevron-down"></span>
        </a>
      </li>
      @endif
      @if( session('usuario_rol') == 'ROL_ADMIN' || session('usuario_rol') == 'ROL_REPORTES_CONTROL_INSUMOS')
      <li>
        <a href="{{ route('reporteSecuenciaInsumos') }}">
          <i class="fa fa-file"></i> Reporte Secuencia Insumos
          <span class="fa fa-chevron-down"></span>
        </a>
      </li>
      @endif
      @if( session('usuario_rol') == 'ROL_ADMIN' || session('usuario_rol') == 'ROL_REPORTES_CONTROL_INSUMOS')
      <li>
        <a href="{{ route('reporteControlInsumos') }}">
          <i class="fa fa-file"></i> Reporte Control de Insumos
          <span class="fa fa-chevron-down"></span>
        </a>
      </li>
      @endif
    </ul>
  </div>

  </div>
<!-- /menu footer buttons -->
<div class="sidebar-footer hidden-small">
  <a data-toggle="tooltip" data-placement="top" title="Settings">
    <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
  </a>
  <a data-toggle="tooltip" data-placement="top" title="FullScreen">
    <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
  </a>
  <a data-toggle="tooltip" data-placement="top" title="Lock">
    <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
  </a>
  <a data-toggle="tooltip" data-placement="top" title="Logout">
    <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
  </a>
</div>
<!-- /menu footer buttons -->