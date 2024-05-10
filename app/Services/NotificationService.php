<?php

namespace App\Services;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Notification;
use App\Models\UserDevice;
use App\Models\User;
use Illuminate\Support\Facades\Log;
/**
 * Class NotificationService.
 */
class NotificationService
{
    public function sendNotification($receiver, $sender, $message, $section) {
        DB::beginTransaction();
        try {
            // Create a new notification instance
            $notification                       = new Notification();
            $notification->receiver_id          = $receiver->id;
            $notification->sender_id            = $sender->id;
            $notification->notification_type    = $section;
            $notification->message              = $message;

            if ($notification->save()) {
                // Get the device tokens for the receiver
                //get notification data
                $notification_data      =           Notification::where('id',$notification->id)->first();
                $deviceTokens           =           UserDevice::where('user_id', $receiver->id)->get();
                // Handle sending notifications to each device
                foreach ($deviceTokens as $token) {
                    // $devtoken              =   $token->device_token;
                    if ($token->device_type == 1) {
                        // Handle iOS device notification
                        //IosPush($token,$message,$section,$notification_data);
                        // You can add your iOS notification logic here
                    } else {
                        // Handle Android device notification
                        // You can add your Android notification logic here
                    }
                }
                DB::commit();
                return ['status' => 200, 'message' => "success"];
            } else {
                DB::rollback();
                Log::error('Error caught: "notifications FAILED" ' );
                return ['status' => 400, 'message' => "failed"];
            }
        } catch (Exception $e) {
            Log::error('Error caught: "notifications" ' . $e->getMessage());
            DB::rollback();
            return ['status' => 400, 'message' => "failed"];
        }
    }
    
    public function pushNotificationOnly($receiver,$message, $section) {
        try {
            // Create a new notification instance
                // Get the device tokens for the receiver
                //get notification data
                $deviceTokens           =           UserDevice::where('user_id', $receiver->id)->get();
                // Handle sending notifications to each device
                foreach ($deviceTokens as $token) {

                    $token              =   $token->device_token;

                    if ($token->device_type == 1) {
                        // Handle iOS device notification
                        //IosPush($token,$message,$section,$notification_data);
                        // You can add your iOS notification logic here
                    } else {
                        // Handle Android device notification
                        // You can add your Android notification logic here
                    }
                }

                return ['status' => 200, 'message' => "success"];

        } catch (Exception $e) {
            return ['status' => 400, 'message' => "failed"];
        }
    }



 public function sendNotificationNew($sender, $receiver, $type, $data) {

        DB::beginTransaction();
        try {
            #1=Document verified
            #2=Document not verified
            #3=Profile not complete
            #6=Password changed
            #7=Started supporting / Accepted support request
            #8=Requested to support
            #16=Post share #-------- NEED POST ID----------#
            #17=Support new member
            #18=Message
            if($type == 1 || $type == 2 || $type == 3 || $type == 6 || $type == 7 || $type == 8 || $type == 16 || $type == 17 || $type == 18){
                if(isset($data['message'])){
                    // Create a new notification
                    $notification                       =   new Notification();
                    $notification->receiver_id          =   $receiver['id'];
                    $notification->sender_id            =   $sender['id'];
                    $notification->notification_type    =   $type;
                    $notification->message              =   $data['message'];

                }else{

                    //return ['status' => 400, 'message' => "failed type 1/2/3/6/7/8/16/17/18 notification"];
                }
            }
            #4=Plan activated
            #5=Plan expired
            elseif($type == 4 || $type == 5){
                if(isset($data['message']) && isset($data['user_plan_id'])){
                    // Create a new notification
                    $notification                       =   new Notification();
                    $notification->receiver_id          =   $receiver['id'];
                    $notification->sender_id            =   $sender['id'];
                    $notification->user_plan_id         =   $data['user_plan_id'];
                    $notification->notification_type    =   $type;
                    $notification->message              =   $data['message'];

                }else{
                   // return ['status' => 400, 'message' => "failed type 4/5 notification"];
                }
            }
            #9=Joined the community
            elseif($type == 9){
                if(isset($data['message']) && isset($data['community_member_id']) && isset($data['community_id'])){
                    // Create a new notification
                    $notification                       =   new Notification();
                    $notification->receiver_id          =   $receiver['id'];
                    $notification->sender_id            =   $sender['id'];
                    $notification->community_member_id  =   $data['community_member_id'];
                    $notification->community_id         =   $data['community_id'];
                    $notification->notification_type    =   $type;
                    $notification->message              =   $data['message'];

                }else{
                   // return ['status' => 400, 'message' => "failed type 9 notification"];
                }
            }
            #10=Posted in community
            #15=Reposted the post 
            elseif($type == 10 || $type == 15){
                if(isset($data['message']) && isset($data['post_id']) && isset($data['community_id'])){
                    // Create a new notification
                    $notification                       =   new Notification();
                    $notification->receiver_id          =   $receiver['id'];
                    $notification->sender_id            =   $sender['id'];
                    $notification->post_id              =   $data['post_id'];
                    $notification->community_id         =   $data['community_id'];
                    $notification->notification_type    =   $type;
                    $notification->message              =   $data['message'];

                }else{
                    //return ['status' => 400, 'message' => "failed type 10/15 notification"];
                } 
            }
            #11=Post like
            elseif($type == 11){
                if(isset($data['message']) && isset($data['like_id']) && isset($data['post_id']) && isset($data['community_id'])){
                    // Create a new notification
                    $notification                       =   new Notification();
                    $notification->receiver_id          =   $receiver['id'];
                    $notification->sender_id            =   $sender['id'];
                    $notification->like_id              =   $data['like_id'];
                    $notification->post_id              =   $data['post_id'];
                    $notification->community_id         =   $data['community_id'];
                    $notification->notification_type    =   $type;
                    $notification->message              =   $data['message'];

                }else{
                    //return ['status' => 400, 'message' => "failed type 11 notification"];
                } 
            }
            #12=Comment 
            #14=Comment reply
            elseif($type == 12 || $type == 14){
                if(isset($data['message']) && isset($data['comment_id']) && isset($data['post_id']) && isset($data['community_id'])){
                    // Create a new notification
                    $notification                       =   new Notification();
                    $notification->receiver_id          =   $receiver['id'];
                    $notification->sender_id            =   $sender['id'];
                    $notification->comment_id           =   $data['comment_id'];
                    $notification->post_id              =   $data['post_id'];
                    $notification->community_id         =   $data['community_id'];
                    $notification->notification_type    =   $type;
                    $notification->message              =   $data['message'];

                }else{
                   // return ['status' => 400, 'message' => "failed type 12/14 notification"];
                } 
            }
            #13=Comment like
            elseif($type == 13){
                if(isset($data['message']) && isset($data['comment_like_id']) && isset($data['comment_id']) && isset($data['post_id']) && isset($data['community_id'])){
                    // Create a new notification
                    $notification                       =   new Notification();
                    $notification->receiver_id          =   $receiver['id'];
                    $notification->sender_id            =   $sender['id'];
                    $notification->comment_like_id      =   $data['comment_like_id'];
                    $notification->comment_id           =   $data['comment_id'];
                    $notification->post_id              =   $data['post_id'];
                    $notification->community_id         =   $data['community_id'];
                    $notification->notification_type    =   $type;
                    $notification->message              =   $data['message'];

                }else{
                   // return ['status' => 400, 'message' => "failed type 13 notification"];
                } 
            }
            else{
                return ['status' => 400, 'message' => "Somthing went wrong"];
            }
            if ($notification->save()){
                
                $notification_data              =   Notification::find($notification->id);
                sendPushNotificationNew($sender, $receiver, $notification_data);
                DB::commit();
                return ['status' => 200, 'message' => "success"];
            }
            else{
                DB::rollback();
                Log::error('Error caught: "notifications FAILED" ' );
                return ['status' => 400, 'message' => "failed 1"];
            }
        } catch (Exception $e) {
            Log::error('Error caught: "notifications" ' . $e->getMessage());
            DB::rollback();
            return ['status' => 400, 'message' => "failed"];
        }
    }














}
