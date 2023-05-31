<?php

use App\Http\Controllers\PartieController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('parties')
    ->controller(PartieController::class)
    ->middleware(['auth:sanctum', 'throttle:120,1'])
    ->group(function () {
        Route::post('/', 'nouvellePartie');
        Route::post('/{partie}/missiles', 'tirerMissile');
        Route::put('/{partie}/missiles/{coordonnee}', 'updateMissile');
        Route::delete('/{partie}', 'finPartie');
    });
