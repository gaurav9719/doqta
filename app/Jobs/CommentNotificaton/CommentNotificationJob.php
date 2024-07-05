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
    }
}
