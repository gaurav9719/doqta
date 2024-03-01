<?php

namespace App\Http\Controllers\Api\Dater;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPortfolio;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\GetUserService;
use App\Services\UserProfileUpdate;
use Illuminate\Support\Facades\Storage;
use App\Models\PartnerMatch;
use Illuminate\Cache\RateLimiting\Limit;
use App\Models\Message;
use App\Http\Requests\SendMessage;
use App\Services\NotificationService;
use App\Models\MyTeamMember;
use App\Models\UserBlockList;
class MessageController extends Controller
{
    protected $notification;
    public function __construct(NotificationService $notification)
    {
        $this->notification         = $notification;
    }

    #--------------  GET    MATCHING    THREAD  ---------------------#
    public function getThread(Request $request){
        try {
            $authUser       =   Auth::user();
            $limit          =   10;
            if(isset($request->limit) && !empty($request->limit)){
                $limit      =   $request->limit;
            }
            $threads = PartnerMatch::leftJoin('users as U', function ($join) use ($request, $authUser) {

                $join->on(function ($query) use ($authUser) {
                    // Join condition when user1_id matches myId
                    $query->where('partner_matches.user1_id', '=', $authUser->id)
                        ->where('partner_matches.user2_id', '=', DB::raw('U.id'));
                })->orWhere(function ($query) use ($authUser) {
                    // Join condition when user2_id matches myId
                    $query->where('partner_matches.user2_id', '=', $authUser->id)
                        ->where('partner_matches.user1_id', '=', DB::raw('U.id'));
                });
            })
            ->when(!empty($request->search), function ($query) use ($request) {
                // Filtering based on the first_name column of the 'users' table
                return $query->where('U.name', 'LIKE', '%' . $request['search'] . '%');
            })
            ->where(function ($query) use ($authUser) {
                // Filter the threads where I am the sender or receiver
                $query->where('partner_matches.user1_id', '=', $authUser->id);
                $query->orWhere('partner_matches.user2_id', '=', $authUser->id);
            })
            ->where('partner_matches.is_active','=',1)

            ->where(function ($query) use ($authUser) {
                $query->where('partner_matches.is_sender_trash', '!=', $authUser->id);
                $query->orWhere('partner_matches.is_reciver_trash', '!=', $authUser->id);
            })
            ->select('partner_matches.*', 'U.name', 'U.user_name', 'U.profile_pic', 'U.id as other_user_id','U.qr_code')
            ->orderBy('partner_matches.updated_at', 'DESC') // Order by 'updated_at' column
            ->simplePaginate($limit);                       // Paginate the results

            $threads->getCollection()->transform(function ($thread) use ($request) {

                if(isset($thread) && !empty($thread)){

                    if(empty($thread->profile_pic) && $thread->profile_pic == null){
                        //check in portfolio 
                        $profileExist               =   UserPortfolio::where('user_id', $thread->other_user_id)->whereNotNull('image')->first();
                        if(empty($profileExist)){
                            $thread->profile_pic    =   null;
                        }else{
                            $thread->profile_pic    =   $profileExist->image;
                        }
                    }
                    if(empty($thread->qr_code) && $thread->qr_code == null){  
                        $thread->qr_code =   asset('storage/'.$thread->qr_code);
                    }
                }
                return $thread;           
            });
            return $this->sendResponse($threads, trans("message.message_thread"), 200);
        } catch (Exception $e) {
            Log::error('Error caught: "getThread" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------------------- E N D -----------------------------#

    /*-------------------------- C H A T   H I S T O R Y -----------------------------*/
    public function chatHistory(Request $request)
    {
        try {
            $validator                          =               Validator::make($request->all(), ['inbox_id' => 'required|exists:partner_matches,id']);
            $limit                              =               20;
            if ($validator->fails()) {

                return $this->sendResponsewithoutData(getErrorAsString($validator->errors()), 422);

            } else {

                if (isset($request->limit) && !empty($request->limit)) {

                    $limit                      =               $request->limit;
                }
                $myId                           =               Auth::id();
                $inboxId                        =               $request->inbox_id;
                $inboxExist                     =               PartnerMatch::where(function ($query) use ($myId) {
                    // Filter the threads where I am the sender or receiver
                    $query->where('partner_matches.user1_id', '=', $myId);
                    $query->orWhere('partner_matches.user2_id', '=', $myId);

                })->where(['id',$request->inbox_id,'is_active'=>1])->exists();
                if($inboxExist){
                    $messages                        =               Message::where('id',$inboxId)->where(function ($query) use ($myId) {
                        $query->where('is_sender_trash','!=', $myId, )
                            ->orWhere('is_reciver_trash','!=' ,$myId, );
                    })->orderBy('id', 'desc')->simplePaginate($limit);

                    $messages->setCollection($messages->getCollection()->reverse()->values());
                    return $this->sendResponse($messages, trans('message.chat_history'), 200);
                }else{
                    return $this->sendResponse([], trans("message.something_went_wrong"), 400);
                }
            }
        } catch (Exception $e) {

            Log::error('Error caught: "chatHistory" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    /*-------------------------------------C H A T   H I S T O R Y -----------------------------------------*/

    #----------------------------------- S E N D    M E S S A G E ------------------------------------------#

    public function sendMessage(SendMessage $request)
    {
        try {

            DB::beginTransaction();
            $myId                           =               Auth::id();
            $reciever                       =               $request->receiver_id;
            $message_type                   =               $request->message_type;
            if($myId==$reciever){
                
                return response()->json(['status'=>422,'message'=>"You are not allowed to message yourself."],422);
           }
            $matchExist                        =           PartnerMatch::where(function ($query) use ($myId, $reciever) {

                $query->where(['sender_id' => $myId, 'receiver_id' => $reciever])
                    ->orWhere(['receiver_id' => $myId, 'sender_id' => $reciever]);
            })->where('is_active',1)->first();

            if (empty($matchExist) || $matchExist==null) {       // match is there

                return $this->sendResponsewithoutData(trans('message.unable_to_send_message'), 422);
               
            } else {                                // no match or disable the chat

                $sendMesssage                       =          new  Message(); 
                $sendMesssage->sender_id            =          $myId;
                $sendMesssage->receiver_id          =          $reciever;
                $sendMesssage->message_type         =          $message_type;
                $sendMesssage->match_id             =          $matchExist->id;
            
                if (isset($request->message) && !empty($request->message)) { 

                    $sendMesssage->message          =         $request->message;
                }
                if ($request->hasFile('media')) {
                    
                    $sendMesssage->media            =         message_media($request->media, $message_type);
                }
                if (isset($request->thumbnails) && !empty($request->thumbnails)) {

                    $sendMesssage->media_thumbnail  =         message_media($request->thumbnails, 10);
                }
                $sendMesssage->save();
                $messageId                          =         $sendMesssage->id;
                $message                            =         Message::find($messageId);
                // U P D A T E     M A T C H     T A B L E  

                $matchExist->send_by                 =       $myId;
                $matchExist->message                 =       $message->message;
                $matchExist->media                   =       $message->media;
                $matchExist->media_thumbnail         =       $message->media_thumbnail;
                $matchExist->message_type            =       $message->message_type;
                $matchExist->save();
                DB::commit();
                // SEND PUSH AND NOTIFICATION TO RECEIVER

                $reciever                       =               User::find($reciever, ['id', 'device_token', 'device_type']);
                $section                        =               trans('notificaion_message.send_new_message_type');
                $myName                         =               Auth::user()->first_name;
                $message                        =               trans('notificaion_message.send_new_message_type')." ". $myName;
                $sender                         =               User::find($myId);
                $status                         =               $this->notification->sendNotification(2,$reciever, $sender, $message, $section);
                $last_message                   =              Message::find($sendMesssage->id);
                $last_message->time_ago         =              $last_message->updated_at->diffForHumans();
                $user_profile                   =              User::find($reciever, ['id', 'name', 'user_name', 'profile_pic']);
                if(empty($user_profile->profile_pic) && $user_profile->profile_pic == null){
                    //check in portfolio 
                    $profileExist               =   UserPortfolio::where('user_id', $user_profile->id)->whereNotNull('image')->first();
                    if(empty($profileExist)){

                        $user_profile['profile_pic']    =   null;

                    }else{

                        $user_profile['profile_pic']    =   $profileExist->image;
                    }
                }
                $last_message['profile']        =              $user_profile;
                return $this->sendResponse($last_message, "Message send.", 200);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "sendMessage" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    #-------------------------------------******* E N D *******---------------------------------------------#


    #---------------------  U N M A T C H E D       ----------------------------------------#
    public function unmatchUser(Request $request)
    {
        try {
            
            DB::beginTransaction();
            $validator                          =               Validator::make($request->all(), ['user_id' => 'required|exists:users,id']);
            $limit                              =               20;
            if ($validator->fails()) {

                return $this->sendResponsewithoutData(getErrorAsString($validator->errors()), 422);

            } else {
                //check record is exist or not
                $authId =   Auth::id();
                $userid =   $request->user_id;
                $isMatchExist = PartnerMatch::where(function($query) use ($authId, $userid) {
                    $query->where('user1_id', $authId)
                          ->where('user2_id', $userid);
                })->orWhere(function($query) use ($authId, $userid) {
                    $query->where('user1_id', $userid)
                          ->where('user2_id', $authId);
                })->first();

                if(isset($isMatchExist) && !empty($isMatchExist)){

                    $isMatchExist->delete();
                    MyTeamMember::where(function($query) use ($authId, $userid) {
                            $query->where('member_id', $authId)
                                  ->where('dater_id', $userid);
                        })->orWhere(function($query) use ($authId, $userid) {
                            $query->where('member_id', $userid)
                                  ->where('dater_id', $authId);
                        })->delete();
                        DB::commit();

                }else{

                    return $this->sendResponse([], trans('message.something_went_wrong'), 403);
                }
           }
        } catch (Exception $e) {
            
            DB::rollback();
            Log::error('Error caught: "unmatchUser" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    #------------ B L O C K    U S E R  --------------------#

    public function blockUser(Request $request)
    {
        try {
            
            DB::beginTransaction();
            $validator                          =               Validator::make($request->all(), ['user_id' => 'required|exists:users,id']);
            $limit                              =               20;
            if ($validator->fails()) {

                return $this->sendResponsewithoutData(getErrorAsString($validator->errors()), 422);

            } else {
                //check record is exist or not
                $authId =   Auth::id();
                $userid =   $request->user_id;
                $isMatchExist = PartnerMatch::where(function($query) use ($authId, $userid) {
                    $query->where('user1_id', $authId)
                          ->where('user2_id', $userid);
                })->orWhere(function($query) use ($authId, $userid) {
                    $query->where('user1_id', $userid)
                          ->where('user2_id', $authId);
                })->first();

                if(isset($isMatchExist) && !empty($isMatchExist)){

                    $isMatchExist->delete();
                    MyTeamMember::where(function($query) use ($authId, $userid) {
                            $query->where('member_id', $authId)
                                  ->where('dater_id', $userid);
                        })->orWhere(function($query) use ($authId, $userid) {
                            $query->where('member_id', $userid)
                                  ->where('dater_id', $authId);
                        })->delete();

                        // ADD TO BLOCK TABLE 
                        UserBlockList::updateOrCreate(
                            ['user_id' => $authId, 'blocked_user_id' => $userid],
                            ['created_at' => now()] // If you want to update the timestamp
                        );
                        DB::commit();

                }else{

                    return $this->sendResponse([], trans('message.something_went_wrong'), 403);
                }
           }
        } catch (Exception $e) {
            
            DB::rollback();
            Log::error('Error caught: "unmatchUser" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }



}
