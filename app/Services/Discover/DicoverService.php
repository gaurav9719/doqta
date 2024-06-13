<?php

namespace App\Services\Discover;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\GroupMemberRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Like;
use App\Models\UserFollower;
use App\Models\UserParticipantCategory;
use GuzzleHttp\Psr7\Query;
use App\Traits\postCommentLikeCount;
use App\Traits\IsLikedPostComment;

/**
 * Class DicoverService.
 */
class DicoverService extends BaseController
{

    use postCommentLikeCount, IsLikedPostComment;
    public function discover($request, $userId, $limit)
    {
        if (empty($request->type)) {

            return $this->all($request, $userId, $limit);
        } else {

            if ($request->type == 1) {          //posts

                return $this->getDiscoverPost($request, $userId);
            } elseif ($request->type == 2) {   // community

                return $this->getDiscoverCommunity($request, $userId);
            } elseif ($request->type == 3) {   //people

                return $this->getDiscoverPeople($request, $userId, $limit);

            } elseif ($request->type == 4) {   //media

                return $this->getMedia($request, $userId, $limit);
            }
        }
    }


    public function all($request, $authId, $limit)
    {
        try {

            $data                               =   [];
            $support                            =   $this->supportShareInterest($request, $authId, $limit, 1);
            if ($support != "400") {

                $data['support_shared_interests']  = $support;
            } else {

                $data['support_shared_interests']  = [];
            }

            // Fetch top community
            $topCommunity = $this->topCommunityThisWeek($request, $authId, $limit, 1);

            if ($topCommunity != "400") {

                $data['top_communities_this_week'] = $topCommunity; // Use correct variable here

            } else {

                $data['top_communities_this_week'] = [];
            }

            #------------------ T O P        A R T I C L E S -------------------#

            $topArticles  =   $this->topArticles($request, $authId, $limit, 1);

            if ($topArticles != "400") {
                $data['care_takers']       =  $topArticles;
            } else {
                $data['care_takers']       =      [];
            }
            #------------------ T O P        A R T I C L E S -------------------#
            $topVideo       =            $this->topVideos($request, $authId, $limit, 1);

            if ($topVideo != "400") {

                $data['top_videos']       =  $topVideo;
            } else {

                $data['top_videos']       =      [];
            }
            $notification_count     =   notification_count();
            return $this->sendResponse($data, trans('message.discover_all'), 200, $notification_count);
        } catch (Exception $e) {
            Log::error('Error caught: "discover-all"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #------------ S U P P O R T         S H A R E D     I N T E R E S T  -----------------#
    public function supportShareInterest($request, $authId, $limit, $type = "")
    {

        #---------- S U P P O R T       S H A R E D     I N T E R E S T  --------------#
        try {
            $groupIdsQuery  =   GroupMember::where(['user_id' => $authId, 'is_active' => 1]);

            if (!empty($request->search)) {

                $groupIdsQuery->whereHas('communities', function ($query) use ($request) {
                    $query->where('name', 'like', "%$request->search%");
                });
            }

            $groupIds       =   $groupIdsQuery->pluck('group_id');

            if (isset($groupIds) && !empty($groupIds)) {  //check if groups id are coming

                // Get the member_ids where group_id is in $groupIds and is_active is truthy
                $memberIds      = GroupMember::whereHas('groupUser', function ($isActive) {

                    $isActive->where('is_active', 1); //check user is active or not

                })->with(['groupUser' => function ($query) {

                    $query->select('id', 'name', 'user_name', 'profile');
                }, 'communities' => function ($query) {

                    $query->select('id', 'name');
                }]) // Exclude users followed by the authenticated user
                    ->whereDoesntHave('user.followers', function ($query) use ($authId) {

                        $query->where('follower_user_id', $authId);
                    })->whereHas('user')->whereDoesntHave('user.blockedUsers', function ($query) use ($authId) {

                        $query->where('blocked_user_id', $authId);
                    })
                    ->whereDoesntHave('user.blockedBy', function ($query) use ($authId) {
                        $query->where('user_id', $authId);
                    })


                    // ->whereNotExists(function ($query) use ($authId) {

                    //     $query->select(DB::raw(1))
                    //         ->from('user_followers')
                    //         ->whereColumn('user_followers.user_id', '=', 'group_members.user_id')
                    //         ->where('user_followers.follower_user_id', '=', $authId);
                    // })

                    // ->whereNotExists(function ($query) use ($authId) {

                    //     $query->select(DB::raw(1))->from('blocked_users')

                    //         ->where(function ($query) use ($authId) {
                    //             // Check if the authenticated user has blocked someone
                    //             $query->where('user_id', $authId)
                    //                 ->whereColumn('blocked_users.blocked_user_id', 'group_members.user_id');
                    //         })
                    //         ->orWhere(function ($query) use ($authId) {
                    //             // Check if the authenticated user has been blocked by someone
                    //             $query->where('blocked_user_id', $authId)
                    //                 ->whereColumn('blocked_users.user_id', 'group_members.user_id');
                    //         });
                    // })




                    ->whereIn('group_id', $groupIds)

                    ->where('is_active', 1) // Assuming 'is_active' field is boolean
                    ->where('user_id', '<>', $authId)
                    ->groupBy('user_id');

                if (isset($type) && !empty($type)) {  #------------- W H E N      R E T U R N      O N L Y     F E W        R E C O R D S -----------#  

                    $memberIds      =   $memberIds->get()->take($limit);
                } else {

                    $memberIds      =   $memberIds->simplePaginate($limit);
                }
                if (isset($memberIds) && !empty($memberIds)) {
                    $memberIds->each(function ($suggestMember) use ($authId) {

                        if (isset($suggestMember->groupUser) && !empty($suggestMember->groupUser)) {

                            if (isset($suggestMember->groupUser->profile) && !empty($suggestMember->groupUser->profile)) {

                                $suggestMember->groupUser->profile =   $this->addBaseInImage($suggestMember->groupUser->profile);
                            }
                        }

                        $suggestMember->is_supporting    =   (UserFollower::where(['user_id' => $suggestMember->user_id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0;
                    });
                }
                if (isset($type) && !empty($type)) {  #------------- W H E N      R E T U R N      O N L Y     F E W        R E C O R D S -----------#  

                    return $memberIds;
                } else {

                    return $this->sendResponse($memberIds, trans("message.shared_support_users"), 200);
                }
            } else {

                if (isset($type) && !empty($type)) {  #------------- W H E N      R E T U R N      O N L Y     F E W        R E C O R D S -----------#  

                    return [];
                } else {

                    return $this->sendResponse([], trans("message.shared_support_users"), 200);
                }
            }
        } catch (Exception $e) {
            Log::error('Error caught: "supportShareInterest" ' . $e->getMessage());
            return 400;
        }
    }
    #---------- S U P P O R T       S H A R E D     I N T E R E S T  --------------#


    #-------------*******  T O P       C O M M U N I T Y       T H I S         W E E K **************-------------#

    public function topCommunityThisWeek($request, $authId, $limit, $type = "", $isWeekly = "")
    {
        try {


            $startOfWeek                            =       Carbon::now()->startOfWeek();
            $endOfWeek                              =       Carbon::now()->endOfWeek();
            if (isset($isWeekly) && !empty($isWeekly)) {

                $topCommunities                         =       Group::withCount(['groupMember']);
            } else {

                $topCommunities                         =       Group::withCount(['groupMember' => function ($query) use ($startOfWeek, $endOfWeek) {

                    $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                }]);
            }

            if (isset($request->search) && !empty($request->search)) {

                $topCommunities = $topCommunities->where('name', 'LIKE', "%$request->search%");
            }

            $topCommunities                         =       $topCommunities->whereNotExists(function ($query) use ($authId) {
                $query->select(DB::raw(1))
                    ->from('group_members')
                    ->whereColumn('group_members.group_id', '=', 'groups.id')
                    ->where('group_members.user_id', '=', $authId);
            })->whereNotExists(function ($query) use ($authId) {

                $query->select(DB::raw(1))->from('blocked_users')

                    ->where(function ($query) use ($authId) {
                        // Check if the authenticated user has blocked someone
                        $query->where('user_id', $authId)

                            ->whereColumn('blocked_users.blocked_user_id', 'groups.created_by');
                    })
                    ->orWhere(function ($query) use ($authId) {
                        // Check if the authenticated user has been blocked by someone
                        $query->where('blocked_user_id', $authId)
                            ->whereColumn('blocked_users.user_id', 'groups.created_by');
                    });
            })->whereHas('groupMember', function ($query) use ($startOfWeek, $endOfWeek) {
                $query->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->where('is_active', 1); // Assuming 'is_active' field exists in group members
            })->having('group_member_count', '>', 0)

                ->orderByDesc('post_count');

            if (!empty($type)) {  #------------- W H E N      R E T U R N      O N L Y     F E W        R E C O R D S -----------#  

                $topCommunities      =   $topCommunities->limit($limit)->get();
            } else {

                $topCommunities      =   $topCommunities->simplePaginate($limit);
            }

            if (isset($topCommunities[0]) && !empty($topCommunities[0])) {

                $topCommunities->each(function ($topCommunity) use ($authId) {

                    if (isset($topCommunity->cover_photo) && !empty($topCommunity->cover_photo)) {

                        $topCommunity->cover_photo =   asset('storage/' . $topCommunity->cover_photo);
                    }

                    $topCommunity->isJoined         =   (GroupMember::where(['group_id' => $topCommunity->id, 'user_id' => $authId])->exists()) ? 1 : 0;
                });
                //check is join community or not
            }
            if (!empty($type)) {  #------------- W H E N      R E T U R N      O N L Y     F E W        R E C O R D S -----------#  

                return $topCommunities;
            } else {

                return $this->sendResponse($topCommunities, trans("message.top_community"), 200);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "topCommunityThisWeek" ' . $e->getMessage());
            return 400;
        }
    }
    #-------------*******  T O P       C O M M U N I T Y       T H I S         W E E K **************-------------#



    #********* ---------------  T O P       A R T I C L E S     --------------------------***************



    public function getMedia($request, $authId, $limit, $type = "")
    {

        try {
            $data                               =   [];
            #------------------ T O P        A R T I C L E S -------------------#
            $topArticles  =   $this->topArticles($request, $authId, $limit, 1);

            if ($topArticles !== "400") {
                $data['care_articles']       =  $topArticles;
            } else {
                $data['care_articles']       =      [];
            }
            #------------------ T O P        A R T I C L E S -------------------#
            $topVideo       =            $this->topVideos($request, $authId, $limit, 1);

            if ($topVideo !== "400") {

                $data['top_videos']       =  $topVideo;
            } else {

                $data['top_videos']       =      [];
            }
            return $this->sendResponse($data, trans('message.discover_media'), 200);
        } catch (Exception $e) {

            Log::error('Error caught: "getMedia"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }


    public function topArticlesOLd($request, $authId, $limit, $type = "")
    {

        try {

            $topArticles   = Post::with(['post_user' => function ($query) {

                $query->select('id', 'name', 'user_name', 'profile');

            }])->whereHas('parent_post', function ($query) {

                $query->where('is_active', 1);

            })->with(['parent_post' => function ($query) {

                $query->select('*')

                    ->where('is_active', 1)

                    ->with([

                        'post_user' => function ($query) {

                            $query->select('id', 'name', 'user_name', 'profile');

                        }, 'post_user.user_medical_certificate'=>function($q){

                            $q->select('id','medicial_degree_type','user_id');
    
                        },

                        'post_user.user_medical_certificate.medical_certificate'=>function($q){
    
                            $q->select('id','name');
                        },
                    ]);

            }])->whereNotNull('link');

            if (isset($request->search) && !empty($request->search)) {

                $search = $request->search;
                // Apply the search condition using whereHas directly
                $topArticles->whereHas('group_post', function ($query) use ($search) {

                    $query->where('name', 'LIKE', "%$search%");
                });
            }
            $topArticles = $topArticles->whereNotExists(function ($query) use ($authId) {

                $query->select(DB::raw(1))->from('blocked_users')

                    ->where(function ($query) use ($authId) {
                        // Check if the authenticated user has blocked someone
                        $query->where('user_id', $authId)

                            ->whereColumn('blocked_users.blocked_user_id', 'posts.user_id');
                    })
                    ->orWhere(function ($query) use ($authId) {
                        // Check if the authenticated user has been blocked by someone
                        $query->where('blocked_user_id', $authId)
                            ->whereColumn('blocked_users.user_id', 'posts.user_id');
                    });
            })->whereNotExists(function ($query) use ($authId) {

                $query->select(DB::raw(1))
                    ->from('report_posts')
                    ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                    ->where('report_posts.user_id', '=', $authId); // Check if the current user has reported the post

            })->orderByDesc('like_count');
            if (!empty($type)) {

                $topArticles  =  $topArticles->limit($limit)->get();
            } else {

                $topArticles  =  $topArticles->simplePaginate($limit);
            }

            if (isset($topArticles) && !empty($topArticles)) {

                $topArticles->each(function ($topArticle) use ($authId) {

                    if (isset($topArticle->post_user) && !empty($topArticle->post_user)) {

                        if (isset($topArticle->post_user->profile) && !empty($topArticle->post_user->profile)) {

                            $topArticle->post_user->profile =   $this->addBaseInImage($topArticle->post_user->profile);
                        }
                    }
                    if (isset($topArticle->media_url) && !empty($topArticle->media_url)) {

                        $topArticle->media_url =   $this->addBaseInImage($topArticle->media_url);
                    }

                    if (isset($topArticle->thumbnail) && !empty($topArticle->thumbnail)) {

                        $topArticle->thumbnail =   $this->addBaseInImage($topArticle->thumbnail);
                    }

                    $hasLiked                                         =   Like::where(['user_id' => $authId, 'post_id' => $topArticle->id])->whereNull('comment_id')->exists();
                    $topArticle->is_liked                             = ($hasLiked) ? 1 : 0;

                    if (isset($topArticle->parent_post) && !empty($topArticle->parent_post)) {

                        if ($topArticle->parent_post->post_user && $topArticle->parent_post->post_user->profile) {

                            $topArticle->parent_post->post_user->profile       = $this->addBaseInImage($topArticle->parent_post->post_user->profile);
                        }
                        if (isset($topArticle->parent_post->media_url) && !empty($topArticle->parent_post->media_url)) {

                            $topArticle->parent_post->media_url        =       $this->addBaseInImage($topArticle->parent_post->media_url);
                        }

                        if (isset($topArticle->parent_post->thumbnail) && !empty($topArticle->parent_post->thumbnail)) {

                            $topArticle->parent_post->thumbnail        =       $this->addBaseInImage($topArticle->parent_post->thumbnail);
                        }
                        $isExist                                       =       $this->IsPostLiked($topArticle->id, $authId, 1);
                        $topArticle->parent_post->is_liked             =       $isExist['is_liked'];
                        $topArticle->parent_post->reaction             =       $isExist['reaction'];
                        $topArticle->parent_post->total_likes_count    =       $isExist['total_likes_count'];
                        $topArticle->parent_post->total_comment_count  =       $isExist['total_comment_count'];
                        $isRepost                                      =       Post::where(['parent_id' => $topArticle->parent_post->id, 'user_id' => $authId, 'is_active' => 1])->exists();
                        $topArticle->parent_post->is_reposted          =       ($isRepost) ? 1 : 0;
                    }
                });
            }
            // Assign the result to the correct variable
            if (!empty($type)) {

                return $topArticles;

            } else {

                return $this->sendResponse($topArticles, trans("message.top_articles"), 200);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "topArticles" ' . $e->getMessage());
            return 400;
        }
    }

    #---------------- G E T         A R T I C L E S ----------------#

    #***************------ T O P        V I D E O S     ----------*********************######
    public function topVideosOLD($request, $authId, $limit, $type = "")
    {

        try {

            $topVideos         =       Post::whereHas('group_post', function ($is_active) use($authId) { // check group is active or not

                $is_active->where('is_active', 1)

                    ->whereDoesntHave('groupOwner.blockedBy', function ($query) use ($authId) {

                        $query->where('user_id', $authId);
                    })->whereDoesntHave('groupOwner.blockedUsers', function ($query) use ($authId) {

                        $query->where('blocked_user_id', $authId);
                    });
            })->with(['post_user' => function ($query) {

                $query->select('id', 'name', 'user_name', 'profile');

            }])->whereNotNull('media_url')->where('media_type', 2)->whereNotExists(function ($query) use ($authId) {

                $query->select(DB::raw(1))

                    ->from('report_posts')
                    ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                    ->where('report_posts.user_id', '=', $authId); // Check if the current user has reported the post

            })->whereNotExists(function ($query) use ($authId) {

                $query->select(DB::raw(1))->from('blocked_users')

                    ->where(function ($query) use ($authId) {
                        // Check if the authenticated user has blocked someone
                        $query->where('user_id', $authId)
                            ->whereColumn('blocked_users.blocked_user_id', 'posts.user_id');
                    })
                    ->orWhere(function ($query) use ($authId) {
                        // Check if the authenticated user has been blocked by someone
                        $query->where('blocked_user_id', $authId)
                            ->whereColumn('blocked_users.user_id', 'posts.user_id');
                    });
            });         //2 means video

            if (isset($request->search) && !empty($request->search)) {

                $search = $request->search;
                // Apply the search condition using whereHas directly
                $topVideos->whereHas('group_post', function ($query) use ($search) {

                    $query->where('name', 'LIKE', "%$search%");
                });
            }
            // Limit the results to 5 and get the data
            $topVideos = $topVideos->where('is_active', 1)->orderByDesc('like_count');

            if (!empty($type)) {

                $topVideos = $topVideos->limit($limit)->get();

            } else {

                $topVideos = $topVideos->simplePaginate($limit);
            }

            if (isset($topVideos[0]) && !empty($topVideos[0])) {

                $topVideos->each(function ($topVideo) use ($authId) {

                    if (isset($topVideo->post_user) && !empty($topVideo->post_user)) {

                        if (isset($topVideo->post_user->profile) && !empty($topVideo->post_user->profile)) {

                            $topVideo->post_user->profile =   $this->addBaseInImage($topVideo->post_user->profile);
                        }
                    }
                    if (isset($topVideo->media_url) && !empty($topVideo->media_url)) {

                        $topVideo->media_url =   $this->addBaseInImage($topVideo->media_url);
                    }

                    if (isset($topVideo->thumbnail) && !empty($topVideo->thumbnail)) {

                        $topVideo->thumbnail =   $this->addBaseInImage($topVideo->thumbnail);
                    }

                    $hasLiked                =   Like::where(['user_id' => $authId, 'post_id' => $topVideo->id])->whereNull('comment_id')->exists();
                    $topVideo->is_liked      = ($hasLiked) ? 1 : 0;
                });
            }

            if (!empty($type)) {

                return $topVideos;
                
            } else {

                return $this->sendResponse($topVideos, trans("message.top_videos"), 200);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "topvideo" ' . $e->getMessage());
            return 400;
        }
    }
    #***************------ T O P        V I D E O S     ----------*********************######


    public function topArticles($request, $authId, $limit, $type = "")
    {

        try {


            $topArticles        =      Post::where(['is_active'=>1,'media_type'=>4]);

            $topArticles       =       getPost($authId,$topArticles);

            if (isset($request->search) && !empty($request->search)) {

                $search = $request->search;
                // Apply the search condition using whereHas directly
                $topArticles->whereHas('group', function ($query) use ($search) {

                    $query->where('name', 'LIKE', "%$search%");
                });
            }

            $topArticles = $topArticles->orderByDesc('like_count');

            if (!empty($type)) {

                $topArticles = $topArticles->limit($limit)->get();

            } else {

                $topArticles = $topArticles->simplePaginate($limit);
            }

            if (isset($topArticles[0]) && !empty($topArticles[0])) {

                $topArticles->each(function ($topArticle) use ($authId) {

                    $topArticle = transformPostData($topArticle, $authId);
                        #------------ parent post data-----------------#
                    if (isset($topArticle->parent_post) && !empty($topArticle->parent_post)) {

                        $topArticle= transformParentPostData($topArticle, $authId);
                    }
                    return $topArticle;
                }); 
            }
            // Assign the result to the correct variable
            if (!empty($type)) {

                return $topArticles;

            } else {

                return $this->sendResponse($topArticles, trans("message.top_articles"), 200);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "topArticles" ' . $e->getMessage());
            return 400;
        }
    }









    public function topVideos($request, $authId, $limit, $type = "")
    {
        try {
            $discoverPost        =      Post::where(['is_active'=>1,'media_type'=>2]);
            $discoverPost       =       getPost($authId,$discoverPost);
            if (isset($request->search) && !empty($request->search)) {

                $search = $request->search;
                // Apply the search condition using whereHas directly
                $discoverPost->whereHas('group', function ($query) use ($search) {

                    $query->where('name', 'LIKE', "%$search%");
                });
            }
             // Limit the results to 5 and get the data
             $topVideos = $discoverPost->orderByDesc('like_count');

            if (!empty($type)) {

                $topVideos = $topVideos->limit($limit)->get();

            } else {

                $topVideos = $topVideos->simplePaginate($limit);
            }

            if (isset($topVideos[0]) && !empty($topVideos[0])) {

                $topVideos->each(function ($topVideo) use ($authId) {
                    $topVideo = transformPostData($topVideo, $authId);
                        #------------ parent post data-----------------#
                    if (isset($topVideo->parent_post) && !empty($topVideo->parent_post)) {

                        $topVideo= transformParentPostData($topVideo, $authId);
                    }
                    return $topVideo;
                }); 
            }
            if (!empty($type)) {
                return $topVideos;

            } else {

                return $this->sendResponse($topVideos, trans("message.top_videos"), 200);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "topvideo" ' . $e->getMessage());
            return 400;
        }
    }
    // public function getDiscoverPost($request,$authId){

    //     try {

    //         $limit              =       10;

    //         if (isset($request->limit) && !empty($request->limit)) {

    //             $limit          =       $request->limit;
    //         }
    //         $search             =  "";

    //         if(isset($request->search) && !empty($request->search)){

    //             $search         =       $request->search;
    //         }

    //         $user               =       User::findOrFail($authId);
    //         // $posts              =       $user->posts()->latest()->simplePaginate($limit);
    //         // $posts = $user->posts()->where(['posts.is_active' => 1])->whereNotExists('')->latest()->simplePaginate($limit);
    //         $homeScreenPosts = $user->posts()

    //         ->where('posts.is_active', 1)
    //         ->whereNotExists(function ($query) use ($user) {
    //             $query->select(DB::raw(1))
    //                 ->from('report_posts')
    //                 ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
    //                 ->where('report_posts.user_id', '=', $user->id); // Check if the current user has reported the post
    //         })
    //         ->when((!empty($request->search) && $request->search!=""), function ($query) use ($search) {
    //             return $query->whereHas('group', function ($query) use ($search) {

    //                 $query->where('name', 'like', '%' . $search . '%');

    //             });
    //         })
    //         ->with(['parent_post' => function ($query) {
    //             $query->select('id', 'user_id', 'title', 'repost_count', 'like_count', 'comment_count', 'is_high_confidence')
    //                 ->where('is_active', 1)
    //                 ->with(['post_user' => function ($query) {
    //                     $query->select('id', 'name', 'profile');
    //                 }]);
    //         },'group'=>function ($query){
    //             $query->select('id','name');
    //         }])
    //         ->latest()
    //         ->simplePaginate($limit);

    //             $homeScreenPosts->each(function ($homeScreenPost) {


    //                 if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

    //                     $homeScreenPost->media_url = asset('storage/' . $homeScreenPost->media_url);
    //                 }

    //                 if ($homeScreenPost->parent_post && $homeScreenPost->parent_post->post_user &&      $homeScreenPost->parent_post->post_user->profile) {
    //                     $homeScreenPost->parent_post->post_user->profile = asset('storage/'.$$homeScreenPost->parent_post->post_user->profile);         
    //                 }
    //                 $homeScreenPost->postedAt = Carbon::parse($homeScreenPost->created_at)->diffForHumans();

    //             });


    //         return $this->sendResponse($homeScreenPosts, trans("message.dicover_post"), 200);

    //     } catch (Exception $e) {

    //         Log::error('Error caught: "getDiscoverPost" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);
    //     }
    // }


    #-----------------  N E W    F U N C T I O N    T O       G E T     P O S T ---------------#
    public function getDiscoverPost($request, $authId)
    {
        try {
            
            $limit              =       10;

            if (isset($request->limit) && !empty($request->limit)) {

                $limit          =       $request->limit;
            }
            $search             =  "";

            if (isset($request->search) && !empty($request->search)) {

                $search         =       $request->search;
            }
            $homeScreenPosts    =   Post::where('posts.is_active', 1)

                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('report_posts')
                        ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('report_posts.user_id', '=', $authId); // Check if the current user has reported the post
                })

                ->whereNotExists(function ($query) use ($authId) {

                    $query->select(DB::raw(1))->from('blocked_users')

                        ->where(function ($query) use ($authId) {
                            // Check if the authenticated user has blocked someone
                            $query->where('user_id', $authId)

                                ->whereColumn('blocked_users.blocked_user_id', 'posts.user_id');
                        })
                        ->orWhere(function ($query) use ($authId) {
                            // Check if the authenticated user has been blocked by someone
                            $query->where('blocked_user_id', $authId)
                                ->whereColumn('blocked_users.user_id', 'posts.user_id');
                        });
                })
                ->when((!empty($request->search) && $request->search != ""), function ($query) use ($search) {

                    return $query->where('title', 'like', '%' . $search . '%')  //this line

                        ->orWhereHas('group', function ($query) use ($search) {

                            $query->where('name', 'like', '%' . $search . '%');
                        });
                })
                ->with(['parent_post' => function ($query) {
                    $query->where('is_active', 1)

                        ->with(['post_user' => function ($query) {

                            $query->select('id', 'name', 'profile', 'user_name');
                        }, 
                        'post_user.user_medical_certificate'=>function($q){

                            $q->select('id','medicial_degree_type','user_id');
    
                        },
                        'post_user.user_medical_certificate.medical_certificate'=>function($q){
    
                            $q->select('id','name');
                        },
                        
                        'group' => function ($query) {

                            $query->select('id', 'name', 'description', 'created_by');
                        }]);
                }, 'group' => function ($query) {

                    $query->select('id', 'name', 'description', 'created_by');
                }, 'post_user' => function ($q) {

                    $q->select('id', 'user_name', 'name', 'profile');
                    
                }, 'post_user.user_medical_certificate'=>function($q){

                    $q->select('id','medicial_degree_type','user_id');

                },
                'post_user.user_medical_certificate.medical_certificate'=>function($q){

                    $q->select('id','name');
                }])
                ->orderBy('like_count', 'desc')
                ->simplePaginate($limit);

            $homeScreenPosts->each(function ($homeScreenPost) use ($authId) {
                #----------- check has liked or not------------#

                if (isset($homeScreenPost->post_user) && !empty($homeScreenPost->post_user->profile)) {

                    $homeScreenPost->post_user->profile = $this->addBaseInImage($homeScreenPost->post_user->profile);
                }

                if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

                    $homeScreenPost->media_url = $this->addBaseInImage($homeScreenPost->media_url);
                }

                if (isset($homeScreenPost->thumbnail) && !empty($homeScreenPost->thumbnail)) {

                    $homeScreenPost->thumbnail = $this->addBaseInImage($homeScreenPost->thumbnail);
                }

                if (isset($homeScreenPost->parent_post) && !empty($homeScreenPost->parent_post)) {

                    if ($homeScreenPost->parent_post->post_user &&  $homeScreenPost->parent_post->post_user->profile) {

                        $homeScreenPost->parent_post->post_user->profile = $this->addBaseInImage($homeScreenPost->parent_post->post_user->profile);
                    }


                    if (isset($homeScreenPost->parent_post->media_url) && !empty($homeScreenPost->parent_post->media_url)) {

                        $homeScreenPost->parent_post->media_url          =  $this->addBaseInImage($homeScreenPost->parent_post->media_url);
                    }

                    if (isset($homeScreenPost->parent_post->thumbnail) && !empty($homeScreenPost->parent_post->thumbnail)) {

                        $homeScreenPost->parent_post->thumbnail          =  $this->addBaseInImage($homeScreenPost->parent_post->thumbnail);
                    }


                    $homeScreenPost->parent_post->postedAt = time_elapsed_string($homeScreenPost->parent_post->created_at);

                    $isExist                                           = $this->IsPostLiked($homeScreenPost->parent_post['id'], $authId, 1);

                    $homeScreenPost->parent_post->is_liked              = $isExist['is_liked'];
                    $homeScreenPost->parent_post->reaction              = $isExist['reaction'];
                    $homeScreenPost->parent_post->total_likes_count     = $isExist['total_likes_count'];
                    $homeScreenPost->parent_post->total_comment_count   = $isExist['total_comment_count'];
                    $homeScreenPost->is_reposted                        = $isExist['is_reposted'];
                }

                if (isset($homeScreenPost->post_user) && !empty($homeScreenPost->post_user)) {

                    $homeScreenPost->media_url = $this->addBaseInImage($homeScreenPost->media_url);
                }

                if (isset($homeScreenPost->post_user) && !empty($homeScreenPost->post_user)) {

                    $homeScreenPost->thumbnail = $this->addBaseInImage($homeScreenPost->thumbnail);
                }
                // $homeScreenPost->postedAt = Carbon::parse($homeScreenPost->created_at)->diffForHumans();
                $homeScreenPost->postedAt               = time_elapsed_string($homeScreenPost->created_at);
                $isExist                                = $this->IsPostLiked($homeScreenPost->id, $authId, 1);
                $homeScreenPost->is_liked               = $isExist['is_liked'];
                $homeScreenPost->reaction               = $isExist['reaction'];
                $homeScreenPost->total_likes_count      = $isExist['total_likes_count'];
                $homeScreenPost->total_comment_count    = $isExist['total_comment_count'];
                $homeScreenPost->is_reposted            = $isExist['is_reposted'];
            });
            $notification_count     =   notification_count();
            return $this->sendResponse($homeScreenPosts, trans("message.dicover_post"), 200, $notification_count);
        } catch (Exception $e) {

            Log::error('Error caught: "getDiscoverPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }






    public function getDiscoverCommunityOLD($request, $authId)
    {
        try {

            $limit              =   10;

            if (($request->limit) && !empty($request->limit)) {

                $limit          =   $request->limit;
            }

            $data               =    [];

            $discoverCommunity  =  Group::whereHas('groupMember', function ($query) use ($authId) {

                $query->where('user_id', '<>', $authId);
            })->where('created_by', '<>', $authId)->where('is_active', 1);

            if (!empty($request->search)) {

                $discoverCommunity =   $discoverCommunity->where('name', 'like', "%$request->search%");
            }
            $discoveredCommunity       =   $discoverCommunity->whereNotExists(function ($subquery) use ($authId) {

                $subquery->select(DB::raw(1))

                    ->from('group_members')

                    ->whereRaw("group_id = groups.id AND user_id=" . $authId);
            })->simplePaginate($limit);

            $discoveredCommunity->each(function ($query) use ($authId) {

                // if (isset($query->member_count) && !empty($query->member_count)) {

                //     // $query->member_count    =   shortNumber($query->member_count);
                //     $query->member_count        =  $query->member_count;
                // }

                if (isset($query->cover_photo) && !empty($query->cover_photo)) {

                    $query->cover_photo    =   $this->addBaseInImage($query->cover_photo);
                }
                $query->isJoined         =   (GroupMember::where(['group_id' => $query->id, 'user_id' => $authId])->exists()) ? 1 : 0;
            });
            // Get the member_ids where group_id is in $groupIds and is_active is truthy
            $notification_count     =   notification_count();
            return $this->sendResponse($discoveredCommunity, trans('message.dicover_community'), 200, $notification_count);
        } catch (Exception $e) {

            Log::error('Error caught: "discover-getDiscoverCommunity"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    public function getDiscoverCommunity($request, $authId, $type = "")
    {
        try {

            $limit              =   10;

            if (($request->limit) && !empty($request->limit)) {

                $limit          =   $request->limit;
            }

            $data               =   [];

            $discoverCommunity  =       Group::where('is_active', 1)

                ->whereHas('groupOwner', function ($query) use ($authId) {

                    $query->where('is_active', 1) // Check if group owner is active

                        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                            $query->where('user_id', $authId);
                        })
                        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                            $query->where('blocked_user_id', $authId);
                        });
                });

            if (!empty($request->search)) {

                $discoverCommunity =   $discoverCommunity->where('name', 'like', "%$request->search%");
            }
            // $discoveredCommunity  =   $discoverCommunity->whereNotExists(function ($query) use ($authId) {

            //     $query->select(DB::raw(1))->from('blocked_users')

            //         ->where(function ($query) use ($authId) {
            //             // Check if the authenticated user has blocked someone
            //             $query->where('user_id', $authId)

            //                 ->whereColumn('blocked_users.blocked_user_id', 'groups.created_by');
            //         })
            //         ->orWhere(function ($query) use ($authId) {
            //             // Check if the authenticated user has been blocked by someone
            //             $query->where('blocked_user_id', $authId)

            //                 ->whereColumn('blocked_users.user_id', 'groups.created_by');
            //         });
            // });
            if (isset($type) && !empty($type)) {

                $discoveredCommunity       = $discoverCommunity->limit($limit)->get();
            } else {

                $discoveredCommunity       = $discoverCommunity->simplePaginate($limit);
            }
            $discoveredCommunity->each(function ($query) use ($authId) {

                // if (isset($query->member_count) && !empty($query->member_count)) {

                //     // $query->member_count    =   shortNumber($query->member_count);
                //     $query->member_count        =  $query->member_count;
                // }

                if (isset($query->cover_photo) && !empty($query->cover_photo)) {

                    $query->cover_photo    =   $this->addBaseInImage($query->cover_photo);
                }
                $query->isJoined           =   (GroupMember::where(['group_id' => $query->id, 'user_id' => $authId])->exists()) ? 1 : 0;
            });
            // Get the member_ids where group_id is in $groupIds and is_active is truthy
            $notification_count             =   notification_count();
            return $this->sendResponse($discoveredCommunity, trans('message.dicover_community'), 200, $notification_count);
        } catch (Exception $e) {

            Log::error('Error caught: "discover-getDiscoverCommunity"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #---------------------  O L D      F U N C T I O N ----------------------#

    // public function getDiscoverPeople($request,$authId){

    //     try {

    //         $limit          =   10;
    //         if(isset($request->limit) && !empty($request->limit)){

    //             $limit      =   $request->limit;
    //         }


    //         $data           =   [];

    //         $groupIdsQuery  = GroupMember::where(['user_id' => $authId, 'is_active' => 1]);

    //         if (!empty($request->search)) {

    //             $groupIdsQuery->whereHas('communities', function ($query) use ($request) {

    //                 $query->where('name', 'like', "%$request->search%");

    //             });
    //         }

    //         $groupIds       =   $groupIdsQuery->pluck('group_id');
    //         // Get the member_ids where group_id is in $groupIds and is_active is truthy
    //         $memberIds      =   GroupMember::with(['groupUser'=>function($query){

    //             $query->select('id','name','profile');

    //         },'communities'=>function($query){

    //             $query->select('id','name');

    //         }])->whereIn('group_id', $groupIds)
    //         ->where('is_active', 1) // Assuming 'is_active' field is boolean
    //         ->where('user_id', '<>', $authId)
    //         ->whereNotExists(function ($subquery) use ($authId) {    
    //             $subquery->select(DB::raw(1))
    //                 ->from('friend_requests')
    //                 ->whereRaw(("sender_id ='".$authId."' AND receiver_id=user_id") or ("sender_id =user_id AND receiver_id='".$authId."'"));
    //         })->get()->take(10);

    //         if(isset($memberIds) && !empty($memberIds)){
    //             $memberIds->each(function($suggestMember){
    //                 if(isset($suggestMember->groupUser) && !empty($suggestMember->groupUser)){
    //                     if(isset($suggestMember->groupUser->profile) && !empty($suggestMember->groupUser->profile)){
    //                         $suggestMember->groupUser->profile =   asset('storage/'.$suggestMember->groupUser->profile); 
    //                     }
    //                 }
    //             });
    //         }
    //         $data['show_your_support']       =      $memberIds;
    //         // $startOfWeek                            =       Carbon::now()->startOfWeek();
    //         // $endOfWeek                              =       Carbon::now()->endOfWeek();
    //         // $topCommunities                         =       Group::withCount(['posts' => function ($query) use ($startOfWeek, $endOfWeek) {
    //         //     $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
    //         // }]);
    //         // if(isset($request->search) && !empty($request->search)){
    //         //     $topCommunities=$topCommunities->where('name','LIKE',"%$request->search%");
    //         // }
    //         // $topCommunities=$topCommunities->orderByDesc('posts_count')
    //         // ->limit(5)
    //         // ->get();

    //         // if(isset($topCommunities) && !empty($topCommunities)){
    //         //     $topCommunities->each(function($topCommunity){
    //         //         if(isset($topCommunity->cover_photo) && !empty($topCommunity->cover_photo)){

    //         //             $topCommunity->cover_photo =   asset('storage/'.$topCommunity->cover_photo); 
    //         //         }
    //         //     });
    //         // }
    //         // $data['top_communities_this_week']      =     $topCommunities;
    //         return $this->sendResponse($data, trans('message.dicover_people'), 200);

    //     } catch (Exception $e) {
    //         Log::error('Error caught: "discover-all"' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);
    //     }
    // }
    #---------------------------------  E N D  ------------------------------#
    public function getDiscoverPeople($request, $authId, $limit, $type = "")
    {

        try {

            $data                               =   [];
            $support                            =   $this->supportUsers($request, $authId, $limit, 1);
            if ($support !== "400") {
                $data['show_your_support']      = $support;
            } else {
                $data['show_your_support']      = [];
            }

            // Fetch top health provider
            $topHealthProvider = $this->topHealthProvider($request, $authId, $limit, 1);

            if ($topHealthProvider !== "400") {

                $data['top_health_provider'] = $topHealthProvider; // Use correct variable here

            } else {

                $data['top_health_provider'] = [];
            }

            $careTaker  =   $this->careTakerBySearch($request, $authId, $limit, 1);
            if ($careTaker !== "400") {

                $data['care_takers']       =  $careTaker;
            } else {

                $data['care_takers']       =      [];
            }
            $notification_count     =   notification_count();
            return $this->sendResponse($data, trans('message.discover_people'), 200, $notification_count);
        } catch (Exception $e) {
            Log::error('Error caught: "discover_people"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    public function getDiscoverMedia($request, $authId)
    {
        try {

            $limit          =   10;
            if (isset($request->limit) && !empty($request->limit)) {

                $limit      =   $request->limit;
            }
            $data           =   [];
            $groupIdsQuery  =   GroupMember::where(['user_id' => $authId, 'is_active' => 1]);

            if (!empty($request->search)) {

                $groupIdsQuery->whereHas('communities', function ($query) use ($request) {

                    $query->where('name', 'like', "%$request->search%");

                });
            }
            $groupIds            =      $groupIdsQuery->pluck('group_id');
            $discoverPost        =      Post::where(['is_active'=>1,'media_type'=>2]);
            
            $discoverPost       =       $discoverPost->getPost($authId,$discoverPost);

            if (isset($request->search) && !empty($request->search)) {

                $search = $request->search;
                // Apply the search condition using whereHas directly
                $discoverPost->whereHas('group', function ($query) use ($search) {

                    $query->where('name', 'LIKE', "%$search%");
                });
            }
            // Limit the results to 5 and get the data
            $topVideos = $discoverPost->orderByDesc('like_count');
            $discoverPost->whereIn('group_id', $groupIds)->simplePaginate($limit);


            // if(isset($discoverPost[0]) && !empty($discoverPost[0])){

            //     $discoverPost->each(function ($discoverPosts) use ($authId) {

            //     $discoverPost = transformPostData($discoverPosts, $authId);
            //         #------------ parent post data-----------------#

            //         if (isset($discoverPosts->parent_post) && !empty($discoverPosts->parent_post)) {

            //             $discoverPosts= transformParentPostData($discoverPosts, $authId);
            //         }
            //         return $discoverPosts;

            //     });
            // }
            $notification_count     =   notification_count();
            return $this->sendResponse($discoverPost, trans('message.dicover_media'), 200, $notification_count);
        } catch (Exception $e) {
            Log::error('Error caught: "discover-all"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }




    #------------------- G E T       T O P      H E A L T H         P R O V I D E R  ------------------------#

    public function topHealthProviderNew($request, $authId, $limit, $type = "")
    {
        try {
            $limit = $request->limit ?? 10;
            $groupIds = ['0'];
            $userIds = [];

            if (!empty($request->search)) {
                $groupIds   =   Group::where('name', 'like', "%{$request->search}%")->pluck('id');
                $userIds    =   User::where('user_name', 'like', "%{$request->search}%")
                    ->whereDoesntHave('blockedUsers', fn ($query) => $query->where('blocked_user_id', $authId))
                    ->whereDoesntHave('blockedBy', fn ($query) => $query->where('user_id', $authId))
                    ->pluck('id');
            }

            $maxLikesPostsQuery = Post::whereHas('post_user')
                ->selectRaw('group_id, user_id, COUNT(*) as post_count, SUM(support_count + helpful_count) as total_likes_count')
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))->from('blocked_users')
                        ->where(fn ($query) => $query->where('user_id', $authId)->whereColumn('blocked_users.blocked_user_id', 'user_id'))
                        ->orWhere(fn ($query) => $query->where('blocked_user_id', $authId)->whereColumn('blocked_users.user_id', 'user_id'));
                })
                ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('user_followers')->whereColumn('user_followers.user_id', 'posts.user_id')->where('user_followers.follower_user_id', $authId))
                ->where('user_id', '<>', $authId)
                ->when($request->search, fn ($query) => $query->whereIn('group_id', $groupIds)->orWhere(fn ($q) => $q->whereIn('group_id', $userIds)))
                ->groupBy('user_id')
                ->havingRaw('total_likes_count > 0')
                ->orderByDesc('total_likes_count')
                ->with([
                    'post_user' => fn ($query) => $query->select('id', 'name', 'user_name', 'profile'),
                    'post_user.user_medical_certificate' => fn ($query) => $query->select('id', 'user_id', 'medicial_degree_type', 'verified_status'),
                    'post_user.user_medical_certificate.medical_certificate' => fn ($query) => $query->select('id', 'name')
                ])
                ->whereHas('post_user.userParticipant', fn ($query) => $query->where('participant_id', 3));

            if (!empty($type)) {
                $maxLikesPosts = $maxLikesPostsQuery->get()->take($limit);

                $maxLikesPosts->each(function ($post) use ($authId) {
                    if (!empty($post->post_user->profile)) {
                        $post->post_user->profile = $this->addBaseInImage($post->post_user->profile);
                    }
                    $post->is_supporting = UserFollower::where(['user_id' => $post->user_id, 'follower_user_id' => $authId, 'status' => 2])->exists() ? 1 : 0;
                });

                return $maxLikesPosts;
            } else {
                $maxLikesPosts = $maxLikesPostsQuery->simplePaginate($limit);
                $uniqueUserIds = collect();

                $maxLikesPosts->getCollection()->transform(function ($post) use ($uniqueUserIds, $authId) {
                    if (!empty($post->post_user->profile)) {
                        $post->post_user->profile = $this->addBaseInImage($post->post_user->profile);
                    }
                    $post->is_supporting = UserFollower::where(['user_id' => $post->user_id, 'follower_user_id' => $authId, 'status' => 2])->exists() ? 1 : 0;

                    if (!$uniqueUserIds->contains($post->user_id)) {
                        $uniqueUserIds->push($post->user_id);
                        return $post;
                    }
                    return null;
                })->filter()->values();

                $notification_count = notification_count();
                return $this->sendResponse($maxLikesPosts, trans('message.discover_media'), 200, $notification_count);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "topHealthProvider"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }


    public function topHealthProvider($request, $authId, $limit, $type = "")
    {
        try {

            $limit                 =    $request->limit ?? 10;
            $groupIds              =    ['0'];
            $userIds               =    ['0'];
            if (isset($request->search) && !empty($request->search)) {

                $groupIds          =    Group::whereHas('groupOwner', function ($query) use ($authId) {

                    $query->where('is_active', 1) // Check if group owner is active

                        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                            $query->where('user_id', $authId);
                        })
                        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                            $query->where('blocked_user_id', $authId);
                        });
                })->where('name', 'like', "%{$request->search}%")->pluck('id')->toArray();

                $userIds           =    User::where('user_name', 'like', "%{$request->search}%")->where('is_active', 1)->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                    $query->where('blocked_user_id', $authId);
                })->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                    $query->where('user_id', $authId);
                })->pluck('id')->toArray();
            }

            $maxLikesPosts         =    Post::selectRaw('
                                            group_id,
                                            user_id,
                                            COUNT(*) as post_count,
                                            SUM(support_count + helpful_count) as total_likes_count
                                        ')->whereHas('post_user')
                ->whereNotExists(function ($query) use ($authId) {

                    $query->select(DB::raw(1))->from('blocked_users')

                        ->where(function ($query) use ($authId) {
                            // Check if the authenticated user has blocked someone
                            $query->where('user_id', $authId)
                                ->whereColumn('blocked_users.blocked_user_id', 'user_id');
                        })
                        ->orWhere(function ($query) use ($authId) {
                            // Check if the authenticated user has been blocked by someone
                            $query->where('blocked_user_id', $authId)
                                ->whereColumn('blocked_users.user_id', 'user_id');
                        });
                })

                ->whereNotExists(function ($query) use ($authId) {

                    $query->select(DB::raw(1))
                        ->from('user_followers')
                        ->whereColumn('user_followers.user_id', '=', 'posts.user_id')
                        ->where('user_followers.follower_user_id', '=', $authId);
                })->with(['post_user' => function ($query) {

                    $query->select('id', 'name', 'user_name', 'profile');

                }, 'post_user.user_medical_certificate' => function ($q) {

                    $q->select('id', 'user_id', 'medicial_degree_type', 'verified_status');

                }, 'post_user.user_medical_certificate.medical_certificate' => function ($q) {

                    $q->select('id', 'name');

                }])->whereHas('post_user.userParticipant', function ($q) {  //check user medical or not

                    $q->where('participant_id', [3]);
                })
                ->where('user_id', '<>', $authId)
                ->where('is_active', 1)
                ->when($request->search, function ($query) use ($groupIds, $userIds) {

                    $query->whereIn('group_id', $groupIds)

                        ->orWhere(function ($query) use ($groupIds, $userIds) {
                            $query->whereIn('user_id', $userIds);
                        });
                })
                ->groupBy('user_id')
                ->havingRaw('total_likes_count > 0')
                ->orderByDesc('total_likes_count');

            //dd(DB::getQueryLog());

            if (isset($type) && !empty($type)) {

                $maxLikesPosts  =   $maxLikesPosts->get()->take($limit);

                $maxLikesPosts->each(function ($post) use ($authId) {

                    if (isset($post->post_user) && !empty($post->post_user)) {

                        if (isset($post->post_user->profile) && !empty($post->post_user->profile)) {

                            $post->post_user->profile   =   $this->addBaseInImage($post->post_user->profile);
                        }
                    }

                    $post->is_supporting                =   (UserFollower::where(['user_id' => $post->user_id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0;
                });
                return  $maxLikesPosts;
            } else {

                $maxLikesPosts  =   $maxLikesPosts->simplePaginate($limit);
                $uniqueUserIds = collect();
                // Process each page of results
                // Process each page of results
                $maxLikesPosts->getCollection()->transform(function ($post) use ($uniqueUserIds, $authId) {

                    if (isset($post->post_user) && !empty($post->post_user)) {

                        if (isset($post->post_user->profile) && !empty($post->post_user->profile)) {

                            $post->post_user->profile   =   $this->addBaseInImage($post->post_user->profile);
                        }
                    }
                    $post->is_supporting                =   (UserFollower::where(['user_id' => $post->user_id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0;
                    if (!$uniqueUserIds->contains($post->user_id)) {

                        $uniqueUserIds->push($post->user_id);
                        return $post; // Include this post in filtered result
                    }
                    return null; // Exclude this post
                });
                // Filter out null values (excluded posts) and reset keys
                $filteredPosts = $maxLikesPosts->getCollection()->filter()->values();
                // Update the collection in $maxLikesPosts with the filtered posts
                $maxLikesPosts->setCollection($filteredPosts);
                // Return the paginated results after processing
                $notification_count     =   notification_count();
                return $this->sendResponse($maxLikesPosts, trans('message.discover_media'), 200, $notification_count);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "topHealthProvider"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    public function topHealthProviderWithoutSerach($request, $authId, $limit, $type = "")
    {

        $limit              =    $request->limit ?? 10;

        $maxLikesPosts      =   Post::selectRaw('
                                        group_id,
                                        user_id,
                                        COUNT(*) as post_count,
                                        SUM(support_count + helpful_count) as total_likes_count
                                    ')
            ->whereNotExists(function ($query) use ($authId) {

                $query->select(DB::raw(1))->from('blocked_users')

                    ->where(function ($query) use ($authId) {
                        // Check if the authenticated user has blocked someone
                        $query->where('user_id', $authId)
                            ->whereColumn('blocked_users.blocked_user_id', 'user_id');
                    })
                    ->orWhere(function ($query) use ($authId) {
                        // Check if the authenticated user has been blocked by someone
                        $query->where('blocked_user_id', $authId)
                            ->whereColumn('blocked_users.user_id', 'user_id');
                    });
            })

            ->whereNotExists(function ($query) use ($authId) {
                $query->select(DB::raw(1))
                    ->from('user_followers')
                    ->whereColumn('user_followers.user_id', '=', 'posts.user_id')
                    ->where('user_followers.follower_user_id', '=', $authId);
            })->with('post_user', function ($query) {

                $query->select('id', 'name', 'user_name', 'profile');
            })
            ->where('user_id', '<>', $authId)
            ->where('is_health_provider', 1)
            ->groupBy('user_id', 'group_id')
            ->havingRaw('total_likes_count > 0')
            ->orderByDesc('total_likes_count')
            ->simplePaginate($limit);
        $uniqueUserIds = collect();

        // Process each page of results
        // Process each page of results
        $maxLikesPosts->getCollection()->transform(function ($post) use ($uniqueUserIds, $authId) {

            if (isset($post->post_user) && !empty($post->post_user)) {

                if (isset($post->post_user->profile) && !empty($post->post_user->profile)) {

                    $post->post_user->profile   =   $this->addBaseInImage($post->post_user->profile);
                }
            }
            $post->is_supporting                =   (UserFollower::where(['user_id' => $post->user_id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0;
            if (!$uniqueUserIds->contains($post->user_id)) {

                $uniqueUserIds->push($post->user_id);
                return $post; // Include this post in filtered result
            }
            return null; // Exclude this post
        });
        // Filter out null values (excluded posts) and reset keys
        $filteredPosts = $maxLikesPosts->getCollection()->filter()->values();
        // Update the collection in $maxLikesPosts with the filtered posts
        $maxLikesPosts->setCollection($filteredPosts);
        // Return the paginated results after processing
        $notification_count     =   notification_count();

        return $this->sendResponse($maxLikesPosts, trans('message.discover_media'), 200, $notification_count);
    }


    #------------------ G E T       S U P P O R T       U S E R  ----------#
    public function supportUsers($request, $authId, $limit, $type = "")
    {
        try {

            if (isset($request->search) && !empty($request->search)) {

                $groupIdsQuery      =    Group::where('name', 'like', "%$request->search%")->where('is_active', 1)

                    ->whereHas('groupOwner', function ($query) use ($authId) {

                        $query->where('is_active', 1) // Check if group owner is active

                            ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                                $query->where('user_id', $authId);
                            })
                            ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                                $query->where('blocked_user_id', $authId);
                            });
                    });

                $groupIds           =    $groupIdsQuery->pluck('id');
            }
            // Get the member_ids where group_id is in $groupIds and is_active is truthy
            $supportUser            =   GroupMember::with(['groupUser' => function ($query) {

                $query->select('id', 'name', 'user_name', 'profile');

            }, 'communities' => function ($query) {

                $query->select('id', 'name', 'description', 'cover_photo','member_count','post_count');
            }])

                ->whereHas('groupUser', function ($query) use ($authId) {

                    $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                        $query->where('user_id', $authId);
                    })

                        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                            $query->where('blocked_user_id', $authId);
                        });
                })

                ->whereHas('group', function ($query) use ($authId) {

                    $query->whereHas('groupOwner', function ($query) use ($authId) {

                        $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                            $query->where('user_id', $authId);
                        })

                            ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                                $query->where('blocked_user_id', $authId);
                            });
                    });
                })
                // Exclude users followed by the authenticated user
                ->whereDoesntHave('user.followers', function ($query) use ($authId) {

                    $query->where('follower_user_id', $authId);
                })->whereHas('user');

            // ->whereDoesntHave('user.followers', function ($query) use ($authId) {

            //     $query->where('follower_user_id', $authId);

            // });
            if (isset($request->search) && !empty($request->search)) {

                $supportUser = $supportUser->whereIn('group_id', $groupIds);
            }

            $supportUser = $supportUser->where('is_active', 1) // Assuming 'is_active' field is boolean

                ->where('user_id', '<>', $authId)

                ->groupBy('user_id');

            if (isset($type) && !empty($type)) {

                $supportUser  =   $supportUser->get()->take($limit);
            } else {

                $supportUser  =   $supportUser->simplePaginate($limit);
            }


            if (isset($supportUser[0]) && !empty($supportUser[0])) {

                $supportUser->each(function ($suggestMember) use ($authId) {

                    if (isset($suggestMember->groupUser) && !empty($suggestMember->groupUser)) {
                        if (isset($suggestMember->groupUser->profile) && !empty($suggestMember->groupUser->profile)) {
                            $suggestMember->groupUser->profile =   $this->addBaseInImage($suggestMember->groupUser->profile);
                        }
                    }

                    if (isset($suggestMember->communities) && !empty($suggestMember->communities)) {
                        if (isset($suggestMember->communities->cover_photo) && !empty($suggestMember->communities->cover_photo)) {

                            $suggestMember->communities->cover_photo =   $this->addBaseInImage($suggestMember->communities->cover_photo);
                        }
                    }

                    $suggestMember->is_supporting                =   (UserFollower::where(['user_id' => $suggestMember->user_id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0;
                });
            }
            if (isset($type) && !empty($type)) {

                return  $supportUser;
                // return $data;

            } else {
                $notification_count     =   notification_count();
                return $this->sendResponse($supportUser, trans('message.dicover_people'), 200, $notification_count);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "supportUsers-service"' . $e->getMessage());
            return 400;
        }
    }
    #------------------ G E T       S U P P O R T       U S E R  ----------#



    #-------------------     G E T      C A R E     T A K E R  ---------------#
    public function getCareTaker($request, $authId, $limit, $type = "")
    {

        // User::with('user_group')->whereHas()

        // UserParticipantCategory::where(['participant_id'=>2])->with('user', function($query){

        //     $query->where('is_active',1);

        // })->with('userParticipant',function($q){

        //     $q->where('participant_id',2);

        // });

        $careTaker = User::select('id', 'name', 'user_name', 'profile')->where('is_active', 1)

            ->whereHas('userParticipant', function ($query) {

                $query->where('participant_id', 2);

            })->with(['user_group' => function ($q) {

                $q->select('id', 'group_id', 'user_id')->limit(1);

            }, 'user_group.group' => function ($q) {

                $q->select('id', 'name', 'cover_photo');

            }])
            // ->whereHas('user_group.group', function ($q) {

            //     $q->where('is_active', 1);
            // })

            ->whereHas('user_group.group')
            ->whereHas('user_group')

            ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                $query->where('blocked_user_id', $authId);
            })
            ->whereDoesntHave('user.blockedBy', function ($query) use ($authId) {

                $query->where('user_id', $authId);
            })->whereDoesntHave('followers', function ($query) use ($authId) {

                $query->where('follower_user_id', $authId);
            });
        // ->whereNotExists(function ($query) use ($authId) {

        //     $query->select(DB::raw(1))

        //         ->from('blocked_users')

        //         ->where(function ($query) use ($authId) {
        //             // Check if the authenticated user has blocked someone
        //             $query->where('user_id', $authId)
        //                 ->whereColumn('blocked_users.blocked_user_id', 'users.id');
        //         })
        //         ->orWhere(function ($query) use ($authId) {
        //             // Check if the authenticated user has been blocked by someone
        //             $query->where('blocked_user_id', $authId)
        //                 ->whereColumn('blocked_users.user_id', 'users.id');
        //         });
        // })

        // ->whereNotExists(function ($query) use ($authId) {

        //     $query->select(DB::raw(1))
        //         ->from('user_followers')
        //         ->whereColumn('user_followers.user_id', '=', 'users.id')
        //         ->where('user_followers.follower_user_id', '=', $authId);
        // })->where('id', '<>', $authId);

        if (isset($type) && !empty($type)) {

            $careTaker  =   $careTaker->simplePaginate($limit);
        } else {

            $careTaker  =   $careTaker->get()->take($limit);
        }


        // $groupIdsQuery          =   GroupMember::where(['user_id' => $authId, 'is_active' => 1])->pluck('user_id');

        $notification_count     =   notification_count();
        return $this->sendResponse($careTaker, trans('message.dicover_people'), 200, $notification_count);

        // if (!empty($request->search)) {

        //     $groupIdsQuery      =   Group::where('name', 'like', "%$request->search%");

        // }
        // $groupIds               =   $groupIdsQuery->pluck('user_id');



    }
    #-------------------     G E T      C A R E     T A K E R  ---------------#


    #-----------------------care taker by search ---------------#
    // public function careTakerBySearch($request, $authId, $limit, $type = "")
    // {
    //     try {

    //         DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
    //         // Initialize base query based on search input
    //         $groupMembers = GroupMember::query();

    //         if (!empty($request->search)) {
    //            // Get the group IDs matching the search term
    //             $groupIds = Group::where('name', 'like', "%{$request->search}%")->pluck('id');

    //             // Get the user IDs matching the search term, excluding those who are blocked by or have blocked the authenticated user
    //             $userIds = User::where('user_name', 'like', "%{$request->search}%")
    //                 ->whereDoesntHave('blockedUsers', fn ($query) => $query->where('blocked_user_id', $authId))
    //                 ->whereDoesntHave('blockedBy', fn ($query) => $query->where('user_id', $authId))
    //                 ->pluck('id');
    //             // Add the conditions to the query
    //             $groupMembers->where(function ($query) use ($groupIds, $userIds) {
    //                 $query->whereIn('group_id', $groupIds)
    //                     ->orWhereIn('user_id', $userIds);
    //             });
    //         }
    //         // Filter out the authenticated user's own group memberships
    //         $groupMembers->where('user_id', '<>', $authId);
    //         // Eager load related models with selected fields
    //         $groupMembers->with([
    //             'communities:id,name,cover_photo,member_count,post_count',
    //             'user:id,name,user_name,profile'
    //         ]);
    //         // Filter based on user participant
    //         $groupMembers->whereHas('user.userParticipant', function ($query) {

    //             $query->whereIn('participant_id', [2]);

    //         });
    //         // Exclude blocked users and those who blocked the authenticated user
    //         $groupMembers->whereDoesntHave('user.blockedUsers', function ($query) use ($authId) {

    //             $query->where('blocked_user_id', $authId);
    //         })
    //             ->whereDoesntHave('user.blockedBy', function ($query) use ($authId) {

    //                 $query->where('user_id', $authId);
    //             });

    //         // Exclude users followed by the authenticated user
    //         $groupMembers->whereDoesntHave('user.followers', function ($query) use ($authId) {
    //             $query->where('follower_user_id', $authId);
    //         });
    //         // Only include active users
    //         $groupMembers->whereHas('user')->groupBy('user_id');
    //         // Apply pagination or limit
    //         if (isset($type) && !empty($type)) {
    //             $groupMembers = $groupMembers->limit($limit)->get();
    //         } else {
    //             $groupMembers = $groupMembers->simplePaginate($limit);
    //         }
    //         if (isset($groupMembers[0]) && !empty($groupMembers[0])) {

    //             $groupMembers->each(function ($member) use ($authId) {

    //                 $member->is_supporting                =   (UserFollower::where(['user_id' => $member->user_id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0;
    //                 if (isset($member->user) && !empty($member->user)) {

    //                     if (isset($member->user->profile) && !empty($member->user->profile)) {

    //                         $member->user->profile      =   $this->addBaseInImage($member->user->profile);
    //                     }
    //                 }

    //                 if (isset($member->communities) && !empty($member->communities)) {

    //                     if (isset($member->communities->cover_photo) && !empty($member->communities->cover_photo)) {

    //                         $member->communities->cover_photo      =   $this->addBaseInImage($member->communities->cover_photo);
    //                     }
    //                 }
    //             });
    //         }
    //         if (isset($type) && !empty($type)) {

    //             return $groupMembers;

    //         } else {
    //             $notification_count     =   notification_count();
    //             return $this->sendResponse($groupMembers, trans('message.dicover_people'), 200, $notification_count);
    //         }
    //     } catch (Exception $e) {
    //         Log::error('Error caught: "careTakerBySearch"' . $e->getMessage());
    //         return 400;
    //     }
    // }
    public function careTakerBySearch($request, $authId, $limit, $type = "")
{
    try {
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

        // Initialize base query based on search input
        $groupMembers = GroupMember::query();

        if (!empty($request->search)) {
            // Get the group IDs matching the search term
            $groupIds = Group::where('name', 'like', "%{$request->search}%")->pluck('id');

            // Get the user IDs matching the search term, excluding those who are blocked by or have blocked the authenticated user
            $userIds = User::where('user_name', 'like', "%{$request->search}%")
                ->whereDoesntHave('blockedUsers', fn ($query) => $query->where('blocked_user_id', $authId))
                ->whereDoesntHave('blockedBy', fn ($query) => $query->where('user_id', $authId))
                ->pluck('id');

            // Add the conditions to the query
            $groupMembers->where(function ($query) use ($groupIds, $userIds) {
                $query->whereIn('group_id', $groupIds)
                      ->orWhereIn('user_id', $userIds);
            });
        }

        // Filter out the authenticated user's own group memberships
        $groupMembers->where('user_id', '<>', $authId);

        // Eager load related models with selected fields
        $groupMembers->with([
            'communities:id,name,cover_photo,member_count,post_count',
            'user:id,name,user_name,profile'
        ]);

        // Filter based on user participant
        $groupMembers->whereHas('user.userParticipant', function ($query) {
            $query->whereIn('participant_id', [2]);
        });

        // Exclude blocked users and those who blocked the authenticated user
        $groupMembers->whereDoesntHave('user.blockedUsers', function ($query) use ($authId) {
            $query->where('blocked_user_id', $authId);
        })
        ->whereDoesntHave('user.blockedBy', function ($query) use ($authId) {
            $query->where('user_id', $authId);
        });

        // Exclude users followed by the authenticated user
        $groupMembers->whereDoesntHave('user.followers', function ($query) use ($authId) {
            $query->where('follower_user_id', $authId);
        });

        // Only include active users
        $groupMembers->whereHas('user')->groupBy('user_id');

        // Apply pagination or limit
        if (isset($type) && !empty($type)) {
            $groupMembers = $groupMembers->limit($limit)->get();
        } else {
            $groupMembers = $groupMembers->simplePaginate($limit);
        }

        if ($groupMembers->isNotEmpty()) {
            $groupMembers->each(function ($member) use ($authId) {
                $member->is_supporting = UserFollower::where([
                    'user_id' => $member->user_id,
                    'follower_user_id' => $authId,
                    'status' => 2
                ])->exists() ? 1 : 0;

                if (!empty($member->user) && !empty($member->user->profile)) {
                    $member->user->profile = $this->addBaseInImage($member->user->profile);
                }

                if (!empty($member->communities) && !empty($member->communities->cover_photo)) {
                    $member->communities->cover_photo = $this->addBaseInImage($member->communities->cover_photo);
                }
            });
        }

        if (!empty($type)) {
            return $groupMembers;
        } else {
            $notification_count = notification_count();
            return $this->sendResponse($groupMembers, trans('message.dicover_people'), 200, $notification_count);
        }
    } catch (Exception $e) {
        Log::error('Error caught: "careTakerBySearch"' . $e->getMessage());
        return $this->sendError($e->getMessage(), [], 400);
    }
}

}
