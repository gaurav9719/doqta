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
use App\Models\GroupMemberRequest;
use App\Models\Post;
use Exception;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\GetCommunityService;


class CommunityController extends BaseController
{
   
    protected $notification,$get_community_service;
    public function __construct(NotificationService $notification,GetCommunityService $get_community_service)
    {
        $this->notification                  = $notification;
        $this->get_community_service         = $get_community_service;
    }

    public function index(Request $request)
    {
        try {
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
            $communities->each(function ($community) use($authId) {

                if(isset($community->communities) && !empty($community->communities) ){

                    if(isset($community->communities->cover_photo) && !empty($community->communities->cover_photo)){

                        $community->communities->cover_photo     =   asset('storage/'.$community->communities->cover_photo);
                    }
                    
                }

                //check i am the member of the community or not

                $isExist                        =   GroupMember::where(['group_id'=>$community->id,'is_active'=>1,'user_id'=>$authId])->exists();
                if($isExist){

                    $community->is_joined       =   1;
                }else{

                    $request                    =   GroupMemberRequest::where(['group_id'=>$community->id,'is_active'=>1,'user_id'=>$authId])->first();
                    if(isset($request) && !empty($request)){
                        if($request->status="pending"){
                            $community->is_joined       =   2; // pending request
                        }elseif ($request->status="rejected") {
                            $community->is_joined       =   3; // rejected
                        }
                    }else{

                        $community->is_joined       =   0; // not join the group
                    }
                }
            });
            
            return $this->sendResponse($communities, trans("message.communities"), 200);
    
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
            //check name is already exist or not 
            $communityName              =       filter_text($request->name);
            
            $isExist                    =       Group::where(['name'=>$communityName,'is_active'=>1])->exists();
            if($isExist){

                return $this->sendResponsewithoutData("The community name is already in use", 422);

            }

            $addCommunity->name                =       $communityName;
            if(isset($request->description) && !empty($request->description)){

                $addCommunity->description     =       filter_text($request->description);

            }
            $addCommunity->created_by           =       $authId;

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
            $updatedCommunity         =       $this->get_community_service->getCommunityById($addCommunity->id,$authId,trans('message.community_added'));
            return $updatedCommunity;

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

            $authId                                     =   Auth::id();
            $isExist                                    =   Group::where(['id'=>$id,'created_by'=>$authId,'is_active'=>1])->exists();
            if($isExist){ 
                                          //updated
                $addCommunity                           =   [];
                                                            filter_text($request->name);

                $isExist                                =   Group::where(['name'=>filter_text($request->name),'is_active'=>1])->where('id','<>','')->exists();

                if(isset($request->name) && !empty($request->name)){

                    $addCommunity['name']               =       filter_text($request->name);
                }

                if(isset($request->description) && !empty($request->description)){

                    $addCommunity['description']         =       filter_text($request->description);
                }

             
                if ($request->hasFile('cover_photo')) {

                    $cover_photo                        =       $request->file('cover_photo');
                    $Uploaded                           =       upload_file($cover_photo, 'cover_photo');
                    $addCommunity['cover_photo']        =       $Uploaded;
                }

                if(isset($request)  && !empty($request)){

                    Group::updateOrCreate(['created_by' => $authId,'id'=>$id],$addCommunity);

                    DB::commit();
                }

                // $community                              =       Group::find($id);
                // $community->cover_photo                 =   asset('storage/'.$community->cover_photo);

                $updatedCommunity                       =       $this->get_community_service->getCommunityById($id,$authId,trans('message.edit_community_successfully'));

                return $updatedCommunity;

            }else{      //invalid

                return $this->sendError(trans('message.something_went_wrong'), [], 403);
            }

        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "delete community" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }


    #------------------ U P D A T E         P O S T  ------------------------_#
    public function updateCommunity(EditCommunity $request)
    {
        //
        DB::beginTransaction();
        try {

            $authId                                     =   Auth::id();
            $isExist                                    =   Group::where(['id'=>$request->id,'created_by'=>$authId,'is_active'=>1])->exists();
            if($isExist){ 
                                          //updated
                $addCommunity                           =   [];
                $communityName                          =   filter_text($request->name);
                $isExist                                =   Group::where(['name'=>$communityName,'is_active'=>1])->where('id','<>',$request->id)->exists();

                if($isExist){

                    return $this->sendResponsewithoutData("The community name is already in use", 422);

                }
                if(isset($request->name) && !empty($request->name)){

                    $addCommunity['name']               =       $communityName;

                }
                if(isset($request->description) && !empty($request->description)){

                    $addCommunity['description']         =       filter_text($request->description);
                }

                if ($request->hasFile('cover_photo')) {

                    $cover_photo                        =       $request->file('cover_photo');
                    $Uploaded                           =       upload_file($cover_photo, 'cover_photo');
                    $addCommunity['cover_photo']        =       $Uploaded;
                }

                if(isset($request)  && !empty($request)){

                    Group::updateOrCreate(['created_by' => $authId,'id'=>$request->id],$addCommunity);

                    DB::commit();
                }

                // $community                              =       Group::find($id);
                // $community->cover_photo                 =   asset('storage/'.$community->cover_photo);

                $updatedCommunity                       =       $this->get_community_service->getCommunityById($request->id,$authId,trans('message.edit_community_successfully'));

                return $updatedCommunity;

            }else{      //invalid

                return $this->sendError(trans('message.something_went_wrong'), [], 403);
            }

        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "delete community" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------------ U P D A T E         P O S T  ------------------------#






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

                    $isExist    =   Group::where(['id'=>$id,'created_by'=>$authId])->update(['is_active'=>0]);

                    Post::where(['group_id'=>$id])->update(['is_active'=>0]);

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



    #------------************** J O I N         C O M M U N I T Y ************----------------#
    public function joinCoummnity(Request $request){

        DB::beginTransaction();

        try {

            $authId                     =   Auth::id();
            $validator                  =   Validator::make( $request->all(),['community_id' => 'required|integer|exists:groups,id']);
    
            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            }else{

                $alreadyMember          =   GroupMember ::where(['group_id'=>$request->community_id,'user_id'=>$authId])->exists();

                if($alreadyMember){

                    return $this->sendResponsewithoutData(trans('message.already_group_member'), 409);

                }else{
                                 //check group type is public or private

                    $group                          =   Group::find($request->community_id);

                    if($group->visibility==1){          ##--------- PUBLIC COMMUNITIES ------------#

                        $addGroupMember             =   new GroupMember();
                        $addGroupMember->group_id   =   $request->community_id;
                        $addGroupMember->user_id    =   $authId;
                        $addGroupMember->role       =   "member";

                        if($addGroupMember->save()){
                            // increment in group member
                            incrementMember($authId,$request->community_id,1);
                            $reciever                           =       User::select('id', 'device_token', 'device_type')->where("id", $group->user_id)->first();
                            $sender                             =       User::select('id', 'device_token', 'device_type')->where("id", $authId)->first();
                            $notification_type                  =       trans('notification_message.new_memeber_join_group_type');
                            $notification_message               =       trans('notification_message.new_memeber_join_group_message');
                            $this->notification->sendNotification($reciever,$sender,$notification_message,$notification_type);
                            DB::commit();
                            return $this->sendResponsewithoutData(trans('message.community_joined_successfully'), 200);
                        }

                    }else{                              ##--------- PRVATE COMMUNITIES ------------#


                        $checkRequest                           =   GroupMemberRequest::where(['user_id'=>$authId,'group_id'=>$request->community_id]);

                        if($checkRequest){

                            return $this->sendError(trans('message.something_went_wrong'), [], 403);

                        }else{

                            $groupRequest                       =       new GroupMemberRequest();
                            $groupRequest->user_id              =       $authId;
                            $groupRequest->group_id             =       $request->community_id;
                            $groupRequest->save();
                            $group                              =       Group::find($request->community_id);
                            $reciever                           =       User::select('id', 'device_token', 'device_type')->where("id", $group->user_id)->first();
                            $sender                             =       User::select('id', 'device_token', 'device_type')->where("id", $authId)->first();
                            $notification_type                  =       trans('notification_message.new_memeber_group_request_type');
                            $notification_message               =       trans('notification_message.new_memeber_group_request_type_message');
                            $this->notification->sendNotification($reciever,$sender,$notification_message,$notification_type);

                            DB::commit();

                            return $this->sendResponsewithoutData(trans('message.request_send_successfuly'), 200);

                        }
                    }
                }
            }

        } catch (Exception $e) {
            
            DB::rollback();
            Log::error('Error caught: "joinCoummnity" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------************** J O I N         C O M M U N I T Y ************----------------#


    #---------------------- C O M M U N I T Y        R E Q U E S T  ---------------------------#
    public function communityRequest(Request $request){

        try {

            $limit          =   10;
            if(isset($request->limit) && !empty($request->limit)){

                $limit      =   $request->limit;
            }
            $authId         =   Auth::id();
            $requests       = GroupMemberRequest::select('id', 'user_id', 'group_id', 'status')
            
            ->whereHas('myGroup', function ($query) use ($authId) {
            
                $query->where('created_by', $authId);
            
            })

            ->with(['myGroup' => function ($selected) {
    
                $selected->select('id', 'name', 'description', 'cover_photo', 'visibility', 'member_count');
            }, 'requested_user' => function ($query) {
                $query->select('id', 'name', 'profile');
            }])
            ->simplePaginate($limit);
    
            $requests->each(function ($groupRequest) {
    
                if(isset($groupRequest->myGroup) && !empty($groupRequest->myGroup)){
    
                    $groupRequest->myGroup->cover_photo    =   asset('storage/'.$groupRequest->myGroup->cover_photo);
                }
    
                if(isset($groupRequest->requested_user) && !empty($groupRequest->requested_user)){
    
                    if(isset($groupRequest->requested_user->profile) && !empty($groupRequest->requested_user->profile)){
    
                        $groupRequest->requested_user->profile    =   asset('storage/'.$groupRequest->myGroup->profile);
                    }
                }
            });
            return $this->sendResponse($requests, trans("message.community_request"), 200);

        } catch (Exception $e) {
    
            DB::rollback();
            Log::error('Error caught: "communityRequest" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------------- C O M M U N I T Y        R E Q U E S T  ---------------------------#


    #-----------------****** A C C E P T         C O M M U N I T Y     R E Q E U E S T ******* ----------------#

    public function acceptRejectCommunityRequest(Request $request){

        DB::beginTransaction();
        try {
            
            $authId                     =   Auth::id();
            $validator                  =   Validator::make( $request->all(),['action' => 'required|integer|between:0,1','request_id' => 'required|integer|exists:group_member_requests,id']);
    
            if ($validator->fails()) {
    
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            }else{
    
                $requestId              =   GroupMemberRequest::select('group_id')->where('id',$request->request_id)->first();
            
                // $hasOwner               =   Group::where(['id'=>$request->community_id,'created_by'=>$authId])->exists();
                $hasOwner               =   GroupMember::where(['group_id'=>$requestId->group_id,'user_id'=>$authId,'role'=>'admin'])->exists();
                if($hasOwner){
    
                    if($request->action==0){
    
                        $requestId->status      =   "rejected";
                        $requestId->save();
                        DB::commit();
    
                    }elseif ($request->action==1) {
                       
                        $addMember              =   new  GroupMember();
                        $addMember->group_id    =   $requestId->group_id;
                        $addMember->user_id     =   $authId;
                        $addMember->role        =   "member";   
                        $addMember->save();
                        $requestId->delete();
                        DB::commit();
                    }
                    
                    return $this->sendResponsewithoutData(trans('message.request_accepted'), 200);

                }else{  // permission denied becaue your not owner
    
                    return $this->sendError(trans('message.something_went_wrong'), [], 403);
                }
            }
        } catch (Exception $e) {
            
            DB::rollback();
            Log::error('Error caught: "acceptRejectCommunityRequest" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
       


    }
    #-----------------****** A C C E P T         C O M M U N I T Y     R E Q E U E S T ******* ----------------#






}