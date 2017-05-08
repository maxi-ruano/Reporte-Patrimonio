<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\EtlExamenPregunta;

use App\EtlPreguntaRespuesta;

class EtlExamenPreguntaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $preguntas = EtlExamenPregunta::all();
        //$preguntas = 'hola este es el index';
        dd($preguntas);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getPreguntasExamen(Request $request)
    {
      $preguntas = EtlExamenPregunta::where('examen_id', $request->input('dni'))->get();

      foreach ($preguntas as $key => $value) {
        $value['respuestas'] = EtlPreguntaRespuesta::where('pregunta_id', $value->pregunta_id)->get();
      }

      return View('examen.pregunta')->with('preguntas', $preguntas);
    }


}
