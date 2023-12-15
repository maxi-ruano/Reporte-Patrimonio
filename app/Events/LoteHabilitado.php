<?php

namespace App\Events;

use App\AnsvLotes;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoteHabilitado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lote;

    public function __construct(AnsvLotes $lote)
    {
        $this->lote = $lote;
    }
}
