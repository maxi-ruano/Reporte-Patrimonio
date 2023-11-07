<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AnsvControl extends Model
{
  protected $table = 'ansv_control';
  protected $primaryKey = 'tramite_id';
  protected $fillable = ['tramite_id', 'nro_control', 'created_by', 'creation_date', 'liberado'];

  public $timestamps = false;



public function tramite()
{
    return $this->belongsTo(Tramites::class, 'tramite_id');
}


  




}
