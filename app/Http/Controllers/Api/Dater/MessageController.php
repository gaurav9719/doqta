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
class MessageController extends Controller
{
    #--------------  GET    MATCHING    THREAD  ---------------------#
    public function getThread(Request $request){   
        try {
            $authUser       =   Auth::user();
            $limit          =   10;
            if(isset($request->limit) && !empty($request->limit)){
                $limit      =   $request->limit;
            }
            $messageThread  =   PartnerMatch::where(function($query) use ($request, $authUser){
                $query->where(['user1_id'=>$authUser->id]);
                $query->orWhere(['user2_id'=>$authUser->id]);
            })->simplePaginate($limit);

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
            ->select('partner_matches.*', 'U.name', 'U.user_name', 'U.profile_pic', 'U.id as other_user_id')
            ->orderBy('partner_matches.updated_at', 'DESC') // Order by 'updated_at' column
            ->simplePaginate($limit);                       // Paginate the results
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

    public function sendMessage(Request $request)
    {
        try {

            DB::beginTransaction();

            $myId                           =               Auth::id();
            $reciever                       =               $request->receiver_id;
            $message_type                   =               $request->message_type;
            
            if($myId==$reciever){
                
                return response()->json(['status'=>422,'message'=>"You are not allowed to message yourself."],422);
           }
            $message                        =               Chat_thread::where(function ($query) use ($myId, $reciever) {

                $query->where(['sender_id' => $myId, 'receiver_id' => $reciever])
                    ->orWhere(['receiver_id' => $myId, 'sender_id' => $reciever]);
            })->first();

            if (isset($message) && !empty($message)) {

                $message->sender_id            =          $myId;
                $message->receiver_id          =          $reciever;
                $message->message_type         =          $message_type;

            } else {

                // create new thread
                $message                       =               new Chat_thread();
                $message->sender_id            =               $myId;
                $message->receiver_id          =               $reciever;
                $message->message_type         =               $message_type;

            }

            if (isset($request->message) && !empty($request->message)) {

                $message->message           =              $request->message;
            }

            if ($request->hasFile('media')) {
                
                $message->media             =             message_media($request->media, $message_type);

            }

            if (isset($request->thumbnails) && !empty($request->thumbnails)) {

                $message->media_thumbnail   =             message_media($request->thumbnails, 10);

            }

            $message->save();

            $threadId                       =               $message->id;
            // SET IN THREAD TABLE
            $threadData                     =               Chat_thread::find($threadId);
            $chat_message                   =               new Chat();
            $chat_message->thread_id        =               $threadId;
            $chat_message->sender_id        =               $threadData->sender_id;
            $chat_message->receiver_id      =               $threadData->receiver_id;
            $chat_message->message          =               $threadData->message;
            $chat_message->media            =               $threadData->media;
            $chat_message->media_thumbnail  =               $threadData->media_thumbnail;
            $chat_message->message_type     =               $threadData->message_type;
            $chat_message->save();
            DB::commit();
            // SEND PUSH AND NOTIFICATION TO RECEIVER

            $reciever                       =               User::find($reciever, ['id', 'device_token', 'device_type']);
            $section                        =               6;
            $myName                         =               Auth::user()->first_name;
            $message                        =               "Your have recieved new message from  " . $myName;
            $sender                         =               User::find($myId);
            $status                         =               $this->notificationService->sendNotification($reciever, $sender, $message, $section);

            $last_message                =              Chat::find($chat_message->id);
            $last_message->time_ago      =              $last_message->updated_at->diffForHumans();
            $last_message['profile']     =              User::find($reciever, ['id', 'first_name', 'last_name', 'last_name', 'profile_pic']);
            return $this->sendResponse($last_message, "Message send.", 200);
        } catch (Exception $e) {

            DB::rollback();
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    #-------------------------------------******* E N D *******---------------------------------------------#





}
