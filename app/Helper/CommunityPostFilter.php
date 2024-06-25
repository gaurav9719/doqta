<?php

use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;



    if (!function_exists('fetchPosts')) {
        function fetchPosts($request, $community_id, $limit, $authUser)
        {
           
            $authId     =    $authUser->id;
            $posts      =   Post::where('is_active', 1)
                ->where('group_id', $community_id)
                ->whereHas('post_user', function ($query) {

                    $query->where('is_active', 1);

                });
            #-------------- TRENDING POST --------------#
            if (!empty($request->trending)) {

                $posts = applyTrendingFilter($posts, $request->trending_type);
            }
            #-------------------------------------------#

            #----------- CONFIDENCE SCORE --------------#
            if (!empty($request->confidence)) {

                $posts->where('is_high_confidence', 1);
            }
            #-------------------------------------------#

            #-------------- LOCATION -------------------#
            if (!empty($request->location)) {
                
                $distance   =   $request->distance ?? 100;

                $lat        =   $authUser->lat;
                $long       =   $authUser->long;

                if(!empty($request->lat) &&  !empty($request->long)){

                    $posts = applyLocationFilter($posts, $distance, $request->lat, $request->long);

                }else{

                    if(!empty($lat) && !empty($long)){

                        $posts = applyLocationFilter($posts, $distance, $lat, $long);
                    }
                }
            }
            #-------------------------------------------#

            #------ CHECK IF HEALTH PROVIDER -----------#
            if (!empty($request->health_provider)) {

                $posts->whereHas('post_user.userParticipant', fn ($query) => $query->where('participant_id', 3));
            }
            #-------------------------------------------#

            #------------ APPLY BLOCKING ---------------#
            $posts = applyBlockingFilters($posts, $authId);
            #-------------------------------------------#

            #------------- APPLY HIDING ----------------#
            $posts = applyHidingFilters($posts, $authId);
            #-------------------------------------------#

            #------------ CATEGORY FILTER --------------#
            if (!empty($request['post_category_id'])) {

                $posts->where('post_category', $request['post_category_id']);
            }
            #-------------------------------------------#
            #-------------- WITH RELATIONS -------------#
            $posts->with([

                'group:id,name,description,cover_photo,post_count',
                'post_user:id,user_name,name,profile',
                
                'post_user.user_medical_certificate'=>function($q){

                            $q->select('id','medicial_degree_type','user_id');
    
                        },
                        'post_user.user_medical_certificate.medical_certificate'=>function($q){
    
                            $q->select('id','name');
                        },
                'parent_post' => function ($query) {

                    $query->select('*')
                        ->where('is_active', 1)
                        ->with([
                            'post_user:id,name,user_name,profile',
                            'post_user.user_medical_certificate'=>function($q){

                                $q->select('id','medicial_degree_type','user_id');
        
                            },
                            'post_user.user_medical_certificate.medical_certificate'=>function($q){
        
                                $q->select('id','name');
                            },

                            'group:id,name,description,created_by,post_count,is_active'
                        ]);
                }
            ]);
            #-------------------------------------------#

            #--------------- SORTING -------------------#
            $posts->orderByDesc('id');
            #-------------------------------------------#

            return $posts->simplePaginate($limit);
        }
    }


    if (!function_exists('applyTrendingFilter')) {

        function applyTrendingFilter($posts, $trending_type)
        {
            $now = now();
            if ($trending_type == 1) {

                $one_week_ago = $now->subWeek()->toDateString(); // Convert to date string
                $posts->whereDate('created_at', '>=', $one_week_ago)
                    ->orderBy('like_count', 'desc');
                    
            } elseif ($trending_type == 2) {

                $start_of_month = $now->startOfMonth()->toDateString(); // Convert to date string
                $posts->whereDate('created_at', '>=', $start_of_month)
                    ->orderBy('like_count', 'desc');
            }
            return $posts;
        }
    }
    if (!function_exists('applyLocationFilter')) {

        function applyLocationFilter($posts, $distance, $lat, $long)
        {
            $posts->select('*', DB::raw("round(6371 * acos(cos(radians($lat)) 
                * cos(radians(`lat`)) 
                * cos(radians(`long`) - radians($long)) 
                + sin(radians($lat)) 
                * sin(radians(`lat`))),2) AS distance"))
                ->having("distance", "<", $distance);
            return $posts;
        }
    }
    if (!function_exists('applyBlockingFilters')) {

        function applyBlockingFilters($posts, $authId)
        {
            $posts->where(function ($query) use ($authId) {

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
            });
            return $posts;
        }
    }

    if (!function_exists('applyHidingFilters')) {

        function applyHidingFilters($posts, $authId)
        {
            $posts->whereNotExists(function ($query) use ($authId) {
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
            return $posts;
        }
    }


    function sendNotification($isExist, $rePost, $authId)
    {
        $group          = Group::find($isExist->group_id);
        $sender         = Auth::user();
        $receiver       = User::find($isExist->user_id);
        $message        = "{$sender->name} reposted your post in {$group->name}";
        $data = [
            "message" => $message,
            "post_id" => $rePost->id,
            "community_id" => $isExist->group_id
        ];

        $postUser = Post::select('user_id')->where('id', $rePost->parent_id)->first();

        if ($postUser->user_id != $authId) {
            $serviceS = app(NotificationService::class);
            
            $serviceS->sendNotificationNew($sender, $receiver, trans('notification_message.reposted_post_type'), $data);
        }
    }

    function logActivity($authId, $rePost, $parent_id, $group_id)
    {
        $activity = new ActivityLog();
        $activity->user_id = $authId;
        $activity->community_id = $group_id;
        $activity->post_id = $rePost->id;
        $activity->parent_id = $parent_id;
        $activity->action_details = "Reposted the post in group ID {$group_id}";
        $activity->action = trans('notification_message.reposted_post_type');
        $activity->save();
    }





