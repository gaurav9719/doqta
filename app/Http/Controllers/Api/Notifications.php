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

class Notifications extends BaseController
{
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

    public function notifications(Request $request){

        try {
            
            $userID         =   Auth::id();
            $limit          =   10;
            $today_time     =   Carbon::now()->format('Y-m-d');
            $yesterday      =   Carbon::now()->subDays()->format('Y-m-d');
            if(isset($request->limit) && !empty($request->limit)){

                $limit      =   $request->limit;
            }
            $final_array    =   array();
            $final_notification= [];
            
            $notifications  =   Notification::selectRaw("DATE_FORMAT(updated_at,'%Y-%m-%d') AS notification_on")->where(['receiver_id'=>$userID,'status'=>1])->groupByRaw("notification_on")->orderByRaw("notification_on DESC")->paginate($limit);
            
            if(isset($notifications) && !empty($notifications)){

                for($i=0;$i<count($notifications); $i++){

                    $dateNotifications  =       Notification::where(['receiver_id'=>$userID,'status'=>1])->with(['sender'=>function($query) {

                    $query->select('id','name','user_name','email','profile'); 

                    }])->whereDate('created_at', '=', $notifications[$i]['notification_on'])->get();

                    if(isset($dateNotifications) && !empty($dateNotifications)){

                        for ($j=0; $j < count($dateNotifications); $j++) { 
                            

                            if(isset($dateNotifications[$j]['sender']) && !empty($dateNotifications[$j]['sender'])){

                                $dateNotifications[$j]['sender']['profile']        = asset('storage/'.$dateNotifications[$j]['sender']['profile']);
                            }
                        }
                    }
                    
                    if($notifications[$i]['notification_on']==$today_time){

                        $notifications[$i]['notification_on'] =  "Today"; 

                    }elseif($notifications[$i]['notification_on']==$yesterday){
                        
                        $notifications[$i]['notification_on'] =  "Yesterday"; 

                    }
                    $notifications[$i]['notification']= $dateNotifications;

                }
            }
            return response()->json(['message'=>'All notifications','data'=>$notifications,'status'=>200]);

        } catch (Exception $e) {
            
            DB::rollback();
            Log::error('Error caught: "notifications" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);            

        }
    }
}
