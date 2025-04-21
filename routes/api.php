<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TicketController;
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

/*
/======================================================
/  SEIYA SEKATA BERSAMA SAMA SEIYA SEKATA HADAPI DUNIA!
/======================================================
*/


Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

Route::prefix('events')->middleware(['jwt.verify', 'api'])->group(function () {
    Route::get('/', [EventController::class, 'getEvents']);
    Route::post('/', [EventController::class, 'createEvent']);
    Route::get('/own', [EventController::class, 'getEventsByEO']);
    Route::put('/{id}', [EventController::class, 'editEvent']);
    Route::delete('/{id}', [EventController::class, 'deleteEvent']);
    Route::get('/{id}', [EventController::class, 'getEventById']);
});

Route::prefix('ticket-categories')->middleware(['jwt.verify', 'api'])->group(function () {
    Route::get('/', [TicketController::class, 'getTicketCategories']);
    Route::post('/', [TicketController::class, 'addTicketCategories']);
    Route::put('/{id}', [TicketController::class, 'editTicketCategory']);
    Route::delete('/{id}', [TicketController::class, 'deleteTicketCategory']);
    Route::get('/{ticket_category_id}', [TicketController::class, 'getTicketCategoryById']);
});


Route::prefix('tickets')->middleware(['jwt.verify', 'api'])->group(function () {
    Route::get('/', [TicketController::class, 'getTicketsByEventId']);
    Route::post('/', [TicketController::class, 'createTicket']);
    Route::put('/{id}', [TicketController::class, 'updateTicket']);
    Route::delete('/{id}', [TicketController::class, 'deleteTicket']);
});
Route::prefix('users')->middleware(['jwt.verify', 'api'])->group(function () {
    Route::get('/', [AuthController::class, 'getUsers']);
    Route::get('/roles', [AuthController::class, 'getRoles']);
    Route::post('/', [AuthController::class, 'register']);
    Route::put('/{id}', [AuthController::class, 'updateUser']);
    Route::delete('/{id}', [AuthController::class, 'deleteUser']);
});
// Admin DashBoard
Route::prefix('dasdata')->middleware(['jwt.verify', 'api'])->group(function () {
    Route::get('/', [DashboardController::class, 'getStats']);
});

//====================================================================================================================>
// NO AUTH FOR BOXTIX HOME->DetailTicket->Payment

// PaymentSections
Route::post('pay', [PaymentController::class, 'reqTokenBayar']);
Route::post('pay/callback', [PaymentController::class, 'handleAfterPayment']);
//KDOAKDokok
Route::get('event/{slug}', [EventController::class, 'getEventBySlug']);
Route::get('ticket/own', [TicketController::class, 'getTicketsByEventId']);
//ASAADEKOON
Route::get('sss/{cust_id}', [PaymentController::class, 'sendTicketToCustomer']);
Route::get('newest/event', [HomeController::class, 'getLatestEvent']);
//=====================================================================================================================>
// Scanner Api For VAlidation ticket
