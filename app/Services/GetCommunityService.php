<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use App\Models\GroupMember;
use App\Models\Post;
use Carbon\Carbon;
use App\Services\NotificationService;
use App\Services\AddCommunityPost;
use App\Models\User;

/**
 * Class GetCommunityService.
 */
class GetCommunityService extends BaseController
{


    protected $addCommunityPost, $notification;
    public function __construct(AddCommunityPost $addCommunityPost, NotificationService $notification)
    {
        $this->addCommunityPost         = $addCommunityPost;
        $this->notification         = $notification;
    }


    #------********  G E T      C O M M U N I T Y       P O S T   *********------------#
    public function homeScreen($request,$authId)
    {
        try {
           
            $limit              =       10;

            if(isset($request->limit) && !empty($request->limit)){
                $limit          =       $request->limit;
            }

            $user               =       User::findOrFail($authId);
            $posts              =       $user->posts()->latest()->simplePaginate($limit);
        
            return $this->sendResponse($posts, trans("message.add_posted_successfully"), 200);



            // $post = Post::with('group_post:name,description,cover_photo,member_count', 'post_user:id,name,profile')->simplePaginate($limit);

            // if (!$post) {

            //     return $this->sendError('Post not found.', [], 404);
            // }

            // if ($post->media_url) {

            //     $post->media_url = asset('storage/' . $post->media_url);
            // }

            // if ($post->post_user && $post->post_user->profile) {

            //     $post->post_user->profile = asset('storage/' . $post->post_user->profile);
            // }

            // $post->postedAt = Carbon::parse($post->created_at)->diffForHumans();

            // return $this->sendResponse($post, trans("message.add_posted_successfully"), 200);
            
        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError('Error occurred while fetching post.', [], 500);
        }
    }
    #------********  G E T      C O M M U N I T Y       P O S T   *********------------#
}
