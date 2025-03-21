<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

Route::prefix('events')->middleware('jwt.verify')->group(function () {
    Route::get('/', [EventController::class, 'getEvents']);
    Route::post('/', [EventController::class, 'createEvent']);
    Route::get('/own', [EventController::class, 'getEventsByEO']);
    Route::put('/{id}',[EventController::class, 'updateEvent']);
    Route::delete('/{id}',[EventController::class, 'deleteEvent']);
    Route::get('/{id}',[EventController::class, 'getEventById']);
});
