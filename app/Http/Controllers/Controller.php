<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;
use App\SysMultivalue;
use App\SysConfig;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    //SIGECI Cursos
    //public $prestacionesCursos = [1604, 1543];
    public $prestacionesCursos = [2744,2764,2864,3045,3046,3064,3065,3066,3085,3086,3206,3225,3226,3585,3605,3707,3765,3766,3846,3847,3685,3686,4705,4706,4707,4708,4709,4710,4712,4713,4714,4715,4726,4727,4728,4729,4806,4808,4809,4814,4817,4823,4824,4865,4886,4887,4888,4894,4895,4905,4906,4907,5065];

    public function __construct(){
       $this->crearConstantes();
    }

    //Funcion Global para Exportar un archivo en cualquier formato
    function exportFile($data, $type, $namefile = 'exportfile', $nameSheet = 'hoja' ){
        /** 
         * Creamos nuestro archivo
         * expected xls, xlsx, xml, csv, txt, html, pdf, htm, xlsm, xltx, xltm,  xlt, ods, ots, slk, gnumeric.
         * */

        Excel::create($namefile, function ($excel) use ($data, $nameSheet) {
            /** Creamos una hoja */
            $excel->sheet($nameSheet, function ($sheet) use ($data) {
                /**
                 * Insertamos los datos en la hoja con el método with/fromArray
                 * Parametros: (Datos, encabezado de la columna, Celda de Inicio, Comparación estricta de los valores del encabezado, Impresión de los encabezados)
                 * */
                //$sheet->fromArray($data, null, 'A1', false, false);
                $sheet->fromArray($data);
            });
        
        /** Descargamos nuestro archivo pasandole la extensión deseada (xls, xlsx) */
        })->export($type);
    }

    public function crearConstantes(){
        $constantes = SysMultivalue::where('type', 'AUTO')
                                    ->orWhere('type', 'VALP')
                                    ->orWhere('type', 'CONS')
                                    ->get();
        foreach($constantes as $value){
            if(!defined($value->text_id))
                define($value->text_id,$value->id);
        }

        $constantes_sys_config = SysConfig::all();
        foreach($constantes_sys_config as $value){
            $const = $value->name.'_'.$value->param;
            if(!defined($const))
                define($const,$value->value);
        }

    }

    public function file_contents_exist($url)
    {
	if($url == NULL) return false;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); 
        if($httpcode>=200 && $httpcode<300){
            return true;
        } else {
            return false;
        }
    }
}
