<?php

namespace App\Http\Controllers\Api\ChatController;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Inbox;
use App\Models\Message;
use App\Models\ChatGroup;
use App\Models\BlockedUser;
use App\Models\Participant;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\ReportedUser;
use App\Models\UserFollower;
use Illuminate\Http\Request;
use App\Models\ChatGroupMember;
use App\Jobs\SendNotificaionJob;
use App\Http\Requests\ChatRequest;
use App\Models\ParticipantMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\postCommentLikeCount;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\GroupRequest\GroupRequestValidation;


class GroupChatController extends BaseController

{

    use postCommentLikeCount;

    protected $notification;
    public function __construct(NotificationService $notification)
    {
        $this->notification         = $notification;
    }
    /**
     * Display a listing of the resource.
     */












    public function index(Request $request)
    {
        try {

            $limit                          =               20;

            if (isset($request->limit) && !empty($request->limit)) {

                $limit                      =               $request->limit;
            }
            $myId                           =               Auth::id();
            $data                           =               $request->all();

            $threads = Inbox::leftJoin('users as U', function ($join) use ($myId) {

                $join->on('inboxes.sender_id', '=', 'U.id')

                    ->orOn('inboxes.receiver_id', '=', 'U.id');
            })

                ->leftJoin('chat_group_members as GU', 'GU.user_id', '=', DB::raw($myId))

                ->leftJoin('chat_groups as G', 'G.id', '=', 'inboxes.group_id')

                ->leftJoin('messages as GM', function ($join) {

                    $join->on('GM.group_id', '=', 'G.id')

                        ->whereRaw('GM.id = (SELECT MAX(id) FROM messages WHERE group_id = G.id)');
                })
                ->where('inboxes.is_active', 1)

                ->with([
                    'group' => function ($query) {

                        $query->select('id', 'name', 'created_by');
                    }, 'group.members' => function ($q) {

                        $q->select('id', 'group_id', 'user_id');
                    },
                    'group.members.user' => function ($q) {

                        $q->select('id', 'name', 'user_name', 'profile');
                    }

                ])

                ->where(function ($query) use ($myId) {

                    $query->whereNull('inboxes.is_user1_trash')
                        ->orWhere('inboxes.is_user1_trash', '!=', $myId)
                        ->orWhereNull('inboxes.is_user2_trash')
                        ->orWhere('inboxes.is_user2_trash', '!=', $myId);
                })
                ->where(function ($query) use ($myId) {

                    $query->where('inboxes.sender_id', $myId)
                        ->orWhere('inboxes.receiver_id', $myId)
                        ->orWhere('GU.user_id', $myId);
                })
                ->when(!empty($request->search), function ($query) use ($request) {

                    $query->where(function ($query) use ($request) {

                        $query->where('U.user_name', 'LIKE', '%' . $request->search . '%')
                            ->orWhere('G.name', 'LIKE', '%' . $request->search . '%');
                    });
                })->whereNotExists(function ($q) use ($myId) {

                    $q->select(DB::raw(1))
                        ->from('group_user_deletes')
                        ->whereColumn('group_user_deletes.group_id', 'inboxes.group_id')
                        ->where('group_user_deletes.user_id', $myId);
                })
                ->select(
                    'inboxes.*',
                    'U.name',
                    'U.profile',
                    'U.user_name',
                    'U.id as other_user_id',
                    'U.is_active as u_is_active',
                    'G.id as group_id',
                    'G.name as group_name',
                    DB::raw('COALESCE(G.updated_at, inboxes.updated_at) as updated_at')
                )->groupBy('id')

                ->orderByDesc(DB::raw('COALESCE(G.updated_at, inboxes.updated_at)'))

                ->simplePaginate($limit);

            if (isset($threads[0]) && !empty($threads[0])) {

                $threads->each(function ($result) use ($myId) {

                    $result['unread_message_count'] =   Message::where(['inbox_id' => $result->id])->where(function ($query) use ($myId) {

                        $query->where('is_user1_trash', '!=', $myId)->orWhere('is_user2_trash', '!=', $myId);
                    })->where('sender_id', '!=', $myId)->where('isread', 0)->count();

                    if (isset($result->profile) && !empty($result->profile)) {

                        $result['profile']          =       $this->addBaseInImage($result->profile);
                    }

                    $result['last_message']         =       Message::select('id', 'message', 'sender_id', 'media', 'media_thumbnail', 'message_type', 'replied_to_message_id', 'is_user1_trash', 'is_user2_trash', 'isread')->where(['id' => $result->message_id])->first();

                    $result->time_ago               =       time_elapsed_string($result->updated_at);
                    $result->is_blocked             =       isBlockedUser($myId, $result->other_user_id);
                    $result->blocked_by             =       isBlockedUser($result->other_user_id, $myId);

                    if ($result->group && $result->group->members) {
                        $result->group->members = $result->group->members->pluck('user');
                    }
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
    public function store(GroupRequestValidation $request)
    {
        DB::beginTransaction();

        try {

            $myId                           =               Auth::id();
            $message_type                   =               $request->message_type;
            $reveiverIds                    =               explode(',', $request->receiver_id);
            if (in_array($myId, $reveiverIds)) {

                return response()->json(['status' => 422, 'message' => "You are not allowed to message yourself."], 422);
            }

            $count                          =               count($reveiverIds);
            for ($i = 0; $i < $count; $i++) {

                $blockedByOther             =               isBlockedUser($reveiverIds[$i], $myId);
                $isBlockedByMe              =               isBlockedUser($myId, $reveiverIds[$i]);
                if ($blockedByOther || $isBlockedByMe) {
                    return $this->sendResponsewithoutData(trans('message.blocked_user'), 403);
                }
            }

            if (empty($request->type) || $request->type == "") {              #--- ONE TO ONE CHAT ------#

                $reciever                       =           $request->receiver_id;
                $isExistInbox                   =           Inbox::where(function ($query) use ($myId, $reciever) {

                    $query->where(function ($subQuery) use ($myId, $reciever) {

                        $subQuery->where('sender_id', $myId)
                            ->where('receiver_id', $reciever);
                    })->orWhere(function ($subQuery) use ($myId, $reciever) {

                        $subQuery->where('receiver_id', $myId)
                            ->where('sender_id', $reciever);
                    });
                })->first();
            } else {                              #--- GROUP CHAT ------#

                $reveiverIds[]              =       $myId; // Add the current user to the group
                sort($reveiverIds); // Sort the user IDs to ensure consistent ordering
                $group                      =       ChatGroup::whereHas('members', function ($query) use ($reveiverIds) {
                    $query->whereIn('user_id', $reveiverIds)
                        ->groupBy('group_id')
                        ->havingRaw('COUNT(DISTINCT user_id) = ?', [count($reveiverIds)]);
                })->first();

                if (!$group) {
                    // Create a new group
                    $group                  =           ChatGroup::create(['created_by' => $myId]);
                    // Add users to the group
                    foreach ($reveiverIds as $userId) {
                        ChatGroupMember::create([
                            'group_id' => $group->id,
                            'user_id' => $userId,
                            'joined_at' => Carbon::now()
                        ]);
                    }
                }

                $groupId                    =       $group->id;
                $isExistInbox               =       Inbox::where(['group_id' => $groupId, 'is_active' => 1])->first();
            }

            if (empty($isExistInbox)) {
                // create new thread
                $message                      =               new Inbox();
                $message->sender_id           =               $myId;
                if (isset($groupId) && !empty($groupId)) {
                    $message->group_id        =       $groupId;
                }

                if (empty($type)) {

                    $message->receiver_id     =       $request->receiver_id;
                }

                $message->receiver_id         =       $reciever;
                $message->save();
            } else {
                //
                if ($request->type == "" || $request->type == null) {

                    if (($isExistInbox->is_user1_trash == $myId) || ($isExistInbox->is_user2_trash == $myId)) {

                        if ($isExistInbox->is_user1_trash == $myId) {

                            $isExistInbox->is_user1_trash    =   null;
                        } else {

                            $isExistInbox->is_user2_trash    =   null;
                        }
                    }

                    if (($isExistInbox->is_user1_trash == $reciever) || ($isExistInbox->is_user2_trash == $reciever)) {

                        if ($isExistInbox->is_user1_trash == $reciever) {

                            $isExistInbox->is_user1_trash    =   null;
                        } else {

                            $isExistInbox->is_user2_trash    =   null;
                        }
                    }

                    if ($isExistInbox->is_active == 0) {

                        $isExistInbox->is_active         =   1;
                    }
                    $isExistInbox->save();
                }
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

            if (isset($groupId) && !empty($groupId)) {

                $sendMessage->group_id          =                $groupId;
            }

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

            if ($request->type == "" ||    $request->type == null) {

                #send notification
                $receiver                           =               User::find($reciever);
                $sender                             =               User::find($myId);
                $message                            =               "New message from " . $sender->name;
                $data                               =               ["message" => $message, 'notification_type' => trans('notification_message.send_message_type')];
                sendPushNotificationNew($sender, $receiver, $data);
            }
            DB::commit();
            // SEND PUSH AND NOTIFICATION TO RECEIVER
            $sent_message                        =               checkLastMessage($lastMessageId, $myId);
            // dd($sent_message);
            return $this->sendResponse($sent_message, "Message send.", 200);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "sendMessage" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request,string $id)
    {
        $validate       =       Validator::make($request->all(), [

                                    'type' => 'required|between:1,2','is_group'=>'required|between:0,1']);

        if ($validate->fails()) {

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }
        $userId                     =       Auth::id();
        $type                       =       $request->type;
        $isGroup                    =       $request->is_group;

        // Fetch conversation ID based on the type
        if ($type ==1) {
            // Check if the user is a participant in the conversation
      
            $participant            =       Conversation::with(['conversation_participants'=>function($q) use($userId){
                $q->where('user_id',$userId);
            }])->where('id', $id)->whereHas('conversation_participants',function($q) use($userId){

                $q->whereIn('user_id',[$userId]);

            })->where('is_group',$isGroup)->first();
                                          
        } elseif ($type == 2) {             // check with user_id

            $authUser               =       User::find($userId);
            $participant            =       $authUser->conversations()
                                                ->where('is_group', 0) // Ensure it's a peer-to-peer conversation
                                                ->whereHas('participants', function ($query) use ($id) {
                                                    $query->where('user_id', $id);
                                                        //->where('status', 'active');
                                                })->first();
        }
        // Check if participant exists
        if (!$participant) {

            return response()->json(['message' => 'Conversation not found or user not a participant'], 400);
        }
        // Fetch messages
        $messagesQuery          = ParticipantMessage::where('conversation_id', $participant->id)

                                ->whereNotExists(function ($query) use ($userId) {
                                    $query->select(DB::raw(1))
                                        ->from('deleted_messages')
                                        ->whereColumn('deleted_messages.message_id', 'participant_messages.id')
                                        ->where('deleted_messages.user_id', $userId);
                                })
                                ->orderBy('created_at', 'asc');

        if (isset($participant->conversation_participants[0]) && !empty($participant->conversation_participants[0]->deleted_at)) {

            $messagesQuery->where('created_at', '>', $participant->conversation_participants[0]->deleted_at);

        } elseif (isset($participant->conversation_participants[0])  && !empty($participant->conversation_participants[0]->status == 'left')) {
            
            $messagesQuery->where('created_at', '<=', $participant->conversation_participants[0]->left_at);
        }
        // Include sender details and read count
        $messages = $messagesQuery->with(['sender'=>function($user){

            $user->select('id','name','user_name','profile');

        }, 'readReceipts.user'=>function($user){

            $user->select('id','name','user_name','profile');
        }])
                                ->withCount('readReceipts')
                                ->simplePaginate(20); // Adjust the pagination limit as needed
        if (isset($messages[0]) && !empty($messages[0])) {

            $messages->each(function ($result) use ($userId) {

                if (isset($result->sender->profile) && !empty($result->sender->profile)) {

                    $result->sender->profile     =       $this->addBaseInImage($result->sender->profile);
                }
                $result->time_ago                =       time_elapsed_string($result->updated_at);
                $result->sender->is_blocked      =       isBlockedUser($userId, $result->other_user_id);
                $result->sender->blocked_by      =       isBlockedUser($result->other_user_id, $userId);
                // if ($result->group && $result->group->members) {
                //     $result->group->members = $result->group->members->pluck('user');
                // }
            });
        }
        return $this->sendResponse($messages, "Message histoty.", 200);
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

    public function getInboxOLD(Request $request)
    {
        $userId = Auth::id();
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 10);
        $isGroup = $request->input('is_group', null); // null means both, true for group, false for one-to-one
        DB::enableQueryLog();
        $inboxQuery = Conversation::whereHas('participants', function ($query) use ($userId) {

            $query->where('user_id', $userId)
                ->whereIn('status', ['active', 'deleted']);
        })

            ->with(['participants.user', 'lastMessage'])

            ->leftJoinSub(function ($query) use ($userId) {

                $query->select('conversation_id', DB::raw('MAX(created_at) AS max_sent_at'))

                    ->from('participant_messages')

                    ->whereNotExists(function ($subQuery) use ($userId) {

                        $subQuery->select(DB::raw(1))

                            ->from('deleted_messages')

                            ->whereColumn('deleted_messages.message_id', 'participant_messages.id')

                            ->where('deleted_messages.user_id', $userId);
                    })
                    ->groupBy('conversation_id');
            }, 'last_message_time', function ($join) {
                $join->on('conversations.id', '=', 'last_message_time.conversation_id');
            })
            ->leftJoin('participant_messages as pm', function ($join) use ($userId) {
                $join->on('last_message_time.conversation_id', '=', 'pm.conversation_id')
                    ->on('last_message_time.max_sent_at', '=', 'pm.created_at');
            })
            ->whereNotExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('deleted_messages')
                    ->whereColumn('deleted_messages.message_id', 'pm.id')
                    ->where('deleted_messages.user_id', $userId);
            })
            ->select(
                'conversations.*',
                'pm.message AS last_message',
                'pm.message_type AS last_message_type',
                'pm.created_at AS last_message_sent_at'
            )
            ->orderByDesc('last_message_sent_at');

        // Apply search filter
        if (!empty($search)) {
            $inboxQuery->where(function ($query) use ($search) {
                $query->whereHas('participants.user', function ($query) use ($search) {
                    $query->where('user_name', 'like', '%' . $search . '%');
                });
            });
        }

        // Apply isGroup filter
        if ($isGroup !== null) {
            $inboxQuery->where('conversations.is_group', $isGroup);
        }

        // Paginate results
        $inbox = $inboxQuery->simplePaginate($perPage);

        dd(DB::getQueryLog());
        // Transform and modify results
        if ($inbox->isNotEmpty()) {

            $inbox->getCollection()->transform(function ($result) use ($userId) {
                foreach ($result->participants as $participant) {
                    $participant->is_blocked = isBlockedUser($userId, $participant->user_id);
                    $participant->blocked_by = isBlockedUser($participant->user_id, $userId);

                    if (isset($participant->user->profile)) {
                        $participant->user->profile = addBaseUrl($participant->user->profile);
                    }
                }

                $result['unread_message_count'] = $result->unreadMessagesForUser($userId)->count();

                if (isset($result->profile)) {
                    $result['profile'] = $this->addBaseInImage($result->profile);
                }

                // Fetch last message details
                $result['last_message'] = Message::select('id', 'message', 'sender_id', 'media', 'media_thumbnail', 'message_type', 'replied_to_message_id', 'is_user1_trash', 'is_user2_trash', 'isread')
                    ->where('id', $result->message_id)
                    ->first();

                $result->time_ago = time_elapsed_string($result->updated_at);

                return $result;
            });
        }

        return $this->sendResponse($inbox, "Inbox fetched successfully.", 200);
    }


    // public function getInbox(Request $request)
    // {
    //     $userId  = Auth::id();
    //     $search  = $request->input('search', '');
    //     $perPage = $request->input('per_page', 10);
    //     $isGroup = $request->input('is_group', null); // null means both, true for group, false for one-to-one
    //     $inboxQuery = Conversation::whereHas('participants', function ($query) use ($userId) {

    //         $query->where('user_id', $userId)
    //             // ->where('status', '<>', 'left');
    //             ->whereIn('status', ['active', 'deleted']); // Include 'deleted' status her

    //     })

    //         ->with(['participants' => function ($query) {
    //             $query->select('id','conversation_id','user_id','status')
    //             ->with('user:id,name,user_name,profile'); // Assuming the user's name is needed

    //         }, 'lastMessage' => function ($query) use ($userId) {

    //             $query->whereNotExists(function ($subQuery) use ($userId) {
    //                 $subQuery->select(DB::raw(1))
    //                     ->from('deleted_messages')
    //                     ->whereColumn('deleted_messages.message_id', 'participant_messages.id')
    //                     ->where('deleted_messages.user_id', $userId);
    //             });
    //         }])
    //         ->select(
    //             'conversations.*',
    //             'pm.message AS last_message',
    //             'pm.message_type AS last_message_type',
    //             'pm.created_at AS last_message_sent_at'
    //         )
    //         ->leftJoinSub(function ($query) use ($userId) {

    //             $query->select('conversation_id', DB::raw('MAX(created_at) AS max_sent_at'))

    //                 ->from('participant_messages')
    //                 ->whereNotExists(function ($subQuery) use ($userId) {
    //                     $subQuery->select(DB::raw(1))
    //                         ->from('deleted_messages')
    //                         ->whereColumn('deleted_messages.message_id', 'participant_messages.id')
    //                         ->where('deleted_messages.user_id', $userId);
    //                 })
    //                 ->groupBy('conversation_id');

    //         }, 'last_message_time', function ($join) {

    //             $join->on('conversations.id', '=', 'last_message_time.conversation_id');
    //         })
    //         ->leftJoin('participant_messages as pm', function ($join) use ($userId) {
    //             $join->on('last_message_time.conversation_id', '=', 'pm.conversation_id')
    //                 ->on('last_message_time.max_sent_at', '=', 'pm.created_at');
    //         })
    //         ->whereNotExists(function ($query) use ($userId) {
    //             $query->select(DB::raw(1))
    //                 ->from('deleted_messages')
    //                 ->whereColumn('deleted_messages.message_id', 'pm.id')
    //                 ->where('deleted_messages.user_id', $userId);
    //         })
    //         ->orderByDesc('last_message_sent_at');

    //         if (!empty($search)) {
    //             $inboxQuery->where(function ($query) use ($search) {
    //                 $query->WhereHas('participants.user', function ($query) use ($search) {
    //                           $query->where('user_name', 'like', '%' . $search . '%');
    //                       });
    //             });
    //         }
    //     if ($isGroup !== null) {

    //         $inboxQuery->where('conversations.is_group', $isGroup);

    //     }

    //     $inbox = $inboxQuery->simplePaginate($perPage);

    //     if (isset($inbox[0]) && !empty($inbox[0])) {

    //         $inbox->getCollection()->transform(function ($result) use ($userId) {

    //             if(isset($result->participants) && !empty($result->participants)){

    //                 foreach ($result->participants as $participant) {

    //                     if(isset($participant->user) && !empty($participant->user)){

    //                         $participant->is_blocked             =       isBlockedUser($userId, $participant->user_id);

    //                         $participant->blocked_by             =       isBlockedUser($participant->user_id, $userId);

    //                         if(isset($participant->user->profile) && !empty($participant->user->profile)){

    //                             $participant->user->profile     =   addBaseUrl($participant->user->profile);
    //                         }
    //                     }
    //                 }
    //             }
    //             //DB::enableQueryLog();
    //             $result['unread_message_count'] =       $result->unreadMessagesForUser($userId)->count();
    //            // dd(DB::getQueryLog());

    //             if (isset($result->profile) && !empty($result->profile)) {

    //                 $result['profile']          =       $this->addBaseInImage($result->profile);

    //             }

    //             $result['last_message']         =       Message::select('id', 'message', 'sender_id', 'media', 'media_thumbnail', 'message_type', 'replied_to_message_id', 'is_user1_trash', 'is_user2_trash', 'isread')->where(['id' => $result->message_id])->first();
    //             $result->time_ago               =       time_elapsed_string($result->updated_at);
    //             //  $result->is_blocked             =       isBlockedUser($userId, $result->other_user_id);
    //             //  $result->blocked_by             =       isBlockedUser($result->other_user_id, $userId);

    //             return $result;
    //         });
    //     }



    //     return $this->sendResponse($inbox, "Inbox fetched successfully.", 200);
    // }


    public function sendMessage(Request $request)
    {
        $myId           = Auth::id();
        $messageType    = $request->message_type;
        $receiverIds    = explode(',', $request->receiver_id);
        $type           = $request->type; // Assuming this is passed in the request
        // Check if the user is trying to message themselves
        if (in_array($myId, $receiverIds)) {

            return response()->json(['status' => 422, 'message' => "You are not allowed to message yourself."], 422);
        }

        // Check for blocked users
        foreach ($receiverIds as $receiverId) {
            if (isBlockedUser($receiverId, $myId) || isBlockedUser($myId, $receiverId)) {
                return $this->sendResponsewithoutData(trans('message.blocked_user'), 403);
            }
        }

        $senderId = $myId;
        $participantIds = array_merge([$senderId], $receiverIds);
        sort($participantIds);
        DB::transaction(function () use ($request, $senderId, $participantIds, $type) {

            $messageContent = $request->message;
            $messageType = $request->message_type;

            // Handle media and thumbnails if present
            $media = $request->hasFile('media') ? message_media($request->media, $messageType) : null;
            $mediaThumbnail = isset($request->thumbnails) && !empty($request->thumbnails) ? message_media($request->thumbnails, 10) : null;

            $conversation = null;

            if ($type === 'group') {
                // Check if a group conversation with the same participants already exists
                $conversation = Conversation::where('is_group', true)
                    ->whereHas('participants', function ($query) use ($participantIds) {
                        $query->whereIn('user_id', $participantIds)
                            ->groupBy('conversation_id')
                            ->havingRaw('COUNT(conversation_id) = ?', [count($participantIds)]);
                    })
                    ->first();

                if (!$conversation) {
                    // Create a new group conversation
                    $conversation = Conversation::create([
                        'title' => 'Group Chat',
                        'creator_id' => $senderId,
                        'is_group' => true,
                    ]);

                    foreach ($participantIds as $userId) {
                        Participant::create([
                            'conversation_id' => $conversation->id,
                            'user_id' => $userId,
                            'status' => 'active',
                        ]);
                    }
                }
            } else {
                // Check if a one-to-one conversation already exists
                $conversation = Conversation::whereHas('participants', function ($query) use ($participantIds) {
                    $query->whereIn('user_id', $participantIds)
                        ->groupBy('conversation_id')
                        ->havingRaw('COUNT(conversation_id) = ?', [count($participantIds)]);
                })->first();

                if (!$conversation) {
                    // Create a new one-to-one conversation
                    $conversation = Conversation::create([
                        'title' => 'Chat',
                        'creator_id' => $senderId,
                    ]);

                    foreach ($participantIds as $userId) {
                        Participant::create([
                            'conversation_id' => $conversation->id,
                            'user_id' => $userId,
                            'status' => 'active',
                        ]);
                    }
                } else {
                    // Reactivate a deleted conversation if necessary
                    $participant = Participant::where('conversation_id', $conversation->id)
                        ->where('user_id', $senderId)
                        ->first();

                    if ($participant && $participant->deleted_at) {

                        $participant->update(['deleted_at' => null]);
                    }
                }
            }

            // Create the new message
            ParticipantMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'message' => $messageContent,
                'message_type' => $messageType,
                'sent_at' => now(),
                'media' => $media,
                'media_thumbnail' => $mediaThumbnail,
                'lat' => $request->lat ?? null,
                'long' => $request->long ?? null,
            ]);
        });

        return response()->json(['status' => 200, 'message' => "Message sent successfully."]);
    }


    public function getChatHistoryOLD($conversationId)
    {

        $userId         =   Auth::id();
        // Check if the user is a participant in the conversation
        $participant    =   Participant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {

            $participant    =   Participant::where('conversation_id', $conversationId)
                ->where('user_id', $userId)
                ->simplePaginate(10);
            return false; // Conversation not found or user not a participant
        }
        $deletedMessageIds = DB::table('deleted_messages')

            ->where('user_id', $userId)
            ->pluck('message_id');

        $messagesQuery = Message::where('conversation_id', $conversationId)

            ->whereNotIn('id', $deletedMessageIds)
            ->orderBy('created_at', 'asc');

        if ($participant->deleted_at) {

            $messagesQuery->where('created_at', '>', $participant->deleted_at);
        } elseif ($participant->left_at && $participant->status == 'left') {

            $messagesQuery->where('created_at', '<=', $participant->left_at);
        }
        // Include sender details (assuming 'sender_id' is a foreign key to users table)
        $messages = $messagesQuery->with('sender')
            ->simplePaginate(20); // You can adjust the pagination limit as needed
        return $messages;
    }



    public function deleteThread($conversationId)
    {
        $userId         = Auth::id();
        // Find the participant record for the current user in the conversation
        $participant    = Participant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();
        if ($participant) {
            // Update the participant's status to 'deleted' and set the delete timestamp
            $participant->update([
                'status' => 'deleted',
                'deleted_at' => now(),
            ]);
        }
    }

    public function getInbox(Request $request)
    {
        $userId = Auth::id();
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 10);
        $isGroup = $request->input('is_group', null); // null means both, true for group, false for one-to-one

        // DB::enableQueryLog();
        $inboxQuery = Conversation::query()

            ->join('participants AS p', 'conversations.id', '=', 'p.conversation_id')

            ->where('p.user_id', $userId)

            ->whereIn('p.status', ['active', 'deleted'])

            ->leftJoin('participant_messages AS cm', function ($join) {

                $join->on('conversations.id', '=', 'cm.conversation_id')

                    ->whereNull('cm.deleted_at');
            })

            ->where(function ($query) use ($userId) {

                $query->where(function ($subQuery) {

                    $subQuery->where('p.status', '!=', 'deleted');
                })

                    ->orWhere(function ($subQuery) use ($userId) {

                        $subQuery->where('p.status', 'deleted')

                            ->whereExists(function ($subSubQuery) use ($userId) {

                                $subSubQuery->select(DB::raw(1))
                                    ->from('participant_messages')
                                    ->whereColumn('participant_messages.conversation_id', 'p.conversation_id')
                                    ->where('participant_messages.created_at', '>', DB::raw('p.deleted_at'))
                                    ->whereNull('participant_messages.deleted_at');
                            });
                    });
            })->with(['conversation_participants' => function ($query) {

                $query->select('id', 'conversation_id', 'user_id', 'status', 'left_at', 'deleted_at')->with('user', function ($q) {
                    $q->select('id', 'name', 'user_name', 'profile', 'is_active');
                });
            }])
            ->select(
                'conversations.*',
                DB::raw('MAX(cm.created_at) AS last_message_sent_at'),
                DB::raw('MAX(cm.message) AS last_message'),
                DB::raw('cm.id AS message_id'),
                DB::raw('MAX(cm.message_type) AS last_message_type')
            )

            ->groupBy('conversations.id')
            ->orderByDesc('last_message_sent_at');

        // Apply search filter
        if (!empty($search)) {

            $inboxQuery->where(function ($query) use ($search) {

                $query->whereHas('conversation_participants.user', function ($query) use ($search) {

                    $query->where('user_name', 'like', '%' . $search . '%');
                });
            });
        }
        // Apply isGroup filter
        if ($isGroup !== null) {

            $inboxQuery->where('conversations.is_group', $isGroup);
        }
        // Paginate results
        $inbox = $inboxQuery->simplePaginate($perPage);

        // dd(DB::getQueryLog());


        // Transform and modify results
        if ($inbox->isNotEmpty()) {
            $inbox->getCollection()->transform(function ($result) use ($userId) {
                if (isset($result->conversation_participants) && !empty($result->conversation_participants)) {

                    foreach ($result->conversation_participants as $participant) {
                        $participant->is_blocked = isBlockedUser($userId, $participant->user_id);
                        $participant->blocked_by = isBlockedUser($participant->user_id, $userId);

                        if (isset($participant->user->profile)) {
                            $participant->user->profile = addBaseUrl($participant->user->profile);
                        }
                    }
                }

                $result['unread_message_count'] = $result->unreadMessagesForUser($userId)->count();

                if (isset($result->profile)) {
                    $result['profile'] = $this->addBaseInImage($result->profile);
                }

                $result->time_ago = time_elapsed_string($result->updated_at);

                return $result;
            });
        }

        return $this->sendResponse($inbox, "Inbox fetched successfully.", 200);
    }

    public function getChatHistory($conversationId)
    {
        $userId = Auth::id();
        // Check if the user is a participant in the conversation
        $participant = Participant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {

            return response()->json(['message' => 'Conversation not found or user not a participant'], 404);
        }
        $messagesQuery = Message::where('conversation_id', $conversationId)

            ->whereNotExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('deleted_messages')
                    ->whereColumn('deleted_messages.message_id', 'messages.id')
                    ->where('deleted_messages.user_id', $userId);
            })
            ->orderBy('created_at', 'asc');

        if ($participant->deleted_at) {

            $messagesQuery->where('created_at', '>', $participant->deleted_at);

        } elseif ($participant->left_at && $participant->status == 'left') {

            $messagesQuery->where('created_at', '<=', $participant->left_at);
        }
        // Include sender details and read count
        $messages = $messagesQuery->with(['sender', 'readReceipts'])
            ->withCount('readReceipts')
            ->simplePaginate(20); // Adjust the pagination limit as needed
            
        return $messages;
    }
}
