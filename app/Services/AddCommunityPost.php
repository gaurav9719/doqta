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
use App\Models\Post;
use Carbon\Carbon;
use App\Models\Comment;
use App\Models\GroupMember;
use App\Models\PostLike;
use App\Models\GroupMemberRequest;
use App\Models\CommentLike;
use App\Models\User;
use App\Traits\postCommentLikeCount;
use App\Traits\IsCommunityJoined;
use App\Traits\FeedPostNotification;
use App\Traits\IsLikedPostComment;
use App\Models\UserParticipantCategory;
use App\Models\ActivityLog;
use App\Jobs\FeedPostNotification as feedPostionJob;

/**
 * Class AddCommunityPost.
 */
class AddCommunityPost extends BaseController
{
    use postCommentLikeCount, IsCommunityJoined, FeedPostNotification, IsLikedPostComment;
    private $notification;
    public function __construct(NotificationService $notification)
    {
        $this->notification = $notification;
    }
    #*********-------     A D D        P O S T     ---------------********#
    public function addPost($request, $authId)
    {
        DB::beginTransaction();

        try {
            $is_health_provider= UserParticipantCategory::where('user_id', $authId)->where('participant_id', 3)->exists() ? 1 : 0;

            $post = new Post();
            $post->user_id = $authId;
            $post->title = $request->title;
            $post->content = $request->content;
            $post->is_health_provider = $is_health_provider; // add true is user is health provider

            if ($request->hasFile('media')) {

                $post_image     = $request->file('media');
                $Uploaded       = upload_file($post_image, 'post_images');
                $post->media_url = $Uploaded;
                $post->media_type = $request->media_type;
            }
            if (isset($request->lat) && !empty($request->lat)) {

                $post->lat = $request->lat;
            }

            if (isset($request->long) && !empty($request->long)) {

                $post->long = $request->long;
            }
            if (isset($request->link) && !empty($request->link)) {

                $post->link = $request->link;
            }
            if (isset($request->wrote_by) && !empty($request->wrote_by)) {

                $post->wrote_by = $request->wrote_by;
            }
            $post->group_id             = $request->community_id;
            $post->media_type           = $request->media_type;
            $post->post_type            = $request->post_type; //normal,community
            $post->post_category        = $request->post_category; //1: seeing advice, 2: giving advice, 3: sharing media	
            $post->save();
            $postId = $post->id;
            // add increment to group post
            increment('groups', ['id' => $request->community_id], 'post_count', 1);

            #-------  A C T I V I T Y -----------#
            $group                      =    Group::find($request->community_id);
            $activity                   =    new ActivityLog();
            $activity->user_id          =    $authId;
            $activity->post_id          =    $post->id;
            $activity->community_id     =    $group->id;
            $activity->action_details   =    "Posted in  " . $group->name;
            $activity->action           =    3;    //Posted in community    
            $activity->save();
            #-------  A C T I V I T Y -----------#
            $this->feedPostNotification($request->community_id, $postId, Auth::user());
            
            DB::commit();
            // add increment to group post
            return $this->getCommunityAndPost($request->community_id, $authId, trans("message.add_posted_successfully"), $request);
        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "addPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #*******----------- A D D          P O S T  --------------***********#



    ##### ********* ------   E D I T      P O S T  ------ ******** ########

    public function editPost($request, $authId, $postId)
    {
        DB::beginTransaction();
        try {
            $editPost = Post::find($postId);
            $editPost->user_id = $authId;
            
            if (isset($request->title) && !empty($request->title)) {

                $editPost->title = $request->title;
            }
            if (isset($request->content) && !empty($request->content)) {

                $editPost->content = $request->content;
            }
            if (isset($request->media_url) && !empty($request->media_url)) {

                $editPost->media_url = $request->media_url;
            }
            if (isset($request->group_id) && !empty($request->group_id)) {

                $editPost->group_id = $request->group_id;
            }
            if (isset($request->link) && !empty($request->link)) {

                $editPost->link = $request->link;
            }
            $editPost->media_type      = $request->media_type;
            $editPost->post_type        = $request->post_type;
            $editPost->post_category = $request->post_category;
            $editPost->save();
            DB::commit();
            return $this->getPost($postId, $authId, trans('message.update_post_successfully'));
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "addPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    ##### ********* -------   E D I T      P O S T  ------ ******** ########




    #-------------  G E T   P O S T    B Y      I D  ------------------#
    public function getPost($id, $authId, $message)
    {
        try {
            $post = Post::with([
                'group' => function ($query) {

                    $query->select('id', 'name', 'description', 'cover_photo', 'post_count');
                },
                'post_user:id,name,profile'
            ])

                ->find($id);

            if (!$post) {

                return $this->sendError('Post not found.', [], 404);
            }

            if ($post->media_url) {

                $post->media_url = asset('storage/' . $post->media_url);
            }

            if (isset($post->group) && !empty($post->group)) {

                if (isset($post->group->cover_photo) && !empty($post->group->cover_photo)) {

                    $post->group->cover_photo = asset('storage/' . $post->group->cover_photo);
                }
            }
            if ($post->post_user && $post->post_user->profile) {

                $post->post_user->profile = asset('storage/' . $post->post_user->profile);
            }

            $isExist = PostLike::where(['user_id' => $authId, 'post_id' => $post->id])->first();
            // sdd($isExist);
            $post->is_liked = (isset($isExist) && !empty($isExist)) ? 1 : 0;
            $post->reaction = (isset($isExist) && !empty($isExist)) ? $isExist->reaction : 0;
            $isRepost = Post::where(['parent_id' => $post->id, 'user_id' => $authId, 'is_active' => 1])->exists();
            $post->is_reposted = ($isRepost) ? 1 : 0;

            // $post->postedAt = Carbon::parse($post->created_at)->diffForHumans();
            $post->post_category_name = post_category($post->post_category);
            $post->postedAt = time_elapsed_string($post->created_at);



            return $this->sendResponse($post, $message, 200);
        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError('Error occurred while fetching post.', [], 500);
        }
    }

    #--------------  G E T           C O M M U N I T Y       P O S T    ----------------------#

    // public function getCommunityAndPost($community_id,$message="",$request="",){

    //     try {
    //         $limit              =    10;

    //         if(isset($request['limit']) && !empty($request['limit'])){

    //             $limit          =   $request['limit'];
    //         }
    //         // DB::enableQueryLog();
    //         $post               =   Post::whereHas('post_user', function($query){

    //                                     $query->where('is_active',1);

    //                                 })->with(['group'=>function($query){

    //                                     $query->select('id','name','description','cover_photo','post_count');

    //                                 }, 'post_user:id,name,profile'])->where(['group_id'=>$community_id,'is_active'=>1]);

    //                                 if(isset($request['post_category']) && !empty($request['post_category'])){

    //                                     //1: seeing advice, 2: giving advice, 3: sharing media	 
    //                                     $post       =   $post->where('post_category',$request['post_category']);
    //                                 }
    //         $post               =    $post->simplePaginate($limit);

    //         //  dd(DB::getQueryLog());
    //         if(isset($post[0]) && !empty($post[0])){

    //             $post->each(function($groupPost){

    //                 if ($groupPost->media_url) {

    //                     $groupPost->media_url = asset('storage/' . $groupPost->media_url);
    //                 }

    //                 if (isset($groupPost->group) && !empty($groupPost->group)) {

    //                     if(isset($groupPost->group->cover_photo) && !empty($groupPost->group->cover_photo)){

    //                         $groupPost->group->cover_photo = asset('storage/' . $groupPost->group->cover_photo);
    //                     }
    //                 }

    //                 if ($groupPost->post_user && $groupPost->post_user->profile) {

    //                     $groupPost->post_user->profile = asset('storage/' . $groupPost->post_user->profile);
    //                 }

    //                 $groupPost->postedAt = Carbon::parse($groupPost->created_at)->diffForHumans();
    //             });
    //         }
    //         // if (!$post) {

    //         //     return $this->sendError('Post not found.', [], 404);
    //         // }
    //         return $this->sendResponse($post, $message, 200);



    //     } catch (Exception $e) {

    //         Log::error('Error caught: "getPost" ' . $e->getMessage());
    //         return $this->sendError('Error occurred while fetching post.', [], 500);
    //     }


    // }

    // public function getCommunityAndPost($community_id, $message = "", $request = "") {
    //     try {
    //         $limit = $request['limit'] ?? 10;

    //         $posts = Post::where('group_id', $community_id)
    //             ->where('is_active', 1)
    //             ->whereHas('post_user', function ($query) {
    //                 $query->where('is_active', 1);
    //             })
    //             ->when(!empty($request['post_category']), function ($query) use ($request) {
    //                 $query->where('post_category', $request['post_category']);
    //             })
    //             ->with([
    //                 'group:id,name,description,cover_photo,post_count',
    //                 'post_user:id,name,profile'
    //             ])
    //             ->simplePaginate($limit);

    //             if($posts[0]){

    //                 $posts->each(function ($community_post) {
    //                     $this->processPostData($community_post);
    //                 });
    //             }

    //         return $this->sendResponse($posts, $message, 200);
    //     } catch (Exception $e) {
    //         Log::error('Error caught: "getPost" ' . $e->getMessage());
    //         return $this->sendError('Error occurred while fetching post.', [], 500);
    //     }
    // }

    // private function processPostData($community_post) {

    //     if ($community_post->media_url) {

    //         $community_post->media_url = $this->getAssetUrl($community_post->media_url);
    //     }

    //     if ($community_post->group && $community_post->group->cover_photo) {

    //         $community_post->group->cover_photo = $this->getAssetUrl($community_post->group->cover_photo);
    //     }

    //     if ($community_post->post_user && $community_post->post_user->profile) {

    //         $community_post->post_user->profile = $this->getAssetUrl($community_post->post_user->profile);
    //     }

    //     $community_post->postedAt = Carbon::parse($community_post->created_at)->diffForHumans();
    // }

    // private function getAssetUrl($path) {
    //     return asset('storage/' . $path);
    // }

    public function getCommunityAndPost($community_id, $authId, $message = "", $request = "")
    {
        try {

            $group = Group::withCount('groupMember')->find($community_id);

            $limit = 10;

            if (isset($request['limit']) && !empty($request['limit'])) {

                $limit = $request['limit'];
            }
            $posts = Post::whereHas('post_user', function ($query) {

                $query->where('is_active', 1);
            })
                ->with([
                    'group:id,name,description,cover_photo,post_count',
                    'post_user:id,name,profile'

                ])
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
                })
                ->addSelect([
                    'is_liked' => function ($query) use ($authId) {
                        $query->selectRaw('IF(EXISTS(SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = posts.id AND comment_id IS NULL), 1, 0)', [$authId]);
                    }
                ]);

            if (isset($request['post_category_id']) && !empty($request['post_category_id'])) {

                $posts->where('post_category', $request['post_category_id']);
            }

            $posts = $posts->orderByDesc('id')->simplePaginate($limit);

            if (!empty($posts)) {
                foreach ($posts as $groupPost) {

                    $media_url = isset($groupPost->media_url) ? asset('storage/' . $groupPost->media_url) : '';
                    $cover_photo = isset($groupPost->group) && isset($groupPost->group->cover_photo) ?
                        (filter_var($groupPost->group->cover_photo, FILTER_VALIDATE_URL) ? $groupPost->group->cover_photo : asset('storage/' . $groupPost->group->cover_photo)) : '';
                    $profile = isset($groupPost->post_user) && isset($groupPost->post_user->profile) ?
                        (filter_var($groupPost->post_user->profile, FILTER_VALIDATE_URL) ? $groupPost->post_user->profile : asset('storage/' . $groupPost->post_user->profile)) : '';


                    $groupPost->media_url = $media_url;
                    $groupPost->group->cover_photo = $cover_photo;
                    $groupPost->post_user->profile = $profile;
                    // $groupPost->postedAt = Carbon::parse($groupPost->created_at)->diffForHumans();
                    $groupPost->postedAt = time_elapsed_string($groupPost->created_at);
                    $groupPost->post_category_name = post_category($groupPost->post_category);
                }
            }

            if (isset($group) && !empty($group)) {

                $group->cover_photo = isset($group->cover_photo) && isset($group->cover_photo) ?
                    (filter_var($group->cover_photo, FILTER_VALIDATE_URL) ? $group->cover_photo : asset('storage/' . $group->cover_photo)) : '';
            }

            return response()->json(['status' => 200, 'message' => $message, 'data' => $posts, 'group' => $group]);
        } catch (Exception $e) {
            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError('Error occurred while fetching post.', [], 500);
        }
    }


    #------  G E T      A L L       C O M M U N I T Y       C O M M E N T S -------------#
    public function getComments($request, $authId)
    {
        try {
            $groupId = Post::select('group_id')->find($request->post_id);
            if ($groupId) {

                $group = Group::withCount('groupMember')->find($groupId->group_id);
            }
            $limit = 10;

            if (isset($request['limit']) && !empty($request['limit'])) {

                $limit = $request['limit'];
            }
            $comments = Comment::with([
                'commentUser' => function ($query) {

                    $query->select('id', 'name', 'user_name', 'profile');
                },
                'replies.commentUser' => function ($query) {

                    $query->select('id', 'name', 'user_name', 'profile');
                },
                'replies.replied_to' => function ($query) {

                    $query->select('id', 'name', 'user_name', 'profile');
                }
            ])->withCount(['totalLikes', 'total_comment'])
                ->where('post_id', $request->post_id)
                ->whereNull('parent_id')
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('blocked_users')
                        ->where(function ($query) use ($authId) {
                            // Check if the authenticated user has blocked someone
                            $query->where('user_id', $authId)
                                ->whereColumn('blocked_users.blocked_user_id', 'comments.user_id');
                        })
                        ->orWhere(function ($query) use ($authId) {
                            // Check if the authenticated user has been blocked by someone
                            $query->where('blocked_user_id', $authId)
                                ->whereColumn('blocked_users.user_id', 'comments.user_id');
                        });
                })->orderByDesc('id')->paginate($limit);

            $comments->getCollection()->transform(function ($comment) use ($authId) {

                $isExist = $this->IsCommentLiked($comment->post_id, $comment->id, $authId);
                $comment->is_liked = $isExist['is_liked'];
                $comment->reaction = $isExist['reaction'];
                $comment->total_likes_count = $isExist['total_likes_count'];

                if (isset ($comment->commentUser) && !empty ($comment->commentUser->profile)) {

                    $comment->commentUser->profile = $this->addBaseInImage($comment->commentUser->profile);
                    // $comment->commentUser->profile      =   isset($comment->commentUser) && isset($comment->commentUser->profile) ? (filter_var($comment->commentUser->profile, FILTER_VALIDATE_URL) ?  $comment->commentUser->profile : asset('storage/' .  $comment->commentUser->profile)) : '';
                }
                if (isset ($comment->replies[0]) && ($comment->replies[0])) {

                    $comment->replies->each(function ($replies) use ($authId) {

                        $isExist = $this->IsCommentLiked($replies->post_id, $replies->id, $authId);
                        $replies->is_liked = $isExist['is_liked'];
                        $replies->reaction = $isExist['reaction'];
                        $replies->total_likes_count = $isExist['total_likes_count'];

                        // $isExist                    =       CommentLike::where(['user_id' => $authId, 'post_id' => $replies->post_id, 'comment_id' => $replies->id])->first();
                        // $replies->is_liked          =       (isset($isExist) && !empty($isExist)) ? 1 : 0;
                        // $replies->reaction          =       (isset($isExist) && !empty($isExist)) ? $isExist->reaction : 0;
                        // $replies->total_likes_count =       CommentLike::where(['comment_id' => $replies->id])->count();

                        if (isset ($replies->commentUser) && !empty ($replies->commentUser)) {

                            if (isset ($replies->commentUser->profile) && !empty ($replies->commentUser->profile)) {

                                $replies->commentUser->profile = $this->addBaseInImage($replies->commentUser->profile);
                            }
                        }
                        if (isset ($replies->replied_to) && !empty ($replies->replied_to)) {

                            if (isset ($replies->replied_to->profile) && !empty ($replies->replied_to->profile)) {

                                $replies->replied_to->profile = $this->addBaseInImage($replies->replied_to->profile);
                            }
                        }
                    });
                }
                $comment->postedAt = time_elapsed_string($comment->created_at);
                ;
                return $comment;
            });

            $post = Post::withCount(['comment'])->with('post_user', function ($q) {
                $q->select('id', 'name', 'user_name', 'profile');
            })->find($request->post_id);

            if (isset($post) && !empty($post)) {

                if (isset($post->media_url) && !empty($post->media_url)) {

                    $post->media_url = $this->addBaseInImage($post->media_url);
                }
                if (!empty($post->post_user)) {

                    $post->post_user->profile = (isset($post->post_user) && !empty($post->post_user->profile)) ? $this->addBaseInImage($post->post_user->profile) : null;
                }

                $post->is_joined = $this->checkCommunityJoind($post->group_id);
                $isExist = $this->IsPostLiked($post->id, $authId);
                $post->is_liked = $isExist['is_liked'];
                $post->reaction = $isExist['reaction'];
                $post->total_likes_count = $isExist['total_likes_count'];
            }
            $data = $comments->items();
            $recordsPerPage = $comments->perPage();
            $currentPage = $comments->currentPage();
            $lastPage = $comments->lastPage();
            $totalRecords = $comments->total();
            $recordsLeft = ($totalRecords - ($recordsPerPage * $currentPage) < 0 ? 0 : $totalRecords - ($recordsPerPage * $currentPage));
            // Merge data items with pagination information
            // Extract pagination metadata
            $paginationInfo = [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'total' => $totalRecords,
                'left' => $recordsLeft,
                // Add other pagination information as needed
            ];

            $responseData = array_merge(['data' => $data], $paginationInfo);


            return response()->json(['status' => 200, 'message' => "comments", 'data' => $responseData, 'post' => $post]);
        } catch (Exception $e) {
            Log::error('Error caught: "getComments" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 500);
        }
    }
    #------  G E T      A L L       C O M M U N I T Y       C O M M E N T S -------------#

    #------------------  G E T      C O M M U N I T Y      P O S T  --------------------#
    public function getCommunityPost($community_id, $authId, $request)
    {
        try {
            // check if i have join the community or not
            $group = Group::withCount('groupMember')->find($community_id);
            if (isset($group) && !empty($group)) {

                if ((isset($group->cover_photo) && !empty($group->cover_photo))) {

                    $group->cover_photo = $this->addBaseInImage($group->cover_photo);
                }

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
            $isGroupMember = GroupMember::where(['group_id' => $community_id, 'user_id' => $authId, 'is_active' => 1])->first();
            if (!$isGroupMember) {

                return response()->json(['status' => 400, 'message' => trans('message.you_are_not_group_member'), 'group' => $group]);
            }

            $limit = 10;
            if (isset($request['limit']) && !empty($request['limit'])) {
                $limit = $request['limit'];
            }


            $posts = Post::whereHas('post_user', function ($query) {
                $query->where('is_active', 1);
            })
                ->with([
                    'group:id,name,description,cover_photo,post_count',
                    'post_user:id,user_name,name,profile'

                ])
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

            // if (!empty($posts)) {

            //     foreach ($posts as $groupPost) {

            //         $media_url = isset($groupPost->media_url) ? asset('storage/' . $groupPost->media_url) : '';
            //         $cover_photo = isset($groupPost->group) && isset($groupPost->group->cover_photo) ?
            //             (filter_var($groupPost->group->cover_photo, FILTER_VALIDATE_URL) ? $groupPost->group->cover_photo : asset('storage/' . $groupPost->group->cover_photo)) : '';
            //         $profile = isset($groupPost->post_user) && isset($groupPost->post_user->profile) ?
            //             (filter_var($groupPost->post_user->profile, FILTER_VALIDATE_URL) ? $groupPost->post_user->profile : asset('storage/' . $groupPost->post_user->profile)) : '';

            //         $groupPost->media_url = $media_url;
            //         $groupPost->group->cover_photo = $cover_photo;
            //         $groupPost->post_user->profile = $profile;
            //         // $groupPost->postedAt = Carbon::parse($groupPost->created_at)->diffForHumans();
            //         $groupPost->postedAt = time_elapsed_string($groupPost->created_at);


            //         $isRepost                =   Post::where(['parent_id'=>$groupPost->id,'user_id'=>$authId,'is_active'=>1])->exists();
            //         $groupPost->is_reposted  =  ($isRepost)?1:0;
            //         $isExist                 =   PostLike::where(['user_id'=>$authId,'post_id'=>$groupPost->id])->first();
            //         $groupPost->reaction     =   (isset($isExist) && !empty($isExist))?$isExist->reaction:0;

            //         $groupPost->post_category_name  =  post_category($groupPost->post_category);

            //     }
            // }
            $posts->getCollection()->transform(function ($post) use ($authId) {
                $isExist = $this->IsPostLiked($post->id, $authId,1);
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
            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError('Error occurred while fetching post.', [], 400);
        }
    }
    #------------------  G E T      C O M M U N I T Y      P O S T  --------------------#



    #---------------- G E T         C O M M E N T      B Y      I D  --------------------#

    public function getCommentById($request, $authId, $message = "")
    {
        try {

            $comment = Comment::where(['id' => $request->comment_id, 'is_active' => 1])->with([
                'commentUser' => function ($query) {
                    $query->select('id', 'name', 'user_name', 'profile');
                },
                'replies.commentUser' => function ($query) {
                    $query->select('id', 'name', 'user_name', 'profile');
                },
                'replies.replied_to' => function ($query) {
                    $query->select('id', 'name', 'user_name', 'profile');
                }
            ])
                ->withCount(['total_comment'])
                ->where('post_id', $request->post_id)
                // ->whereNull('parent_id')
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('blocked_users')
                        ->where(function ($query) use ($authId) {
                            // Check if the authenticated user has blocked someone
                            $query->where('user_id', $authId)
                                ->whereColumn('blocked_users.blocked_user_id', 'comments.user_id');
                        })
                        ->orWhere(function ($query) use ($authId) {
                            // Check if the authenticated user has been blocked by someone
                            $query->where('blocked_user_id', $authId)
                                ->whereColumn('blocked_users.user_id', 'comments.user_id');
                        });
                })->first();
            if (isset($comment) && !empty($comment)) {
                if ($comment->commentUser && $comment->commentUser->profile) {
                    $comment->commentUser->profile = $this->addBaseInImage($comment->commentUser->profile);
                }
                $isExist = $this->IsCommentLiked($comment->post_id, $comment->id, $authId);
                $comment->is_liked = $isExist['is_liked'];
                $comment->reaction = $isExist['reaction'];
                $comment->total_likes_count = $isExist['total_likes_count'];

                if (isset($comment->replies) && ($comment->replies)) {

                    $comment->replies->each(function ($replies) use ($authId) {

                        $isExist = $this->IsCommentLiked($replies->post_id, $replies->id, $authId);
                        $replies->is_liked = $isExist['is_liked'];
                        $replies->reaction = $isExist['reaction'];
                        $replies->total_likes_count = $isExist['total_likes_count'];
                    });
                }
                $comment->postedAt = time_elapsed_string($comment->created_at);
            }

            #------------- Post-comment-----------#
            $postData = Post::select('id', 'support_count', 'helpful_count', 'unhelpful_count', 'is_high_confidence', 'share_count', 'share_count')->withCount(['total_comment'])->find($request->post_id);

            if (isset($postData) && !empty($postData)) {
                $isExist = $this->IsPostLiked($postData->id, $authId);
                $postData->is_liked = $isExist['is_liked'];
                $postData->reaction = $isExist['reaction'];
                $postData->total_likes_count = $isExist['total_likes_count'];
            }

            return response()->json(['status' => 200, 'message' => (!empty($message) ? $message : "comments"), 'data' => $comment, 'post' => $postData]);
        } catch (Exception $e) {

            Log::error('Error caught: "getComments" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #-------------- G E T       P O S T   / C O M M E N T       L I K E S   C O U N T -----------------#

}
