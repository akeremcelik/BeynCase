<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1'], function(){
    Route::post('/register', 'App\Http\Controllers\Api\AuthController@registerUser');
    Route::post('/login', 'App\Http\Controllers\Api\AuthController@loginUser');

    Route::middleware('auth.api')->group(function() {
        Route::post('/add-balance', 'App\Http\Controllers\Api\CustomerController@addBalance');
        Route::get('/get-services', 'App\Http\Controllers\Api\CustomerController@getServices');
        Route::post('/place-order', 'App\Http\Controllers\Api\CustomerController@placeOrder');
    });
});
