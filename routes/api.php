<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
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

// Route::post('/login', [AuthController::class, 'login'])->name('login');


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//   return $request->user();
// });

// Route::post('/login', function (Request $request) {
//     $request->validate([
//         'email' => 'required|email',
//         'password' => 'required',
//     ]);
//     return ['message' => 'Hello, API!'];
// })->name('login');

// Route::group(['prefix' => 'user', 'as' => 'user.'], function(){
Route::resource('/users', UserController::class);
Route::post('/user/auth', [UserController::class, 'auth']);
Route::post('/user/forgot/password', [UserController::class, 'forgotPassword']);
Route::post('/user/info', [UserController::class, 'getUserInfo']);
Route::post('/user/create', [UserController::class, 'createUser']);
Route::patch('/user/update', [UserController::class, 'updateUser']);

Route::middleware(['api'])->group(function () {
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/buy/{id}', [SubscriptionController::class, 'buy'])->name('subscriptions.buy');
    Route::get('/subscriptions/success', [SubscriptionController::class, 'success'])->name('subscriptions.success');
    Route::get('/subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    //credit
    Route::post('/create-payment', [PayPalController::class, 'createPayment']);
    Route::post('/capture-payment', [PayPalController::class, 'capturePayment']);
});
