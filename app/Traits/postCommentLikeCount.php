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

trait postCommentLikeCount
{
    use IsLikedPostComment;
    public function postLikeCount($post_id)
    {

        $userId                 =       Auth::id();
        $postLike['total_likes_count']           =       PostLike::where(['post_id' => $post_id])->count();
        $hasLiked               =       PostLike::where(['post_id' => $post_id, 'user_id' => $userId])->first();
        $postLike['is_liked']   =       (isset($hasLiked) && !empty($hasLiked)) ? 1 : 0;
        $postLike['reaction']   =       (isset($hasLiked) && !empty($hasLiked)) ? $hasLiked->reaction : 0;
        return $postLike;
    }

    public function commentLikeCount($comment_id)
    {

        $userId                         =       Auth::id();
        $postLike['total_likes_count']  =       CommentLike::where(['comment_id' => $comment_id])->count();
        $hasLiked                       =       CommentLike::where(['comment_id' => $comment_id, 'user_id' => $userId])->first();
        $postLike['is_liked']           =       (isset($hasLiked) && !empty($hasLiked)) ? 1 : 0;
        $postLike['reaction']           =       (isset($hasLiked) && !empty($hasLiked)) ? $hasLiked->reaction : 0;
        return $postLike;
    }
    public function addBaseInImage($cover_photo)
    {

        if (isset($cover_photo) && !empty($cover_photo)) {

            return (filter_var($cover_photo, FILTER_VALIDATE_URL)) ? $cover_photo : asset('storage/' . $cover_photo);
        } else {

            return null;
        }
    }


    public function getPost($postId, $authId, $mesage)
    {
        try {

            $user           =      User::findOrFail($authId);
            $homeScreenPost =      Post::where(['posts.is_active' => 1, 'id' => $postId])

                ->whereNotExists(function ($query) use ($user) {

                    $query->select(DB::raw(1))

                        ->from('report_posts')
                        ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('report_posts.user_id', '=', $user->id); // Check if the current user has reported the post
                })

                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                    $query->where('blocked_user_id', $authId);
                })
                ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                    $query->where('user_id', $authId);
                })

                ->whereNotExists(function ($query) use ($authId) {

                    $query->select(DB::raw(1))
                        ->from('hidden_posts')
                        ->whereColumn('hidden_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('hidden_posts.user_id', '=', $authId); // Check if the current user has reported the post 
                })
                #----- check if post user blocked or login user blocked to post user
                ->where(function ($query) use ($authId) {

                    $query->whereDoesntHave('parent_post', function ($query) use ($authId) {

                        $query->where('is_active', 1)

                            ->whereHas('post_user', function ($query) use ($authId) {
                                // Check if authenticated user is not blocked by the post user
                                $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                                    $query->where('user_id', $authId);
                                });
                            });
                    })
                        ->orWhereHas('parent_post', function ($query) use ($authId) {
                            $query->where('is_active', 1)
                                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
                                    // Check if post user is not blocked by the authenticated user
                                    $query->where('blocked_user_id', $authId);
                                });
                        });
                })

                #----- check if post user blocked or login user blocked to post user
                ->with([
                    'post_user' => function ($query) {

                        $query->select('id', 'name', 'user_name', 'profile');
                    },
                    'group' => function ($query) {
                        $query->select('id', 'name', 'description', 'cover_photo', 'member_count', 'post_count', 'created_by');
                    },
                    'parent_post' => function ($query) {
                        $query->select('*')
                            ->where('is_active', 1)
                            ->with([
                                'post_user' => function ($query) {

                                    $query->select('id', 'name', 'user_name', 'profile');
                                }
                            ]);
                    }
                ])->withCount(['total_likes', 'total_comment'])->first();


            if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

                $homeScreenPost->media_url      =  $this->addBaseInImage($homeScreenPost->media_url);
            }

            if (isset($homeScreenPost->thumbnail) && !empty($homeScreenPost->thumbnail)) {

                $homeScreenPost->thumbnail      =  $this->addBaseInImage($homeScreenPost->thumbnail);
            }

            if ($homeScreenPost->parent_post && $homeScreenPost->parent_post->post_user && $homeScreenPost->parent_post->post_user->profile) {

                $homeScreenPost->parent_post->post_user->profile = $this->addBaseInImage($homeScreenPost->parent_post->post_user->profile);
            }

            if (isset($homeScreenPost->post_user) &&  !empty($homeScreenPost->post_user->profile)) {

                $homeScreenPost->post_user->profile      =  $this->addBaseInImage($homeScreenPost->post_user->profile);
            }
            if ($homeScreenPost->group &&  $homeScreenPost->group->cover_photo) {

                $homeScreenPost->group->cover_photo      =  $this->addBaseInImage($homeScreenPost->group->cover_photo);
            }
            $isExist                         =   $this->IsPostLiked($homeScreenPost->id, $authId);
            $homeScreenPost->is_liked        =   $isExist['is_liked'];
            $homeScreenPost->reaction        =   $isExist['reaction'];
            $isRepost                        =   Post::where(['parent_id' => $homeScreenPost->id, 'user_id' => $authId, 'is_active' => 1])->exists();
            $homeScreenPost->is_reposted     =  ($isRepost) ? 1 : 0;

            $homeScreenPost->post_category_name = post_category($homeScreenPost->post_category);
            #------------ parent post data-----------------#
            if (isset($homeScreenPost->parent_post) && !empty($homeScreenPost->parent_post)) {

                if (isset($homeScreenPost->parent_post->media_url) && !empty($homeScreenPost->parent_post->media_url)) {

                    $homeScreenPost->parent_post->media_url   =  $this->addBaseInImage($homeScreenPost->parent_post->media_url);
                }

                if (isset($homeScreenPost->parent_post->thumbnail) && !empty($homeScreenPost->parent_post->thumbnail)) {

                    $homeScreenPost->parent_post->thumbnail   =  $this->addBaseInImage($homeScreenPost->parent_post->thumbnail);
                }


                $isExist                                      =   $this->IsPostLiked($homeScreenPost->parent_post->id, $authId);
                $homeScreenPost->parent_post->is_liked        =   $isExist['is_liked'];
                $homeScreenPost->parent_post->reaction        =   $isExist['reaction'];
                $homeScreenPost->parent_post->is_reposted     =  $isExist['is_reposted'];
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
    public function getCommunityPostOLD($community_id, $authId, $request)
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
                            $query->select('id', 'name', 'user_name', 'profile');
                        }
                    ]);
            }, 'parent_post.group' => function ($query) {

                $query->select('id', 'name', 'description', 'created_by');
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
                // ->whereNotExists(function ($query) use ($authId) {

                //     $query->select(DB::raw(1))

                //         ->from('blocked_users')

                //         ->where('user_id', '=', $authId) // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                //         ->where('blocked_users.blocked_user_id', '=', 'posts.user_id'); // Check if the current user has reported the post
                // });
                #------ jun 10 ----------#
                ->whereNotExists(function ($query) use ($authId) {

                    $query->select(DB::raw(1))->from('blocked_users')
                        ->where(fn ($query) => $query->where('user_id', $authId)->whereColumn('blocked_users.blocked_user_id', 'posts.user_id'))
                        ->orWhere(fn ($query) => $query->where('blocked_user_id', $authId)->whereColumn('blocked_users.user_id', 'posts.user_id'));
                });
            #------ jun 10 ----------#
            if (isset($request['post_category_id']) && !empty($request['post_category_id'])) {

                $posts->where('post_category', $request['post_category_id']);
            }
            $posts = $posts->orderByDesc('id')->simplePaginate($limit);

            //dd(DB::getQueryLog());
            $posts->getCollection()->transform(function ($post) use ($authId) {

                if (isset($post->parent_post) && !empty($post->parent_post)) {


                    return transformParentPostData($post, $authId);

                    // if ($post->parent_post->post_user && $post->parent_post->post_user->profile) {

                    //     $post->parent_post->post_user->profile       = $this->addBaseInImage($post->parent_post->post_user->profile);
                    // }

                    // if (isset($post->parent_post->media_url) && !empty($post->parent_post->media_url)) {

                    //     $post->parent_post->media_url        =       $this->addBaseInImage($post->parent_post->media_url);
                    // }

                    // if (isset($post->parent_post->thumbnail) && !empty($post->parent_post->thumbnail)) {

                    //     $post->parent_post->thumbnail        =       $this->addBaseInImage($post->parent_post->thumbnail);
                    // }

                    // $isExist                                 =       $this->IsPostLiked($post->parent_post->id, $authId,1);
                    // $post->parent_post->is_liked             =       $isExist['is_liked'];
                    // $post->parent_post->reaction             =       $isExist['reaction'];
                    // $post->parent_post->total_likes_count    =       $isExist['total_likes_count'];
                    // $post->parent_post->total_comment_count  =       $isExist['total_comment_count'];
                    // $isRepost                                =       Post::where(['parent_id'=>$post->parent_post->id,'user_id'=>$authId,'is_active'=>1])->exists();
                    // $post->parent_post->is_reposted          =       ($isRepost)?1:0;
                    // $post->parent_post->postedAt             =      time_elapsed_string($post->created_at);

                }


                return transformPostData($post, $authId);
                // $isRepost                                    =      Post::where(['parent_id'=>$post->id,'user_id'=>$authId,'is_active'=>1])->exists();
                // $post->is_reposted                           =      ($isRepost)?1:0;
                // $isExist                                     =      $this->IsPostLiked($post->id, $authId,1);
                // $post->is_liked =       $isExist['is_liked'];
                // $post->reaction =       $isExist['reaction'];
                // $post->total_likes_count = $isExist['total_likes_count'];
                // $post->total_comment_count = $isExist['total_comment_count'];

                // if (isset ($post->media_url) && !empty ($post->media_url)) {

                //     $post->media_url = $this->addBaseInImage($post->media_url);
                // }

                // if (isset ($post->thumbnail) && !empty ($post->thumbnail)) {

                //     $post->thumbnail = $this->addBaseInImage($post->thumbnail);
                // }

                // if ($post->group && $post->group->cover_photo) {

                //     $post->group->cover_photo = $this->addBaseInImage($post->group->cover_photo);
                // }

                // // Check if the post has a user associated with it and user profile is set
                // if ($post->post_user && $post->post_user->profile) {

                //     $post->post_user->profile = $this->addBaseInImage($post->post_user->profile);
                // }
                // $post->postedAt = time_elapsed_string($post->created_at);

                // $post->is_reposted = (Post::where('parent_id', $post->id)
                //     ->where('user_id', $authId)
                //     ->where('is_active', 1)
                //     ->exists())?1:0;
                // $post->post_category_name = post_category($post->post_category);

                // return $post;
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



    #----------- G E T      C O M M U N I T Y           P O S T         B Y     I D ------------------_#
    public function getCommunityPostJUN13($community_id, $authId, $request)
    {
        try {
            
            $group =     Group::where('id', $community_id)->where('is_active', 1)

                        ->whereHas('groupOwner', function ($query) use ($authId) {

                            $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {
                                    $query->where('user_id', $authId);
                                })
                                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
                                    $query->where('blocked_user_id', $authId);
                                });
                        })->withCount('groupMember')->first();

            if (empty($group)) {

                return $this->sendResponsewithoutData(trans('message.invalid_group'), 400);
            }

            //check group created user not block me or neither blocked by me 
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

                $query->where('is_active', 1);
            })
            ->with([
                'group:id,name,description,cover_photo,post_count',
                'post_user:id,user_name,name,profile',
                'parent_post' => function ($query) {
                    $query->select('*')
                        ->where('is_active', 1)
                        ->with([
                            'post_user:id,name,user_name,profile',
                            'group:id,name,description,created_by,is_active'
                        ]);
                }
            ])
            ->where('group_id', $community_id)
            ->where('is_active', 1)
            ->where(function ($query) use ($authId) {
                $query->whereDoesntHave('post_user.blockedBy', function ($query) use ($authId) {
                        $query->where('user_id', $authId);
                    })
                    ->whereDoesntHave('post_user.blockedUsers', function ($query) use ($authId) {
                        $query->where('blocked_user_id', $authId);
                    })
                    ->whereDoesntHave('parent_post.post_user.blockedBy', function ($query) use ($authId) {
                        $query->where('user_id', $authId);
                    })
                    ->whereDoesntHave('parent_post.post_user.blockedUsers', function ($query) use ($authId) {
                        $query->where('blocked_user_id', $authId);
                    });
            })
            ->whereNotExists(function ($query) use ($authId) {
                $query->select(DB::raw(1))
                    ->from('hidden_posts')
                    ->whereColumn('hidden_posts.post_id', 'posts.id')
                    ->where('hidden_posts.user_id', $authId);
            })
            ->whereNotExists(function ($query) use ($authId) {
                $query->select(DB::raw(1))
                    ->from('report_posts')
                    ->whereColumn('report_posts.post_id', 'posts.id')
                    ->where('report_posts.user_id', $authId);
            })
            ->whereNotExists(function ($query) use ($authId) {
                $query->select(DB::raw(1))
                    ->from('blocked_users')
                    ->where(function ($query) use ($authId) {
                        $query->where('user_id', $authId)
                            ->whereColumn('blocked_users.blocked_user_id', 'posts.user_id')
                            ->orWhere('blocked_user_id', $authId)
                            ->whereColumn('blocked_users.user_id', 'posts.user_id');
                    });
            });
            
            if (isset($request['post_category_id']) && !empty($request['post_category_id'])) {
                $posts->where('post_category', $request['post_category_id']);
            }
            
            $posts = $posts->orderByDesc('id')->simplePaginate($limit);

            $posts->getCollection()->transform(function ($post) use ($authId) {
                if(isset($post) && !empty($post)){

                    $post  = transformPostData($post, $authId);
                }
                
                if (isset($post->parent_post) && !empty($post->parent_post)) {

                    $post = transformParentPostData($post, $authId);
                }
                return $post;
            });

            #------------ G R O U P        D A T A    ---------------------#
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


    public function getCommunityPost($community_id, $authId, $request,$authUser)
    {
        try {

            $groupExit              =   GroupData($authId, $community_id);

            if(isset($groupExit) && $groupExit!="400"){

                $group              =   $groupExit;

            }else{

                return $this->sendResponsewithoutData(trans('message.invalid_group'), 400);

            }
           
            $isGroupMember          =   GroupMember::where(['group_id' => $community_id, 'user_id' => $authId, 'is_active' => 1])->first();

            if (!$isGroupMember) {

                return response()->json(['status' => 201, 'message' => trans('message.you_are_not_group_member'), 'group' => $group]);
            }
            $limit                  =   10;

            if (isset($request['limit']) && !empty($request['limit'])) {

                $limit              =   $request['limit'];
            }

            $posts                 =   fetchPosts($request, $community_id, $limit, $authUser);

            $posts->getCollection()->transform(function ($post) use ($authId) {

                if(isset($post) && !empty($post)){

                    $post  = transformPostData($post, $authId);
                }
                if (isset($post->parent_post) && !empty($post->parent_post)) {

                    $post = transformParentPostData($post, $authId);
                }
                return $post;
            });

            return response()->json(['status' => 200, 'message' => trans('message.community_post'), 'data' => $posts, 'group' => $group]);

        } catch (Exception $e) {
            Log::error('Error caught: "getCommunityPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    public function getPostNew($postId, $authId, $mesage)
    {
        try {

            $user           =      User::findOrFail($authId);
            $homeScreenPost =      Post::where(['posts.is_active' => 1, 'id' => $postId])

            ->whereHas('group.groupOwner', function ($query) use ($authId) {

                $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                        $query->where('user_id', $authId);

                    })
                    ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                        $query->where('blocked_user_id', $authId);

                    });
                })->whereNotExists(function ($query) use ($user) {

                    $query->select(DB::raw(1))

                        ->from('report_posts')
                        ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('report_posts.user_id', '=', $user->id); // Check if the current user has reported the post
                })

                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                    $query->where('blocked_user_id', $authId);
                })

                ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                    $query->where('user_id', $authId);
                })

                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('hidden_posts')
                        ->whereColumn('hidden_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('hidden_posts.user_id', '=', $authId); // Check if the current user has reported the post 
                })

                #----- check if post user blocked or login user blocked to post user
                ->where(function ($query) use ($authId) {

                    $query->whereDoesntHave('parent_post', function ($query) use ($authId) {
                        $query->where('is_active', 1)
                        ->whereHas('post_user', function ($query) use ($authId) {
                            // Check if authenticated user is not blocked by the post user
                            $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {
                                
                                $query->where('user_id', $authId);
                            });
                        });
                    })
                    ->orWhereHas('parent_post', function ($query) use ($authId) {
                        $query->where('is_active', 1)
                        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
                            // Check if post user is not blocked by the authenticated user
                            $query->where('blocked_user_id', $authId);
                        });
                    });

                })
                #----- check if post user blocked or login user blocked to post user
                ->with([

                    'post_user' => function ($query) {
                        $query->select('id', 'name', 'user_name', 'profile');
                    },

                    'post_user.user_medical_certificate'=>function($q){

                        $q->select('id','medicial_degree_type','user_id');

                    },
                    'post_user.user_medical_certificate.medical_certificate'=>function($q){

                        $q->select('id','name');
                    },
                    'group' => function ($query) {
                        $query->select('id', 'name', 'description', 'cover_photo', 'member_count', 'post_count', 'created_by');
                    },
                    'parent_post' => function ($query) {
                        $query->select('*')
                        ->where('is_active', 1)
                        ->with([
                            'post_user' => function ($query) {
                                $query->select('id', 'name', 'user_name', 'profile');
                            },
                            'post_user.user_medical_certificate'=>function($q){

                                $q->select('id','medicial_degree_type','user_id');
        
                            },
                            'post_user.user_medical_certificate.medical_certificate'=>function($q){
        
                                $q->select('id','name');
                            },
                        ]);
                    }
                ])->withCount(['total_likes', 'total_comment'])->first();

            if (isset($homeScreenPost) && !empty($homeScreenPost)) {

                $homeScreenPost = transformPostData($homeScreenPost, $authId);

                if (isset($homeScreenPost->parent_post) && !empty($homeScreenPost->parent_post)) {

                    $homeScreenPost = transformParentPostData($homeScreenPost, $authId);
                }
            }
            return $this->sendResponse($homeScreenPost, $mesage, 200);

        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getLine());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }


    public function getCommunityPostCopy($community_id, $authId, $request)
    {
        try {

            $group =     Group::where('id', $community_id)->where('is_active', 1)

                        ->whereHas('groupOwner', function ($query) use ($authId) {

                            $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {
                                    $query->where('user_id', $authId);
                                })
                                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
                                    $query->where('blocked_user_id', $authId);
                                });
                        })->withCount('groupMember')->first();

            if (empty($group)) {

                return $this->sendResponsewithoutData(trans('message.invalid_group'), 400);
            }

            //check group created user not block me or neither blocked by me 
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

            $authUser               =   Auth::user();






            $posts = Post::whereHas('post_user', function ($query) {
                
                $query->where('is_active', 1);
            });

            #-------------- trending post -----------___#

            if(isset($request->trending) && !empty($request->trending)){

                if ($request->trending_type == 1 ) {

                    $one_week_ago = now()->subWeek();

                    $posts->where('created_at', '>=', $one_week_ago)

                        ->orderBy('like_count', 'desc');

                } elseif ($request->trending_type == 2) {

                    $start_of_month = now()->startOfMonth();

                    $posts->where('created_at', '>=', $start_of_month)

                        ->orderBy('like_count', 'desc');
                }
            }
            #----------------------------------------------#

            #------------ CONFIDENCE SCORE -----------#

            if (isset($request->confidence) && !empty($request->confidence)) {

                $posts->where('is_high_confidence', 1);
            }
             #------------ CONFIDENCE SCORE -----------#

              #-------------- LOCATION -----------------#
            if (isset($request->location) && !empty($request->location)) {

                $lat   =  $authUser->lat;
                $long  = $authUser->long;
                $distance   = $request->distance;
                $posts->select('*',DB::raw("round(6371 * acos(cos(radians('". $lat."')) 
                * cos(radians(`lat`)) 
                * cos(radians(`long`) 
                - radians('" .$long. "')) 
                + sin(radians('" . $lat. "')) 
                * sin(radians(`lat`))),2) AS distance"))->having("distance", "<", $distance); 
            }

            #------- check if health provider ----------#

            if (isset($request->health_provider) && !empty($request->health_provider)) {

                $posts->whereHas('post_user.userParticipant', fn ($query) => $query->where('participant_id', 3));

            }
            #------- check if health provider ----------#

            $posts->with([

                'group:id,name,description,cover_photo,post_count',
                'post_user:id,user_name,name,profile',
                'parent_post' => function ($query) {
                    $query->select('*')
                        ->where('is_active', 1)
                        ->with([
                            'post_user:id,name,user_name,profile',
                            'group:id,name,description,created_by,is_active'
                        ]);
                }
            ])
            ->where('group_id', $community_id)
            ->where('is_active', 1)
            ->where(function ($query) use ($authId) {

                $query->whereDoesntHave('post_user.blockedBy', function ($query) use ($authId) {
                        $query->where('user_id', $authId);
                    })
                    ->whereDoesntHave('post_user.blockedUsers', function ($query) use ($authId) {
                        $query->where('blocked_user_id', $authId);
                    })
                    ->whereDoesntHave('parent_post.post_user.blockedBy', function ($query) use ($authId) {
                        $query->where('user_id', $authId);
                    })
                    ->whereDoesntHave('parent_post.post_user.blockedUsers', function ($query) use ($authId) {
                        $query->where('blocked_user_id', $authId);
                    });
            })


            ->whereNotExists(function ($query) use ($authId) {
                $query->select(DB::raw(1))
                    ->from('hidden_posts')
                    ->whereColumn('hidden_posts.post_id', 'posts.id')
                    ->where('hidden_posts.user_id', $authId);
            })
            ->whereNotExists(function ($query) use ($authId) {
                $query->select(DB::raw(1))
                    ->from('report_posts')
                    ->whereColumn('report_posts.post_id', 'posts.id')
                    ->where('report_posts.user_id', $authId);
            })
            ->whereNotExists(function ($query) use ($authId) {
                $query->select(DB::raw(1))
                    ->from('blocked_users')
                    ->where(function ($query) use ($authId) {
                        $query->where('user_id', $authId)
                            ->whereColumn('blocked_users.blocked_user_id', 'posts.user_id')
                            ->orWhere('blocked_user_id', $authId)
                            ->whereColumn('blocked_users.user_id', 'posts.user_id');
                    });
            });
            
            if (isset($request['post_category_id']) && !empty($request['post_category_id'])) {

                $posts->where('post_category', $request['post_category_id']);
            }
            
            $posts = $posts->orderByDesc('id')->simplePaginate($limit);







            $posts->getCollection()->transform(function ($post) use ($authId) {
                if(isset($post) && !empty($post)){

                    $post  = transformPostData($post, $authId);
                }
                
                if (isset($post->parent_post) && !empty($post->parent_post)) {

                    $post = transformParentPostData($post, $authId);
                }
                return $post;
            });

            #------------ G R O U P        D A T A    ---------------------#
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







}
