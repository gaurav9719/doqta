<?php

namespace App\Http\Controllers\Api\Search;

use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Group;
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

class SearchController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $authId         =   Auth::id();
        $userInterest   =   UsersInterest::with('interest',function($query){

            $query->select('id','name');

        })->where('user_id',$authId)->get();
        if(isset($userInterest[0]) && !empty($userInterest[0])){
            
            $interest       =   [];

            foreach($userInterest as $interest){

                if(isset($interest['interest']) && !empty($interest['interest']['name']))
                {

                    $interest[]  = $interest['interest']['name'];

                }
            }

            if(isset($interest) && !empty($interest)){




            }



        }else{





        }





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



    public function searchCommunity($request,$authId,$interest){

        try {

            $limit              =   10;
            
            if (($request->limit) && !empty($request->limit)) {

                $limit          =   $request->limit;
            }
            $data               =    [];

            $discoverCommunity  =           Group::whereHas('groupMember', function ($query) use ($authId) {

                $query->where('user_id', '<>', $authId);

            })->where('created_by', '<>', $authId)->where('is_active', 1);

            if (!empty($interest)) {


                ->where(function ($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query->orWhere('name', 'like', '%' . $term . '%');
                    }
                })






                $discoverCommunity =   $discoverCommunity->where('name', 'like', "%$request->search%");

            }
            $discoveredCommunity       =   $discoverCommunity->whereNotExists(function ($subquery) use ($authId) {    
                $subquery->select(DB::raw(1))
                    ->from('group_members')
                    ->whereRaw("group_id = groups.id AND user_id=".$authId);
            })->simplePaginate($limit);

            $discoveredCommunity->each(function ($query) use ($authId) {

                if (isset($query->member_count) && !empty($query->member_count)) {

                    $query->member_count    =   shortNumber($query->member_count);
                }

                if (isset($query->cover_photo) && !empty($query->cover_photo)) {

                    $query->cover_photo    =   $this->addBaseInImage($query->cover_photo);
                }
                $query->isJoined         =   (GroupMember::where(['group_id' => $query->id, 'user_id' => $authId])->exists()) ? 1 : 0;
            });
            // Get the member_ids where group_id is in $groupIds and is_active is truthy

            return $this->sendResponse($discoveredCommunity, trans('message.dicover_community'), 200);
        } catch (Exception $e) {

            Log::error('Error caught: "discover-getDiscoverCommunity"' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }






}
