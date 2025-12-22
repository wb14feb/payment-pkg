<?php

use AnyTech\Jinah\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Payment routes
Route::group(['prefix' => 'jinah/payment', 'as' => 'jinah.payment.', 'middleware' => 'web'], function () {
    // Display payment page
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    
    // Process payment
    Route::post('/process/{transactionId}', [PaymentController::class, 'process'])->name('process');
    
    // Payment result pages
    Route::get('/success/{transactionId}', [PaymentController::class, 'success'])->name('success');
    Route::get('/failed/{transactionId}', [PaymentController::class, 'failed'])->name('failed');

    Route::get('/status/{transactionId}', [PaymentController::class, 'status'])->name('status');
});