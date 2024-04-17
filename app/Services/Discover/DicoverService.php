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
            // ->pluck('user_id')
            // ->take(5);
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
            $startOfWeek                            =       Carbon::now()->startOfWeek();
            $endOfWeek                              =       Carbon::now()->endOfWeek();
            $topCommunities                         =       Group::withCount(['posts' => function ($query) use ($startOfWeek, $endOfWeek) {
                $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            }]);
            if(isset($request->search) && !empty($request->search)){
                $topCommunities=$topCommunities->where('name','LIKE',"%$request->search%");
            }
            $topCommunities=$topCommunities->orderByDesc('posts_count')
            ->limit(5)
            ->get();

            if(isset($topCommunities) && !empty($topCommunities)){
                $topCommunities->each(function($topCommunity){
                    if(isset($topCommunity->cover_photo) && !empty($topCommunity->cover_photo)){

                        $topCommunity->cover_photo =   asset('storage/'.$topCommunity->cover_photo); 
                    }
                });
            }
            $data['top_communities_this_week']      =     $topCommunities;
            return $this->sendResponse($data, "dds", 200);

        } catch (Exception $e) {
            Log::error('Error caught: "discover-all"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    
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

            $user               =       User::findOrFail($authId);
            // $posts              =       $user->posts()->latest()->simplePaginate($limit);
            // $posts = $user->posts()->where(['posts.is_active' => 1])->whereNotExists('')->latest()->simplePaginate($limit);
            $homeScreenPosts = $user->posts()
            
            ->where('posts.is_active', 1)
            ->whereNotExists(function ($query) use ($user) {
                $query->select(DB::raw(1))
                    ->from('report_posts')
                    ->whereColumn('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                    ->where('report_posts.user_id', '=', $user->id); // Check if the current user has reported the post
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

                $homeScreenPosts->each(function ($homeScreenPost) {


                    if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

                        $homeScreenPost->media_url = asset('storage/' . $homeScreenPost->media_url);
                    }

                    if ($homeScreenPost->parent_post && $homeScreenPost->parent_post->post_user &&      $homeScreenPost->parent_post->post_user->profile) {
                        $homeScreenPost->parent_post->post_user->profile = asset('storage/'.$$homeScreenPost->parent_post->post_user->profile);         
                    }
                    $homeScreenPost->postedAt = Carbon::parse($homeScreenPost->created_at)->diffForHumans();

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

    public function getDiscoverPeople($request,$authId){

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
