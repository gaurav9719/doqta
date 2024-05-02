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

/**
 * Class DicoverService.
 */
class DicoverService extends BaseController
{

    public function discover($request,$userId){

        if(empty($request->type)){

            return $this->all($request,$userId);

        }else{

            if($request->type==1){          //posts

                return $this->getDiscoverPost($request,$userId);

            }elseif ($request->type==2) {   // community

                return $this->getDiscoverCommunity($request,$userId);

            }elseif ($request->type==3) {   //people
            
                return $this->getDiscoverPeople($request,$userId);

            }elseif ($request->type==4) {   //media
              
                return $this->getDiscoverMedia($request,$userId);

            }
        }
    }


    public function all($request,$authId){

        try {
            $data           =   [];

        #---------- S U P P O R T       S H A R E D     I N T E R E S T  --------------#

            $groupIdsQuery  = GroupMember::where(['user_id' => $authId, 'is_active' => 1]);

            if (!empty($request->search)) {

                $groupIdsQuery->whereHas('communities', function ($query) use ($request) {

                    $query->where('name', 'like', "%$request->search%");
                });
            }
            $groupIds       =   $groupIdsQuery->pluck('group_id');
            // Get the member_ids where group_id is in $groupIds and is_active is truthy
            $memberIds = GroupMember::with(['groupUser'=>function($query){
            
                $query->select('id','name','profile');

            },'communities'=>function($query){
                
                $query->select('id','name');

            }])->whereIn('group_id', $groupIds)
            ->where('is_active', 1) // Assuming 'is_active' field is boolean
            ->where('user_id', '<>', $authId)
            ->whereNotExists(function ($subquery) use ($authId) {    
                $subquery->select(DB::raw(1))
                    ->from('friend_requests')
                    ->whereRaw(("sender_id ='".$authId."' AND receiver_id=user_id") or ("sender_id =user_id AND receiver_id='".$authId."'"));
            })
            ->distinct('group_id')->get()->take(5);
         
            if(isset($memberIds) && !empty($memberIds)){
                $memberIds->each(function($suggestMember){
                    if(isset($suggestMember->groupUser) && !empty($suggestMember->groupUser)){
                        if(isset($suggestMember->groupUser->profile) && !empty($suggestMember->groupUser->profile)){
                            $suggestMember->groupUser->profile =   asset('storage/'.$suggestMember->groupUser->profile); 
                        }
                    }
                });
            }
            $data['support_shared_interests']       =      $memberIds;
        #---------- S U P P O R T       S H A R E D     I N T E R E S T  --------------#


        #-------------  T O P       C O M M U N I T Y       T H I S         W E E K -------------#

            $startOfWeek                            =       Carbon::now()->startOfWeek();
            $endOfWeek                              =       Carbon::now()->endOfWeek();
            $topCommunities                         =       Group::withCount(['groupMember' => function ($query) use ($startOfWeek, $endOfWeek) {

                $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);


            }]);

            if(isset($request->search) && !empty($request->search)){

                $topCommunities=$topCommunities->where('name','LIKE',"%$request->search%");
            }
            $topCommunities = $topCommunities->orderByDesc('post_count')

            ->limit(5)
            ->get();

            if(isset($topCommunities) && !empty($topCommunities)){

                $topCommunities->each(function($topCommunity) use($authId){

                    if(isset($topCommunity->cover_photo) && !empty($topCommunity->cover_photo)){

                        $topCommunity->cover_photo =   asset('storage/'.$topCommunity->cover_photo); 
                    }

                    $topCommunity->isJoined         =   (GroupMember::where(['group_id'=>$topCommunity->id,'user_id'=>$authId])->exists())?1:0;
                });
                //check is join community or not
            }
            $data['top_communities_this_week']      =     $topCommunities;

        #-------------  T O P       C O M M U N I T Y       T H I S         W E E K -------------#


        #---------------   G E T        A R T I C L E S ----------------#

            $topArticles = Post::with(['post_user'=>function($query){

                $query->select('id','name','user_name','profile');

            }])->whereNotNull('link');

            if (isset($request->search) && !empty($request->search)) {

                $search = $request->search;
                // Apply the search condition using whereHas directly
                $topArticles->whereHas('group_post', function($query) use ($search) {

                    $query->where('name', 'LIKE', "%$search%");

                });

            }
            // Limit the results to 5 and get the data
            $topArticles = $topArticles->orderByDesc('like_count')->limit(5)->get();
            
            if(isset($topArticles) && !empty($topArticles)){

                $topArticles->each(function($topArticle){

                    if(isset($topArticle->post_user) && !empty($topArticle->post_user)){

                        if(isset($topArticle->post_user->profile) && !empty($topArticle->post_user->profile)){

                            $topArticle->post_user->profile =   asset('storage/'.$topArticle->post_user->profile); 
                        }
                    }
                    if(isset($topArticle->media_url) && !empty($topArticle->media_url)){

                        $topArticle->media_url =   asset('storage/'.$topArticle->media_url); 
                    }
                });
            }
            // Assign the result to the correct variable
            $data['top_articles'] = $topArticles;
        #---------------- G E T         A R T I C L E S ----------------#

        #------------ T O P     V I D E O   -------------------------#

        $topVideos = Post::with(['post_user'=>function($query){

            $query->select('id','name','user_name','profile');

        }])->whereNotNull('media_url')->where('media_type',2); //2 means video

        if (isset($request->search) && !empty($request->search)) {

            $search = $request->search;
            // Apply the search condition using whereHas directly
            $topVideos->whereHas('group_post', function($query) use ($search) {

                $query->where('name', 'LIKE', "%$search%");

            });
        }
        // Limit the results to 5 and get the data
        $topVideos = $topVideos->orderByDesc('like_count')->limit(5)->get();
        
        if(isset($topVideos) && !empty($topVideos)){

            $topVideos->each(function($topVideo){

                if(isset($topVideo->post_user) && !empty($topVideo->post_user)){

                    if(isset($topVideo->post_user->profile) && !empty($topVideo->post_user->profile)){

                        $topVideo->post_user->profile =   asset('storage/'.$topVideo->post_user->profile); 
                    }
                }
                if(isset($topVideo->media_url) && !empty($topVideo->media_url)){

                    $topVideo->media_url =   asset('storage/'.$topVideo->media_url); 
                }
            });
        }
        // Assign the result to the correct variable
        $data['top_videos'] = $topVideos;

            return $this->sendResponse($data, trans('message.discover_all'), 200);

        } catch (Exception $e) {
            Log::error('Error caught: "discover-all"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
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
    public function getDiscoverPost($request,$authId){

        try {

            $limit              =       10;

            if (isset($request->limit) && !empty($request->limit)) {

                $limit          =       $request->limit;
            }
            $search             =  "";
            
            if(isset($request->search) && !empty($request->search)){

                $search         =       $request->search;
            }

        
            $homeScreenPosts    =   Post::where('posts.is_active', 1)

            ->whereNotExists(function ($query) use ($authId) {

                $query->select(DB::raw(1))
                    ->from('report_posts')
                    ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                    ->where('report_posts.user_id', '=', $authId); // Check if the current user has reported the post
            })
            ->when((!empty($request->search) && $request->search!=""), function ($query) use ($search) {

                return $query->whereHas('group', function ($query) use ($search) {

                    $query->where('name', 'like', '%' . $search . '%');

                });
            })
            ->with(['parent_post' => function ($query) {
                $query->select('id', 'user_id', 'title', 'repost_count', 'like_count', 'comment_count', 'is_high_confidence')
                    ->where('is_active', 1)
                    ->with(['post_user' => function ($query) {
                        $query->select('id', 'name', 'profile');
                    }]);
            },'group'=>function ($query){

                $query->select('id','name');

            }])
            ->latest()
            ->simplePaginate($limit);

                $homeScreenPosts->each(function ($homeScreenPost) use($authId) {
                    #----------- check has liked or not------------#
                    $hasLiked                       =   Like::where(['user_id'=>$authId,'post_id'=>$homeScreenPost->id])->whereNull('comment_id')->exists();
                    $homeScreenPost->is_liked      = ($hasLiked)?1:0;

                    if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

                        $homeScreenPost->media_url = asset('storage/' . $homeScreenPost->media_url);
                    }

                    if ($homeScreenPost->parent_post && $homeScreenPost->parent_post->post_user &&      $homeScreenPost->parent_post->post_user->profile) {
                        $homeScreenPost->parent_post->post_user->profile = asset('storage/'.$homeScreenPost->parent_post->post_user->profile);         
                    }
                    // $homeScreenPost->postedAt = Carbon::parse($homeScreenPost->created_at)->diffForHumans();
                    $homeScreenPost->postedAt = time_elapsed_string($homeScreenPost->created_at);
                    
                });
            

            return $this->sendResponse($homeScreenPosts, trans("message.dicover_post"), 200);

        } catch (Exception $e) {

            Log::error('Error caught: "getDiscoverPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }






    public function getDiscoverCommunity($request,$authId){

        try {

            $limit              =   10;
            if(($request->limit) && !empty($request->limit)){

                $limit          =   $request->limit;
            }
            $data               =    [];

            $discoverCommunity  =  Group::whereHas('groupMember', function ($query) use($authId) {
        
                $query->where('user_id','<>' ,$authId);

                })->where('created_by','<>' ,$authId);

            if (!empty($request->search)) {

                $discoverCommunity = $discoverCommunity->where('name', 'like', "%$request->search%");
  
            }
            $discoveredCommunity       =   $discoverCommunity->simplePaginate($limit);

            $discoveredCommunity->each(function($query){

                if(isset($query->member_count) && !empty($query->member_count)){

                    $query->member_count    =   shortNumber($query->member_count);

                }

                if(isset($query->cover_photo) && !empty($query->cover_photo)){

                    $query->cover_photo    =   asset('storage/'.$query->cover_photo);

                }
            });
            // Get the member_ids where group_id is in $groupIds and is_active is truthy
            
            return $this->sendResponse($discoveredCommunity, trans('message.dicover_community'), 200);

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
    public function getDiscoverPeople($request,$authId){

        try {

            $limit          =   10;

            if(isset($request->limit) && !empty($request->limit)){

                $limit      =   $request->limit;
            }

            $data           =   [];

            DB::enableQueryLog();
            $discoverPeople =   User::where('is_active',1)->where('id','<>',$authId);

            if (!empty($request->search)) {

            $discoverPeople     =  $discoverPeople->whereHas('checkGroup', function($query) use($request){

                $query->where('groups.name', 'like', "%$request->search%");

            });

            }
            $discoverPeople         =  $discoverPeople->with('checkGroup')->whereDoesntHave('following', function($query) use($authId){

                                        $query->where('follower_user_id','=',$authId);
            })->get()->take(10);

            //  dd(DB::getQueryLog());
            
            
            // whereDoesntHave('following', function($query) use($authId){

            //                         $query->where('follower_user_id','=',$authId);

            //                     })->where('is_active',1)->whereNotExists(function ($subquery) use ($authId) {    

            //                         $subquery->select(DB::raw(1))

            //                             ->from('friend_requests')

            //                             ->whereRaw(("sender_id ='".$authId."' AND receiver_id=id") or ("sender_id =id AND receiver_id='".$authId."'"));

            //                     })->where('id','<>',$authId)->get()->take(10);
            
                                // dd(DB::getQueryLog());
            

            return $this->sendResponse($discoverPeople, trans('message.dicover_people'), 200);





            $discoverCommunity  =  Group::whereHas('groupMember', function ($query) use($authId) {
        
                $query->where('user_id','<>' ,$authId);

                })->where('created_by','<>' ,$authId);

            if (!empty($request->search)) {

                $discoverCommunity = $discoverCommunity->where('name', 'like', "%$request->search%");
  
            }


           








            $groupIdsQuery  = GroupMember::where(['user_id' => $authId, 'is_active' => 1]);

            if (!empty($request->search)) {

                $groupIdsQuery->whereHas('communities', function ($query) use ($request) {

                    $query->where('name', 'like', "%$request->search%");

                });
            }

            $groupIds       =   $groupIdsQuery->pluck('group_id');
            // Get the member_ids where group_id is in $groupIds and is_active is truthy
            $memberIds      =   GroupMember::with(['groupUser'=>function($query){
            
                $query->select('id','name','profile');

            },'communities'=>function($query){
                
                $query->select('id','name');

            }])->whereIn('group_id', $groupIds)
            ->where('is_active', 1) // Assuming 'is_active' field is boolean
            ->where('user_id', '<>', $authId)
            ->whereNotExists(function ($subquery) use ($authId) {    
                $subquery->select(DB::raw(1))
                    ->from('friend_requests')
                    ->whereRaw(("sender_id ='".$authId."' AND receiver_id=user_id") or ("sender_id =user_id AND receiver_id='".$authId."'"));
            })->get()->take(10);
           
            if(isset($memberIds) && !empty($memberIds)){
                $memberIds->each(function($suggestMember){
                    if(isset($suggestMember->groupUser) && !empty($suggestMember->groupUser)){
                        if(isset($suggestMember->groupUser->profile) && !empty($suggestMember->groupUser->profile)){
                            $suggestMember->groupUser->profile =   asset('storage/'.$suggestMember->groupUser->profile); 
                        }
                    }
                });
            }
            $data['show_your_support']       =      $memberIds;
            // $startOfWeek                            =       Carbon::now()->startOfWeek();
            // $endOfWeek                              =       Carbon::now()->endOfWeek();
            // $topCommunities                         =       Group::withCount(['posts' => function ($query) use ($startOfWeek, $endOfWeek) {
            //     $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            // }]);
            // if(isset($request->search) && !empty($request->search)){
            //     $topCommunities=$topCommunities->where('name','LIKE',"%$request->search%");
            // }
            // $topCommunities=$topCommunities->orderByDesc('posts_count')
            // ->limit(5)
            // ->get();

            // if(isset($topCommunities) && !empty($topCommunities)){
            //     $topCommunities->each(function($topCommunity){
            //         if(isset($topCommunity->cover_photo) && !empty($topCommunity->cover_photo)){

            //             $topCommunity->cover_photo =   asset('storage/'.$topCommunity->cover_photo); 
            //         }
            //     });
            // }
            // $data['top_communities_this_week']      =     $topCommunities;
            return $this->sendResponse($data, trans('message.dicover_people'), 200);

        } catch (Exception $e) {
            Log::error('Error caught: "discover-all"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    public function getDiscoverMedia($request,$authId){

        try {

            $limit          =   10;
            if(isset($request->limit) && !empty($request->limit)){

                $limit      =   $request->limit;

            }
            $data           =   [];
            $groupIdsQuery  = GroupMember::where(['user_id' => $authId, 'is_active' => 1]);

            if (!empty($request->search)) {

                $groupIdsQuery->whereHas('communities', function ($query) use ($request) {
                    
                    $query->where('name', 'like', "%$request->search%");

                });
            }
            $groupIds       =   $groupIdsQuery->pluck('group_id');

            $discoverPost   =   Post::with(["post_user"=>function($query){

                $query->select('id','name','profile');

            }])->whereIn('group_id', $groupIds)
            ->whereNotNull('media_url')
            ->where('is_active', 1)->simplePaginate($limit);
        
            return $this->sendResponse($discoverPost, trans('message.dicover_media'), 200);

        } catch (Exception $e) {
            Log::error('Error caught: "discover-all"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
