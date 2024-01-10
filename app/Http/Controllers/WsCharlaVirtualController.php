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
    private $wsKey;
    
    public function __construct(){
      $this->crearConstantes();
      $this->url = CharlaVirtualWS_ws_url;
      $this->wsEnabled = CharlaVirtualWS_enabled;
      $this->wsKey = CharlaVirtualWS_ws_key;
    }

    public function consultar($tramite)
    {
        $success = false;
        $message = '';
        $response = '';
        try {
            
	    $request 	= $this->url.$tramite->nro_doc."/".strtolower($tramite->sexo);
	    $options = array(
		'http' =>  array(
	        	'header'=> 'api_key: ' . $this->wsKey,
	    	)
	    );
		
	    $json 	= file_get_contents($request, false, stream_context_create($options));
	    $response 	= json_decode($json);

	    if(isset($response->error)){
		$message = $response->message;
	    }else{
		// $message = isset($response->mensaje)?$response->mensaje:'';    
		
		//if($response->encontrado){    
		    if( isset($response->codigo) ){    
			$success = true;
		    }else{
			$message = 'Código incorrecto: La charla no fue finalizada o aprobada con exito';
		    }
		//}
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
			'nro_doc' 		=> $charla->dni,
			'apellido'		=> $charla->apellido,
			'nombre'		=> $charla->nombre,
			'sexo' 			=> $charla->genero,
			'email'			=> ' ',
			'aprobado'		=> true,
			'fecha_nacimiento'	=> $charla->fecha_nacimiento,
			'fecha_charla'		=> $charla->inicio_el,
			'fecha_aprobado'	=> $charla->finalizo_el,
			'fecha_vencimiento'	=> $charla->vencimiento,
			'categoria'		=> $charla->categoria,
			'response_ws'		=> json_encode($charla)
		]);
	    }
	 return $codigo;
    }

 	public function buscarCharla(){

	return view('charla.buscarCharla');

	}



	public function buscarCharlaPost(Request $request)
	{
		$nro_doc = $request->nro_doc;
		$sexo = $request->sexo;
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $this->url.$nro_doc."/".strtolower($sexo),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'api_key: ' . $this->wsKey
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		//echo $response;

		return response()->json($response);
	}

}
