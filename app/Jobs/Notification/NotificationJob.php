<?php

namespace App\Jobs\Notification;

use Exception;
use App\Models\Group;
use App\Models\UserDevice;
use App\Models\GroupMember;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $sender,$userData,$notification;

    /**
     * Create a new job instance.
     */
    public function __construct($sender, $userData, $notification)
    {
        //
        $this->sender                    =   $sender;
        $this->userData                  =   $userData;
        $this->notification              =   $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        try {

           $userDevices     =   UserDevice::where(['user_id' => $this->userData['id']])->get();

            if (isset($userDevices) && !empty($userDevices[0])) {

                foreach ($userDevices as $userDevice) {

                    if (isset($userDevice['device_token']) && !empty($userDevice['device_token'])) {

                        if ($userDevice['device_type'] == 1) {        // call ios function

                            IosPush($this->userData['device_token'], $this->notification['message'], $this->notification['notification_type'], $this->notification, $mood_icon = '');
                            
                        } elseif ($userDevice['device_type'] == 2) {     // call andriod function

                            //  androidPushNotification($userData['device_token'] ,$notification['message'], $notification['notification_type'], $notification);

                        }
                    }
                }
            }
        } catch (Exception $e) {

            Log::error('Error : ' . $e->getMessage());
        }
    }
}
