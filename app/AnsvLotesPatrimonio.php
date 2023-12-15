<?php

// app/AnsvLotesPatrimonio.php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnsvLotesPatrimonio extends Model
{
    // use SoftDeletes;

    protected $table = 'ansv_lotes_patrimonio';
    public $timestamps = false;

    protected $primaryKey = 'id';
    protected $fillable = ['id', 'nro_control_desde', 'nro_control_hasta', 'fecha_recibido_nacion', 'fecha_habilitado_sede', 'fecha_recibido_sede', 'creation_by', 'modification_date','nro_kit'];
    
    // const DELETED_AT = 'end_date';
    // protected $dates = ['end_date'];




    public function lote()
    {
        return $this->belongsTo(AnsvLotes::class, 'nro_control_desde', 'control_desde')
            ->where('nro_control_hasta', '=', 'control_hasta');
    }
    
}

