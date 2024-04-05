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
    public function homeScreen($request, $authId)
    {
        try {

            $limit              =       10;

            if (isset($request->limit) && !empty($request->limit)) {
                $limit          =       $request->limit;
            }

            $user               =       User::findOrFail($authId);
            // $posts              =       $user->posts()->latest()->simplePaginate($limit);
            // $posts = $user->posts()->where(['posts.is_active' => 1])->whereNotExists('')->latest()->simplePaginate($limit);
            $homeScreenPosts = $user->posts()
            ->where('posts.is_active', 1)
            ->whereNotExists(function ($query) use ($user) {
                $query->select(DB::raw(1))
                    ->from('report_posts')
                    ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                    ->where('report_posts.user_id', '=', $user->id); // Check if the current user has reported the post
            })->with(['parent_post' => function ($query) {
                $query->select('id', 'user_id', 'title', 'repost_count', 'like_count', 'comment_count', 'is_high_confidence')
                    ->where('is_active', 1)
                    ->with(['post_user' => function ($query) {
                        $query->select('id', 'name', 'profile');
                    }]);
            }])
            ->latest()
            ->simplePaginate($limit);

                $homeScreenPosts->each(function ($homeScreenPost) {


                    if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

                        $homeScreenPost->media_url = asset('storage/' . $homeScreenPost->media_url);
                    }

                    if ($homeScreenPost->parent_post && $homeScreenPost->parent_post->post_user &&      $homeScreenPost->parent_post->post_user->profile) {
                        $homeScreenPost->parent_post->post_user->profile = asset('storage/'.$$homeScreenPost->parent_post->post_user->profile);         
                    }
                    $homeScreenPost->postedAt = Carbon::parse($homeScreenPost->created_at)->diffForHumans();

                });
            

            return $this->sendResponse($homeScreenPosts, trans("message.home_screen_post"), 200);

        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------********  G E T      C O M M U N I T Y       P O S T   *********------------#
}
