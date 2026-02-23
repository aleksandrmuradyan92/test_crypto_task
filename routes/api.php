<?php

use App\Http\Controllers\CryptoBalanceController;
use Illuminate\Support\Facades\Route;

Route::post('/crypto-balance/credit', [CryptoBalanceController::class, 'credit']);
Route::post('/crypto-balance/debit', [CryptoBalanceController::class, 'debit']);
Route::get('/crypto-balance/balance', [CryptoBalanceController::class, 'balance']);
