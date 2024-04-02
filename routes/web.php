<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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

Route::get('/', function () {
    return view('welcome');
});

// Route::queueMonitor('queue-monitor');
Route::group(['prefix' => 'admin', 'as'=> 'admin.'], function () {

    // Route::get('/login',[Login::class,'login'])->name('login');
    // Route::get('/login',Login::class)->name('login');
    // Route::get('/dashboard',Dashboard::class);
    // Route::get('/logout', [Logout::class, 'logout']);

    // Route::match(['get', 'post'], '/login', [AuthController::class, 'index'])->name('admin.login');



    // Route::middleware(['authredirect'])->group(function () {
        // Your login route here

        // Route::get('/login', [AuthController::class, 'index'])->name('admin.login');
        // Route::post('/login', [AuthController::class, 'validate_login'])->name('login.check');

    // });


    Route::match(['GET','POST'], '/login', [AuthController::class, 'index'])->name('admin.login');


    Route::middleware(['auth'])->group(function () {
        // Your login route here

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/influencer', [UsersController::class, 'influencer'])->name('influencer');
        Route::get('/business', [UsersController::class, 'business'])->name('business');
        Route::get('/Userbusiness', [UsersController::class, 'Userbusiness'])->name('Userbusiness');
        Route::post('/activeInactive', [UsersController::class, 'activeInactive'])->name('activeInactive');
        Route::post('/getBusinessCampagins', [UsersController::class, 'getBusinessCampagins'])->name('business.campaign');




    });

});
