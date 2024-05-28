<?php

namespace App\Http\Controllers\Api\Search;

use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use App\Models\UsersInterest;
use App\Models\ReportToComment;
use App\Traits\IsCommunityJoined;
use Illuminate\Support\Facades\DB;
use App\Services\Like\likesService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Services\Discover\DicoverService;
use App\Traits\postCommentLikeCount;
class SearchController extends BaseController
{

    use postCommentLikeCount;
    protected $discoverCoummunity; 
    public function __construct(DicoverService $discoverCoummunity)
    {
        $this->discoverCoummunity = $discoverCoummunity;
       
    }
    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request)
    // {
    //     $limit          =   $request->limit??4;
    //     $authId         =   Auth::id();
    //     // dd($authId);
    //     $community          =   [];
    //     $interest           =   [];
    //     if(isset($request->search) && !empty($request->search)){

    //         $request['limit']           =   $limit;

    //         $json       = $this->discoverCoummunity->getDiscoverCommunity($request,$authId,1);
    //         // dd($json->getData()->data);
    //         // $jsonArray  = json_decode($json);
    //         //  dd($json->getData()->status);
    //         if($json->getData()->status!=400){

    //             $community['search_community']= $json->getData()->data;

    //         }else{

    //             $community['search_community']=[];
    //         }

    //     }else{

    //         $userInterest   =   UsersInterest::with('interest',function($query){

    //             $query->select('id','name');
    
    //         })->where('user_id',$authId)->get();
    
          
    //         if(isset($userInterest[0]) && !empty($userInterest[0])){
                
    //             foreach($userInterest as $interest){
    
    //                 if(isset($interest['interest']) && !empty($interest['interest']['name']))
    //                 {
    //                     $interest[]  = $interest['interest']['name'];
    //                 }
    //             }
    //         }
    //         $response                =   $this->searchCommunity($request,$authId,$interest,$limit);
    //         if($response!="400"){
    
    //             $community['suggest_for_you']=$response;
    
    //         }else{
    
    //             $community['suggest_for_you']=[];
    //         }
    //         $topCommunity       =   $this->discoverCoummunity->topCommunityThisWeek($request,$authId,$limit,1,1);

            
    //         if(isset($topCommunity) && $topCommunity!="400"){
    
    //             $community['trending_community']=$topCommunity;
    
    //         }else{
    //             $community['trending_community']=[];
    
    //         }
    //     }
    //     return $this->sendResponse($community, trans('message.search_community'), 200);
    // }
    public function index(Request $request)
    {
        $limit = $request->limit ?? 4;
        $authId = Auth::id();
        $community = [];
    
        if (!empty($request->search)) {

            $request['limit'] = $limit;
            $json = $this->discoverCoummunity->getDiscoverCommunity($request, $authId, 1);
    
            $community['search_community'] = $json->getData()->status != 400 
                ? $json->getData()->data 
                : [];

        } else {

            $userInterests = UsersInterest::with('interest:id,name')
                ->where('user_id', $authId)
                ->get();
    
            $interests = $userInterests->isNotEmpty()
                ? $userInterests->pluck('interest.name')->filter()->toArray()
                : [];
    
            $response = $this->searchCommunity($request, $authId, $interests, $limit);
            $community['suggest_for_you'] = $response != "400" ? $response : [];
            $topCommunity = $this->discoverCoummunity->topCommunityThisWeek($request, $authId, $limit, 1, 1);
            $community['trending_community'] = $topCommunity != "400" ? $topCommunity : [];
        }
    
        return $this->sendResponse($community, trans('message.search_community'), 200);
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }



    // public function searchCommunity($request,$authId,$interest){

    //     try {

    //         $limit              =   5;
            
    //         if (($request->limit) && !empty($request->limit)) {

    //             $limit          =   $request->limit;
    //         }
    //         $data               =    [];

           
    //         //DB::enableQueryLog();
    //         $discoverCommunity  =           Group::whereHas('groupMember', function ($query) use ($authId) {

    //             $query->where('user_id', '<>', $authId);

    //         })->where('created_by', '<>', $authId)->where('is_active', 1);
            
    //         if (!empty($interest)) {

    //             $discoverCommunity =   $discoverCommunity->where(function ($query) use ($interest) {

    //                 foreach ($interest as $term) {

    //                     $query->orWhere('name', 'like', '%' . $term . '%');
                        
    //                 }
    //             });
    //         }
    //         $discoveredCommunity       =   $discoverCommunity->whereNotExists(function ($subquery) use ($authId) { 

    //             $subquery->select(DB::raw(1))

    //                 ->from('group_members')

    //                 ->whereRaw("group_id = groups.id AND user_id=".$authId);

    //         })->get($limit);

    //       dd($discoverCommunity);

    //         if(empty($discoverCommunity[0])){

    //             $discoverCommunity  =           Group::whereHas('groupMember', function ($query) use ($authId) {

    //                 $query->where('user_id', '<>', $authId);
    
    //             })->where('created_by', '<>', $authId)->where('is_active', 1);
    
    //             if (!empty($interest)) {
    
    //                 $discoverCommunity =   $discoverCommunity->where(function ($query) use ($interest) {
    
    //                     foreach ($interest as $term) {
    
    //                         $query->orWhere('name', 'like', '%' . $term . '%');
                            
    //                     }
    //                 });
    //             }
    //             $discoveredCommunity       =   $discoverCommunity->whereNotExists(function ($subquery) use ($authId) { 
    
    //                 $subquery->select(DB::raw(1))
    
    //                     ->from('group_members')
    
    //                     ->whereRaw("group_id = groups.id AND user_id=".$authId);
    
    //             })->get($limit);
    //         }
    //         dd(DB::getQueryLog());
    //         if(isset($discoverCommunity[0]) && !empty($discoverCommunity[0])){

    //             $discoveredCommunity->each(function ($query) use ($authId) {
    
    //                 if (isset($query->member_count) && !empty($query->member_count)) {
    
    //                     $query->member_count    =   shortNumber($query->member_count);
    //                 }
    
    //                 if (isset($query->cover_photo) && !empty($query->cover_photo)) {
    
    //                     $query->cover_photo     =   $this->addBaseInImage($query->cover_photo);
    //                 }
    //                 $query->isJoined            =   (GroupMember::where(['group_id' => $query->id, 'user_id' => $authId])->exists()) ? 1 : 0;
    //             });
    //         }
    //         // Get the member_ids where group_id is in $groupIds and is_active is truthy
    //         return $discoveredCommunity;

    //     } catch (Exception $e) {
    //         Log::error('Error caught: "discover-getDiscoverCommunity"' . $e->getMessage());
            
    //         return $e->getMessage();
    //     }
    // }


    public function searchCommunity($request, $authId, $interest,$limit)
    {
        try {

            $discoverCommunity = Group::whereHas('groupMember', function ($query) use ($authId) {

                    $query->where('user_id', '<>', $authId);
                })

                ->where('created_by', '<>', $authId)
                ->where('is_active', 1);
            if (!empty($interest)) {
                $discoverCommunity->where(function ($query) use ($interest) {
                    foreach ($interest as $term) {

                        $query->orWhere('name', 'like', '%' . $term . '%');
                    }
                });
            }
            // Exclude groups where the user is already a member
            $discoverCommunity->whereNotExists(function ($subquery) use ($authId) {
                $subquery->select(DB::raw(1))
                    ->from('group_members')
                    ->whereRaw("group_id = groups.id AND user_id = ?", [$authId]);
            });

            // Retrieve the limited number of discovered communities
            $discoveredCommunity = $discoverCommunity->limit($limit)->get();
    
            // If no communities found, attempt a fallback (optional, based on business logic)
            if ($discoveredCommunity->isEmpty()) {

                $discoverCommunity = Group::whereHas('groupMember', function ($query) use ($authId) {
                        $query->where('user_id', '<>', $authId);
                    })
                    ->where('created_by', '<>', $authId)
                    ->where('is_active', 1);
    
                if (!empty($interest)) {
                    $discoverCommunity->where(function ($query) use ($interest) {
                        foreach ($interest as $term) {
                            $query->orWhere('name', 'like', '%' . $term . '%');
                        }
                    });
                }
    
                $discoverCommunity->whereNotExists(function ($subquery) use ($authId) {
                    $subquery->select(DB::raw(1))
                        ->from('group_members')
                        ->whereRaw("group_id = groups.id AND user_id = ?", [$authId]);
                });
    
                $discoveredCommunity = $discoverCommunity->limit($limit)->get();
            }
            // Process the discovered communities for additional data
            $discoveredCommunity->each(function ($community) use ($authId) {

                $community->member_count = isset($community->member_count) ? $community->member_count : 0;
                $community->cover_photo  = isset($community->cover_photo) ? $this->addBaseInImage($community->cover_photo) : null;
                $community->isJoined     = GroupMember::where(['group_id' => $community->id, 'user_id' => $authId])->exists() ? 1 : 0;
            });
    
            return $discoveredCommunity;
    
        } catch (Exception $e) {
            Log::error('Error caught: "discover-getDiscoverCommunity"' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    



}
