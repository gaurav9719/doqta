<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserQuota extends Model
{
    use HasFactory;

    protected $fillable = ['id',
        'user_id', 'date', 'community_posts', 'chatbot_messages', 'journal_entries',
        'rewrite_with_ai', 'friend_requests', 'post_comments', 'community_join_requests','is_active'
    ];



    public static function updateQuota($userId, $action)
    {

        try {
             // Define the limits for each action
        $limits = [
            'community_post' => 2,
            'chatbot_message' => 10,
            'journal_entry' => 2,
            'rewrite_with_ai' => 4,
            'friend_request' => 10,
            'post_comment' => 20,
            'community_join_request' => 5
        ];

        // Get today's date
        $date = date('Y-m-d');

        // Get or create the quota record for today
        $quota = self::firstOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'community_posts' => 0,
                'chatbot_messages' => 0,
                'journal_entries' => 0,
                'rewrite_with_ai' => 0,
                'friend_requests' => 0,
                'post_comments' => 0,
                'community_join_requests' => 0
            ]
        );

        // Increment the appropriate quota based on the action
        $updated = false;
      
        switch ($action) {
            case 'community_post':
                if ($quota->community_posts < $limits['community_post']) {
                  
                    $quota->community_posts++;
                    $updated = true;
                }
                break;
            case 'chatbot_message':
                if ($quota->chatbot_messages < $limits['chatbot_message']) {
                    $quota->chatbot_messages++;
                    $updated = true;
                }
                break;
            case 'journal_entry':
                if ($quota->journal_entries < $limits['journal_entry']) {
                    $quota->journal_entries++;
                    $updated = true;
                }
                break;
            case 'rewrite_with_ai':
                if ($quota->rewrite_with_ai < $limits['rewrite_with_ai']) {
                    $quota->rewrite_with_ai++;
                    $updated = true;
                }
                break;
            case 'friend_request':
                if ($quota->friend_requests < $limits['friend_request']) {
                    $quota->friend_requests++;
                    $updated = true;
                }
                break;
            case 'post_comment':
                if ($quota->post_comments < $limits['post_comment']) {
                    $quota->post_comments++;
                    $updated = true;
                }
                break;
            case 'community_join_request':
                if ($quota->community_join_requests < $limits['community_join_request']) {
                    $quota->community_join_requests++;
                    $updated = true;
                }
                break;
        }

        if ($updated) {
            $quota->save();
            return true;
        } else {
            Log::info("Quota exceeded for action: $action");
            return false;
        }
        } catch (Exception $e) {
           
            return $e;
        }








      
    }




}
