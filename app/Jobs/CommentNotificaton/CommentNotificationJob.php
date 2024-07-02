<?php

namespace App\Jobs\CommentNotificaton;

use Exception;
use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CommentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $sender,$userData,$notificatonData,$notificationType;

    /**
     * Create a new job instance.
     */
    public function __construct($sender, $notificatonData,$notification_type)
    {
        //
        $this->sender                       =   $sender;
        $this->notificatonData              =   $notificatonData;
        $this->notificationType             =   $notification_type;

    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $groupId            =       $this->notificatonData['community_id'];
            $sender             =       $this->sender;
            $postId             =       $this->notificatonData['post_id'];
        
            // Fetch post title and group name in a single query using eager loading
            $post = Post::select('title')

                    ->where('id', $postId)
                    
                    ->with(['comment' => function ($query) {

                        $query->groupBy('user_id')->select('user_id');

                    }, 'group' => function ($query) use ($groupId) {

                        $query->where('id', $groupId)->select('id', 'name');

                    }])->first();
        
            if (!$post) {

                throw new Exception('Post not found.');
            }
        
            $group                  =      $post->group;

            $commentUsers           =      $post->comments->pluck('user_id');
    
            if ($commentUsers->isNotEmpty()) {
                // Fetch all active users who commented in a single query
                $receivers          =       User::whereIn('id', $commentUsers)->where('is_active', 1)->get();
        
                foreach ($receivers as $receiver) {

                    $message        =       "**{$sender->user_name}** commented on a post you also commented on: **{$post->title}**";
                    // Create and save notification
                    $notification   =         Notification::create([

                        'receiver_id' => $receiver->id,
                        
                        'sender_id' => $sender->id,

                        'post_id' => $postId,

                        'community_id' => $groupId,

                        'notification_type' => $this->notificationType,

                        'message' => $message,

                    ]);

                    $lastNotification                   =   $notification->id;

                    $notification                       =   Notification::find($lastNotification);
        
                    sendPushNotificationNew($sender, $receiver, $notification);
                }
            }
        } catch (Exception $e) {

            Log::error('Error: ' . $e->getMessage());
        }
        
        // try {

        //     $groupId                    =   $this->notificatonData['community_id'];

        //     $sender                     =   $this->sender;

        //     $postId                     =   $this->notificatonData['post_id'];

        //     $post                       =   Post::select('title')->where('id', $postId)->first();

        //     $commentUsers               =   Comment::where('post_id',$postId)->groupBy('user_id')->pluck('user_id');
           
        //     $group                      =   Group::where(['id' => $groupId])->first();

        //     $type                       =   trans('notification_message.posted_in_community');

        //     if (isset($commentUsers) && !empty($commentUsers)) {

        //         $data                           =  [];

        //         foreach ($commentUsers as $member) {

        //             $receiver                                   =       User::where(['id' => $member, 'is_active' => 1])->first();

        //             if (isset($receiver) && !empty($receiver)) {

        //                // $message                                =      "New post in **{$group->name}**:  {$post['title']}";
        //                 $message                                =       "**{$sender->user_name}** commented on a post you also commented on: **{$post->title}"; 
                       
        //                 if (isset($message) && !empty($message)) {
        //                     // Create a new notification
        //                     $notification                       =   new Notification();
        //                     $notification->receiver_id          =   $receiver['id'];
        //                     $notification->sender_id            =   $sender->id;
        //                     $notification->post_id              =   $postId;
        //                     $notification->community_id         =   $groupId;
        //                     $notification->notification_type    =   $this->notificationType;
        //                     $notification->message              =   $message;
        //                     $notification->save();
        //                     $lastNotification                   =   $notification->id;
        //                     $notification                       =   Notification::find($lastNotification);
        //                     sendPushNotificationNew($sender, $receiver, $notification);
        //                 }
        //             }
        //         }
        //     }
        // } catch (Exception $e) {

        //     Log::error('Error : ' . $e->getMessage());
        // }
    }
}
