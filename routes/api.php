<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PointSystem;
use App\Http\Controllers\Api\CommunityPost;
use App\Http\Controllers\Api\InputsOptions;
use App\Http\Controllers\Api\Notifications;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JournalEntries;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\FeelingController;
use App\Http\Controllers\Api\Chat\ChatController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\SignStepsController;
use App\Http\Controllers\Api\Likes\LikeController;
use App\Http\Controllers\Api\AiChatController\AiChat;
use App\Http\Controllers\Api\Gemini\GeniminController;
use App\Http\Controllers\Api\Dater\PortfolioController;
use App\Http\Controllers\Api\Journals\JournalController;
use App\Http\Controllers\Api\Payments\PaymentController;
use App\Http\Controllers\Api\Discover\DiscoverController;
use App\Http\Controllers\Api\Recruiter\GhostRequestController;
use App\Http\Controllers\Api\Journals\JournalAnalyzerController;
use App\Http\Controllers\Api\FollowFollowing\FollowFollowingController;
use App\Http\Controllers\Api\Search\SearchController;;

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
    Route::delete('deleteAccount','deleteAccount')->middleware('auth:api');
    Route::post('calculateScore','calculateScore');

    
});

Route::middleware(['with_fast_api_key','auth:api','is_verified_email'])->controller(SignStepsController::class)->group(function () {
    Route::post('completeSignUpSteps','completeSignUpSteps');

    
    
});

Route::middleware(['with_fast_api_key','auth:api'])->controller(GeniminController::class)->group(function () {
    Route::post('summerize','summerize');
    Route::get('summarizeImage','summarizeImage');
    Route::get('generateContentWithCurl','generateContentWithCurl');
    Route::get('analyzeJournalOld','analyzeJournalOld');
    Route::get('doqtachat','chat');
    
    
    
    
});
Route::middleware(['with_fast_api_key','is_verified_email'])->controller(InputsOptions::class)->group(function () {
    Route::get('inputSelection','inputSelection');
});





Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function () {
    Route::get('community/memberRequest', [CommunityController::class,'communityRequest']);
    Route::post('updateCommunity', [CommunityController::class,'updateCommunity']);
    Route::post('community/join', [CommunityController::class,'joinCoummnity']);
    Route::put('community/assignRole', [CommunityController::class,'AssignRole']);
    Route::delete('community/removeMember', [CommunityController::class,'removeMember']);
    Route::get('community/members', [CommunityController::class,'communityUsers']);
    Route::put('community/udpateRequest', [CommunityController::class,'acceptRejectCommunityRequest']);
    Route::resource('community', CommunityController::class);


    

});


Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){

    Route::post('communityPosts/likePost', [CommunityPost::class,'likePost']);
    Route::post('communityPosts/resharePost', [CommunityPost::class,'resharePost']);
    Route::patch('communityPosts/hideSavePost', [CommunityPost::class,'hideSavePost']);
    Route::post('communityPosts/report', [CommunityPost::class,'reportPost']);
    Route::get('communityPosts/comments', [CommunityPost::class,'comments']);
    Route::get('communityPosts/savedPost', [CommunityPost::class,'savedPosts']);
    Route::post('communityPosts/addComment', [CommunityPost::class,'addComment']);
    Route::delete('communityPosts/deleteComment', [CommunityPost::class,'deleteComment']);
    Route::post('communityPosts/share', [CommunityPost::class,'sharePost']);
    Route::resource('communityPosts', CommunityPost::class);

    
});


Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){

    // Route::post('communityPosts/likePost', [FollowFollowingController::class,'likePost']);
    // Route::post('communityPosts/resharePost', [CommunityPost::class,'resharePost']);
    // Route::patch('communityPosts/hideSavePost', [CommunityPost::class,'hideSavePost']);
    // Route::post('communityPosts/report', [CommunityPost::class,'reportPost']);
    // Route::get('communityPosts/comments', [CommunityPost::class,'comments']);
    // Route::get('communityPosts/savedPost', [CommunityPost::class,'savedPosts']);
    // Route::post('communityPosts/addComment', [CommunityPost::class,'addComment']);

    Route::resource('supportSupporting', FollowFollowingController::class);
    
});

Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){

    // Route::post('communityPosts/likePost', [FollowFollowingController::class,'likePost']);
    // Route::post('communityPosts/resharePost', [CommunityPost::class,'resharePost']);
    // Route::patch('communityPosts/hideSavePost', [CommunityPost::class,'hideSavePost']);
    // Route::post('communityPosts/report', [CommunityPost::class,'reportPost']);
    // Route::get('communityPosts/comments', [CommunityPost::class,'comments']);
    // Route::get('communityPosts/savedPost', [CommunityPost::class,'savedPosts']);
    // Route::post('communityPosts/addComment', [CommunityPost::class,'addComment']);
    Route::post('reportComment', [LikeController::class,'reportComment']);
    Route::resource('like', LikeController::class);
    
});







Route::middleware(['with_fast_api_key', 'auth:api'])->group(function(){

    
    Route::get('feeling', [FeelingController::class,'feeling']);
   
    
});


Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){

    Route::post('generate-report', [JournalAnalyzerController::class, 'generateReport']);


    // Route::post('communityPost/likePost', [JournalEntries::class,'likePost']);
    Route::post('journal/addToFavorite', [JournalController::class,'addToFavorite']);
    Route::post('journal/updateJournal', [JournalController::class,'updateJournal']);
    
    Route::post('journal/journalEntry', [JournalController::class,'journalEntry']);
    Route::get('journal/insights', [JournalController::class,'insights']);
    Route::get('journal/getJournalEntries', [JournalController::class,'getJournalEntries']);
    Route::get('journal/symtoms', [JournalController::class,'symtoms']);
    Route::resource('journal', JournalController::class);


});

Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){

    // Route::post('communityPost/likePost', [JournalEntries::class,'likePost']);
   
    
    Route::resource('planSubscription', PaymentController::class);

    
});




Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){
    // Route::post('communityPost/likePost', [JournalEntries::class,'likePost']);
    // Route::post('communityPost/resharePost', [CommunityPost::class,'resharePost']);
    // Route::patch('communityPost/hideSavePost', [CommunityPost::class,'hideSavePost']);
    Route::get('discover/topHealthProvider', [DiscoverController::class,'topHealthProvider']);
    Route::get('discover/parts', [DiscoverController::class,'discoverPart']);
    Route::resource('discover', DiscoverController::class);

});



Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){
    // Route::post('communityPost/likePost', [JournalEntries::class,'likePost']);
    // Route::post('communityPost/resharePost', [CommunityPost::class,'resharePost']);
    // Route::patch('communityPost/hideSavePost', [CommunityPost::class,'hideSavePost']);
    // Route::patch('journal/addToFavorite', [DiscoverController::class,'addToFavorite']);
    Route::resource('chat', ChatController::class);

});

Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){
    // Route::post('communityPost/likePost', [JournalEntries::class,'likePost']);
    // Route::post('communityPost/resharePost', [CommunityPost::class,'resharePost']);
    // Route::patch('communityPost/hideSavePost', [CommunityPost::class,'hideSavePost']);
    Route::get('aiChat/chatLogs', [AiChat::class,'chatLogs']);
    Route::get('aiChat/chatLogs2', [AiChat::class,'chatLogs2']);
    Route::post('aiChat/pinUnpinMessage', [AiChat::class,'pinUnpinMessage']);
    Route::get('aiChat/pinnedMessage', [AiChat::class,'pinnedMessage']);
    Route::post('aiChat/storeMessage', [AiChat::class,'storeMessage']);
    Route::get('aiChat/insights', [AiChat::class,'insights']);
    Route::get('aiChat/shareMedia', [AiChat::class,'shareMedia']);
    Route::get('aiChat/threadMessage', [AiChat::class,'threadMessage']);
    Route::resource('aiChat', AiChat::class);

});


Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){
   
    Route::resource('search', SearchController::class);

});









Route::middleware(['auth:api','with_fast_api_key','is_verified_email'])->controller(UserController::class)->group(function () {
    // Route::put('updateUserPreferences','updateUserPreferences');
    Route::post('changePassword','changePassword');
    Route::post('update_profile','update_profile');
    Route::get('getUserProfile','getUserProfile');
    Route::get('getUserPost','getUserPost');
    Route::post('switchUser','switchUser');
    Route::get('checkAiFInder','checkAiFInder');
    Route::post('requestEmailChange','requestEmailChange');
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


Route::middleware(['with_fast_api_key','auth:api'])->controller(FeelingController::class)->group(function () {

    Route::get('chatHistory','chatHistory');
    Route::get('chatHistory','chatHistory');

});



















