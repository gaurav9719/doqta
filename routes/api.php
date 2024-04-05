<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\PointSystem;
use App\Http\Controllers\Api\Notifications;
use App\Http\Controllers\Api\Recruiter\GhostRequestController;
use App\Http\Controllers\Api\Dater\PortfolioController;
use App\Http\Controllers\Api\SignStepsController;
use App\Http\Controllers\Api\InputsOptions;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\CommunityPost;

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
    Route::post('verifyEmail','verifyEmail')->middleware('auth:api');
    Route::post('signIn','signIn');
    Route::post('logout','logout')->middleware('auth:api');
    Route::get('qr','qr');
    Route::get('matchQr','matchQr');
    Route::post('forgotPassword','forgotPassword');
    Route::post('socialLogin','socialLogin');
});

Route::middleware(['with_fast_api_key','auth:api','is_verified_email'])->controller(SignStepsController::class)->group(function () {
    Route::post('completeSignUpSteps','completeSignUpSteps');

    
    
});
Route::middleware(['with_fast_api_key','is_verified_email'])->controller(InputsOptions::class)->group(function () {
    Route::get('inputSelection','inputSelection');
});





Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function () {
    Route::get('communityRequest', [CommunityController::class,'communityRequest']);
    Route::resource('community', CommunityController::class);

});


Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){

    
    Route::post('communityPost/likePost', [CommunityPost::class,'likePost']);
    Route::post('communityPost/resharePost', [CommunityPost::class,'resharePost']);
    Route::patch('communityPost/hideSavePost', [CommunityPost::class,'hideSavePost']);
    Route::post('communityPost/report', [CommunityPost::class,'reportPost']);

    
    Route::resource('communityPost', CommunityPost::class);

    
});













Route::middleware(['auth:api','with_fast_api_key','is_verified_email'])->controller(UserController::class)->group(function () {
    // Route::put('updateUserPreferences','updateUserPreferences');
    Route::post('changePassword','changePassword');
    Route::post('update_profile','update_profile');
    
    
    Route::post('update_profile','update_profile');
    Route::post('switchUser','switchUser');
    Route::get('checkAiFInder','checkAiFInder');

});


Route::middleware(['with_fast_api_key','auth:api'])->controller(StatsController::class)->group(function () {

    Route::get('Statistics','Statistics');
    Route::post('addStatistics','addStatistics');
    
});


route::middleware(['with_fast_api_key','auth:api'])->controller(PointSystem::class)->group(function () {

    Route::get('pointSystem','pointSystem');
});


Route::middleware(['with_fast_api_key','auth:api','is_verified_email'])->controller(Notifications::class)->group(function () {

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


Route::middleware(['with_fast_api_key','auth:api'])->controller(MessageController::class)->group(function () {

    Route::get('chatHistory','chatHistory');
    Route::get('chatHistory','chatHistory');

});



















