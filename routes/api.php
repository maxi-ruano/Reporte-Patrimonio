<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Permitir el acceso desde otro cliente por hhtp
Route::options('*', function () {
    $response = Response::make('');
    $response->header('Access-Control-Allow-Origin', '*');
    $response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization');
    $response->header('Access-Control-Allow-Credentials' ,'true');

});


//API para authenticar el acceso con Laravel/passport
Route::group(['prefix' => 'auth', 'middleware' => 'cors'], function () {
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');

    Route::group(['middleware' => 'auth:api'], function() {
        Route::get('logout', 'AuthController@logout');
        Route::get('user', 'AuthController@user');
    });
});

Route::group(['prefix'=>'user', 'middleware' => 'auth:api'], function() {
    Route::get('roles', 'RoleController@getRolesPermissions');
    Route::get('roles/{id}','RoleController@getRolesPermissions');
});

//API desarrolladas para consultar externas
Route::group(['prefix'=>'reportes', 'middleware'=>'cors'], function(){
    Route::get('get_errores_precheck', 'PreCheckController@get_errores_precheck')->name('get_errores_precheck');
    Route::get('get_tramites_precheck', 'PreCheckController@get_tramites_precheck')->name('get_tramites_precheck');
    Route::get('get_licencias_emitidas', 'TramitesController@get_licencias_emitidas')->name('get_licencias_emitidas');
    Route::get('get_precheck_comprobantes', 'PreCheckController@get_precheck_comprobantes')->name('get_precheck_comprobantes');
    Route::get('get_corresponde_tramite', 'TramitesController@get_corresponde_tramite')->name('get_corresponde_tramite');
});

//API desarrolladas para conexion con los web services
Route::group(['prefix'=>'ws', 'middleware'=>'cors'], function(){
    Route::get('getLibreDeuda', 'TramitesAInicarController@api_getLibreDeuda')->name('getLibreDeuda');
});


//API desarrolladas para consultar internas del sistema
Route::group(['prefix'=>'funciones', 'middleware'=>'cors'], function(){
    Route::post('obtenerSucursales', 'DashboardController@obtenerSucursales')->name('obtenerSucursales');
});

//API App Movil
Route::group(['prefix'=>'appMovil'], function(){
   Route::get('auth','AppMovilController@auth');
   Route::get('getCodigoPais','AppMovilController@getCodigoPais');
   Route::get('buscarTramite','AppMovilController@buscarTramite');
   Route::post('postExamen','AppMovilController@postExamen');
});

Route::fallback(function(){
    return response()->json(['message' => 'Not Found.'], 404);
})->name('api.fallback.404');
