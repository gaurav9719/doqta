<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AddCommunity;
use App\Http\Requests\EditCommunity;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Exception;

class CommunityController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            DB::enableQueryLog();
            // Get the authenticated user's ID
            $authId = Auth::id();
            // Query to fetch communities where the user is a member and communities are active
            $communitiesQuery = GroupMember::where('user_id', $authId)
                ->whereHas('communities', function($query) {
                    $query->where('is_active', 1);
                });
            // Check if search term is provided and apply search filter
            if ($request->filled('search')) {

                $searchTerm = $request->input('search');
                $communitiesQuery->whereHas('communities', function($query) use ($searchTerm) {
                    $query->where('name', 'LIKE', "%$searchTerm%");
                });
            }
            // Get the communities
            $communities = $communitiesQuery->with('communities')->simplePaginate(10);
            // Return the communities
            $communities->each(function ($community) {

                if(isset($community->communities) && !empty($community->communities) ){

                    $community->communities->cover_photo     =   asset('storage/'.$community->communities->cover_photo);
                }
            });
            
            return $this->sendResponse($communities, trans("message.community_added"), 200);
    
        } catch (Exception $e) {
            // Handle exceptions
            Log::error('Error caught: "get community" ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred.'], 400);
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
    public function store(AddCommunity $request)
    {
        DB::beginTransaction();

        try {

            $authId                     =       Auth::id();
            $addCommunity               =       new Group();
            $addCommunity->name         =       filter_text($request->name);
            $addCommunity->description  =       filter_text($request->description);
            $addCommunity->created_by   =       $authId;
            if ($request->hasFile('cover_photo')) {
                $cover_photo                    =       $request->file('cover_photo');
                $Uploaded                       =       upload_file($cover_photo, 'cover_photo');
                $addCommunity->cover_photo      =       $Uploaded;
            }
    
            if($addCommunity->save()){ //** ADD IN MEMBER TABLE */
    
                $groupMember            =       new GroupMember();
                $groupMember->group_id  =       $addCommunity->id;
                $groupMember->user_id   =       $authId;
                $groupMember->role      =       'admin';
                if($groupMember->save()){
                    incrementMember($authId,$addCommunity->id,1);
                }
            }
            DB::commit();
            $community                  =       Group::find($addCommunity->id);
            $community->cover_photo     =   asset('storage/'.$community->cover_photo);

            
            return $this->sendResponse($community, trans("message.community_added"), 200);


        } catch (Exception $e) {
           
            DB::rollback();
            Log::error('Error caught: "community_added" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);

        }
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
    public function update(EditCommunity $request, string $id)
    {
        //
        DB::beginTransaction();
        try {

            $authId         =   Auth::id();
            $isExist        =   Group::where(['id'=>$id,'created_by'=>$authId,'is_active'=>1])->exists();
            if($isExist){ 
                                          //updated
                $addCommunity =   [];

                if(isset($request->name) && !empty($request->name)){

                    $addCommunity['name']         =       filter_text($request->name);
                }

                if(isset($request->description) && !empty($request->description)){

                    $addCommunity['description']         =       filter_text($request->description);
                }

             
                if ($request->hasFile('cover_photo')) {

                    $cover_photo                    =       $request->file('cover_photo');
                    $Uploaded                       =       upload_file($cover_photo, 'cover_photo');
                    $addCommunity['cover_photo']      =       $Uploaded;
                }

                if(isset($request)  && !empty($request)){

                    Group::updateOrCreate(['created_by' => $authId,'id'=>$id],$addCommunity);

                    DB::commit();
                }

                $community                  =       Group::find($id);
                $community->cover_photo     =   asset('storage/'.$community->cover_photo);
                return $this->sendResponsewithoutData(trans('message.community_updated'), 200);

            }else{      //invalid

                return $this->sendError(trans('message.something_went_wrong'), [], 403);
            }

        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "delete community" ' . $e->getMessage());
            return $this->sendError($e, [], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

     #------------***************** D E L E T E      C O M M U N I T Y  ***************----------------#
    public function destroy(string $id)
    {
        //
        DB::beginTransaction();
        try {
            $authId     =   Auth::id();
            $validator  =   Validator::make(['id' => $id], [
                'id' => 'required|integer|exists:groups,id', // Adjust 'your_table_name' with your actual table name
            ]);
        
            if ($validator->fails()) {
                // Handle validation failure
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            }else{
    
                $isExist        =   Group::where(['id'=>$id,'created_by'=>$authId])->exists();
    
                if($isExist){ //deleted
    
                    $isExist        =   Group::where(['id'=>$id,'created_by'=>$authId])->update(['is_active'=>0]);
                    DB::commit();
                    return $this->sendResponsewithoutData(trans('message.community_deleted'), 200);
                }else{      //invalid
                    return $this->sendError(trans('message.something_went_wrong'), [], 403);
                }
            }

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "delete community" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------***************** D E L E T E      C O M M U N I T Y  ***************----------------#

}
