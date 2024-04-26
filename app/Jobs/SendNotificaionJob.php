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

    public function __construct($data)
    {
        $this->notificatonData      =   $data;

      
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notification): void
    {
        
        try {
            $receiver = User::findOrFail($this->notificatonData['receiver'], ['id', 'name']);
            $sender = User::findOrFail($this->notificatonData['sender'], ['id', 'name']);
            $messageType = $this->notificatonData['message_type'];
            $message = $this->notificatonData['message'];

            $notification->sendNotification($receiver, $sender, $message, $messageType);
        } catch (\Exception $e) {
            Log::error('Error sending notification: ' . $e->getMessage());
            // Optionally handle the error further or notify administrators
        }
    }
}
