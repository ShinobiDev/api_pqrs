<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

// Serve L5-Swagger generated JSON at /docs to satisfy Swagger UI requests
use Illuminate\Http\Request;
Route::get('/docs', function (Request $request) {
    $docsPath = storage_path('api-docs/api-docs.json');
    if (!file_exists($docsPath)) {
        return response('Not Found', 404);
    }
    $content = file_get_contents($docsPath);
    return response($content, 200, ['Content-Type' => 'application/json']);
})->name('l5-swagger.default.docs');
