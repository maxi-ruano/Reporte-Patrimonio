<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LibreDeudaHdr extends Model
{
  protected $table = 'libredeuda_hdr';
  protected $primaryKey = 'libredeuda_hdr_id';
  public $timestamps = false;
  protected $fillable = ['libredeuda_hdr_id',
                         'nro_doc',
                         'tipo_doc',
                         'sexo',
                         'pais',
                         'nombre',
                         'apellido',
                         'tipo_doc_text',
                         'calle',
                         'numero',
                         'piso',
                         'depto',
                         'telefono',
                         'localidad',
                         'provincia',
                         'provincia_text',
                         'codigo_postal',
                         'saldopuntos',
                         'cantidadvecesllegoa0',
			 'inhabilitado',
                       ];
}
