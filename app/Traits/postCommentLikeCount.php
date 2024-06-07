<?php
namespace App\Traits;

use Exception;
use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\PostLike;
use App\Models\CommentLike;
use App\Models\GroupMember;
use App\Models\GroupMemberRequest;
use App\Traits\IsLikedPostComment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait postCommentLikeCount {
use IsLikedPostComment;
    public function postLikeCount($post_id) {

        $userId                 =       Auth::id();
        $postLike['total_likes_count']           =       PostLike::where(['post_id'=>$post_id])->count();
        $hasLiked               =       PostLike::where(['post_id'=>$post_id,'user_id'=>$userId])->first();
        $postLike['is_liked']   =       (isset($hasLiked) && !empty($hasLiked))?1:0;
        $postLike['reaction']   =       (isset($hasLiked) && !empty($hasLiked))?$hasLiked->reaction:0;
        return $postLike;
        
    }

    public function commentLikeCount($comment_id) {

        $userId                         =       Auth::id();
        $postLike['total_likes_count']  =       CommentLike::where(['comment_id'=>$comment_id])->count();
        $hasLiked                       =       CommentLike::where(['comment_id'=>$comment_id,'user_id'=>$userId])->first();
        $postLike['is_liked']           =       (isset($hasLiked) && !empty($hasLiked))?1:0;
        $postLike['reaction']           =       (isset($hasLiked) && !empty($hasLiked))?$hasLiked->reaction:0;
        return $postLike;
        
    }
    public function addBaseInImage($cover_photo){
        
        if(isset($cover_photo) && !empty($cover_photo)){

            return (filter_var($cover_photo, FILTER_VALIDATE_URL))? $cover_photo : asset('storage/'.$cover_photo);
            
        }else{

            return null;
        }
    }


    public function getPost($postId, $authId,$mesage)
    {
        try {
            $user           =      User::findOrFail($authId);

            $homeScreenPost =      Post::where(['posts.is_active'=>1,'id'=>$postId])

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
                    }
                ])->withCount(['total_likes','total_comment'])->first();

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

                    $homeScreenPost->post_category_name = post_category($homeScreenPost->post_category);
                    #------------ parent post data-----------------#
                    if(isset($homeScreenPost->parent_post) && !empty($homeScreenPost->parent_post)){

                        if (isset($homeScreenPost->parent_post->media_url) && !empty($homeScreenPost->parent_post->media_url)) {

                            $homeScreenPost->parent_post->media_url   =  $this->addBaseInImage($homeScreenPost->parent_post->media_url);
                        }
                        $isExist                                      =   $this->IsPostLiked($homeScreenPost->parent_post->id, $authId);
                        $homeScreenPost->parent_post->is_liked        =   $isExist['is_liked'];
                        $homeScreenPost->parent_post->reaction        =   $isExist['reaction'];
                        $isRepost                                     =   Post::where(['parent_id'=>$homeScreenPost->parent_post->id,'user_id'=>$authId,'is_active'=>1])->exists();
                        $homeScreenPost->parent_post->is_reposted     =  ($isRepost)?1:0;
                        $homeScreenPost->postedAt                     =  time_elapsed_string($homeScreenPost->parent_post->created_at);
                    }
                    $homeScreenPost->postedAt                         =   time_elapsed_string($homeScreenPost->created_at);
            return $this->sendResponse($homeScreenPost, $mesage, 200);

        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getLine());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #----------- G E T      C O M M U N I T Y           P O S T         B Y     I D ------------------_#
    public function getCommunityPost($community_id, $authId, $request)
    {
        try {
            // check if i have join the community or not
            $group          = Group::withCount('groupMember')->find($community_id);

            if (isset($group) && !empty($group)) {

                if ((isset($group->cover_photo) && !empty($group->cover_photo))) {

                    $group->cover_photo = $this->addBaseInImage($group->cover_photo);
                }

                $isGroupMember              =       GroupMember::where(['group_id' => $group->id, 'user_id' => $authId, 'is_active' => 1])->first();

                if (isset($isGroupMember) && !empty($isGroupMember)) {

                    $group->is_joined       =       1; // not join the group
                    $group->role            =       $isGroupMember->role;
                    
                } else {

                    $request                =       GroupMemberRequest::where(['group_id' => $group->id, 'is_active' => 1, 'user_id' => $authId])->first();

                    if (isset($request) && !empty($request)) {
                        if ($request->status == "pending") {

                            $group->is_joined = 2; // pending request

                        } elseif ($request->status == "rejected") {

                            $group->is_joined = 3; // rejected

                        }
                    } else {

                        $group->is_joined = 0; // not join the group

                    }

                    $group->role = null;
                }
            }
            $isGroupMember          =   GroupMember::where(['group_id' => $community_id, 'user_id' => $authId, 'is_active' => 1])->first();
            if (!$isGroupMember) {

                return response()->json(['status' => 201, 'message' => trans('message.you_are_not_group_member'), 'group' => $group]);
            }

            $limit                  =   10;

            if (isset($request['limit']) && !empty($request['limit'])) {

                $limit              =   $request['limit'];
            }
          
            $posts = Post::whereHas('post_user', function ($query) {

                $query->where('is_active', 1);      #----- 

            })->with([

                    'group:id,name,description,cover_photo,post_count',
                    'post_user:id,user_name,name,profile'

                ])->with(['parent_post' => function ($query) {

                    $query->select('*')
                        ->where('is_active', 1)
                        ->with([
                            'post_user' => function ($query) {
                                $query->select('id', 'name','user_name', 'profile');
                            }
                        ]);
                        
                },'parent_post.group'=>function($query){

                    $query->select('id','name','description','created_by');

                }])
                ->where('group_id', $community_id)
                ->where('is_active', 1)
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('hidden_posts')
                        ->whereColumn('hidden_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('hidden_posts.user_id', '=', $authId); // Check if the current user has reported the post 
    
                })
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('report_posts')
                        ->where('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('report_posts.user_id', '=', $authId); // Check if the current user has reported the post
                })
                ->whereNotExists(function ($query) use ($authId) {

                    $query->select(DB::raw(1))

                        ->from('blocked_users')

                        ->where('user_id', '=', $authId) // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('blocked_users.blocked_user_id', '=', 'posts.user_id'); // Check if the current user has reported the post
                });
            if (isset($request['post_category_id']) && !empty($request['post_category_id'])) {

                $posts->where('post_category', $request['post_category_id']);
            }
            $posts = $posts->orderByDesc('id')->simplePaginate($limit);
       
            //dd(DB::getQueryLog());
            $posts->getCollection()->transform(function ($post) use ($authId) {

                if(isset($post->parent_post) && !empty($post->parent_post)){


                    if ($post->parent_post->post_user && $post->parent_post->post_user->profile) {
    
                        $post->parent_post->post_user->profile       = $this->addBaseInImage($post->parent_post->post_user->profile);
                    }
                    if (isset($post->parent_post->media_url) && !empty($post->parent_post->media_url)) {
    
                        $post->parent_post->media_url        =       $this->addBaseInImage($post->parent_post->media_url);
                    }
                    $isExist                                 =       $this->IsPostLiked($post->id, $authId,1);
                    $post->parent_post->is_liked             =       $isExist['is_liked'];
                    $post->parent_post->reaction             =       $isExist['reaction'];
                    $post->parent_post->total_likes_count    =       $isExist['total_likes_count'];
                    $post->parent_post->total_comment_count  =       $isExist['total_comment_count'];
                    $isRepost                                =       Post::where(['parent_id'=>$post->parent_post->id,'user_id'=>$authId,'is_active'=>1])->exists();
                    $post->parent_post->is_reposted          =       ($isRepost)?1:0;
                    $post->parent_post->postedAt                          =      time_elapsed_string($post->created_at);

                }
                $isRepost                                    =      Post::where(['parent_id'=>$post->id,'user_id'=>$authId,'is_active'=>1])->exists();
                $post->is_reposted                           =      ($isRepost)?1:0;
                $isExist                                     =      $this->IsPostLiked($post->id, $authId,1);
                $post->is_liked =       $isExist['is_liked'];
                $post->reaction =       $isExist['reaction'];
                $post->total_likes_count = $isExist['total_likes_count'];
                $post->total_comment_count = $isExist['total_comment_count'];
                if (isset ($post->media_url) && !empty ($post->media_url)) {

                    $post->media_url = $this->addBaseInImage($post->media_url);
                }
                if ($post->group && $post->group->cover_photo) {

                    $post->group->cover_photo = $this->addBaseInImage($post->group->cover_photo);
                }
                // Check if the post has a user associated with it and user profile is set
                if ($post->post_user && $post->post_user->profile) {

                    $post->post_user->profile = $this->addBaseInImage($post->post_user->profile);
                }
                $post->postedAt = time_elapsed_string($post->created_at);

                $post->is_reposted = (Post::where('parent_id', $post->id)
                    ->where('user_id', $authId)
                    ->where('is_active', 1)
                    ->exists())?1:0;
                $post->post_category_name = post_category($post->post_category);

                return $post;
            });
            if (isset($group) && !empty($group)) {

                if (isset($group->cover_photo) && !empty($group->cover_photo)) {

                    $group->cover_photo = $this->addBaseInImage($group->cover_photo);
                }
                //check role of community
                $isGroupMember = GroupMember::where(['group_id' => $group->id, 'user_id' => $authId, 'is_active' => 1])->first();
                if (isset($isGroupMember) && !empty($isGroupMember)) {
                    $group->is_joined = 1; // not join the group
                    $group->role = $isGroupMember->role;
                } else {
                    $request = GroupMemberRequest::where(['group_id' => $group->id, 'is_active' => 1, 'user_id' => $authId])->first();

                    if (isset($request) && !empty($request)) {

                        if ($request->status == "pending") {

                            $group->is_joined = 2; // pending request

                        } elseif ($request->status == "rejected") {

                            $group->is_joined = 3; // rejected
                        }
                    } else {

                        $group->is_joined = 0; // not join the group
                    }
                    $group->role = null;
                }
            }
            return response()->json(['status' => 200, 'message' => trans('message.community_post'), 'data' => $posts, 'group' => $group]);
        } catch (Exception $e) {
            Log::error('Error caught: "getCommunityPostTRAIT" ' . $e->getMessage());
            return $this->sendError('Error occurred while fetching post.', [], 400);
        }
    }
    #------------------  G E T      C O M M U N I T Y      P O S T  --------------------#













}