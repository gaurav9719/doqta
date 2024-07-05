<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\Comment;
use App\Models\PostLike;
use App\Models\UserQuota;
use App\Models\ActivityLog;
use App\Models\GroupMember;
use App\Traits\CommonTrait;
use Illuminate\Http\Request;
use App\Models\GroupMemberRequest;
use App\Services\AddCommunityPost;
use App\Traits\IsLikedPostComment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\postCommentLikeCount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\NotificationService;
use App\Http\Controllers\Api\BaseController;

/**
 * Class GetCommunityService.
 */
class GetCommunityService extends BaseController
{

    use IsLikedPostComment, postCommentLikeCount, CommonTrait;
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
            $user = User::findOrFail($authId);

            $homeScreenPosts = $user->posts()

                ->where('posts.is_active', 1)

                ->whereDoesntHave('reportPosts', function ($query) use ($user) {

                    $query->where('user_id', $user->id);
                })
                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                    $query->where('blocked_user_id', $authId);
                })
                ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                    $query->where('user_id', $authId);
                })
                ->whereDoesntHave('hiddenPosts', function ($query) use ($authId) {

                    $query->where('user_id', $authId);
                })
                ->where(function ($query) {
                    $query->whereDoesntHave('parent_post')
                        ->orWhereHas('parent_post', function ($query) {
                            $query->where('is_active', 1)
                                ->whereHas('post_user');
                        });
                })
                ->with([
                    'post_user:id,name,user_name,profile',
                    'group:id,name,description,cover_photo,member_count,post_count,created_by',
                    'parent_post' => function ($query) {

                        $query->select('*')
                            ->where('is_active', 1)
                            ->with([
                                'post_user:id,name,user_name,profile,is_active',
                                'group:id,name,description,created_by'
                            ]);
                    }
                ])

                ->withCount(['total_likes', 'total_comment'])
                ->orderByDesc('id')
                ->simplePaginate($limit);

            $homeScreenPosts->each(function ($homeScreenPost) use ($authId) {

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
                $homeScreenPost->is_reposted     =   $isExist['is_reposted'];

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
                    $homeScreenPost->parent_post->total_likes_count =   $isExist['total_likes_count'];
                    $homeScreenPost->parent_post->total_comment_count =   Comment::where('post_id', $homeScreenPost->parent_post->id)->count();
                    $isRepost                                     =   Post::where(['parent_id' => $homeScreenPost->parent_post->id, 'user_id' => $authId, 'is_active' => 1])->exists();
                    $homeScreenPost->parent_post->is_reposted     =  ($isRepost) ? 1 : 0;
                    $homeScreenPost->parent_post->postedAt        =  time_elapsed_string($homeScreenPost->parent_post->created_at);
                }


                $homeScreenPost->postedAt                         =   time_elapsed_string($homeScreenPost->created_at);
            });

            $new_health_insight_available     =   $this->checkNewHealthInsights($authId);

            $notification_count     =   notification_count();
            return response()->json([
                'status'                    => 200,
                'message'                   => trans("message.home_screen_post"),
                'data'                      => $homeScreenPosts,
                'notification'              => $notification_count,
                'is_new_insights_available' => $new_health_insight_available
            ]);
            // return $this->sendResponse($homeScreenPosts, trans("message.home_screen_post"), 200, $notification_count);
        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    public function homeScreenComponent($request, $authId)
    {
        try {

            $limit          =       10;

            if (isset($request->limit) && !empty($request->limit)) {

                $limit      =       $request->limit;
            }
            $user           =       User::findOrFail($authId);

            $homeScreenPosts = $user->posts()->where('posts.is_active', 1)->whereHas('group')

                #-------------- comment on jun 28 ----------------------#

                //->whereHas('group', function ($query) use ($authId) {

                // $query->whereDoesntHave('groupOwner.blockedBy', function ($query) use ($authId) {

                //     $query->where('user_id', $authId);

                // })->whereDoesntHave('groupOwner.blockedUsers', function ($query) use ($authId) {

                //     $query->where('blocked_user_id', $authId);
                // });

                //})
                #-------------- comment on jun 28 ----------------------#



                ->whereDoesntHave('reportPosts', function ($query) use ($user) {

                    $query->where('user_id', $user->id);
                })
                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                    $query->where('blocked_user_id', $authId);
                })
                ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                    $query->where('user_id', $authId);
                })
                ->whereDoesntHave('hiddenPosts', function ($query) use ($authId) {

                    $query->where('user_id', $authId);
                })
                #- jun 10 
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
                ->with([

                    'post_user:id,name,user_name,profile',

                    'post_user.user_medical_certificate' => function ($q) {

                        $q->select('id', 'medicial_degree_type', 'user_id');
                    },
                    'post_user.user_medical_certificate.medical_certificate' => function ($q) {

                        $q->select('id', 'name');
                    },

                    'group:id,name,description,cover_photo,member_count,post_count,created_by',
                    'parent_post' => function ($query) {

                        $query->select('*')
                            ->where('is_active', 1)
                            ->with([
                                'post_user:id,name,user_name,profile,is_active',

                                'post_user.user_medical_certificate' => function ($q) {

                                    $q->select('id', 'medicial_degree_type', 'user_id');
                                },
                                'post_user.user_medical_certificate.medical_certificate' => function ($q) {

                                    $q->select('id', 'name');
                                },
                                'group:id,name,description,created_by'
                            ]);
                    }
                ])
                ->withCount(['total_likes', 'total_comment'])
                ->orderByDesc('id')
                ->simplePaginate($limit);

            $homeScreenPosts->each(function ($homeScreenPost) use ($authId) {

                $homeScreenPost = transformPostData($homeScreenPost, $authId);
                #------------ parent post data-----------------#

                if (isset($homeScreenPost->parent_post) && !empty($homeScreenPost->parent_post)) {

                    $homeScreenPost = transformParentPostData($homeScreenPost, $authId);
                }

                return $homeScreenPost;
            });

            $new_health_insight_available     =   $this->checkNewHealthInsights($authId);

            $notification_count     =   notification_count();
            return response()->json([
                'status'                    => 200,
                'message'                   => trans("message.home_screen_post"),
                'data'                      => $homeScreenPosts,
                'notification'              => $notification_count,
                'is_new_insights_available' => $new_health_insight_available
            ]);
            // return $this->sendResponse($homeScreenPosts, trans("message.home_screen_post"), 200, $notification_count);
        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }





    public function homeScreenOld($request, $authId)
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

                })->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                    $query->where('blocked_user_id', $authId);
                })

                ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                    $query->where('user_id', $authId);
                })
                // ->whereNotExists(function ($query) use ($authId) {
                //     $query->select(DB::raw(1))
                //         ->from('blocked_users')
                //         ->where('user_id', '=', $authId)                              // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                //         ->where('blocked_users.blocked_user_id','=','posts.user_id'); // Check if the current user has reported the post
                // })
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('hidden_posts')
                        ->whereColumn('hidden_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('hidden_posts.user_id', '=', $authId); // Check if the current user has reported the post 
                })->where(function ($query) {
                    $query->whereDoesntHave('parent_post') // No parent post
                        ->orWhereHas('parent_post', function ($query) {
                            $query->where('is_active', 1) // Parent post is active
                                ->whereHas('post_user', function ($query) {
                                    $query->where('is_active', 1); // Parent post user is active
                                });
                        });
                })->with([
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

                                    $query->select('id', 'name', 'user_name', 'profile')->where('is_active', 1);
                                }
                            ]);
                    }, 'parent_post.group' => function ($query) {

                        $query->select('id', 'name', 'description', 'created_by');
                    }
                ])->withCount(['total_likes', 'total_comment'])
                ->orderByDesc('id')
                ->simplePaginate($limit);

            $homeScreenPosts->each(function ($homeScreenPost) use ($authId) {

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
                    $homeScreenPost->parent_post->total_likes_count =   $isExist['total_likes_count'];
                    $homeScreenPost->parent_post->total_comment_count =   Comment::where('post_id', $homeScreenPost->parent_post->id)->count();
                    $homeScreenPost->parent_post->is_reposted     =  $isExist['is_reposted'];
                    $homeScreenPost->parent_post->postedAt        =  time_elapsed_string($homeScreenPost->parent_post->created_at);
                }
                $homeScreenPost->postedAt                         =   time_elapsed_string($homeScreenPost->created_at);
            });


            $notification_count     =   notification_count();

            return $this->sendResponse($homeScreenPosts, trans("message.home_screen_post"), 200, $notification_count);
        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #------********  G E T      C O M M U N I T Y       P O S T   *********------------#

    public function getCommunityById($communityId, $authId, $message)   #---- changed on jun 11
    {
        try {

            $community = Group::with([

                'groupMember' => function ($query) use ($authId) {

                    $query->limit(10)

                        ->whereDoesntHave('groupUser.blockedBy', function ($query) use ($authId) {

                            $query->where('user_id', $authId);
                        })
                        ->whereDoesntHave('groupUser.blockedUsers', function ($query) use ($authId) {

                            $query->where('blocked_user_id', $authId);
                        });
                }
            ])
                ->withCount(['groupMember'])

                #-------- commented on jun 28 ---------------_#

                // ->whereHas('groupOwner', function ($query) use ($authId) {

                //     $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                //         $query->where('user_id', $authId);
                //     })

                //         ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                //             $query->where('blocked_user_id', $authId);
                //         });
                // })

                #-------- commented on jun 28 ---------------_#


                ->findOrFail($communityId);

            if (isset($community->cover_photo) && !empty($community->cover_photo)) {

                $community->cover_photo =  addBaseUrl($community->cover_photo);
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
            // $communitiesQuery = GroupMember::where('user_id', $authId)

            //     ->whereHas('communities', function ($query) {

            //         $query->where('is_active', 1);

            //     })->pluck('group_id');


            $communitiesQuery = GroupMember::where('user_id', $authId)  #------ changed on 11 jun

                ->whereHas('communities', function ($query) use ($authId) {

                    $query->where('is_active', 1);

                    // ->whereHas('groupOwner', function ($query) use ($authId) {

                    //     $query->where('is_active', 1) // Ensure group owner is active

                    //         ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                    //             $query->where('user_id', $authId);
                    //         })
                    //         ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                    //             $query->where('blocked_user_id', $authId);

                    //         });
                    // });

                })->pluck('group_id');

            $communities     =          Group::whereIn('id', $communitiesQuery)->orderByDesc('id')->simplePaginate($limit);

            return $this->communityLoop($communities, $authId);
            
        } catch (Exception $e) {
            // Handle exceptions
            Log::error('Error caught: "getJoinedCommunity" ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred.'], 400);
        }
    }
    #----------------- G E T    J O I N E D      C O M M U N I T Y --------------------#


    #------------------------ G E T     C O M M U N I T Y   B Y     S E A R C H  -----------------------#
    public function getCommunityBySearch($request, $authId) #--- changed on 11 jun
    {
        try {

            $limit          =   10;

            if (isset($request->limit) && !empty($request->limit)) {

                $limit      = $request->limit;
            }
            $communities    = Group::where('name', 'LIKE', "%$request->search%")
                ->where('is_active', 1)
                ->orderBy('name', 'asc')
                ->simplePaginate($limit);
            return $this->communityLoop($communities, $authId);

            #-------- removed logic on jun28-----------#

            // ->whereHas('groupOwner', function ($query) use ($authId) {

            //     $query->where('is_active', 1) // Check if group owner is active

            //     ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

            //         $query->where('user_id', $authId);

            //     })
            //     ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

            //         $query->where('blocked_user_id', $authId);

            //     });
            // })
            #-------- removed logic on jun28-----------#

        } catch (Exception $e) {
            // Handle exceptions
            Log::error('Error caught: "getCommunityBySearch" ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    #------------------------ G E T     C O M M U N I T Y   B Y     S E A R C H  -----------------------#


    #----------------------  C O M M U N I T Y      C O M M O N     L O O P -------------------#
    public function communityLoop($communities, $authId)
    {
        $communities->each(function ($community) use ($authId) {

            if (isset($community) && !empty($community)) {

                if (isset($community->cover_photo) && !empty($community->cover_photo)) {

                    $community->cover_photo = addBaseUrl($community->cover_photo);
                }
            }
            //check i am the member of the community or not
            $isExist = GroupMember::where(['group_id' => $community->id, 'is_active' => 1, 'user_id' => $authId])->first();

            if (isset($isExist) && !empty($isExist)) {

                $community->is_joined = 1;
                $community->role      = $isExist->role;
            } else {

                $request                    = GroupMemberRequest::where(['group_id' => $community->id, 'is_active' => 1, 'user_id' => $authId])->first();

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

    public function joinUnjoin($request, $authId, $group)
    {

        if ($request->type == 1) {

            return $this->joinCommunity($request, $authId, $group);
        } else {

            return $this->removeCommunity($request, $authId, $group);
        }
    }
    #-------------_________-----************ E N D ************----------------------------#


    #---------------------------  J O I N       C O M M U N I T Y -------------------------#
    public function joinCommunity($request, $authId, $group)
    {

        DB::beginTransaction();

        try {

            $alreadyMember         =        GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId])->exists();

            if ($alreadyMember) {

                return $this->sendResponsewithoutData(trans('message.already_group_member'), 409);
            }

            #----------- commented on jun 28 -------------------#
            // $isBlocked            =   IsCommunityOwnerBlocked($request->community_id, $authId);

            // if (!$isBlocked) {

            //     return $this->sendResponsewithoutData(trans('message.invalid_group'), 409);
            // }

            #----------- commented on jun 28 -------------------#

            if ($group->visibility == 1) {         ##--------- PUBLIC COMMUNITIES ------------#

                $addGroupMember             =   new GroupMember();

                $addGroupMember->group_id   =   $request->community_id;

                $addGroupMember->user_id    =   $authId;

                $addGroupMember->role       =   "member";

                if ($addGroupMember->save()) {

                    #--------------  RECORD USER QUOTA PER DAY-------------#
                    if (isset($commentId) && !empty($commentId)) {

                        $quotaUpdated               = UserQuota::updateQuota($authId, 'community_join_request');
                    }
                    #--------------  RECORD USER QUOTA PER DAY-------------#

                    // increment in group member
                    incrementMemberWithAuth($request->community_id, 1);

                    $group         =   Group::find($request->community_id);

                    $sender        =   Auth::user();

                    //   $receiver      =   User::find($group->created_by);

                    $receiver      =   GroupMember::with('group_user')->whereHas('groupUser', function ($query) {

                        $query->where('is_active');

                    })->where(['group_id' => $request->community_id, 'role' => "owner"])->first();

                    if ($receiver) {
                        // Retrieve only the group_user data
                        $receiver = $receiver->group_user;

                        // Use $groupUser for further processing

                        $mesage        =   "**{$sender->name}** " . trans('notification_message.joined_community') . " **{$group->name}** community";

                        $data          =   [
                            "message"               => $mesage,
                            "community_member_id"   => $addGroupMember->id,
                            "community_id"          => $group->id
                        ];
                        $type           =       trans('notification_message.joined_community_type');

                        $this->notification->sendNotificationNew($sender, $receiver, $type, $data);
                    }

                    #-------  A C T I V I T Y -----------#
                    $activity                       =    new ActivityLog();

                    $activity->user_id              =    $authId;

                    $activity->community_id         =    $group->id;

                    $activity->community_member_id  =    $addGroupMember->id;

                    $activity->action_details       =    "**{$sender->name}** Joined the **{$group->name}** community";

                    $activity->action               =    $type;    //Joined the community

                    $activity->save();
                    #-------  A C T I V I T Y -----------#
                    DB::commit();
                    
                    $result                         =   $this->communityMemberCount($request->community_id, $authId);

                    return $this->sendResponse($result, trans('message.community_joined_successfully'), 200);
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
                    #--------------  RECORD USER QUOTA PER DAY-------------#
                    if (isset($commentId) && !empty($commentId)) {

                        $quotaUpdated      =    UserQuota::updateQuota($authId, 'community_join_request');

                    }
                    #--------------  RECORD USER QUOTA PER DAY-------------#
                    $group                  =   Group::find($request->community_id);

                    $reciever               =   User::select('id', 'device_token', 'device_type')->where("id", $group->user_id)->first();

                    $sender                 =   User::select('id', 'device_token', 'device_type')->where("id", $authId)->first();

                    $notification_type      =   trans('notification_message.new_memeber_group_request_type');

                    $notification_message   =   trans('notification_message.new_memeber_group_request_type_message');

                    $this->notification->sendNotification($reciever, $sender, $notification_message, $notification_type);

                    DB::commit();
                    $result                 =   $this->communityMemberCount($request->community_id, $authId);
                    return $this->sendResponse($result, trans('message.request_send_successfuly'), 200);
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

    public function removeCommunity($request, $authId, $group)
    {

        DB::beginTransaction();
        try {

            $alreadyMember                  =   GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId])->first();

            if (!$alreadyMember) {

                return $this->sendResponsewithoutData(trans('message.not_group_member'), 409);
            } else {

                if ($group['created_by'] == $authId) {

                    return $this->sendError(trans('message.owner_cannot_leave_community'), [], 400);
                }
                $alreadyMember->delete();
                DB::commit();
                decrementMemberWithAuth($request->community_id, 1);
                $result                 =   $this->communityMemberCount($request->community_id, $authId);
                return $this->sendResponse($result, trans('message.remove_successfully'), 200);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "removeCommunity" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------   R E M O V E        C O M M U N I T Y     -----------------------#



    public function communityMemberCount($communityId, $authId)
    {
        $memberCount                 =   GroupMember::where(['group_id' => $communityId, 'is_active' => 1])->count();
        $is_member                   =   GroupMember::where(['group_id' => $communityId, 'is_active' => 1, 'user_id' => $authId])->exists();
        $response['groupMemberCount'] =   $memberCount;
        $response['is_joined']       =   ($is_member) ? 1 : 0;
        return $response;
    }
}
