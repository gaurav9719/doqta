<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\User;
use App\Services\NotificationService;
use App\Models\UserFollower;
use App\Models\BlockedUser;
use App\Models\ReportedUser;
use App\Jobs\SendNotificaionJob;
use App\Models\Notification;
use App\Models\Inbox;
use App\Models\Message;
use Carbon\Carbon;
use App\Http\Requests\ChatRequest;
use App\Traits\postCommentLikeCount;

class ChatController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    use postCommentLikeCount;

    protected $notification;
    public function __construct(NotificationService $notification)
    {
        $this->notification         = $notification;
    }

    public function index(Request $request) #---------- G E T     T H E       I N B O X ----------#
    {
        try {

            $limit                          =               20;
            if (isset($request->limit) && !empty($request->limit)) {

                $limit                      =               $request->limit;
            }
            $myId                           =               Auth::id();
            $data                           =               $request->all();
            $threads                        =               Inbox::leftJoin('users as U', function ($join) use ($myId) {

                $join->on(function ($query) use ($myId) {
                    // Join condition when sender_id matches myId
                    $query->where('inboxes.sender_id', '=', $myId)
                        ->where('inboxes.receiver_id', '=', DB::raw('U.id'));
                })->orWhere(function ($query) use ($myId) {
                    // Join condition when receiver_id matches myId
                    $query->where('inboxes.receiver_id', '=', $myId)
                        ->where('inboxes.sender_id', '=', DB::raw('U.id'));
                });
            })
                ->when(!empty($request->search), function ($query) use ($data) {
                    // Filtering based on the first_name column of the 'users' table
                    return $query->where('U.inboxes', 'LIKE', '%' . $data['search'] . '%');
                })

                ->where(function ($query) use ($myId) {
                    // Filter the threads where I am the sender or receiver
                    $query->where('inboxes.sender_id', '=', $myId);
                    $query->orWhere('inboxes.receiver_id', '=', $myId);

                })
                ->select('inboxes.*', 'U.name', 'U.profile', 'U.id as other_user_id')
                ->orderBy('inboxes.updated_at', 'DESC') // Order by 'updated_at' column
                ->simplePaginate($limit); // Paginate the results

                if ($threads[0]) {

                    $threads->each(function ($result) use($myId) {

                        $result['unread_message_count'] =   Message::where(['inbox_id'=> $result->id])->where(function($query) use($myId){

                            $query->where('is_user1_trash','!=',$myId)->orWhere('is_user2_trash','!=',$myId);

                        })->where('sender_id','!=',$myId)->where('isread',0)->count();
                       if(isset($result->profile) && !empty($result->profile)){

                           $result['profile']          =       $this->addBaseInImage($result->profile);
                       }
                        $result['last_message']        =   Message::select('id','message','sender_id','media','media_thumbnail','message_type','replied_to_message_id','is_user1_trash','is_user2_trash','isread')->where(['id'=> $result->message_id])->first();
                        
                        $result->time_ago = time_elapsed_string($result->updated_at);
                        
                    });
                }
            return $this->sendResponse($threads, "Inbox.", 200);
        } catch (Exception $e) {

            Log::error('Error caught: "get chat history" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ChatRequest $request)
    {
        //
        DB::beginTransaction();
        try {

            $myId                           =               Auth::id();
            $reciever                       =               $request->receiver_id;
            $message_type                   =               $request->message_type;


            if ($myId == $reciever) {
                return response()->json(['status' => 422, 'message' => "You are not allowed to message yourself."], 422);
            }

            $message = Inbox::where(function ($query) use ($myId, $reciever) {
                $query->where(function ($subQuery) use ($myId, $reciever) {
                    $subQuery->where('sender_id', $myId)
                        ->where('receiver_id', $reciever);
                })->orWhere(function ($subQuery) use ($myId, $reciever) {
                    $subQuery->where('receiver_id', $myId)
                        ->where('sender_id', $reciever);
                });
            })->first();

            if (empty($message)) {
                // create new thread
                $message                       =               new Inbox();
                $message->sender_id            =               $myId;
                $message->receiver_id          =               $reciever;
                $message->save();
            }

            if ($request->hasFile('media')) {

                $media                          =             message_media($request->media, $message_type);
            }

            if (isset($request->thumbnails) && !empty($request->thumbnails)) {

                $media_thumbnail                =             message_media($request->thumbnails, 10);
            }
            $inboxId                            =               $message->id;
            #----------- A D D      D A T A         T O         M E S S A G E       T A B L E -----------#
            $sendMessage                        =                new Message();
            $sendMessage->inbox_id              =                $inboxId;
            $sendMessage->sender_id             =                $myId;
            $sendMessage->message               =                $request->message;
            if (isset($media) && !empty($media)) {

                $sendMessage->media             =                $media;
            }
            if (isset($media_thumbnail) && !empty($media_thumbnail)) {

                $sendMessage->media_thumbnail    =                $media_thumbnail;
            }

            if (isset($lat) && !empty($lat)) {

                $sendMessage->lat                =                $request->lat;
            }

            if (isset($long) && !empty($long)) {

                $sendMessage->long               =                $request->long;
            }
            $sendMessage->message_type           =                $request->message_type;
            $sendMessage->save();
            $lastMessageId                       =                $sendMessage->id;
            Inbox::where('id', $inboxId)->update(['message_id' => $lastMessageId]);

            #send notification
            $receiver                           =               User::find($reciever);
            $sender                             =               User::find($myId);
            $message                            =               "New message from " . $sender->name;
            $data                               =               ["message"=> $message,'notification_type'=>trans('notification_message.send_message_type')];
            sendPushNotificationNew($sender, $receiver,$data);

            DB::commit();
            // SEND PUSH AND NOTIFICATION TO RECEIVER
            $sent_message                        =               $this->getLastMessage($lastMessageId, $myId);
            return $this->sendResponse($sent_message, "Message send.", 200);

            // $last_message                   =              Message::find($chat_message->id);
            // $last_message->time_ago         =              $last_message->updated_at->diffForHumans();
            // $last_message['profile']        =              User::find($reciever, ['id', 'first_name', 'last_name', 'last_name', 'profile_pic']);
            // return $this->sendResponse($last_message, "Message send.", 200);
        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "sendMessage" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request) #------- S H O W       C H A T          H I S T O R Y -----------#
    {
        //
        try {
            if (empty($id)) {

                return $this->sendError("user_id required", [], 422);
                
            } else {

                $limit                          =               10;
                if (isset($request->limit) && !empty($request->limit)) {

                    $limit                      =               $request->limit;
                }

                $myId                           =               Auth::id();

                $reciever                       =               $id;

                if ($myId == $reciever) {

                    return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 422);
                } else {

                    $inbox                    =              Inbox::where(function ($query) use ($myId, $reciever) {
                        $query->where(function ($subQuery) use ($myId, $reciever) {
                            $subQuery->where('sender_id', $myId)
                                ->where('receiver_id', $reciever);
                        })->orWhere(function ($subQuery) use ($myId, $reciever) {
                            $subQuery->where('receiver_id', $myId)
                                ->where('sender_id', $reciever);
                        });
                    })->first();
                    
                    if (isset($inbox) && !empty($inbox)) {

                        $inboxId              =             $inbox->id;
                        $messages             =             Message::with(['sender' => function ($query) {

                            $query->select('id', 'name', 'profile');
                        }, 'reply_to.sender' => function ($query) {

                            $query->select('id', 'name', 'profile');
                        }])->where(function ($query) use ($myId) {

                            $query->where('is_user1_trash', '!=', $myId)
                                ->orWhere('is_user2_trash', '!=', $myId);

                        })->where('inbox_id', $inboxId)->orderByDesc('id')->simplePaginate($limit);
                        if ($messages[0]) {
                            $messages->each(function ($result) {

                                if (isset($result->sender) && !empty($result->sender)) {

                                    if (isset($result->sender->profile) && !empty($result->sender->profile)) {

                                        $result->sender->profile        =   $this->addBaseInImage($result->sender->profile);
                                    }
                                }
                                if (isset($result->media) && !empty($result->media)) {

                                    $result->media        =   $this->addBaseInImage($result->media);
                                }
                                if (isset($result->media_thumbnail) && !empty($result->media_thumbnail)) {

                                    $result->media_thumbnail        =   $this->addBaseInImage($result->media_thumbnail);
                                }
                                if (isset($result->reply_to) && !empty($result->reply_to)) {

                                    if (isset($result->reply_to->media) && !empty($result->reply_to->media)) {

                                        $result->reply_to->media        =   $this->addBaseInImage($result->reply_to->media);
                                    }
                                    if (isset($result->reply_to->media_thumbnail) && !empty($result->reply_to->media_thumbnail)) {

                                        $result->reply_to->media_thumbnail        =   $this->addBaseInImage($result->reply_to->media_thumbnail);
                                    }
                                    if (isset($result->reply_to->sender) && !empty($result->reply_to->sender)) {

                                        if (isset($result->reply_to->sender->profile) && !empty($result->reply_to->sender->profile)) {

                                            $result->reply_to->sender->profile        =   $this->addBaseInImage($result->reply_to->sender->profile);
                                        }
                                    }
                                }

                                $result->time_ago = time_elapsed_string($result->created);

                                if ($result->isread == 0) {
                                    Message::where('id', $result->id)
                                        ->update([
                                            'isread' => 1,
                                            'message_read_time' => now()
                                        ]);
                                }
                            });
                        }
                    } else {

                        $inbox                    =              Inbox::where(function ($query) use ($myId, $reciever) {
                            $query->where(function ($subQuery) use ($myId, $reciever) {
                                $subQuery->where('sender_id', $myId)
                                    ->where('receiver_id', $reciever);
                            })->orWhere(function ($subQuery) use ($myId, $reciever) {
                                $subQuery->where('receiver_id', $myId)
                                    ->where('sender_id', $reciever);
                            });
                        })->simplePaginate($limit);

                        return $this->sendResponse($inbox, "Chat History.", 200);
                    }

                    $messages->setCollection($messages->getCollection()->reverse()->values());

                    return $this->sendResponse($messages, "Chat History.", 200);
                }
            }
        } catch (Exception $e) {

            Log::error('Error caught: "messageHistory" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function getLastMessage($message_id, $myId)
    {

        $result             =             Message::with(['sender' => function ($query) {

            $query->select('id', 'name', 'profile');
        }, 'reply_to.sender' => function ($query) {

            $query->select('id', 'name', 'profile');
        }])->where(function ($query) use ($myId) {

            $query->where('is_user1_trash', '!=', $myId)
                ->orWhere('is_user2_trash', '!=', $myId);
        })->where('id', $message_id)->first();


        if (isset($result) && !empty($result)) {


            // $result->time_ago         =              $result->created_at->diffForHumans();

            $result->time_ago         =              time_elapsed_string($result->created_at);

            if (isset($result->sender) && !empty($result->sender)) {

                if (isset($result->sender->profile) && !empty($result->sender->profile)) {

                    $result->sender->profile        =   $this->addBaseInImage($result->sender->profile);
                }
            }

            if (isset($result->media) && !empty($result->media)) {

                $result->media        =  $this->addBaseInImage($result->media);
            }

            if (isset($result->media_thumbnail) && !empty($result->media_thumbnail)) {

                $result->media_thumbnail        =  $this->addBaseInImage($result->media_thumbnail);
            }

            if (isset($result->reply_to) && !empty($result->reply_to)) {

                if (isset($result->reply_to->media) && !empty($result->reply_to->media)) {

                    $result->reply_to->media        =    $this->addBaseInImage($result->reply_to->media);
                }

                if (isset($result->reply_to->media_thumbnail) && !empty($result->reply_to->media_thumbnail)) {

                    $result->reply_to->media_thumbnail        =   $this->addBaseInImage($result->reply_to->media_thumbnail);
                }

                if (isset($result->reply_to->sender) && !empty($result->reply_to->sender)) {

                    if (isset($result->reply_to->sender->profile) && !empty($result->reply_to->sender->profile)) {

                        $result->reply_to->sender->profile        =   $this->addBaseInImage($result->reply_to->sender->profile);
                    }
                }
            }
        }

        return $result;
    }
}
