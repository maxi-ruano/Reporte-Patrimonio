<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AnsvDescartes extends Model
{
  protected $table = 'ansv_descartes';
  // protected $primaryKey = 'creation_date';
  protected $fillable = ['control', 'motivo', 'descripcion', 'creation_date', 'created_by', 'end_date'];
}
