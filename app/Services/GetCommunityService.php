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
use App\Traits\IsLikedPostComment;
use App\Models\PostLike;
use App\Traits\postCommentLikeCount;
use App\Models\ActivityLog;
use App\Models\Comment;

/**
 * Class GetCommunityService.
 */
class GetCommunityService extends BaseController
{

    use IsLikedPostComment,postCommentLikeCount;
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

            $limit          =       10;
            if (isset($request->limit) && !empty($request->limit)) {
                $limit      =       $request->limit;
            }
            $user           =       User::findOrFail($authId);
            $homeScreenPosts =      $user->posts()
                ->where('posts.is_active', 1)
                ->whereNotExists(function ($query) use ($user) {
                    $query->select(DB::raw(1))
                        ->from('report_posts')
                        ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('report_posts.user_id', '=', $user->id); // Check if the current user has reported the post

                })
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('blocked_users')
                        ->where('user_id', '=', $authId)                              // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('blocked_users.blocked_user_id','=','posts.user_id'); // Check if the current user has reported the post
                })
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('hidden_posts')
                        ->whereColumn('hidden_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('hidden_posts.user_id', '=', $authId); // Check if the current user has reported the post 
                })
                ->with(['post_user'=>function($query){

                    $query->select('id','name','user_name','profile');
                    },
                    'group'=>function($query){
                        $query->select('id','name','description','cover_photo','member_count','post_count','created_by');
                    },
                    'parent_post' => function ($query) {
                        $query->select('*')
                            ->where('is_active', 1)
                            ->with([
                                'post_user' => function ($query) {
                                    $query->select('id', 'name','user_name', 'profile');
                                }
                            ]);
                    },'parent_post.group'=>function($query){

                        $query->select('id','name','description','created_by');
                    }
                ])->withCount(['total_likes','total_comment'])
                ->orderByDesc('id')
                ->simplePaginate($limit);

                $homeScreenPosts->each(function ($homeScreenPost) use($authId) {

                    if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

                        $homeScreenPost->media_url      =  $this->addBaseInImage($homeScreenPost->media_url);
                    }

                    if ($homeScreenPost->parent_post && $homeScreenPost->parent_post->post_user && $homeScreenPost->parent_post->post_user->profile) {

                        $homeScreenPost->parent_post->post_user->profile = $this->addBaseInImage($homeScreenPost->parent_post->post_user->profile);
                    }

                    if (isset($homeScreenPost->post_user) &&  !empty($homeScreenPost->post_user->profile)) {

                        $homeScreenPost->post_user->profile      =  $this->addBaseInImage($homeScreenPost->post_user->profile);
                    }
                    if ($homeScreenPost->group &&  $homeScreenPost->group->cover_photo) {

                        $homeScreenPost->group->cover_photo      =  $this->addBaseInImage($homeScreenPost->group->cover_photo );
                    }
                    $isExist                         =   $this->IsPostLiked($homeScreenPost->id, $authId);
                    $homeScreenPost->is_liked        =   $isExist['is_liked'];
                    $homeScreenPost->reaction        =   $isExist['reaction'];
                    $isRepost                        =   Post::where(['parent_id'=>$homeScreenPost->id,'user_id'=>$authId,'is_active'=>1])->exists();
                    $homeScreenPost->is_reposted     =  ($isRepost)?1:0;
                    #------------ parent post data-----------------#
                    if(isset($homeScreenPost->parent_post) && !empty($homeScreenPost->parent_post)){

                        if (isset($homeScreenPost->parent_post->media_url) && !empty($homeScreenPost->parent_post->media_url)) {

                            $homeScreenPost->parent_post->media_url   =  $this->addBaseInImage($homeScreenPost->parent_post->media_url);
                        }
                        $isExist                                      =   $this->IsPostLiked($homeScreenPost->parent_post->id, $authId);
                        $homeScreenPost->parent_post->is_liked        =   $isExist['is_liked'];
                        $homeScreenPost->parent_post->reaction        =   $isExist['reaction'];
                        $homeScreenPost->parent_post->total_likes_count =   $isExist['total_likes_count'];
                        $homeScreenPost->parent_post->total_comment_count =   Comment::where('post_id',$homeScreenPost->parent_post->id)->count();
                        $isRepost                                     =   Post::where(['parent_id'=>$homeScreenPost->parent_post->id,'user_id'=>$authId,'is_active'=>1])->exists();
                        $homeScreenPost->parent_post->is_reposted     =  ($isRepost)?1:0;
                        $homeScreenPost->parent_post->postedAt        =  time_elapsed_string($homeScreenPost->parent_post->created_at);
                    }
                    $homeScreenPost->postedAt                         =   time_elapsed_string($homeScreenPost->created_at);
                });
            return $this->sendResponse($homeScreenPosts, trans("message.home_screen_post"), 200);

        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #------********  G E T      C O M M U N I T Y       P O S T   *********------------#

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
            $communities = Group::where('name', 'LIKE', "%$request->search%")->where('is_active', 1)->orderBy('name', 'asc')->simplePaginate($limit);

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
            $isExist = GroupMember::where(['group_id' => $community->id, 'is_active' => 1, 'user_id' => $authId])->first();

            if (isset($isExist) && !empty($isExist)) {

                $community->is_joined = 1;
                $community->role      = $isExist->role;

            } else {

                $request = GroupMemberRequest::where(['group_id' => $community->id, 'is_active' => 1, 'user_id' => $authId])->first();

                if (isset($request) && !empty($request)) {

                    if ($request->status = "pending") {

                        $community->is_joined  = 2; // pending request

                    } elseif ($request->status = "rejected") {

                        $community->is_joined  = 3; // rejected
                    }
                    $community->role           = null;
                    
                } else {

                    $community->is_joined = 0; // not join the group
                    $community->role      = null;
                }
            }
        });
        return $this->sendResponse($communities, trans("message.communities"), 200);
    }
    #----------------------  C O M M U N I T Y      C O M M O N     L O O P -------------------#


    #------------------- J O I N / U N J O I N      C O M M U N I T Y ---------------------#

    public function joinUnjoin($request,$authId,$group){

        if($request->type==1){

            return $this->joinCommunity($request,$authId,$group);

        }else{

            return $this->removeCommunity($request,$authId,$group);
        }


    }
    #-------------_________-----************ E N D ************----------------------------#


    #---------------------------  J O I N       C O M M U N I T Y -------------------------#
    public function joinCommunity($request,$authId,$group){
        
        DB::beginTransaction();

        try {
            $alreadyMember                  =   GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId])->exists();

            if ($alreadyMember) {

                return $this->sendResponsewithoutData(trans('message.already_group_member'), 409);
            } 
            if ($group->visibility == 1) {         ##--------- PUBLIC COMMUNITIES ------------#

                $addGroupMember             =   new GroupMember();
                $addGroupMember->group_id   =   $request->community_id;
                $addGroupMember->user_id    =   $authId;
                $addGroupMember->role       =   "member";

                if ($addGroupMember->save()) {
                    // increment in group member
                    incrementMemberWithAuth($request->community_id, 1);
                    $group         =   Group::find($request->community_id);
                    $sender        =   Auth::user();
                    $receiver      =   User::find($group->created_by);
                    $mesage        =   $sender->name." ".trans('notification_message.joined_community')." ".$group->name;
                    $data          =   [
                        "message"               => $mesage,
                        "community_member_id"   => $addGroupMember->id,
                        "community_id"          => $group->id
                    ];
                    $type           =       trans('notification_message.joined_community_type');
                    $this->notification->sendNotificationNew($sender, $receiver, $type, $data);

                    #-------  A C T I V I T Y -----------#
                    $activity                       =    new ActivityLog();
                    $activity->user_id              =    $authId;
                    $activity->community_id         =    $group->id;
                    $activity->community_member_id  =    $addGroupMember->id;
                    $activity->action_details       =    "Joined the community: " . $group->name;
                    $activity->action               =    $type;    //Joined the community
                    $activity->save();
                    #-------  A C T I V I T Y -----------#
                    DB::commit();
                    $result                 =   $this->communityMemberCount($request->community_id,$authId);
                    return $this->sendResponse($result,trans('message.community_joined_successfully'), 200);

                }

            } else {                              ##--------- PRVATE COMMUNITIES ------------#

                $checkRequest               =   GroupMemberRequest::where(['user_id' => $authId, 'group_id' => $request->community_id])->exists();

                if ($checkRequest) {

                    return $this->sendError(trans('message.something_went_wrong'), [], 403);

                } else {

                    $groupRequest           =   new GroupMemberRequest();
                    $groupRequest->user_id  =   $authId;
                    $groupRequest->group_id =   $request->community_id;
                    $groupRequest->save();
                    $group                  =   Group::find($request->community_id);
                    $reciever               =   User::select('id', 'device_token', 'device_type')->where("id", $group->user_id)->first();
                    $sender                 =   User::select('id', 'device_token', 'device_type')->where("id", $authId)->first();
                    $notification_type      =   trans('notification_message.new_memeber_group_request_type');
                    $notification_message   =   trans('notification_message.new_memeber_group_request_type_message');
                    $this->notification->sendNotification($reciever, $sender, $notification_message, $notification_type);
                    DB::commit();
                    $result                 =   $this->communityMemberCount($request->community_id,$authId);
                    return $this->sendResponse($result,trans('message.request_send_successfuly'), 200);
                }
            }
            
        } catch (Exception $e) {
            
            DB::rollback();
            Log::error('Error caught: "removeCommunity" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);

        }
    }
    #---------------------------  J O I N       C O M M U N I T Y -------------------------#


    #-------------------   R E M O V E        C O M M U N I T Y     -----------------------#
    
    public function removeCommunity($request,$authId,$group){

        DB::beginTransaction();
        try {

            $alreadyMember                  =   GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId])->first();

            if (!$alreadyMember) {

                return $this->sendResponsewithoutData(trans('message.not_group_member'), 409);

            }else{
             
                if($group['created_by']==$authId){

                    return $this->sendError(trans('message.owner_cannot_leave_community'), [], 400);
                }
                $alreadyMember->delete();
                DB::commit();
                decrementMemberWithAuth($request->community_id,1);
                $result                 =   $this->communityMemberCount($request->community_id,$authId);
                return $this->sendResponse($result,trans('message.remove_successfully'), 200);

            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "removeCommunity" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------   R E M O V E        C O M M U N I T Y     -----------------------#



    public function communityMemberCount($communityId,$authId){
        $memberCount                 =   GroupMember::where(['group_id'=>$communityId,'is_active'=>1])->count();
        $is_member                   =   GroupMember::where(['group_id'=>$communityId,'is_active'=>1,'user_id'=>$authId])->exists();
        $response['groupMemberCount']=   $memberCount;
        $response['is_joined']       =   ($is_member)?1:0;
        return $response;

    }


}
