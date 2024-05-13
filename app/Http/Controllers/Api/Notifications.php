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

    public function notifications(Request $request)
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
                    ->get();
    
                $dateNotifications->each(function ($notification) {
                    $notification->sender->profile = $this->addBaseInImage($notification->sender->profile);
                    $notification->update(['is_read' => 1]);
                });
    
                $group->notification = $dateNotifications;
                $group->notification_on = $this->formatNotificationDate($notificationDate, $todayDate, $yesterdayDate);
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
    
}
