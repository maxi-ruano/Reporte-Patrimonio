<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AnsvLotes extends Model
{
  protected $table = 'ansv_lotes';
  protected $primaryKey = 'lote_id';
  protected $fillable = ['lote_id', 'sucursal_id', 'control_desde', 'control_hasta', 'habilitado', 'created_by', 'creation_date', 'modified_by', 'modification_date', 'end_date'];




public function scopeNoEliminados($query)
    {
        return $query->whereNull('end_date');
    }


}
