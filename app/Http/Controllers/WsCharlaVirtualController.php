<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CharlaVirtual;
use Http;

class WsCharlaVirtualController extends Controller
{
    private $url;
    private $userName;
    private $userPassword;
    private $wsEnabled;
    
    public function __construct(){
      $this->crearConstantes();
      $this->url = CharlaVirtualWS_ws_url;
      $this->wsEnabled = CharlaVirtualWS_enabled;
    }

    public function consultar($tramite)
    {
        $success = false;
        $message = '';
        $response = '';
        try {
            
	    $request 	= $this->url."/documento/".$tramite->nro_doc."/genero/".strtolower($tramite->sexo);
	    $json 	= file_get_contents($request, false);
	    $response 	= json_decode($json);

	    if($response->error->err == true){
		$message = $response->error->message;
	    }else{
		    
		$message = isset($response->mensaje)?$response->mensaje:'';    
		
		if($response->encontrado){    
		    if($response->codigo != null && $response->codigo != '' && $response->codigo != '0' ){    
			$success = true;
		    }else{
			$message = 'Código incorrecto: La charla no fue finalizada o aprobada con exito';
		    }
		}
	    }
        }catch(\Exception $e) {
            $message = $e->getMessage();
        }

        $salida = array(
            'success' => $success,
            'error' => $message,
            'request' => parse_url($request),
            'response' => $response 
        );
	return (object) $salida;
    }

    public function guardar($charla)
    {
	    $codigo = trim($charla->codigo);   
	    $existe = CharlaVirtual::where('codigo', $codigo)->count();
	    if(!$existe){
		CharlaVirtual::create([  
			'codigo' 		=> $codigo,
			'nro_doc' 		=> $charla->documento,
			'apellido'		=> $charla->apellido,
			'nombre'		=> $charla->nombre,
			'sexo' 			=> $charla->genero,
			'email'			=> $charla->email,
			'aprobado'		=> $charla->aprobado,
			'fecha_nacimiento'	=> $charla->fechaNacimiento,
			'fecha_charla'		=> $charla->fechaIngreso,
			'fecha_aprobado'	=> $charla->fechaAprobado,
			'fecha_vencimiento'	=> $charla->fechaVencimiento,
			'categoria'		=> $charla->categoria,
			'response_ws'		=> json_encode($charla)
		]);
	    }
	 return $codigo;
    }

 	public function buscarCharla(){

	return view('charla.buscarCharla');

	}



	public function buscarCharlaPost()
	{
		$nro_doc = $_POST['nro_doc'];
		$sexo = $_POST['sexo'];
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://transito.buenosaires.gob.ar/api-charlavirtual/v1/charlas/documento/'.$nro_doc.'/genero/'.$sexo.'',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'Cookie: BIGipServerPool_especiales-web=184685578.20480.0000'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		//echo $response;

		return response()->json($response);
	}

}
