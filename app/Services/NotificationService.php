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
}
