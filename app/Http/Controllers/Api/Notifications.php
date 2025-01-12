<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Models\Inbox;
use App\Models\Participant;
use Carbon\Carbon;
use App\Traits\postCommentLikeCount;

class Notifications extends BaseController
{
    use postCommentLikeCount;
    //
    // public function notifications(Request $request){
    //     DB::beginTransaction();
    //     try {

    //         $limit      =   10;

    //         if(isset($request->limit) && !empty($request->limit)){

    //             $limit  =   $request->limit;

    //         }
    //         $authUser       =   Auth::user();
           
    //         $notifications  = Notification::with(['sender' => function ($query) {

    //             $query->select('id', 'name', 'profile');

    //         }])
    //         ->where(['receiver_id'=>$authUser->id])
    //         ->orderByDesc('id')
    //         ->simplePaginate($limit);

    //         $notifications->each(function ($notification) {

    //             if (isset($notification->sender->profile) && !empty($notification->sender->profile)) {

    //                 $notification->sender->profile = asset('storage/'.$notification->sender->profile);

    //             }
    //         });
    //         DB::commit();
    //         // $notification=  Notification::where(['receiver_id'=>$authUser->id])->simpplePaginate($limit);
    //         return $this->sendResponse($notifications, trans('message.notifications'), 200);
    //     } catch (Exception $e) {

    //         DB::rollback();
    //         Log::error('Error caught: "notifications" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);            
    //     }
    // }

    // public function notifications(Request $request){

    //     try {
            
    //         $userID         =   Auth::id();
    //         $limit          =   10;
    //         $today_time     =   Carbon::now()->format('Y-m-d');
    //         $yesterday      =   Carbon::now()->subDays()->format('Y-m-d');
    //         if(isset($request->limit) && !empty($request->limit)){

    //             $limit      =   $request->limit;
    //         }
    //         $final_array    =   array();
    //         $final_notification= [];
            
    //         $notifications  =   Notification::selectRaw("DATE_FORMAT(updated_at,'%Y-%m-%d') AS notification_on")->where(['receiver_id'=>$userID,'status'=>1])->groupByRaw("notification_on")->orderByRaw("notification_on DESC")->paginate($limit);

    //         // dd($notifications);
            
    //         if(isset($notifications) && !empty($notifications)){

    //             for($i=0;$i<count($notifications); $i++){

    //                 $dateNotifications  =       Notification::where(['receiver_id'=>$userID,'status'=>1])->with(['sender'=>function($query) {

    //                 $query->select('id','name','user_name','email','profile'); 

    //                 }])->whereDate('created_at', '=', $notifications[$i]['notification_on'])->get();

    //                 if(isset($dateNotifications) && !empty($dateNotifications)){

    //                     for ($j=0; $j < count($dateNotifications); $j++) { 
                            

    //                         if(isset($dateNotifications[$j]['sender']) && !empty($dateNotifications[$j]['sender'])){

    //                             $dateNotifications[$j]['sender']['profile']   =  $this->addBaseInImage($dateNotifications[$j]['sender']['profile']);

    //                         }
    //                         Notification::where(['id'=>$dateNotifications[$j]['id']])->update(['is_read'=>1]);
    //                     }
    //                 }
                    
    //                 if($notifications[$i]['notification_on']== $today_time){

    //                     $notifications[$i]['notification_on'] =  "Today"; 

    //                 }elseif($notifications[$i]['notification_on']== $yesterday){
                        
    //                     $notifications[$i]['notification_on'] =  "Yesterday"; 

    //                 }
    //                 $notifications[$i]['notification']= $dateNotifications;
    //             }
    //         }

    //         //get unread notification
    //         $unreadNotification    =   Notification::where(['receiver_id'=>$userID,'is_read'=>0])->count();


    //         return response()->json(['message'=>'All notifications','data'=>$notifications,'unread_notification'=>$unreadNotification,'status'=>200]);

    //     } catch (Exception $e) {
            
    //         DB::rollback();
    //         Log::error('Error caught: "notifications" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);            

    //     }
    // }

    public function notificationsOLD(Request $request)
    {
        try {
            $userID     = Auth::id();
            $perPage    = 10;
            if(isset($request->limit) && !empty($request->limit)){

                $perPage    = $request->limit;
            }
    
            $todayDate = Carbon::now()->format('Y-m-d');
            $yesterdayDate = Carbon::yesterday()->format('Y-m-d');
    
            // Retrieve grouped notifications with pagination
            $notifications = Notification::selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d') AS notification_on")
                ->where(['receiver_id' => $userID, 'status' => 1])
                ->groupBy('notification_on')
                ->orderBy('notification_on', 'DESC')
                ->simplePaginate($perPage);

            // Mark unread notifications as read and load sender profiles
            $notifications->each(function ($group) use ($userID ,$todayDate, $yesterdayDate)  {

                $notificationDate = $group->notification_on;
                $dateNotifications = Notification::where('receiver_id', $userID)
                    ->where('status', 1)
                    ->whereDate('created_at', $notificationDate)
                    ->with('sender:id,name,user_name,email,profile')
                    ->orderBy('id', 'DESC')
                    ->get();
    
                $dateNotifications->each(function ($notification) {
                    $notification->sender->profile = $this->addBaseInImage($notification->sender->profile);
                    $notification->update(['is_read' => 1]);
                    $notification->time_ago = time_elapsed_string($notification->created_at);
                });
    
                $group->notification = $dateNotifications;
                $group->notification_on = $this->formatNotificationDate($notificationDate, $todayDate, $yesterdayDate);
                // dd($group);
            });
    
            // Get count of unread notifications
            $unreadNotificationCount = Notification::where('receiver_id', $userID)
                ->where('is_read', 0)
                ->count();
    
            return response()->json([
                'message' => trans('message.notifications'),
                'data' => $notifications,
                'unread_notification_count' => $unreadNotificationCount,
                'status' => 200
            ]);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "notifications" ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    
    // Helper method to format notification date
    private function formatNotificationDate($notificationDate, $todayDate, $yesterdayDate)
    {
        if ($notificationDate === $todayDate) {
            return 'Today';
        } elseif ($notificationDate === $yesterdayDate) {
            return 'Yesterday';
        } else {
            return $notificationDate;
        }
    }


    public function notifications(Request $request)
    {
    try {
        $userID = Auth::id();
        
        $perPage = $request->get('limit', 10);

        $today = Carbon::now()->startOfDay();
        $yesterday = Carbon::yesterday()->startOfDay();
        $last7Days = Carbon::now()->subDays(7)->startOfDay();
        $last30Days = Carbon::now()->subDays(30)->startOfDay();

        // Retrieve notifications with pagination and eager load sender
        $notifications = Notification::where('receiver_id', $userID)->whereHas('sender')
            ->where('status', 1)
            ->orderBy('created_at', 'DESC')
            ->with(['sender:id,name,user_name,email,profile','invitation:id,role,accepted'])  // Eager load sender details
            ->simplePaginate($perPage);

           

        // Group notifications
        $groupedNotifications = $notifications->getCollection()->groupBy(function ($date) use ($today, $yesterday, $last7Days, $last30Days) {
            $notificationDate = Carbon::parse($date->created_at)->startOfDay();
            if ($notificationDate->equalTo($today)) {
                return 'Today';
            } elseif ($notificationDate->equalTo($yesterday)) {
                return 'Yesterday';
            } elseif ($notificationDate->greaterThanOrEqualTo($last7Days)) {
                return 'Last 7 Days';
            } elseif ($notificationDate->greaterThanOrEqualTo($last30Days)) {
                return 'Last 30 Days';
            } else {
                return 'Older';
            }
        });

        // Process each group
        $groupedNotifications = $groupedNotifications->map(function ($group, $key) use ($userID) {
            return [
                'notification_on' => $key,
                'notifications' => $group->map(function ($notification) use ($userID) {
                    $notification->sender->profile = $this->addBaseInImage($notification->sender->profile);
                   // $notification->update(['is_read' => 1]);
                    return [
                        'id' => $notification->id,
                        'receiver_id' => $notification->receiver_id,
                        'sender_id' => $notification->sender_id,
                        'notification_type' => $notification->notification_type,
                        'is_read' => $notification->is_read,
                        'message' => $notification->message,
                        'like_id' => $notification->like_id,
                        'community_member_id' => $notification->community_member_id,
                        'user_plan_id' => $notification->user_plan_id,
                        'comment_like_id' => $notification->comment_like_id,
                        'status' => $notification->status,
                        'created_at' => $notification->created_at,
                        'updated_at' => $notification->updated_at,
                        'community_id' => $notification->community_id,
                        'post_id' => $notification->post_id,
                        'comment_id' => $notification->comment_id,
                        'mention_id' => $notification->mention_id,
                        'parent_id' => $notification->parent_id,
                        'invitation_id' => $notification->invitation_id,
                        'invitation' => $notification->invitation,
                        'time_ago' => time_elapsed_string($notification->created_at),
                        'sender' => $notification->sender,  // Include sender details
                    ];
                })
            ];
        });

        // dd($groupedNotifications);

        // Get count of unread notifications
       

        // Return response
        return response()->json([
            'status' => 200,
            'message' => trans('message.notifications'),
            'data' => [
                'current_page' => $notifications->currentPage(),
                'data' => $groupedNotifications->values(), // Ensure indexed array
                'first_page_url' => $notifications->url(1),
                'from' => $notifications->firstItem(),
                'next_page_url' => $notifications->nextPageUrl(),
                'path' => $notifications->path(),
                'per_page' => $notifications->perPage(),
                'prev_page_url' => $notifications->previousPageUrl(),
                'to' => $notifications->lastItem(),
            ],
            'unread_notification_count' => notification_count(),
        ]);
    } catch (Exception $e) {
        Log::error('Error caught: "notifications" ' . $e->getMessage());
        return response()->json(['message' => $e->getMessage()], 400);
    }
}
    


    #-------------  R E A D          N O T I F I C A T I O N        A P I  -------------------#
    public function readNotification(Request $request){

        DB::beginTransaction();
        try {
            
            $validator                 =        Validator::make($request->all(), ['type'=>"required|integer|between:1,2","action"=>'required|integer|between:0,1','notification_id' => 'nullable|integer|exists:notifications,id','thread_id' => 'required_if:type,2|integer|exists:inboxes,id']);

            if ($validator->fails()) {
    
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {
            
                $authId                     =       Auth::id();

                // dd($authId);

                $action                     =       $request->action;    

                if($request->type == 1){                //notifications

                    $notificationId         =       $request->input('notification_id',null);
    
                    if(empty($notificationId)){         // read all notifications

                        $readNotifications   =      Notification::where(['receiver_id'=>$authId,'status'=>1,'is_read'=>0])->update(['is_read'=>1]);

                    }else{

                        $exists              =      Notification::where(['id'=>$request->notification_id,'receiver_id'=>$authId,'status'=>1])->first();
                        if(isset($exists) && !empty($exists)){
            
                            $exists->is_read    =   $action;
                            $exists->save();
        
                        }else{
        
                            return response()->json(['message' => trans('message.Invalid_notification')], 409);
                        }
                    }
                }else{  
                                            //message
                    $thread_id                  =   $request->input('thread_id',null);

                    if(isset($thread_id) && !empty($thread_id)){

                        $isExist                =       Inbox::where(['id'=>$thread_id])->where(function($query) use($authId){

                                                            $query->where(['sender_id'=>$authId])

                                                            ->orWhere(['receiver_id'=>$authId]);

                                                        })->first();
                                                       
                        if(isset($isExist) && !empty($isExist)){

                            if($isExist->sender_id==$authId){

                                $isExist->user1_unread      =   ($request->action==0)?$authId:null;

                            }else{

                                $isExist->user2_unread      =   ($request->action==0)?$authId:null;$authId;
                            }

                            $isExist->save();
                        }


                        //  for group chat table new function
                        // $isExist        =   Participant::where(['conversation_id'=>$thread_id,'user_id'])->first();

                        // if(isset($isExist) && !empty($isExist)){

                        //     $isExist->unread_thread     =   $action;

                        //     $isExist->save();

                        // } 
                    }else{

                        return response()->json(['message' => trans('message.Invalid_thread')], 409);

                    }
                }
                DB::commit();
                $notification_count          =   notification_count();
                return response()->json([
                    'status'                    => 200,
                    'message'                   => trans("message.read_notification"),
                    'data'                      => $action,
                    'notification'              => $notification_count,
                ]);
            }
        } catch (Exception $e) {
            
            Log::error('Error caught: "readNotification" ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    #-------------  R E A D          N O T I F I C A T I O N        A P I  -------------------#








}
