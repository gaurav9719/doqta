<?php

namespace App\Jobs\ChatNotification;

use Exception;
use App\Models\UserDevice;
use App\Models\Participant;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use App\Models\ConversationMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ChatNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $sender,$conversationId,$notification_data;

    /**
     * Create a new job instance.
     */
    public function __construct($sender, $conversationId, $notification_data)
    {
        //
        $this->sender                    =   $sender;
        $this->conversationId                  =   $conversationId;
        $this->notification_data              =   $notification_data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $participants       =   Participant::where('conversation_id',$this->conversationId)->where('user_id','<>',$this->sender)->whereNull('left_at')->get();
            $conversation       =   Conversation::find($this->conversationId);

            if(isset($conversation) && !empty($conversation)){

                if(isset($participants[0]) && !empty($participants[0])){

                    foreach ($participants as  $participant) {
                       
                        $userDevices        =   UserDevice::where('user_id',$participant->user_id)->get();

                        if (isset($userDevices) && !empty($userDevices[0])) {

                            foreach ($userDevices as $userDevice) {
            
                                if (isset($userDevice['device_token']) && !empty($userDevice['device_token'])) {
            
                                    if ($userDevice['device_type'] == 1) {        // call ios function
            
                                        IosPush($userDevice['device_token'], $this->notification_data['message'], $this->notification_data['notification_type'], $this->notification_data, $mood_icon = '');
                                        
                                    } elseif ($userDevice['device_type'] == 2) {     // call andriod function
            
                                        //  androidPushNotification($userData['device_token'] ,$notification['message'], $notification['notification_type'], $notification);
            
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {

            Log::error('groupChatNotification : ' . $e->getMessage());
        }
    }
}
