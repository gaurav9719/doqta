<?php

namespace App\Http\Controllers\Api\ChatController;

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

            })->where('inboxes.is_active',1)
            
            ->where(function ($query) use($myId){

                $query->where(function ($query) use($myId) {

                    $query->whereNull('is_user1_trash')
                          ->orWhere('is_user1_trash', '!=', $myId);
                })
                ->where(function ($query) use($myId) {
                    $query->whereNull('is_user2_trash')
                          ->orWhere('is_user2_trash', '!=', $myId);
                });

            })
            ->when(!empty($request->search), function ($query) use ($data) {
                // Filtering based on the first_name column of the 'users' table
                return $query->where('U.user_name', 'LIKE', '%' . $data['search'] . '%');

            })

            ->where(function ($query) use ($myId) {
                // Filter the threads where I am the sender or receiver
                $query->where('inboxes.sender_id', '=', $myId);
                $query->orWhere('inboxes.receiver_id', '=', $myId);
            })
            ->select('inboxes.*', 'U.name', 'U.profile', 'U.user_name', 'U.id as other_user_id', 'U.is_active as u_is_active')
            ->orderByDesc('inboxes.updated_at') // Order by 'updated_at' column, DESCENDING
            ->simplePaginate($limit); // Paginate the results


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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
}
