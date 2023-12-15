<?php

namespace App\Listeners;

use App\Events\LoteHabilitado;
use Illuminate\Support\Facades\DB;

use App\Models\AnsvPatrimonio;
use Illuminate\Contracts\Queue\ShouldQueue;

class ActualizarFechaPatrimonio implements ShouldQueue
{
    public function handle(LoteHabilitado $event)
    {
        // Obtener el lote desde el evento
        $lote = $event->lote;
        info('Listener ActualizarFechaPatrimonio ejecutado para lote_id: ' . $lote->lote_id);
        // Actualizar la fecha en ansv_patrimonio
        DB::table('ansv_lotes_patrimonio')
        ->where('lote_id', $lote->lote_id)
        ->update(['fecha_habilitado_sede' => now()]);
    }
}
