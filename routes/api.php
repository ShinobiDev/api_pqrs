<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\PqrsController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\TypeController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AnswerController;
use App\Http\Controllers\API\StatusController;
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

// STATUS
Route::get('statuses', [StatusController::class, 'index']);         
Route::post('statuses', [StatusController::class, 'store']);        
Route::put('statuses/{status}', [StatusController::class, 'update']); 
Route::delete('statuses/{status}', [StatusController::class, 'destroy'])->withTrashed();

Route::post('statuses/{id}/restore', [StatusController::class, 'restore']); // Para restaurar un status (método POST para una acción)
Route::delete('statuses/{id}/force-delete', [StatusController::class, 'forceDelete']); // Para eliminar permanentemente (DELETE con segmento específico)

// ROLE
Route::get('roles', [RoleController::class, 'index']);
Route::post('roles', [RoleController::class, 'store']);
Route::put('roles/{role}', [RoleController::class, 'update']);
Route::delete('roles/{role}', [RoleController::class, 'destroy'])->withTrashed();

// TYPE
Route::get('types', [TypeController::class, 'index']);
Route::post('types', [TypeController::class, 'store']);
Route::put('types/{type}', [TypeController::class, 'update']);
Route::delete('types/{type}', [TypeController::class, 'destroy'])->withTrashed();

// USER
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{user}', [UserController::class, 'update']);
Route::delete('/users/{user}', [UserController::class, 'destroy'])->withTrashed();

// PQRS
Route::get('/pqrs', [PqrsController::class, 'index']);
Route::post('/pqrs', [PqrsController::class, 'store']);
Route::put('/pqrs/{pqrs}', [PqrsController::class, 'update']);
Route::delete('/pqrs/{pqrs}', [PqrsController::class, 'destroy'])->withTrashed();

// ANSWER
Route::get('/answers', [AnswerController::class, 'index']);
Route::post('/answers', [AnswerController::class, 'store']);
Route::put('/answers/{answer}', [AnswerController::class, 'update']);
Route::delete('/answers/{answer}', [AnswerController::class, 'destroy'])->withTrashed();

//STATE
Route::get('/states', [StateController::class, 'index']);
Route::post('/states', [StateController::class, 'store']);
Route::put('/states/{state}', [StateController::class, 'update']);
Route::delete('/states/{state}', [StateController::class, 'destroy']);

//CITY
Route::get('/cities', [CityController::class, 'index']);
Route::post('/cities', [CityController::class, 'store']);
Route::put('/cities/{city}', [CityController::class, 'update']);
Route::delete('/cities/{city}', [CityController::class, 'destroy']);

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
