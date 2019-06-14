<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// Cargando clases
use App\Http\Middleware\ApiAuthMiddleware;

/*
// RUTAS DE PRUEBA
Route::get('/', function () {
    return view('welcome');
});
Route::get('/pruebas/{nombre?}',function($nombre = null){ 
    $texto  ='<h2> Texto desde una ruta <br><br>';
    $texto .= 'Nombre: '.$nombre.'</h2>';
    return view('pruebas',array('texto' => $texto));
});
Route::get('/animales', 'PruebasController@index');
Route::get('/testOrm', 'PruebasController@testOrm');
Route::get('/usuario/prueba','UserController@pruebas');
Route::get('/categoria/prueba','CategoryController@pruebas');
Route::get('/entrada/prueba','PostController@pruebas');
***/

//RUTAS DEL API
/*
 * Metodos HTTP comunes
 * GET: Conseguir datos o recursos
 * POST: Manda datos, procesa y puede devolver algo
 * PUT:  Actualiza datos o recursos
 * DELETE: Borra datos o recursos
 */
//Rutas del controlador de usuario
Route::post('/api/register','UserController@register');
Route::post('/api/login','UserController@login');
Route::put('/api/user/update','UserController@update');
Route::post('/api/user/upload','UserController@upload')->middleware(App\Http\Middleware\ApiAuthMiddleware::class);
Route::get('/api/user/avatar/{filename}','UserController@getImage');
Route::get('/api/user/detail/{id}','UserController@detail');

//Rutas del controlador de categorias
Route::resource('/api/category','CategoryController');

//Rutas del controlador de entradas
Route::resource('/api/post','PostController');
Route::post('/api/post/upload','PostController@upload');
Route::get('/api/post/image/{filename}','PostController@getImage');
Route::get('/api/post/category/{id}','PostController@getPostsByCategory');
Route::get('/api/post/user/{id}','PostController@getPostsByUser');

Route::delete('/api/post/image/{filename}','PostController@deleteImage');
