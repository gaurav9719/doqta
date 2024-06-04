<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminBasicController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPartnersDomainController;
use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWebsiteController;
use App\Http\Controllers\Admin\DocumentVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// Route::get('/', [AdminDashboardController::class, 'index']);
Route::get('/', function () {
    
    return view('welcome');

});
Route::prefix('admin')->group(function(){

    Route::resource('login', AdminAuthController::class)->middleware('guest');
    Route::middleware('auth')->group(function(){
        Route::resource('dashboard', AdminDashboardController::class);
        Route::resource('users', AdminUserController::class);
        Route::get('document-verification', [AdminUserController::class, 'documentVerification']);
        Route::resource('document-verification/view', DocumentVerificationController::class);
        // Route::post('verification-pending/view', [DocumentVerificationController::class, 'update']);
        Route::get('logout', [AdminAuthController::class, 'destroy']);
        Route::get('profile', [AdminAuthController::class, 'profile']);
        Route::post('profile', [AdminAuthController::class, 'profileUpdate']);
        Route::get('community', [AdminBasicController::class, 'viewCommunity']);
        Route::put('community/{id}', [AdminBasicController::class, 'updateCommunity']);
        Route::resource('partners-domain', AdminPartnersDomainController::class);
    });
});

