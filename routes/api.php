<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Mail\SendMail;
use Tymon\JWTAuth\Payload;

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
    Route::put('/{id}', [EventController::class, 'updateEvent']);
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
//====================================================================================================================>
// NO AUTH FOR LANDING PAGE

// PaymentSections
Route::post('pay', [PaymentController::class, 'reqTokenBayar']);
Route::post('pay/callback', [PaymentController::class, 'handleAfterPayment']);
//
Route::get('event/{slug}', [EventController::class, 'getEventBySlug']);
Route::get('ticket/own', [TicketController::class, 'getTicketsByEventId']);

Route::get('sss/{cust_id}',[PaymentController::class,'sendTicketToCustomer']);

Route::get('/send-eticket', function () {
    $filePath = 'exported-ticket/EVE-0002/ticket_EVE-0002.pdf';

    // dd($filePath);
    $fileName = 'ticket_EVE-0002.pdf';

    Mail::to('indrahardikap@gmail.com')->send(new SendMail('indra',$filePath,$fileName));

    return 'Email dengan e-ticket berhasil dikirim!';
});

//=====================================================================================================================>
// Scanner Api For VAlidation ticket 