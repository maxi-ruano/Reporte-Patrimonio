<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\TramitesAIniciar;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //Turnos vencidos
        $schedule->call('App\Http\Controllers\MicroservicioController@revisarTurnosVencidos')->weekdays()->at('18:30');
        $schedule->call('App\Http\Controllers\MicroservicioController@revisarTurnosVencidos')->saturdays()->at('08:30');
        //Validaciones Completas
        //$schedule->call('App\Http\Controllers\MicroservicioController@revisarValidaciones')->weekdays()->at('16:55');

        //PRECHECK SIGECI
        $schedule->call('App\Http\Controllers\MicroservicioController@completarTurnosEnTramitesAIniciar')->weekdays()->at('11:26'); //Turnos
        $schedule->call('App\Http\Controllers\MicroservicioController@completarTurnosEnTramitesAIniciar')->saturdays()->at('08:40'); //Turnos

        $schedule->call('App\Http\Controllers\MicroservicioController@verificarLibreDeudaDeTramites')->weekdays()->at('11:30'); //Libre Deuda
        $schedule->call('App\Http\Controllers\MicroservicioController@verificarLibreDeudaDeTramites')->saturdays()->at('09:15'); //Libre Deuda

        $schedule->call('App\Http\Controllers\MicroservicioController@verificarBuiTramites')->weekdays()->at('11:31'); //Bui
        $schedule->call('App\Http\Controllers\MicroservicioController@verificarBuiTramites')->saturdays()->at('09:20'); //Bui

        $schedule->call('App\Http\Controllers\MicroservicioController@completarBoletasEnTramitesAIniciar')->weekdays()->at('11:13'); //Buscar Boleta Cenat
        $schedule->call('App\Http\Controllers\MicroservicioController@completarBoletasEnTramitesAIniciar')->saturdays()->at('09:10'); //Buscar Boleta Cenat

        $schedule->call('App\Http\Controllers\MicroservicioController@emitirBoletasVirtualPago')->weekdays()->at('11:20'); //Obtener Certificado Virtual Cenat
        $schedule->call('App\Http\Controllers\MicroservicioController@emitirBoletasVirtualPago')->saturdays()->at('09:30'); //Obtener Certificado Virtual Cenat

	$schedule->call('App\Http\Controllers\MicroservicioController@verificarCharlaTramites')->weekdays()->at('11:32'); //Charla
        $schedule->call('App\Http\Controllers\MicroservicioController@verificarCharlaTramites')->saturdays()->at('12:00'); //Charla

	//Revisar validaciones completas e iniciar en Sinalic
	$schedule->call('App\Http\Controllers\MicroservicioController@revisarValidaciones')->weekdays()->at('20:00'); //Validaciones Completas
	$schedule->call('App\Http\Controllers\MicroservicioController@revisarValidaciones')->saturdays()->at('10:00'); //Validaciones Completas

        $schedule->call('App\Http\Controllers\MicroservicioController@enviarTramitesASinalic')->weekdays()->at('20:10'); //Turnos a Enviar a Sinalic
        $schedule->call('App\Http\Controllers\MicroservicioController@enviarTramitesASinalic')->saturdays()->at('10:10'); //Turnos a Enviar a Sinalic

        //PRECHECK STD
       $schedule->call('App\Http\Controllers\MicroservicioController@tramitesReimpresionStd')->weekdays()->at('07:51'); //Integración STD reimpresiones
       $schedule->call('App\Http\Controllers\MicroservicioController@tramitesReimpresionStd')->weekdays()->at('15:30'); //Integración STD reimpresiones
       $schedule->call('App\Http\Controllers\MicroservicioController@tramitesReimpresionStd')->saturdays()->at('11:30'); //Integración STD reimpresiones

       $schedule->call('App\Http\Controllers\MicroservicioController@tramitesDuplicadosStd')->weekdays()->at('09:11'); //Integración STD duplicados

       //$schedule->call('App\Http\Controllers\MicroservicioController@enviarTramitesASinalic')->saturdays()->at('10:49'); //Turnos a Enviar a Sinalic
        //FIN PRECHECK

	//Reimpresiones -> licencia emitida
        //$schedule->call('App\Http\Controllers\MicroservicioController@reimpresionesLicenciaEmitida')->weekdays()->at('06:30');
        $schedule->call('App\Http\Controllers\MicroservicioController@reimpresionesLicenciaEmitida')->weekdays()->at('20:00');
        $schedule->call('App\Http\Controllers\MicroservicioController@reimpresionesLicenciaEmitida')->saturdays()->at('13:30');
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
