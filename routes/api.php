<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\Api\Search\SearchController;;
use App\Http\Controllers\Api\Dater\PortfolioController;
use App\Http\Controllers\Api\Journals\JournalController;
use App\Http\Controllers\Api\Payments\PaymentController;
use App\Http\Controllers\Api\Discover\DiscoverController;
use App\Http\Controllers\Api\Journals\JournalAnalyzerController;
use App\Http\Controllers\Api\Journals\JournalAnalyzerControllerNew;
use App\Http\Controllers\Api\AiChatController\ChatAnalyzerController;
use App\Http\Controllers\Api\FollowFollowing\FollowFollowingController;
use App\Http\Controllers\Api\ChatController\GroupChatController;
use App\Http\Controllers\Api\Quota\QuotaController;

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
Route::middleware(['with_fast_api_key','is_verified_email','auth:api'])->controller(InputsOptions::class)->group(function () {
    Route::get('inputSelection','inputSelection');
});



Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function () {
 
    Route::resource('userQuota', QuotaController::class);
});



Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function () {
    Route::get('community/memberRequest', [CommunityController::class,'communityRequest']);
    Route::post('updateCommunity', [CommunityController::class,'updateCommunity']);
    Route::post('community/join', [CommunityController::class,'joinCoummnity'])->middleware('checkUserQuota:community_join_requests');
    Route::put('community/assignRole', [CommunityController::class,'AssignRole']);
    Route::delete('community/removeMember', [CommunityController::class,'removeMember']);
    Route::get('community/members', [CommunityController::class,'communityUsers']);
    Route::put('community/udpateRequest', [CommunityController::class,'acceptRejectCommunityRequest']);
    Route::put('community/invitation', [CommunityController::class,'invitation']);
    Route::resource('community', CommunityController::class);
});


Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function () {
 
    Route::get('groupChat/getInbox', [GroupChatController::class,'getInbox']);
    Route::post('groupChat/sendMessage', [GroupChatController::class,'sendMessage']);
    Route::get('groupChat/history', [GroupChatController::class,'getChatHistory']);

    
    // create group

    Route::post('groupChat/create', [GroupChatController::class,'createGroup']);
    Route::resource('groupChat', GroupChatController::class);

});


Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){

    Route::post('communityPosts/likePost', [CommunityPost::class,'likePost']);
    Route::post('communityPosts/resharePost', [CommunityPost::class,'resharePost']);
    Route::patch('communityPosts/hideSavePost', [CommunityPost::class,'hideSavePost']);
    Route::post('communityPosts/report', [CommunityPost::class,'reportPost']);
    Route::get('communityPosts/comments', [CommunityPost::class,'comments']);
    Route::get('communityPosts/savedPost', [CommunityPost::class,'savedPosts']);
    Route::post('communityPosts/addComment', [CommunityPost::class,'addComment'])->middleware('checkUserQuota:post_comments');
    Route::delete('communityPosts/deleteComment', [CommunityPost::class,'deleteComment']);
    Route::post('communityPosts/share', [CommunityPost::class,'sharePost']);
    Route::get('communityPosts/textSum', [CommunityPost::class,'textSum']);

    Route::get('communityPosts/calculateScoreByAi', [CommunityPost::class,'calculateScoreByAi']);

    
    Route::resource('communityPosts', CommunityPost::class);
    Route::post('summarizeComment', [CommunityPost::class, 'summarizeComment']); #------- summarize comment------#
});


Route::middleware(['with_fast_api_key', 'auth:api','is_verified_email'])->group(function(){

    // Route::post('communityPosts/likePost', [FollowFollowingController::class,'likePost']);
    // Route::post('communityPosts/resharePost', [CommunityPost::class,'resharePost']);
    // Route::patch('communityPosts/hideSavePost', [CommunityPost::class,'hideSavePost']);
    // Route::post('communityPosts/report', [CommunityPost::class,'reportPost']);
    // Route::get('communityPosts/comments', [CommunityPost::class,'comments']);
    // Route::get('communityPosts/savedPost', [CommunityPost::class,'savedPosts']);
    Route::post('user/block', [FollowFollowingController::class,'blockUser']);
    Route::post('user/report', [FollowFollowingController::class,'reportUser']);
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

     Route::get('journal/insights', [JournalAnalyzerController::class, 'generateReport']);





     Route::get('journal/viewInsightsEntries', [JournalAnalyzerControllerNew::class, 'viewInsightsEntries']);

     Route::get('journal/checkPdf', [JournalAnalyzerControllerNew::class, 'checkPdf']);




     Route::get('journal/insightsNew', [JournalAnalyzerControllerNew::class, 'generateReportNew']);


    // Route::post('communityPost/likePost', [JournalEntries::class,'likePost']);
    Route::post('journal/addToFavorite', [JournalController::class,'addToFavorite']);
    Route::post('journal/updateJournal', [JournalController::class,'updateJournal']);
    
    Route::post('journal/journalEntry', [JournalController::class,'journalEntry'])->middleware('checkUserQuota:journal_entries');
    // Route::get('journal/insights', [JournalController::class,'generateReport']);
    Route::get('journal/getJournalEntries', [JournalController::class,'getJournalEntries']);
    Route::get('journal/symtoms', [JournalController::class,'symtoms']);
    Route::get('journal/getJournalBydate', [JournalController::class,'getJournalBydate']);
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
    Route::post('aiChat/storeMessage', [AiChat::class,'storeMessage'])->middleware('checkUserQuota:chatbot_messages'); 
    Route::get('aiChat/insights', [AiChat::class,'insights']);
    Route::get('aiChat/shareMedia', [AiChat::class,'shareMedia']);
    Route::get('aiChat/threadMessage', [AiChat::class,'threadMessage']);
    Route::get('aiChat/insights', [ChatAnalyzerController::class,'insightsNew']);

    Route::post('aiChat/feedback', [AiChat::class,'chatFeedback']);
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
    Route::put('updateLocation','updateLocation');


    Route::post('iosPush','iosPush');
    
});


Route::middleware(['with_fast_api_key','auth:api'])->controller(StatsController::class)->group(function () {

    Route::get('Statistics','Statistics');
    Route::post('addStatistics','addStatistics');
    
});


Route::middleware(['with_fast_api_key','auth:api','is_verified_email'])->controller(Notifications::class)->group(function () {

    Route::get('notifications','notifications');
    Route::put('readNotification','readNotification');

});


Route::middleware(['with_fast_api_key','auth:api'])->controller(FeelingController::class)->group(function () {

    Route::get('chatHistory','chatHistory');
    Route::get('chatHistory','chatHistory');

});



















