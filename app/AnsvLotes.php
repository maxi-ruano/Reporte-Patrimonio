<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class AnsvLotes extends Model
{

  use SoftDeletes;
  protected $table = 'ansv_lotes';
  protected $primaryKey = 'lote_id';
  protected $fillable = ['lote_id', 'sucursal_id', 'control_desde', 'control_hasta', 'habilitado', 'created_by', 'creation_date', 'modified_by', 'modification_date', 'end_date', 'nro_kit'];
  public $timestamps = false;
const DELETED_AT = 'end_date';
protected $dates = ['end_date'];

public function scopeNoEliminados($query)
    {
        return $query->whereNull('end_date');
    }

    public function lotesPatrimonio()
    {
        return $this->hasMany(AnsvLotesPatrimonio::class, 'nro_control_desde', 'control_desde')
            ->where('nro_control_hasta', '=', 'control_hasta');
    }

}
