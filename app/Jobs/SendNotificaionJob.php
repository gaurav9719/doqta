<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class SendNotificaionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
   
    /**
     * Create a new job instance.
     */
    protected $notificatonData,$notification;

    public function __construct($data,NotificationService $notification)
    {
        $this->notificatonData      =   $data;
        $this->notification         =   $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $receiver        =          User::find($this->notificatonData->receiver, ['id','name']);
        $sender          =          User::find($this->notificatonData->sender, ['id','name']);
        $message_type    =           $this->notificatonData->message_type;
        $message         =           $this->notificatonData->message;
        $this->notification->sendNotification($receiver,$sender,$message,$message_type);     // send notification 
        Log::error('Error caught: "sendNotificationJobs"');
    }
}
