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
use App\Models\CommentLike;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use App\Traits\SummarizePost;
use App\Traits\CalculateScore;
use App\Traits\IsCommunityJoined;
use App\Models\GroupMemberRequest;
use App\Traits\IsLikedPostComment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\FeedPostNotification;
use App\Traits\postCommentLikeCount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use GeminiAPI\Laravel\Facades\Gemini;
use App\Models\UserParticipantCategory;
use App\Http\Controllers\Api\BaseController;
use App\Jobs\CalculateScore\scoreCalculation;
use App\Jobs\FeedPostNotification as feedPostionJob;
use App\Jobs\Summarize\SummarizePost as SummarizePostJob;

/**
 * Class AddCommunityPost.
 */
class AddCommunityPost extends BaseController
{
    use postCommentLikeCount, IsCommunityJoined, FeedPostNotification, IsLikedPostComment, SummarizePost, CalculateScore;
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

            $is_health_provider =   UserParticipantCategory::where('user_id', $authId)->where('participant_id', 3)->exists() ? 1 : 0;

            $post               =   new Post();

            $post->user_id      =   $authId;

            $post->title        =   $request->title;

            $post->content      =   $request->content;

            $post->is_health_provider = $is_health_provider; // add true is user is health provider

            $post->media_type   = $request->media_type;

            if ($request->hasFile('media')) {

                $post_image         = $request->file('media');
                $Uploaded           = upload_file($post_image, 'post_images');
                $post->media_url    = $Uploaded;
                $post->media_type   = $request->media_type;
            }

            if ($request->hasFile('thumbnail')) {

                $thumbnail          = $request->file('thumbnail');
                $ThumbnailUploaded  = upload_file($thumbnail, 'post_thumbnail');
                $post->thumbnail    = $ThumbnailUploaded;
            }

            $user                  = User::find($authId);

            if (!empty($user->lat) && !empty($user->long)) {

                $post->lat          =   $user->lat;

                $post->long         =   $user->long;
            } elseif (!empty($request->lat) && !empty($request->long)) {

                $post->lat          =   $request->lat;

                $post->long         =   $request->long;
            }

            if (isset($request->link) && !empty($request->link)) {

                $post->link         =   $request->link;
            }

            if (isset($request->wrote_by) && !empty($request->wrote_by)) {

                $post->wrote_by     =   $request->wrote_by;
            }

            $post->group_id             = $request->community_id;
            $post->post_type            = $request->post_type; //normal,community
            $post->post_category        = $request->post_category; //1: seeing advice, 2: giving advice, 3: sharing media	
            $post->save();
            $postId                     = $post->id;
            #--------------  RECORD USER QUOTA PER DAY-------------#
            if (isset($postId) && !empty($postId)) {

                $quotaUpdated               = UserQuota::updateQuota($authId, 'community_post');
            }
            #--------------  RECORD USER QUOTA PER DAY-------------#\
            DB::commit();

            try {

                if (strlen($post->content) > 75) {

                    dispatch(new SummarizePostJob($postId))->chain([
                        new ScoreCalculation($postId)
                    ]);

                    $this->summerize($postId);
                } else {

                    dispatch(new ScoreCalculation($postId));
                }
                // Dispatch job for summarizing post and calculating score
            } catch (\Throwable $exception) {

                Log::error('Failed to dispatch jobs', ['exception' => $exception]);
            }

            //Do summarize the post
            // $this->calculateScoreByAi($postId);
            increment('groups', ['id' => $request->community_id], 'post_count', 1);          // add increment to group post
            #-------  A C T I V I T Y -----------#
            $group                      =    Group::find($request->community_id);
            $activity                   =    new ActivityLog();
            $activity->user_id          =    $authId;
            $activity->post_id          =    $post->id;
            $activity->community_id     =    $group->id;
            $activity->action_details   =    "Posted in  " . $group->name;
            $activity->action           =    trans('notification_message.posted_in_community');    //Posted in community
            $activity->save();
            #-------  A C T I V I T Y -----------#
            $this->feedPostNotification($request->community_id, $postId, Auth::user());

            DB::commit();
            // add increment to group post
            return $this->sendResponsewithoutData(trans("message.add_posted_successfully"), 200);

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
            $editPost->media_type    = $request->media_type;
            $editPost->post_type     = $request->post_type;
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
                'post_user:id,name,user_name,profile'
            ])->find($id);

            if (!$post) {

                return $this->sendError('Post not found.', [], 404);
            }

            if (isset($post->media_url) && !empty($post->media_url)) {

                $post->media_url        = addBaseUrl($post->media_url);
            }

            if (isset($post->thumbnail) && !empty($post->thumbnail)) {

                $post->thumbnail        = addBaseUrl($post->thumbnail);
            }

            if (isset($post->group) && !empty($post->group)) {

                if (isset($post->group->cover_photo) && !empty($post->group->cover_photo)) {

                    $post->group->cover_photo   =     addBaseUrl($post->group->cover_photo);
                }
            }
            if ($post->post_user && $post->post_user->profile) {

                $post->post_user->profile       =   addBaseUrl($post->post_user->profile);
            }

            $isExist                            = PostLike::where(['user_id' => $authId, 'post_id' => $post->id])->first();
            // sdd($isExist);

            $isExist                    =       $this->IsPostLiked($post->id, $authId, 1);
            $post->is_liked             =       $isExist['is_liked'];
            $post->reaction             =       $isExist['reaction'];
            $post->total_likes_count    =       $isExist['total_likes_count'];
            $post->total_comment_count  =       $isExist['total_comment_count'];
            // $post->is_liked = (isset($isExist) && !empty($isExist)) ? 1 : 0;
            // $post->reaction = (isset($isExist) && !empty($isExist)) ? $isExist->reaction : 0;
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

                    $media_url                          = isset($groupPost->media_url) && !empty(isset($groupPost->media_url)) ? addBaseUrl($groupPost->media_url) : null;
                    $thumbnail                          = isset($groupPost->thumbnail) && !empty($groupPost->thumbnail) ? addBaseUrl($groupPost->thumbnail) : null;
                    $cover_photo                        = isset($groupPost->group) && isset($groupPost->group->cover_photo) ?
                        addBaseUrl($groupPost->group->cover_photo) : null;
                    $profile                            = isset($groupPost->post_user) && isset($groupPost->post_user->profile) ?
                        addBaseUrl($groupPost->post_user->profile) : '';
                    $groupPost->media_url               = $media_url;
                    $groupPost->thumbnail               = $thumbnail;
                    $groupPost->group->cover_photo      = $cover_photo;
                    $groupPost->post_user->profile      = $profile;
                    // $groupPost->postedAt = Carbon::parse($groupPost->created_at)->diffForHumans();
                    $groupPost->postedAt                = time_elapsed_string($groupPost->created_at);
                    $groupPost->post_category_name = post_category($groupPost->post_category);
                }
            }

            if (isset($group) && !empty($group)) {

                $group->cover_photo                     =   isset($group->cover_photo) && isset($group->cover_photo) ?
                    addBaseUrl($group->cover_photo) : null;
            }

            return response()->json(['status' => 200, 'message' => $message, 'data' => $posts, 'group' => $group]);
        } catch (Exception $e) {
            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError('Error occurred while fetching post.', [], 500);
        }
    }


    #------  G E T      A L L       C O M M U N I T Y       C O M M E N T S -------------#
    public function getCommentsOLD($request, $authId)
    {
        try {

            $groupId    = Post::select('group_id')->find($request->post_id);

            if ($groupId) {

                $group = Group::withCount('groupMember')->find($groupId->group_id);
            }
            $limit = 10;

            if (isset($request['limit']) && !empty($request['limit'])) {

                $limit = $request['limit'];
            }
            // $comments = Comment::with([

            //     'commentUser' => function ($query) {

            //         $query->select('id', 'name', 'user_name', 'profile');
            //     },
            //     'replies.commentUser' => function ($query) {

            //         $query->select('id', 'name', 'user_name', 'profile');
            //     },
            //     'replies.replied_to' => function ($query) {

            //         $query->select('id', 'name', 'user_name', 'profile');
            //     }
            // ])

            $comments = Comment::with([

                'commentUser' => function ($query) use ($authId) {
                    $query->select('id', 'name', 'user_name', 'profile')
                        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
                            $query->where('blocked_user_id', $authId);
                        })
                        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {
                            $query->where('user_id', $authId);
                        });
                },
                'replies.commentUser' => function ($query) use ($authId) {
                    $query->select('id', 'name', 'user_name', 'profile')

                        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
                            $query->where('blocked_user_id', $authId);
                        })
                        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {
                            $query->where('user_id', $authId);
                        });
                },
                'replies.replied_to' => function ($query) use ($authId) {

                    $query->select('id', 'name', 'user_name', 'profile')

                        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                            $query->where('blocked_user_id', $authId);
                        })
                        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {
                            $query->where('user_id', $authId);
                        });
                }
            ])

                ->withCount(['totalLikes', 'total_comment'])
                ->where('post_id', $request->post_id)
                ->whereNull('parent_id')
                // ->whereNotExists(function ($query) use ($authId) {

                //     $query->select(DB::raw(1))
                //         ->from('blocked_users')
                //         ->where(function ($query) use ($authId) {
                //             // Check if the authenticated user has blocked someone
                //             $query->where('user_id', $authId)
                //                 ->whereColumn('blocked_users.blocked_user_id', 'comments.user_id');
                //         })
                //         ->orWhere(function ($query) use ($authId) {
                //             // Check if the authenticated user has been blocked by someone
                //             $query->where('blocked_user_id', $authId)
                //                 ->whereColumn('blocked_users.user_id', 'comments.user_id');
                //         });
                // })


                ->orderByDesc('id')->paginate($limit);

            $comments->getCollection()->transform(function ($comment) use ($authId) {

                $isExist = $this->IsCommentLiked($comment->post_id, $comment->id, $authId);
                $comment->is_liked = $isExist['is_liked'];
                $comment->reaction = $isExist['reaction'];
                $comment->total_likes_count = $isExist['total_likes_count'];

                if (isset($comment->commentUser) && !empty($comment->commentUser->profile)) {

                    $comment->commentUser->profile = $this->addBaseInImage($comment->commentUser->profile);
                    // $comment->commentUser->profile      =   isset($comment->commentUser) && isset($comment->commentUser->profile) ? (filter_var($comment->commentUser->profile, FILTER_VALIDATE_URL) ?  $comment->commentUser->profile : asset('storage/' .  $comment->commentUser->profile)) : '';
                }
                if (isset($comment->replies[0]) && ($comment->replies[0])) {

                    $comment->replies->each(function ($replies) use ($authId) {

                        $isExist = $this->IsCommentLiked($replies->post_id, $replies->id, $authId);
                        $replies->is_liked = $isExist['is_liked'];
                        $replies->reaction = $isExist['reaction'];
                        $replies->total_likes_count = $isExist['total_likes_count'];

                        // $isExist                    =       CommentLike::where(['user_id' => $authId, 'post_id' => $replies->post_id, 'comment_id' => $replies->id])->first();
                        // $replies->is_liked          =       (isset($isExist) && !empty($isExist)) ? 1 : 0;
                        // $replies->reaction          =       (isset($isExist) && !empty($isExist)) ? $isExist->reaction : 0;
                        // $replies->total_likes_count =       CommentLike::where(['comment_id' => $replies->id])->count();

                        if (isset($replies->commentUser) && !empty($replies->commentUser)) {

                            if (isset($replies->commentUser->profile) && !empty($replies->commentUser->profile)) {

                                $replies->commentUser->profile = $this->addBaseInImage($replies->commentUser->profile);
                            }
                        }
                        if (isset($replies->replied_to) && !empty($replies->replied_to)) {

                            if (isset($replies->replied_to->profile) && !empty($replies->replied_to->profile)) {

                                $replies->replied_to->profile = $this->addBaseInImage($replies->replied_to->profile);
                            }
                        }
                    });
                }
                $comment->postedAt = time_elapsed_string($comment->created_at);;
                return $comment;
            });

            $post = Post::withCount(['comment'])->with('post_user', function ($q) {
                $q->select('id', 'name', 'user_name', 'profile');
            })->find($request->post_id);

            if (isset($post) && !empty($post)) {

                if (isset($post->media_url) && !empty($post->media_url)) {

                    $post->media_url = $this->addBaseInImage($post->media_url);
                }

                if (isset($post->thumbnail) && !empty($post->thumbnail)) {

                    $post->thumbnail        = $this->addBaseInImage($post->thumbnail);
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
    #-------------- U s e d        in  **comments** api -------------------------------#
    public function getCommentsJUN28($request, $authId)
    {
        try {

            $groupId                        =   Post::select('group_id')->find($request->post_id);

            if ($groupId) {

                $group                      =   Group::withCount('groupMember')->find($groupId->group_id);
            }

            $limit                          =   $request->input('limit', 10);

            $comments                       =   Comment::with([

                            'commentUser' => function ($query) use ($authId) {

                                $query->select('id', 'name', 'user_name', 'profile');
                            },

                            'commentUser.user_medical_certificate' => function ($q) {

                                $q->select('id', 'medicial_degree_type', 'user_id');
                            },

                            'commentUser.user_medical_certificate.medical_certificate' => function ($q) {

                                $q->select('id', 'name');
                            },

                            'replies.commentUser' => function ($query) use ($authId) {

                                $query->select('id', 'name', 'user_name', 'profile');
                            },

                            'replies.commentUser.user_medical_certificate' => function ($q) {

                                $q->select('id', 'medicial_degree_type', 'user_id');
                            },

                            'replies.commentUser.user_medical_certificate.medical_certificate' => function ($q) {

                                $q->select('id', 'name');
                            },

                            'replies.replied_to' => function ($query) use ($authId) {

                                $query->select('id', 'name', 'user_name', 'profile');
                            },

                            'replies.replied_to.user_medical_certificate' => function ($q) {

                                $q->select('id', 'medicial_degree_type', 'user_id');
                            },
                            'replies.replied_to.user_medical_certificate.medical_certificate' => function ($q) {

                                $q->select('id', 'name');
                            },
                            ])
                            ->withCount(['totalLikes', 'total_comment'])

                            ->where('post_id', $request->post_id)

                            ->whereNull('parent_id')

                            ->whereDoesntHave('commentUser.blockedUsers', function ($query) use ($authId) {

                                $query->where('blocked_user_id', $authId);
                            })

                            ->whereDoesntHave('commentUser.blockedBy', function ($query) use ($authId) {

                                $query->where('user_id', $authId);

                            })

                            ->whereDoesntHave('replies.commentUser.blockedUsers', function ($query) use ($authId) {

                                $query->where('blocked_user_id', $authId);
                            })

                            ->whereDoesntHave('replies.commentUser.blockedBy', function ($query) use ($authId) {

                                $query->where('user_id', $authId);
                            })

                            ->whereDoesntHave('replies.replied_to.blockedUsers', function ($query) use ($authId) {

                                $query->where('blocked_user_id', $authId);
                            })

                            ->whereDoesntHave('replies.replied_to.blockedBy', function ($query) use ($authId) {

                                $query->where('user_id', $authId);

                            })

                            ->orderByDesc('id')
                            ->paginate($limit);

            $comments->getCollection()->transform(function ($comment) use ($authId) {

                $isExist                    = $this->IsCommentLiked($comment->post_id, $comment->id, $authId);

                $comment->is_liked          = $isExist['is_liked'];
                
                $comment->reaction          = $isExist['reaction'];

                $comment->total_likes_count = $isExist['total_likes_count'];


                if (isset($comment->commentUser) && !empty($comment->commentUser->profile)) {

                    $comment->commentUser->profile  = $this->addBaseInImage($comment->commentUser->profile);
                }


                if (isset($comment->replies[0]) && ($comment->replies[0])) {

                    $comment->replies->each(function ($replies) use ($authId) {

                        $isExist                    = $this->IsCommentLiked($replies->post_id, $replies->id, $authId);

                        $replies->is_liked          = $isExist['is_liked'];

                        $replies->reaction          = $isExist['reaction'];

                        $replies->total_likes_count = $isExist['total_likes_count'];


                        if (isset($replies->commentUser) && !empty($replies->commentUser)) {

                            if (isset($replies->commentUser->profile) && !empty($replies->commentUser->profile)) {

                                $replies->commentUser->profile      = $this->addBaseInImage($replies->commentUser->profile);
                            }
                        }

                        if (isset($replies->replied_to) && !empty($replies->replied_to)) {

                            if (isset($replies->replied_to->profile) && !empty($replies->replied_to->profile)) {

                                $replies->replied_to->profile       = $this->addBaseInImage($replies->replied_to->profile);
                            }
                        }

                        $replies->postedAt                          =    time_elapsed_string($replies->created_at);
                    });
                }
                $comment->postedAt                                  =    time_elapsed_string($comment->created_at);
                return $comment;
            });

            $post                        =       Post::withCount(['comment'])->with([

                                                'post_user' => function ($q) {

                                                    $q->select('id', 'name', 'user_name', 'profile');
                                                },

                                                'post_user.user_medical_certificate' => function ($q) {

                                                    $q->select('id', 'medicial_degree_type', 'user_id');
                                                },

                                                'post_user.user_medical_certificate.medical_certificate' => function ($q) {

                                                    $q->select('id', 'name');

                                                }, 'group' => function ($query) {

                                                    $query->select('id', 'name', 'description', 'cover_photo', 'member_count', 
                                                    'post_count', 'created_by');
                                                }

                                            ])->find($request->post_id);



            if (isset($post) && !empty($post)) {

                if (isset($post->media_url) && !empty($post->media_url)) {

                    $post->media_url = $this->addBaseInImage($post->media_url);
                }

                if (isset($post->thumbnail) && !empty($post->thumbnail)) {

                    $post->thumbnail = $this->addBaseInImage($post->thumbnail);
                }

                if (!empty($post->post_user)) {

                    $post->post_user->profile = (isset($post->post_user) && !empty($post->post_user->profile)) ? $this->addBaseInImage($post->post_user->profile) : null;
                }

                if ($post->group &&  $post->group->cover_photo) {

                    $post->group->cover_photo      =  addBaseUrl($post->group->cover_photo);
                }

                $post->is_joined            =       $this->checkCommunityJoind($post->group_id);

                $isExist                    =       $this->IsPostLiked($post->id, $authId);


                $post->is_liked             =       $isExist['is_liked'];

                $post->reaction             =       $isExist['reaction'];

                $post->total_likes_count    =       $isExist['total_likes_count'];

            }




            $data                           =       $comments->items();
            $recordsPerPage                 =       $comments->perPage();
            $currentPage                    =       $comments->currentPage();
            $lastPage                       =       $comments->lastPage();
            $totalRecords                   =       $comments->total();
            $recordsLeft                    =       ($totalRecords - ($recordsPerPage * $currentPage) < 0 ? 0 : $totalRecords - ($recordsPerPage * $currentPage));
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

                return response()->json(['status' => 201, 'message' => trans('message.you_are_not_group_member'), 'group' => $group]);
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
                $isExist = $this->IsPostLiked($post->id, $authId, 1);
                $post->is_liked =       $isExist['is_liked'];
                $post->reaction =       $isExist['reaction'];
                $post->total_likes_count = $isExist['total_likes_count'];
                $post->total_comment_count = $isExist['total_comment_count'];

                if (isset($post->media_url) && !empty($post->media_url)) {

                    $post->media_url = $this->addBaseInImage($post->media_url);
                }

                if (isset($post->thumbnail) && !empty($post->thumbnail)) {

                    $post->thumbnail = $this->addBaseInImage($post->thumbnail);
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
                    ->exists()) ? 1 : 0;
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
                'commentUser.user_medical_certificate' => function ($q) {

                    $q->select('id', 'medicial_degree_type', 'user_id');
                },
                'commentUser.user_medical_certificate.medical_certificate' => function ($q) {

                    $q->select('id', 'name');
                },
                'replies.commentUser' => function ($query) {
                    $query->select('id', 'name', 'user_name', 'profile');
                },

                'replies.commentUser.user_medical_certificate' => function ($q) {

                    $q->select('id', 'medicial_degree_type', 'user_id');
                },
                'replies.commentUser.user_medical_certificate.medical_certificate' => function ($q) {

                    $q->select('id', 'name');
                },


                'replies.replied_to' => function ($query) {
                    $query->select('id', 'name', 'user_name', 'profile');
                },
                'replies.replied_to.user_medical_certificate' => function ($q) {

                    $q->select('id', 'medicial_degree_type', 'user_id');
                },
                'replies.replied_to.user_medical_certificate.medical_certificate' => function ($q) {

                    $q->select('id', 'name');
                },
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


    public function postSummaryInstruction($content)
    {

        // $systemInstruction = 'Generate PHP code to display "Hello World"';

        $guidelines = [
            [
                "text" => "System: You are now a specialized AI assistant for Doqta, focused on creating clear, concise, and culturally sensitive summaries of health forum posts for the Black community. Follow these guidelines to ensure your summaries are informative and accessible:"
            ],
            [
                "text" => "Capture the Essence: Identify and highlight the main health topic or concern. Distill key points and questions from the user."
            ],
            [
                "text" => "Simplify Language: Use plain, everyday language that's easily understood by a broad audience. Replace medical jargon with simpler terms when possible, without losing accuracy.If a medical term is crucial, provide a brief, clear explanation in parentheses"
            ],
            [
                "text" => "Maintain Brevity: Keep summaries concise, ideally no more than 3-4 sentences.Focus on the most relevant and impactful information from the original post"
            ],
            [
                "text" => "Preserve Cultural Context: Be mindful of and retain any culturally specific references or concerns, if present. Use culturally appropriate language and examples when clarifying points"
            ],
            [
                "text" => "Highlight Key Elements: Clearly state the health condition, symptoms, or situation being discussed.Note any specific questions or requests for advice made by the original poster.Mention any unique experiences or perspectives shared that might be valuable to others."
            ],
            [
                "text" => "Maintain Neutrality: Present information objectively, without adding personal opinions or medical advice.If the original post contains potentially harmful or inaccurate information, flag it neutrally (e.g., 'Note: This post contains health claims that may require professional verification')"

            ],
            [
                "text" => "Omit any personally identifiable information from the summary.Use general terms instead of specific names or locations (e.g., 'the poster's doctor' instead of 'Dr. Smith')"
            ],
            [
                "text" => "Capture Emotional Context: Briefly convey the emotional tone of the post (e.g.,'The user expresses concern about... or The poster is seeking support for...'). This helps other users understand the poster's state of mind and respond appropriately"
            ],
            [
                "text" => "Structure for Clarity: Use a consistent format for all summaries to aid quick comprehension.Consider a structure like: [Main Topic] - [Key Points/Questions] - [User's Situation/Experience]"
            ],
            [
                "text" => "Highlight Actionable Elements: If the post includes any calls to action or requests for specific types of support, make these clear in the summary"
            ],
            [
                "text" => "Focus only on health-related aspects of the post, even if other topics are mentioned.If a post is not primarily health-related, state this clearly in the summary."
            ],
            [
                "text" => "Employ language that is respectful and inclusive of diverse experiences within the Black community.Avoid assumptions or generalizations based on race or ethnicity."
            ],
            [
                "text" => "Flag Urgent Concerns: If a post indicates a potentially urgent health situation, include a note at the beginning of the summary (e.g., 'Urgent: This post describes symptoms that may require immediate medical attention')."
            ],
            [
                "text" => "Encourage Engagement: End the summary with a brief statement that encourages other users to read the full post if they can relate or have insights to share."
            ],
            [
                "text" => "Maintain Health Focus: Ensure all summaries pertain strictly to medical and health-related topics.If a post contains non-health-related content, focus the summary only on the health aspects",
            ],
            [
                "text" => "Sample Summary Structure:[Health Topic]: User shares experience with [specific condition/symptom]. Key points: [1-2 main ideas]. Seeking: [advice/support/information] on [specific aspect]. Note: [Any important flags or cultural context]."

            ],
            ["text" => "Your goal is to create summaries that allow users to quickly understand the content of health forum posts, decide if they're relevant to their own experiences, and determine whether they want to read the full post or engage with the discussion. Always prioritize clarity, relevance, and cultural sensitivity in your summaries"]
        ];


        $prompt_template = (

            "{system_instruction} content: {question}."
        );
        // Compile the prompt using the provided parameters
        $compiled_prompt = str_replace(
            ['{system_instruction}', '{question}'],
            [json_encode($guidelines), $content,],
            $prompt_template
        );
        return $compiled_prompt;
    }
}
