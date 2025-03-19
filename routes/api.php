<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    InventoryController,
    PurchaseController
};
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
Route::get('/',function(){
    return "Ms Inventory running";
});
Route::prefix('v1')->group(function () {
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/{ingredient}', [InventoryController::class, 'show']);
        Route::post('/request', [InventoryController::class, 'request']);
    });
    Route::prefix('purchases')->group(function () {
        Route::get('/', [PurchaseController::class, 'index']);
    });
});