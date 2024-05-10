<?php

namespace App\Http\Controllers\Api\FollowFollowing;

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

class FollowFollowingController extends BaseController
{

    protected $addCommunityPost, $notification, $getCommunityPost;
    public function __construct(NotificationService $notification)
    {
        $this->notification             = $notification;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //




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
    // public function store(Request $request) // add support
    // {
    //     DB::beginTransaction();
    //     try {

    //         $authId         =   Auth::id();

    //         $validation     =   Validator::make($request->all(),['user_id'=>'required|integer|exists:users,id'],['user_id.integer'=>"Invalid user"]);
    //         if($validation->fails()){

    //             return $this->sendResponsewithoutData($validation->errors()->first(), 422);

    //         }else{

    //             $userId     =   $request->user_id;

    //             //check is blocked or not
    //             $isBlocked =     BlockedUser::where(function ($query) use ($authId,$userId) {

    //                 // Check if the exact combination exists
    //                 $query->where(['user_id' => $authId, 'blocked_user_id' => $userId])
    //                       ->orWhere(['user_id' => $userId, 'blocked_user_id' => $authId]);
    //             })->exists();

    //             if($isBlocked){

    //                 return $this->sendError(trans('message.something_went_wrong'), [], 403);

    //             }
    //             //check if already follow or not 
    //             $following      =   UserFollower::where(['follower_user_id'=>$authId,'user_id'=>$request->user_id])->exists();

    //             if($following){     //delete unfollow

    //                 UserFollower::where(['follower_user_id'=>$authId,'user_id'=>$request->user_id])->delete();
    //                 decrement('users',['id'=>$request->user_id],'followers_count',1);
    //                 decrement('users',['id'=>$authId],'followings_count',1);
    //                 DB::commit();

    //                 Notification::where(['receiver_id'=>$request->user_id,'sender_id'=>$authId,'notification_type'=>trans('notification_message.started_supporting_you_type')])->delete();

    //                 $message                            =   trans('message.user.unfollow');
    //                 $action                             =   0;

    //             }else{      // follow
    //                 //check user account is public or private
    //                 $userData                           =   User::find($request->user_id);
    //                 $addFollowing                       =   new UserFollower();
    //                 $addFollowing->user_id              =   $request->user_id;
    //                 $addFollowing->follower_user_id     =   $authId;
    //                 if($userData['is_public']==0){

    //                     $addFollowing->status           =   1;

    //                 }else{

    //                     $addFollowing->status           =   2;
    //                 }

    //                 $addFollowing->save();
    //                 $action                             =   1;
    //                 increment('users',['id'=>$request->user_id],'followers_count',1);
    //                 increment('users',['id'=>$authId],'followings_count',1);
    //                 $mesage                             =   Auth::user()->name." ".trans('notification_message.started_supporting_you');
    //                 $data                               =   ['receiver'=>$request->user_id,'sender'=>$authId,'message'=>$mesage,'message_type'=>trans('notification_message.started_supporting_you_type')];

    //                 // SendNotificaionJob::dispatch($data);
    //                 DB::commit();
    //                 $message    =  trans('message.user.add_follow');
    //             }
    //             $count['action']=$action;
    //             $count['supporter'] = User::withCount('supporter')->where('id', $userId)->value('supporter_count');
    //             return $this->sendResponse($count, $message, 200);
    //         }
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error caught: "add following" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);
    //     }
    // }


    //  N E W       F U N C T I O N 
    public function store(Request $request) // add support
    {
        DB::beginTransaction();
        try {

            $authId         =   Auth::id();

            $validation     =   Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'type' => 'required|integer|between:1,2'

            ], ['user_id.integer' => "Invalid user"]);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            } else {

                $userId     =   $request->user_id;
                //check is blocked or not
                $isBlocked = BlockedUser::where(function ($query) use ($authId, $userId) {
                    // Check if the exact combination exists
                    $query->where(['user_id' => $authId, 'blocked_user_id' => $userId])
                        ->orWhere(['user_id' => $userId, 'blocked_user_id' => $authId]);

                })->exists();

                if ($isBlocked) {

                    return $this->sendError(trans('message.something_went_wrong'), [], 403);
                }

                if ($request->type == 1) {
                    //check if already follow or not 
                    $following      =   UserFollower::where(['follower_user_id' => $authId, 'user_id' => $request->user_id])->first();

                    if (isset($following)) {     //remove follow request 

                        if ($following->status == 1) {

                            UserFollower::where(['follower_user_id' => $authId, 'user_id' => $request->user_id])->delete();
                            $responseMessage             =  "Support request removed successfully";
                            $action                      =   0;
                            #remove notification
                            Notification::where(['receiver_id' => $request->user_id, 'sender_id' => $authId, 'notification_type' => 8])->delete();

                        } elseif ($following->status == 2) {  //unfollow user

                            UserFollower::where(['follower_user_id' => $authId, 'user_id' => $request->user_id])->delete();
                            decrement('users', ['id' => $request->user_id], 'followers_count', 1);
                            decrement('users', ['id' => $authId], 'followings_count', 1);

                            $responseMessage                   =   "Unfollowed successfully";
                            $action                            =   0;

                            #remove notification
                            Notification::where(['receiver_id' => $request->user_id, 'sender_id' => $authId, 'notification_type' => 7])->delete();
                            Notification::where(['receiver_id' => $authId, 'sender_id' => $request->user_id, 'notification_type' => 7])->delete();

                        }

                        DB::commit();
                    } else {
                        // follow
                        //check user account is public or private
                        $userData                           =   User::find($request->user_id);
                        $addFollowing                       =   new UserFollower();
                        $addFollowing->user_id              =   $request->user_id;
                        $addFollowing->follower_user_id     =   $authId;

                        if ($userData['is_public'] == 0) {

                            $addFollowing->status           =   1;
                            $responseMessage                =  "Support request sent successfully";
                            $type                           =  8;
                        } else {

                            $addFollowing->status           =   2;
                            $responseMessage                =  "Supported successfully";
                            $type                           =  7;
                            increment('users', ['id' => $request->user_id], 'followers_count', 1);
                            increment('users', ['id' => $authId], 'followings_count', 1);
                        }

                        $addFollowing->save();
                        $action                             =   1;

                        #send notification
                        $sender        =   Auth::user();
                        $receiver      =   User::find($request->user_id);
                        $mesage        =   $type == 7 ? $sender->name . " Start supporting you" :  $sender->name . " Requested to support you";
                        $data          =   ["message" => $mesage];
                        $this->notification->sendNotificationNew($sender, $receiver, $type, $data);


                        DB::commit();
                    }
                    $count['action'] = $action;
                    $count['supporter'] = UserFollower::where('user_id', $request->user_id)->where('status', '2')->count();
                    return $this->sendResponse($count, $responseMessage, 200);
                } elseif ($request->type == 2) {
                    $validation     =   Validator::make($request->all(), [
                        'action' => 'required|integer|between:1,2'
                    ]);
                    if ($validation->fails()) {
                        return $this->sendResponsewithoutData($validation->errors()->first(), 422);
                    }

                    $follow = UserFollower::where(['follower_user_id' => $request->user_id, 'user_id' => $authId, 'status' => 1])->first();
                    if (isset($follow)) {

                        if ($request->action == 1) {
                            $follow->status = 2;
                            $follow->save();

                            increment('users', ['id' => $request->user_id], 'followers_count', 1);
                            increment('users', ['id' => $authId], 'followings_count', 1);

                            #send notification
                            $sender        =   Auth::user();
                            $receiver      =   User::find($request->user_id);
                            $mesage        =   $sender->name . " accepted your support request";
                            $data          =   ["message" => $mesage];
                            $this->notification->sendNotificationNew($sender, $receiver, 7, $data);
                            #remove notification
                            Notification::where(['receiver_id' => $authId, 'sender_id' => $request->user_id, 'notification_type' => 8])->delete();

                            DB::commit();
                            return $this->sendResponsewithoutData("Resquest accepted successfully", 200);
                        } else {
                            $follow->delete();
                            #remove notification
                            Notification::where(['receiver_id' => $authId, 'sender_id' => $request->user_id, 'notification_type' => 8])->delete();
                            DB::commit();
                            return $this->sendResponsewithoutData("Resquest rejected successfully", 200);
                        }
                    } else {
                        return $this->sendError(trans('message.something_went_wrong'), [], 403);
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "add following" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
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


    public function blockUser(Request $request)
    {

        DB::beginTransaction();
        try {

            $authId         =   Auth::id();
            $validation     =   Validator::make($request->all(), ['user_id' => 'required|integer|exists:users,id'], ['user_id.*' => "Invalid user"]);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            } else {

                $hasBlocked     =   BlockedUser::where(['user_id' => $authId, 'blocked_user_id' => $request->user_id])->exists();

                if ($hasBlocked) { //already blocked

                    return $this->sendError(trans("message.already_blocked"), [], 400);
                } else {

                    $blockUser                      =   new BlockedUser();
                    $blockUser->user_id             =   $authId;
                    $blockUser->blocked_user_id      =  $request->user_id;
                    $blockUser->save();
                    DB::commit();
                }
                return $this->sendResponsewithoutData(trans('message.user_blocked'), 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "block-user" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #---------------         R E P O R T       T O     U S E R       --------------------#
    public function reportUser(Request $request)
    {

        DB::beginTransaction();

        try {

            $authId         =   Auth::id();
            $validation     =   Validator::make($request->all(), ['user_id' => 'required|integer|exists:users,id', 'reason' => 'nullable|string'], ['user_id.*' => "Invalid user"]);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            } else {

                $hasReported     =   ReportedUser::where(['reporter_id' => $authId, 'reported_user_id' => $request->user_id])->exists();

                if ($hasReported) { //already blocked

                    return $this->sendError(trans("message.already_reported"), [], 400);
                } else {

                    $reportUser                         =   new ReportedUser();
                    $reportUser->reporter_id            =   $authId;
                    $reportUser->reported_user_id       =   $request->user_id;

                    if (isset($request->reason) && !empty($request->reason)) {

                        $reportUser->reason             =  $request->reason;
                    }
                    $reportUser->save();
                    DB::commit();
                }
                return $this->sendResponsewithoutData(trans('message.user_reported'), 200);
            }
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Error caught: "report-user" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------         R E P O R T       T O     U S E R       --------------------#

}
