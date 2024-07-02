<?php

namespace App\Jobs;

use Exception;
use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FeedPostNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $notificatonData, $notification;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        //
        $this->notificatonData      =   $data;
        // log::info($this->notificatonData);
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notification)
    {
        try {

            $groupId                    =   $this->notificatonData['group_id'];

            $postId                     =   $this->notificatonData['post_id'];

            $sender                     =   $this->notificatonData['sender'];

            $post                       =   Post::select('title')->where('id', $postId)->first();

            $allMembers                 =   GroupMember::where(['group_id' => $groupId, 'is_active' => 1])->where('user_id', "<>", $sender['id'])
                                           
                                            ->whereHas('groupUser', function ($query) {

                                                $query->where('is_active', 1);

                                            })->get();

            $group                      =   Group::where(['id' => $groupId])->first();

            $type                       =   trans('notification_message.posted_in_community');

            if (isset($allMembers) && !empty($allMembers)) {

                $data                           =  [];

                foreach ($allMembers as $member) {

                    $receiver                                   =       User::where(['id' => $member->user_id, 'is_active' => 1])->first();

                    if (isset($receiver) && !empty($receiver)) {

                        $message                                =      "New post in **{$group->name}**:  {$post['title']}";

                        if (isset($message) && !empty($message)) {
                            // Create a new notification
                            $notification                       =   new Notification();
                            $notification->receiver_id          =   $receiver['id'];
                            $notification->sender_id            =   $sender['id'];
                            $notification->post_id              =   $postId;
                            $notification->community_id         =   $groupId;
                            $notification->notification_type    =   $type;
                            $notification->message              =   $message;
                            $notification->save();
                            $lastNotification                   =   $notification->id;
                            $notification                       =   Notification::find($lastNotification);

                            sendPushNotificationNew($sender, $receiver, $notification);
                            
                        }
                    }
                }
            }
        } catch (Exception $e) {

            Log::error('Error : ' . $e->getMessage());
        }
    }
}
