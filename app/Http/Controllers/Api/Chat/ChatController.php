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

class ChatController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
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

            return $this->sendResponse($threads, "Inbox.", 200);

        } catch (Exception $e) {

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
    public function show(string $id,Request $request) #------- S H O W       C H A T          H I S T O R Y -----------#
    {
        //
        try {
            if(empty($id)){

                return $this->sendError("user_id required", [], 422);

            }else{
              
                if (isset($request->limit) && !empty($request->limit)) {

                    $limit                      =               $request->limit;
                }

                $myId                           =               Auth::id();

                $reciever                       =               $request->receiver_id;

                if ($myId == $reciever) {

                    return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 422);

                } else {

                    $inbox                    =              Inbox::where(function ($query) use ($myId, $reciever) {

                        $query->where(['sender_id' => $myId, 'receiver_id' => $reciever])
                            ->orWhere(['receiver_id' => $myId, 'sender_id' => $reciever]);

                    })->first();

                    if(isset($inbox) && !empty($inbox)){

                        $inboxId              =             $inbox->id;

                        // Message::where('')


                    }

                    




                    // $messages->setCollection($messages->getCollection()->reverse()->values());
                    // return $this->sendResponse($messages, "Chat History.", 200);
                }
                
            }
        } catch (Exception $e) {
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
}
