<?php

use App\Models\User;
use App\Models\UserFollower;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Post;


function topHealthProviderOLd($request, $authId, $limit, $type = "")
{
    try {

        $all_top_health_provider        =       [];

        if (isset($type) && !empty($type)) {

            $limit                      =           3;

            if (!empty( $request->limit) && isset( $request->limit)) {


                $limit = $request->limit;
            }
            //--------    top likes    ----------//
            $top_likes_post  = User::query()

                ->where('users.is_active', 1)
                ->whereNotNull('users.user_name')
                ->whereHas('user_medical_certificate.medical_certificate')
                ->with([
                        'user_medical_certificate' => function ($q) {

                        $q->select('id', 'medicial_degree_type', 'user_id');
                        },
                        'user_medical_certificate.medical_certificate' => function ($q) {

                            $q->select('id', 'name');
                        },

                ]);
                    //------ serching user name group name who is joined user ---------//
            if (!empty($request->search)) {

                $search         = $request->search;

                $top_likes_post->where(function ($query) use ($search) {
                
                    $query->where('users.user_name', 'LIKE', "%{$search}%")

                    ->orWhereRaw(DB::raw("(SELECT COUNT(*) FROM `groups`
                    JOIN group_members ON `groups`.id = group_members.group_id    
                    WHERE `groups`.name LIKE '%{$search}%' AND group_members.user_id=users.id) > 0"));
                });
            }

            $top_likes_post     =   $top_likes_post ->select('users.id', 'users.name', 'users.user_name', 'users.profile')

                ->addSelect(DB::raw('(SELECT COUNT(*) FROM post_likes
                           JOIN posts ON post_likes.post_id = posts.id
                           WHERE posts.user_id = users.id) AS total_likes_count'))
                ->orderByDesc('total_likes_count')
                ->havingRaw('total_likes_count > 0')
                ->first();

            if ($top_likes_post) {
                
                $all_top_health_provider[]  = [
                    'id' => $top_likes_post->id,
                    'name' => $top_likes_post->name,
                    'user_name' => $top_likes_post->user_name,
                    'profile' => !empty($top_likes_post->profile) ? asset('storage/' . $top_likes_post->profile) : null,
                    'title' => !empty($top_likes_post->total_likes_count) ?
                        ($top_likes_post->total_likes_count > 100 ? 'Over 1k  likes' : $top_likes_post->total_likes_count . ' likes')
                        : null,
                    'total_likes_count' => $top_likes_post->total_likes_count,
                    'is_supporting' => (UserFollower::where(['user_id' => $top_likes_post->id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0,


                    // 'medical_certificate' => $top_likes_post->user_medical_certificate->isNotEmpty() ? $top_likes_post->user_medical_certificate->pluck('medical_certificate') : [],
                    'user_medical_certificate' => $top_likes_post->user_medical_certificate->isNotEmpty() ? $top_likes_post->user_medical_certificate : [],

                ];
            }


            $top_high_confindence_comment = User::query()
                ->with([
                    'user_medical_certificate' => function ($q) {

                        $q->select('id', 'medicial_degree_type', 'user_id');
                        },
                        'user_medical_certificate.medical_certificate' => function ($q) {

                            $q->select('id', 'name');
                        },

                   
                ])
                ->where('users.is_active', 1)
                ->whereNotNull('users.user_name')
                ->whereHas('user_medical_certificate.medical_certificate');
                        //------ serching user name group name who is joined user ---------//
            if (!empty($request->search)) {
                
                $search = $request->search;
               $top_high_confindence_comment ->where(function ($query) use ($search) {
                    $query->where('users.user_name', 'LIKE', "%{$search}%")
                    ->orWhereRaw(DB::raw("(SELECT COUNT(*) FROM `groups`
                    JOIN group_members ON `groups`.id = group_members.group_id    
                    WHERE `groups`.name LIKE '%{$search}%' AND group_members.user_id=users.id) > 0"));
                });
            }
            $top_high_confindence_comment=$top_high_confindence_comment->select('users.id', 'users.name', 'users.user_name', 'users.profile')
                ->addSelect(DB::raw('(SELECT COUNT(*) FROM comments
                               JOIN posts ON comments.post_id = posts.id
                               WHERE posts.user_id = users.id ) AS total_comments_count'))
                ->orderByDesc('total_comments_count')
                ->first();




            if ($top_high_confindence_comment) {
                $all_top_health_provider[]  = [
                    'id' => $top_high_confindence_comment->id,
                    'name' => $top_high_confindence_comment->name,
                    'user_name' => $top_high_confindence_comment->user_name,
                    'profile' => !empty($top_high_confindence_comment->profile) ? asset('storage/' . $top_high_confindence_comment->profile) : null,
                    'title' => !empty($top_high_confindence_comment->total_comments_count) ? $top_high_confindence_comment->total_comments_count . ' high confidence comments' : null,
                    'total_comments_count' => $top_high_confindence_comment->total_comments_count,
                    'is_supporting' => (UserFollower::where(['user_id' => $top_high_confindence_comment->id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0,
                    // 'medical_certificate' => $top_high_confindence_comment->user_medical_certificate->isNotEmpty() ? $top_high_confindence_comment->user_medical_certificate->pluck('medical_certificate') : [],

                    'user_medical_certificate' => $top_high_confindence_comment->user_medical_certificate->isNotEmpty() ? $top_high_confindence_comment->user_medical_certificate : [],

                ];
            }
            return $all_top_health_provider;

        } else {
         
            $limit = 10;
            if (!empty( $request->limit) && isset( $request->limit)) {
                $limit = $request->limit;
            }
            $all_top_health_provider = User::where('users.is_active', 1)
                ->whereNotNull('users.user_name')
                ->whereHas('user_medical_certificate.medical_certificate')
                ->with([
                    'user_medical_certificate' => function ($q) {

                        $q->select('id', 'medicial_degree_type', 'user_id');
                        },
                        'user_medical_certificate.medical_certificate' => function ($q) {

                            $q->select('id', 'name');
                        },
                ])
                ->select('users.id', 'users.name', 'users.user_name');
            //------ serching user name group name who is joined user ---------//
            if (!empty($request->search)) {
                $search = $request->search;
               $all_top_health_provider->where(function ($query) use ($search) {
                    $query->where('users.user_name', 'LIKE', "%{$search}%")
                    ->orWhereRaw(DB::raw("(SELECT COUNT(*) FROM groups
                    JOIN group_members ON groups.id = group_members.group_id    
                    WHERE groups.name LIKE '%{$search}%' AND group_members.user_id=users.id) > 0"));
                });
            }




            $all_top_health_provider = $all_top_health_provider
                ->addSelect(DB::raw('(SELECT COUNT(*) FROM post_likes
                              JOIN posts ON post_likes.post_id = posts.id
                              WHERE posts.user_id = users.id) AS total_likes'))
                ->addSelect(DB::raw('(SELECT COUNT(*) FROM comments
                              JOIN posts ON comments.post_id = posts.id
                              WHERE posts.user_id = users.id  ) AS total_comments'))
                ->orderByDesc('total_likes')
                ->orderByDesc('total_comments')
                ->havingRaw('total_likes > 0 or total_comments > 0')
                ->simplePaginate($limit)
                ->map(function ($user) use($authId){

                    return [

                        'id' => $user->id,
                        'name' => $user->name,
                        'user_name' => $user->user_name,
                        'profile' => !empty($user->profile) ? asset('storage/' . $user->profile) : null,
                        'total_likes_count' => $user->total_likes,
                        'total_comments_count' => $user->total_comments,
                        'title' => !empty($user->total_likes) ?
                            ($user->total_likes > 1000 ? 'Over 1k likes' : $user->total_likes . ' likes')
                            : 'Over ' . $user->total_comments . ' high confidence comments',
                        'is_supporting' => (UserFollower::where(['user_id' => $user->id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0,
                        // 'medical_certificate' => $user->user_medical_certificate->isNotEmpty() ? $user->user_medical_certificate->pluck('medical_certificate') : [],
                        'user_medical_certificate' => $user->user_medical_certificate->isNotEmpty() ? $user->user_medical_certificate : [],

                    ];
                });
            $notification_count     =   notification_count();

    
            return response()->json([
                'status' => 200,
                'message' => "Community deactivated Successfuly",
                'data'=>$all_top_health_provider,
                "notification"=>$notification_count
            ]);
        }
    } catch (Exception $e) {
        Log::error('Error caught: "topvideo" ' . $e->getMessage());
        return $e->getMessage();
    }
}


function topHealthProvider($request, $authId, $limit, $type = "")
{
    try {
        $defaultLimit           =       $type ? 3 : 10;

        $limit                  =       !empty($request->limit) && isset($request->limit) ? $request->limit : $defaultLimit;

        $allTopHealthProviders  =       [];

        if (!empty($type)) {

            $user1              =   "";

            $topLikeUser        =       getTopLikesPost($request, $authId);

            if(isset($topLikeUser) && !empty($topLikeUser)){

                $user1          =       $topLikeUser['id'];
            }
            $allTopHealthProviders[] =  $topLikeUser;

            // $allTopHealthProviders[] = getTopHighConfidenceComment($request, $authId,$user1);
            // return $allTopHealthProviders;

           $topHighConfidencePost= getTopHighConfidencePost($request, $authId,$user1);

           if(isset($topHighConfidencePost) && !empty($topHighConfidencePost)){

                 $allTopHealthProviders[] =$topHighConfidencePost; 
           }

            return $allTopHealthProviders;


        } else {

            $allTopHealthProviders = getAllTopHealthProviders($request, $authId, $limit);
        }
        $notificationCount = notification_count();
        return response()->json([
            'status' => 200,
            'message' => trans('message.top_health_provider'),
            'data' => $allTopHealthProviders,
            'notification' => $notificationCount
        ]);
    } catch (Exception $e) {
        Log::error('Error caught: "topHealthProvider" ' . $e->getMessage());
        return response()->json(['status' => 400, 'message' => $e->getMessage()]);
    }
}

function getTopHighConfidencePost($request, $authId,$user1){
    $query = User::query()
        ->where('users.is_active', 1)
        ->where('users.id','<>' ,$authId);
        if(isset($user1) && !empty($user1)){

            $query->where('users.id','<>',$user1);
        }
        $query->whereNotNull('users.user_name')
        
        ->whereHas('user_medical_certificate.medical_certificate')
        
        ->whereHas('userParticipant',function($q) use ($authId){
            $q->where([
                'participant_id'=>3,
              // 'is_verify'=>1
                ]);
         })
         
        ->with([
            'user_medical_certificate' => function ($q) {

                $q->select('id', 'medicial_degree_type', 'user_id');
                },
                'user_medical_certificate.medical_certificate' => function ($q) {

                    $q->select('id', 'name');
                },
        ]) ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

            $query->where('blocked_user_id', $authId);
        })

        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

            $query->where('user_id', $authId);
        });
        
    addSearchFilter($query, $request->search);

    $topHighConfidenceComment = $query->select('users.id', 'users.name', 'users.user_name', 'users.profile')
        // ->addSelect(DB::raw('COALESCE((SELECT COUNT(*) FROM comments JOIN posts ON comments.post_id = posts.id WHERE posts.user_id = users.id) ,0)AS total_comments_count'))
        ->addSelect(DB::raw('COALESCE((SELECT COUNT(*) FROM  posts  WHERE posts.user_id = users.id  AND is_high_confidence=1 ) ,0) AS total_posts_count'))
        ->havingRaw('total_posts_count > 0 ')
        ->orderByDesc('total_posts_count')
        ->first();

    return formatHealthProvider($topHighConfidenceComment, $authId, 'total_posts_count', 'high confidence post');
}


function getTopLikesPost($request, $authId)
{
   
    $query = User::query()
        ->where('users.is_active', 1)
        ->where('users.id','<>' ,$authId)

        ->whereNotNull('users.user_name')
        ->whereHas('user_medical_certificate.medical_certificate')
        ->whereHas('userParticipant',function($q) use ($authId){
            $q->where([
                'participant_id'=>3,
              //  'is_verify'=>1
                ]);
        })
        ->with([
            'user_medical_certificate' => function ($q) {

                $q->select('id', 'medicial_degree_type', 'user_id');
                },
                'user_medical_certificate.medical_certificate' => function ($q) {

                    $q->select('id', 'name');
                },
        ])

        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

            $query->where('blocked_user_id', $authId);
        })

        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

            $query->where('user_id', $authId);
        });

    addSearchFilter($query, $request->search);

    $topLikesPost = $query->select('users.id', 'users.name', 'users.user_name', 'users.profile')

        ->addSelect(DB::raw('(SELECT COUNT(*) FROM post_likes JOIN posts ON post_likes.post_id = posts.id WHERE posts.user_id = users.id) AS total_likes_count'))
        ->orderByDesc('total_likes_count')
        ->havingRaw('total_likes_count > 0')
        ->first();

    return formatHealthProvider($topLikesPost, $authId, 'total_likes_count', 'likes');
}

function getTopHighConfidenceComment($request, $authId,$user1="")
{
    $query = User::query()
        ->where('users.is_active', 1)
        ->where('users.id','<>' ,$authId);
        if(isset($user1) && !empty($user1)){

            $query->where('users.id','<>',$user1);
        }
        $query->whereNotNull('users.user_name')
        
        ->whereHas('user_medical_certificate.medical_certificate')
        
        ->whereHas('userParticipant',function($q) use ($authId){
            $q->where([
                'participant_id'=>3,
              // 'is_verify'=>1
                ]);
         })
         
        ->with([
            'user_medical_certificate' => function ($q) {

                $q->select('id', 'medicial_degree_type', 'user_id');
                },
                'user_medical_certificate.medical_certificate' => function ($q) {

                    $q->select('id', 'name');
                },
        ]) ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

            $query->where('blocked_user_id', $authId);
        })

        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

            $query->where('user_id', $authId);
        });
        
    addSearchFilter($query, $request->search);

    $topHighConfidenceComment = $query->select('users.id', 'users.name', 'users.user_name', 'users.profile')
        ->addSelect(DB::raw('COALESCE((SELECT COUNT(*) FROM comments JOIN posts ON comments.post_id = posts.id WHERE posts.user_id = users.id) ,0)AS total_comments_count'))
        ->orderByDesc('total_comments_count')->orderByRaw('RAND()')

        ->first();

    return formatHealthProvider($topHighConfidenceComment, $authId, 'total_comments_count', 'high confidence comments');
}

function getAllTopHealthProviders($request, $authId, $limit)
{
    

    $query = User::query()
        ->where('users.is_active', 1)
        ->where('users.id','<>' ,$authId)

        ->whereNotNull('users.user_name')
        ->whereHas('user_medical_certificate.medical_certificate')
         ->whereHas('userParticipant',function($q){
            $q->where([
                'participant_id'=>3,
              //  'is_verify'=>1
                ]);
         })
        ->with([
            'user_medical_certificate' => function ($q) {

                $q->select('id', 'medicial_degree_type', 'user_id');
                },
                'user_medical_certificate.medical_certificate' => function ($q) {

                    $q->select('id', 'name');
                },
        ])->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

            $query->where('blocked_user_id', $authId);
        })

        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

            $query->where('user_id', $authId);
        });

    addSearchFilter($query, $request->search);

    $allTopHealthProviders = $query->select('users.id', 'users.name', 'users.user_name','users.profile')
        ->addSelect(DB::raw('COALESCE((SELECT COUNT(*) FROM post_likes JOIN posts ON post_likes.post_id = posts.id WHERE posts.user_id = users.id),0) AS total_likes_count'))
       ->addSelect(DB::raw('COALESCE((SELECT COUNT(*) FROM  posts  WHERE posts.user_id = users.id  AND is_high_confidence=1 ) ,0)AS total_posts_count'))
        ->orderByDesc('total_posts_count')
        ->orderByDesc('total_likes_count')
        ->havingRaw('total_likes_count > 0 OR total_posts_count > 0')
        ->simplePaginate($limit);

        $allTopHealthProviders->getCollection()->transform(function ($user) use ($authId) {

            return formatHealthProvider($user, $authId, 'total_likes_count', 'likes');
        });
    
        return $allTopHealthProviders;
    //     ->map(function ($user) use ($authId) {
    //         return formatHealthProvider($user, $authId, 'total_likes', 'likes');
    //     });

    // return $allTopHealthProviders;
}

function addSearchFilter($query, $search)
{
    if (!empty($search)) {

        $query->where(function ($query) use ($search) {

            $query->where('users.user_name', 'LIKE', "%{$search}%")

                ->orWhereRaw(DB::raw("(SELECT COUNT(*) FROM `groups` JOIN group_members ON `groups`.id = group_members.group_id WHERE `groups`.name LIKE '%{$search}%' AND group_members.user_id = users.id) > 0"));

        });
    }
}

function formatHealthProvider($user, $authId, $countField, $countLabel)
{
    if (!$user) return null;

    return [
        'id' => $user->id,
        'name' => $user->name,
        'user_name' => $user->user_name,
        'profile' => isset($user->profile) && !empty($user->profile) ? addBaseUrl($user->profile) : null,
        'title' => !empty($user->$countField) ? ($user->$countField > 1000 ? "Over 1k {$countLabel}" : $user->$countField . " {$countLabel}") : null,
        'total_likes_count' => $countField === 'total_likes_count' ? $user->$countField : 0,
        'total_posts_count' => $countField === 'total_posts_count' ? $user->$countField : 0,
        'is_supporting' => UserFollower::where(['user_id' => $user->id, 'follower_user_id' => $authId, 'status' => 2])->exists() ? 1 : 0,
        'user_medical_certificate' => $user->user_medical_certificate->isNotEmpty() ? $user->user_medical_certificate : [],
    ];
}









?>