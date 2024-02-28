<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\PointSystem;
use App\Http\Controllers\Api\Notifications;
use App\Http\Controllers\Api\Recruiter\GhostRequestController;
use App\Http\Controllers\Api\Recruiter\MyTeamController;
use App\Http\Controllers\Api\Dater\DaterPicksController;
use App\Http\Controllers\Api\Dater\PortfolioController;
use App\Http\Controllers\Api\InvitesContact;
use App\Http\Controllers\Api\Recruiter\RecuitsController;
use App\Http\Controllers\Api\Recruiter\AddToMember;
use App\Http\Controllers\Api\AcceptBenchToUser;
use App\Http\Controllers\Api\Recruiter\LeaderBoard;

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


// Route::middleware(['with_fast_api_key'])->controller(AuthController::class)->group(function(){

//     Route::post('signUp');

// });

Route::middleware(['with_fast_api_key'])->controller(AuthController::class)->group(function () {
    Route::post('signUp','signUp');
    Route::post('signIn','signIn');
    Route::post('logout','logout')->middleware('auth:api');
    
});

Route::middleware(['auth:api','with_fast_api_key'])->controller(UserController::class)->group(function () {
    // Route::put('updateUserPreferences','updateUserPreferences');
    Route::post('update_profile','update_profile');
    Route::post('switchUser','switchUser');

    
    
   

});


Route::middleware(['with_fast_api_key','auth:api'])->controller(StatsController::class)->group(function () {

    Route::get('Statistics','Statistics');
    Route::post('addStatistics','addStatistics');
    
});


route::middleware(['with_fast_api_key','auth:api'])->controller(PointSystem::class)->group(function () {

    Route::get('pointSystem','pointSystem');
});


Route::middleware(['with_fast_api_key','auth:api'])->controller(Notifications::class)->group(function () {

    Route::get('notifications','notifications');

});


Route::middleware(['with_fast_api_key','auth:api'])->controller(GhostRequestController::class)->group(function () {

    Route::get('ghostCoachRequest','ghostCoachRequest');
    Route::put('acceptRejectGhostReq','acceptRejectGhostReq');

});

Route::middleware(['with_fast_api_key','auth:api'])->controller(MyTeamController::class)->group(function () {

    Route::get('myTeam','myTeam');
  

});

Route::middleware(['with_fast_api_key','auth:api'])->controller(DaterPicksController::class)->group(function () {

     Route::match(['get', 'post'],'datersPick','datersPick');
    //Route::get('datersPick','datersPick');

});



Route::middleware(['with_fast_api_key','auth:api'])->controller(PortfolioController::class)->group(function () {

    Route::match(['delete', 'post'],'uploadPortfolio/{id?}','uploadPortfolio');

});

Route::middleware(['with_fast_api_key','auth:api'])->controller(InvitesContact::class)->group(function () {

    Route::post('addInvitedFriend','addInvitedFriend');

});

Route::middleware(['with_fast_api_key','auth:api'])->controller(RecuitsController::class)->group(function () {

    Route::get('recruits','recruits');
    Route::match(['get', 'post'],'recruitUser','recruitUser');

    

});

Route::middleware(['with_fast_api_key','auth:api'])->controller(AddToMember::class)->group(function () {

    Route::post('addToTeamBench','addToTeamBench');

});


Route::middleware(['with_fast_api_key','auth:api'])->controller(AcceptBenchToUser::class)->group(function () {

    Route::post('AddToAcceptBench','AddToAcceptBench');

});


Route::middleware(['with_fast_api_key','auth:api'])->controller(LeaderBoard::class)->group(function () {

    Route::get('leaderBoard','leaderBoard');

});


















