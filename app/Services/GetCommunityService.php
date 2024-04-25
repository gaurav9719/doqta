<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Post;
use Carbon\Carbon;
use App\Services\NotificationService;
use App\Services\AddCommunityPost;
use App\Models\User;
use App\Models\GroupMemberRequest;


/**
 * Class GetCommunityService.
 */
class GetCommunityService extends BaseController
{


    protected $addCommunityPost, $notification;
    public function __construct(AddCommunityPost $addCommunityPost, NotificationService $notification)
    {
        $this->addCommunityPost = $addCommunityPost;
        $this->notification = $notification;
    }


    #------********  G E T      C O M M U N I T Y       P O S T   *********------------#
    public function homeScreen($request, $authId)
    {
        try {

            $limit = 10;

            if (isset($request->limit) && !empty($request->limit)) {

                $limit = $request->limit;
            }

            $user = User::findOrFail($authId);
            // dd($user);
            // $posts              =       $user->posts()->latest()->simplePaginate($limit);
            // $posts = $user->posts()->where(['posts.is_active' => 1])->whereNotExists('')->latest()->simplePaginate($limit);
            $homeScreenPosts = $user->posts()
                ->where('posts.is_active', 1)

                // $homeScreenPosts = Post::whereIn('group_id', function($query) use ($user) {
                //     $query->select('group_id')
                //           ->from('group_members')
                //           ->where('user_id', $user->id);
                // })
                ->whereNotExists(function ($query) use ($user) {

                    $query->select(DB::raw(1))
                        ->from('report_posts')
                        ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('report_posts.user_id', '=', $user->id); // Check if the current user has reported the post

                })->with([
                    'parent_post' => function ($query) {

                        $query->select('id', 'user_id', 'title', 'repost_count', 'like_count', 'comment_count', 'is_high_confidence')
                            ->where('is_active', 1)
                            ->with([
                                'post_user' => function ($query) {
                                    $query->select('id', 'name', 'profile');
                                }
                            ]);
                    }
                ])
                ->latest()
                ->simplePaginate($limit);

            $homeScreenPosts->each(function ($homeScreenPost) {


                if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

                    $homeScreenPost->media_url = asset('storage/' . $homeScreenPost->media_url);
                }

                if ($homeScreenPost->parent_post && $homeScreenPost->parent_post->post_user && $homeScreenPost->parent_post->post_user->profile) {
                    $homeScreenPost->parent_post->post_user->profile = asset('storage/' . $$homeScreenPost->parent_post->post_user->profile);
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


    # -------------------- G E T        C O M M U N I T Y       B Y     I D -------------------#
    // public function getCommunityById($communityId,$userid,$message){

    //     try {

    //         $community          =   Group::with(['groupMember'=>function($query){

    //             $query->limit(10);

    //         }])->withCount(['groupMember' ])->where(['id'=>$communityId,'is_active'=>1])->first();

    //         if(isset($community) && !empty($community)){



    //             if(isset($community->cover_photo) && !empty($community->cover_photo)){

    //                 $community->cover_photo =   asset('storage/'.$community->cover_photo); 
    //             }

    //         }

    //         return $this->sendResponse($community, $message, 200);

    //     } catch (Exception $e) {

    //         Log::error('Error caught: "getCommunityById" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);

    //     }




    // }

    public function getCommunityById($communityId, $userid, $message)
    {
        try {
            $community = Group::with([
                'groupMember' => function ($query) {
                    
                    $query->limit(10);
                }
            ])
                ->withCount(['groupMember'])
                ->findOrFail($communityId);

            if ($community->cover_photo) {
                $community->cover_photo = asset('storage/' . $community->cover_photo);
            }

            return $this->sendResponse($community, $message, 200);
        } catch (Exception $e) {
            Log::error('Error caught: "getCommunityById" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    # -------------------- G E T        C O M M U N I T Y       B Y     I D -------------------#


    #----------------- G E T        A L L       C O M M U N I T Y --------------#

    public function getAllCommunity($request, $authId)
    {
        if ($request->filled('search')) {

            return $this->getCommunityBySearch($request, $authId);
        } else {

            return $this->getJoinedCommunity($request, $authId);
        }
    }
    #----------------- G E T        A L L       C O M M U N I T Y --------------#

    #----------------- G E T    J O I N E D      C O M M U N I T Y --------------------#

    public function getJoinedCommunity($request, $authId)
    {

        try {
            $limit = 10;

            if (isset($request->limit) && !empty($request->limit)) {

                $limit = $request->limit;
            }
            $communitiesQuery = GroupMember::where('user_id', $authId)

                ->whereHas('communities', function ($query) {
                    $query->where('is_active', 1);
                })->pluck('group_id');

            $communities     = Group::whereIn('id', $communitiesQuery)->orderByDesc('id')->simplePaginate($limit);

            return $this->communityLoop($communities, $authId);
        } catch (Exception $e) {
            // Handle exceptions
            Log::error('Error caught: "getJoinedCommunity" ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred.'], 400);
        }
    }
    #----------------- G E T    J O I N E D      C O M M U N I T Y --------------------#


    #------------------------ G E T     C O M M U N I T Y   B Y     S E A R C H  -----------------------#
    public function getCommunityBySearch($request, $authId)
    {

        try {
            $limit = 10;

            if (isset($request->limit) && !empty($request->limit)) {

                $limit = $request->limit;
            }
            $communities = Group::where('name', 'LIKE', "%$request->search%")->where('is_active', 1)->simplePaginate($limit);

            return $this->communityLoop($communities, $authId);
        } catch (Exception $e) {
            // Handle exceptions
            Log::error('Error caught: "getCommunityBySearch" ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred.'], 400);
        }
    }
    #------------------------ G E T     C O M M U N I T Y   B Y     S E A R C H  -----------------------#


    #----------------------  C O M M U N I T Y      C O M M O N     L O O P -------------------#
    public function communityLoop($communities, $authId)
    {
        $communities->each(function ($community) use ($authId) {

            if (isset($community) && !empty($community)) {

                if (isset($community->cover_photo) && !empty($community->cover_photo)) {

                    $community->cover_photo = asset('storage/' . $community->cover_photo);
                }
            }
            //check i am the member of the community or not
            $isExist = GroupMember::where(['group_id' => $community->id, 'is_active' => 1, 'user_id' => $authId])->exists();
            if ($isExist) {

                $community->is_joined = 1;
            } else {

                $request = GroupMemberRequest::where(['group_id' => $community->id, 'is_active' => 1, 'user_id' => $authId])->first();

                if (isset($request) && !empty($request)) {

                    if ($request->status = "pending") {

                        $community->is_joined = 2; // pending request

                    } elseif ($request->status = "rejected") {

                        $community->is_joined = 3; // rejected
                    }
                } else {

                    $community->is_joined = 0; // not join the group
                }
            }
        });
        return $this->sendResponse($communities, trans("message.communities"), 200);
    }
    #----------------------  C O M M U N I T Y      C O M M O N     L O O P -------------------#

}
