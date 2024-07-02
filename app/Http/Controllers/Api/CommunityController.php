<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\ActivityLog;
use App\Models\GroupMember;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\GroupMemberRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AddCommunity;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\EditCommunity;
use Illuminate\Support\Facades\Auth;
use App\Services\GetCommunityService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;

class CommunityController extends BaseController
{

    protected $notification, $get_community_service;
    public function __construct(NotificationService $notification, GetCommunityService $get_community_service)
    {
        $this->notification = $notification;
        $this->get_community_service = $get_community_service;
    }

    #------------  G E T    A L L   C O M M U N I T I E S ---------------#

    public function index(Request $request)
    {
        try {
            // Get the authenticated user's ID
            $authId             = Auth::id();

            return  $this->get_community_service->getAllCommunity($request, $authId);
        } catch (Exception $e) {
            // Handle exceptions
            Log::error('Error caught: "get community" ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred.'], 400);
        }
    }

    #------------  G E T    A L L   C O M M U N I T I E S ---------------#

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

            $authId             =       Auth::id();
            $addCommunity       =       new Group();
            //check name is already exist or not 
            $communityName      =       $request->name;
            $isExist            =       Group::where(['name' => $communityName, 'is_active' => 1])->exists();

            if ($isExist) {

                return $this->sendResponsewithoutData(trans('message.community_name_exist'), 422);
            }

            $addCommunity->name =       $communityName;

            if (isset($request->description) && !empty($request->description)) {

                $addCommunity->description = $request->description;
            }

            $addCommunity->created_by       = $authId;

            if ($request->file('cover_photo')) {

                if ($request->cover_photo) {

                    $cover_photo    = $request->file('cover_photo');

                    $Uploaded       = upload_file($cover_photo, 'cover_photo');

                    $addCommunity->cover_photo = $Uploaded;
                }
            }

            if ($addCommunity->save()) {                //** ADD IN MEMBER TABLE */

                $groupMember                = new GroupMember();

                $groupMember->group_id      = $addCommunity->id;

                $groupMember->user_id       = $authId;

                $groupMember->role          = 'owner';

                if ($groupMember->save()) {

                    #-------  A C T I V I T Y -----------#
                    $activity                       =    new ActivityLog();
                    $activity->user_id              =    $authId;
                    $activity->community_id         =    $addCommunity->id;
                    $activity->community_member_id  =    $groupMember->id;
                    $activity->action_details       =    "Created the community: " . $addCommunity->name;
                    $activity->action               =    trans('notification_message.joined_community_type');  //Joined the community as admin
                    $activity->save();
                    #-------  A C T I V I T Y -----------#
                    incrementMember($authId, $addCommunity->id, 1);
                }
            }
            DB::commit();

            $updatedCommunity = $this->get_community_service->getCommunityById($addCommunity->id, $authId, trans('message.community_added'));

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

            $authId     =   Auth::id();
            $isExist    =   Group::where(['id' => $id, 'created_by' => $authId, 'is_active' => 1])->exists();
            if ($isExist) {
                //updated
                $addCommunity = [];
                // filter_text($request->name);

                $isExist = Group::where(['name' => $request->name, 'is_active' => 1])->where('id', '<>', '')->exists();

                if (isset($request->name) && !empty($request->name)) {

                    $addCommunity['name'] = $request->name;
                }

                if (isset($request->description) && !empty($request->description)) {

                    $addCommunity['description'] = $request->description;
                }


                if ($request->hasFile('cover_photo')) {

                    $cover_photo = $request->file('cover_photo');
                    $Uploaded    = upload_file($cover_photo, 'cover_photo');
                    $addCommunity['cover_photo'] = $Uploaded;
                }

                if (isset($request) && !empty($request)) {

                    Group::updateOrCreate(['created_by' => $authId, 'id' => $id], $addCommunity);

                    DB::commit();
                }

                // $community                              =       Group::find($id);
                // $community->cover_photo                 =   asset('storage/'.$community->cover_photo);

                $updatedCommunity = $this->get_community_service->getCommunityById($id, $authId, trans('message.edit_community_successfully'));

                return $updatedCommunity;
            } else {      //invalid

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

            $authId             =       Auth::id();

            $isExist            =       Group::where(['id' => $request->id, 'created_by' => $authId, 'is_active' => 1])->exists();
            if ($isExist) {
                //updated
                $addCommunity   = [];

                if (isset($request->name) && !empty($request->name)) {

                    $communityName  = $request->name;

                    $isExist = Group::where(['name' => $communityName, 'is_active' => 1])->where('id', '<>', $request->id)->exists();

                    if ($isExist) {

                        return $this->sendResponsewithoutData("The community name is already in use", 422);
                    } else {

                        $addCommunity['name']   =  $communityName;
                    }
                }


                if (isset($request->description) && !empty($request->description)) {

                    $addCommunity['description']   =  $request->description;
                }

                if ($request->file('cover_photo')) {

                    $cover_photo                 = $request->file('cover_photo');

                    $Uploaded                    = upload_file($cover_photo, 'cover_photo');

                    $coverImage                  = Group::select('cover_photo')->where(['id' => $request->id])->first();

                    if (isset($coverImage->cover_photo) && !empty($coverImage->cover_photo)) {

                        if (Storage::disk('public')->exists($coverImage->cover_photo)) {

                            Storage::disk('public')->delete($coverImage->cover_photo); // delete file from specific disk e.g; s3, local etc

                        }
                    }

                    $addCommunity['cover_photo']  = $Uploaded;
                }

                if (isset($addCommunity) && !empty($addCommunity)) {

                    Group::updateOrCreate(['created_by' => $authId, 'id' => $request->id], $addCommunity);

                    DB::commit();
                }

                $updatedCommunity = $this->get_community_service->getCommunityById($request->id, $authId, trans('message.edit_community_successfully'));

                return $updatedCommunity;
            } else {      //invalid community

                return $this->sendError(trans('message.something_went_wrong'), [], 403);
            }
        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "update community" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------------ U P D A T E         P O S T  ------------------------#







    #------------***************** D E L E T E      C O M M U N I T Y  ***************----------------#
    public function destroy(string $id)
    {
        //
        DB::beginTransaction();

        try {

            $authId             =       Auth::id();

            $validator          =       Validator::make(['id' => $id], [

                'id' => 'required|integer|exists:groups,id'
            ]);

            if ($validator->fails()) {
                // Handle validation failure
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);

            } else {

                $isExist                        =               Group::where(['id' => $id])->first();

                if (isset($isExist) && !empty($isExist)) {  //deleted action

                    if ($isExist->is_active == 0) {

                        return $this->sendResponsewithoutData(trans('message.community_already_deleted'), 422);
                    }

                    $groupOwner                 =           GroupMember::where(['group_id'=>$id,'role'=>'owner','user_id'=>$authId])->first();

                    if(isset($groupOwner) && !empty($groupOwner)){

                        //check in community have other user post or not

                        $isPostExist            =           Post::where(['group_id'=>$id])->where('user_id','<>',$authId)->first();
                        
                        if(isset($isPostExist) && !empty($isPostExist)){

                            return $this->sendError(trans('message.cannot_delete_community'), [], 423);

                        }

                        $isExist->is_active     =   0;

                        $isExist->save();

                        Post::where(['group_id' => $id])->update(['is_active' => 0]);

                        #delete notification & activity
                        Notification::where('community_id', $id)->delete();

                        ActivityLog::where('community_id', $id)->delete();

                        DB::commit();

                        return $this->sendResponsewithoutData(trans('message.community_deleted'), 200);

                    }else{

                        return $this->sendError(trans('message.cannot_delete_community'), [], 423);

                    }
                } else {      //invalid

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
    public function joinCoummnity(Request $request)
    {
        try {

            $authId                 =       Auth::id();

            $validator              =       Validator::make($request->all(), ['community_id' => 'required|integer|exists:groups,id', 'type' => 'required|integer|between:0,1']);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            } else {

                $group              =   Group::find($request->community_id);

                if (isset($group) && !empty($group)) {

                    if ($group->is_active == 0) {

                        return $this->sendResponsewithoutData(trans('message.invalid_community'), 422);
                    }
                }

                return $this->get_community_service->joinUnjoin($request, $authId, $group);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "joinCoummnity" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------************** J O I N         C O M M U N I T Y ************----------------#


    #---------------------- C O M M U N I T Y        R E Q U E S T  ---------------------------#
    public function communityRequest(Request $request)
    {
        try {

            $limit                  =           10;

            if (isset($request->limit) && !empty($request->limit)) {

                $limit              =           $request->limit;
            }

            $authId                 =           Auth::id();

            $adminGroups            =           GroupMember::where('user_id', $authId)

                ->whereIn('role', ['moderator', 'admin'])

                ->pluck('group_id');

            $requests               =           GroupMemberRequest::select('id', 'user_id', 'group_id', 'status')

                ->with([

                    'group' => function ($selected) {

                        $selected->select('id', 'name', 'description', 'cover_photo', 'visibility', 'member_count');
                    },
                    'requested_user' => function ($query) {

                        $query->select('id', 'name', 'profile');
                    }

                ])->whereIn('group_id', $adminGroups)

                ->simplePaginate($limit);

            $requests->each(function ($groupRequest) {

                if (isset($groupRequest->group) && !empty($groupRequest->group)) {

                    $groupRequest->group->cover_photo               =   addBaseUrl($groupRequest->group->cover_photo);
                }

                if (isset($groupRequest->requested_user) && !empty($groupRequest->requested_user)) {

                    if (isset($groupRequest->requested_user->profile) && !empty($groupRequest->requested_user->profile)) {

                        $groupRequest->requested_user->profile     =    addBaseUrl($groupRequest->myGroup->profile);
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

    public function acceptRejectCommunityRequest(Request $request)
    {

        DB::beginTransaction();
        try {

            $authId         =           Auth::id();
            $validator      =           Validator::make($request->all(), ['action' => 'required|integer|between:0,1', 'request_id' => 'required|integer|exists:group_member_requests,id']);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            } else {

                $requestId  =           GroupMemberRequest::where('id', $request->request_id)->first();

                // $hasOwner               =   Group::where(['id'=>$request->community_id,'created_by'=>$authId])->exists();
                $hasOwner   =           GroupMember::where(['group_id' => $requestId->group_id, 'user_id' => $authId])->where(function ($query) {

                    $query->where('role', 'admin')->orWhere('role', 'moderator');
                })->exists();

                if ($hasOwner) {

                    if ($request->action == 0) {

                        $requestId->delete();

                        DB::commit();
                    } elseif ($request->action == 1) {

                        if (!GroupMember::where(['group_id' => $requestId->group_id, 'user_id' => $requestId->user_id, 'is_active=1'])->exists()) {

                            incrementMemberWithAuth($requestId->group_id, 1);
                        }

                        GroupMember::updateOrCreate(['group_id' => $requestId->group_id, 'user_id' => $requestId->user_id], ['role' => "member"]);

                        $requestId->delete();
                        DB::commit();
                    }

                    return $this->sendResponsewithoutData(trans('message.request_accepted'), 200);
                } else {  // permission denied becaue your not owner

                    return $this->sendError(trans('message.access_denied'), [], 403);
                }
            }
        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "acceptRejectCommunityRequest" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-----------------****** A C C E P T         C O M M U N I T Y     R E Q E U E S T ******* ----------------#


    #-------------- A S S I G N        R O L E         T O         C O M M U N I T Y      M E M B E R -------------#
    public function AssignRole(Request $request)
    {
        DB::beginTransaction();

        try {

            $authId         =       Auth::id();

            $validator      =       Validator::make($request->all(), [

                'community_id' => 'required|integer|exists:groups,id',

                'member_id' => 'required|exists:users,id',

                'role' => [

                    'required',

                    Rule::in(['owner', 'admin', 'moderator', 'member']),

                ]
            ]);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            } else {

                //check group member is exist or not
                $group      =       Group::where('id', $request->community_id)->first();
                // cannot change admin role
                if ($group->created_by == $request->community_id) {

                    return $this->sendResponsewithoutData(trans('message.Permission_denied'), 403);
                }

                $checkMember                =      GroupMember::where(['group_id' => $request->community_id, 'user_id' => $request->member_id, 'is_active' => 1])->first();

                if (!$checkMember) {

                    return $this->sendResponsewithoutData(trans('message.no_member_found_community'), 409);
                } else {

                    if ($authId == $request->member_id) {

                        return $this->sendResponsewithoutData(trans('message.not_change_role_yourself'), 403);
                    }

                    $checkAuthority         =   GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId, 'is_active' => 1])->first();

                    if (isset($checkAuthority) && !empty($checkAuthority)) {

                        if ($checkAuthority->role == "admin" || $checkAuthority->role == "owner") {

                            if ($request->role == "owner" && $checkAuthority->role == "admin") {

                                return $this->sendResponsewithoutData(trans('message.not_permission'), 409);
                            }
                            $checkMember->role = $request->role;

                            $checkMember->save();

                            if ($request->role == "owner" && $checkAuthority->role == "owner") {

                                $checkAuthority->role       =   "member";

                                $checkAuthority->save();
                            }

                            DB::commit();
                            $updatedAuthority               =   GroupMember::where(['group_id' => $request->community_id, 'user_id' => $request->member_id, 'is_active' => 1])->first();

                            return $this->sendResponse($updatedAuthority, trans("message.community_role_updated"), 200);
                        } else {

                            return $this->sendResponsewithoutData(trans('message.not_permission'), 409);
                        }
                    } else {

                        return $this->sendResponsewithoutData(trans('messagge.access_denied'), 403);
                    }
                }
            }
        } catch (Exception $e) {

            DB::rollback();

            Log::error('Error caught: "assign-role-in-community" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #-------------- A S S I G N        R O L E         T O         C O M M U N I T Y      M E M B E R -------------#
    public function removeMember(Request $request)
    {
        DB::beginTransaction();
        try {

            $authId             =           Auth::id();

            $validator          =           Validator::make($request->all(), ['community_id' => 'required|integer|exists:groups,id', 'member_id' => 'required|exists:users,id']);


            if ($validator->fails()) {
                // Handle validation failure
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);

            } else {

                //check group member is exist or not
                $checkMember    =           GroupMember::where(['group_id' => $request->community_id, 'user_id' => $request->member_id, 'is_active' => 1])->first();

                if (!$checkMember) {

                    return $this->sendResponsewithoutData(trans('message.no_member_found_community'), 409);

                } else {

                    if ($authId == $request->member_id) {

                        return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 403);

                    }
                    $checkAuthority         =       GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId, 'is_active' => 1])->first();

                    if (isset($checkAuthority) && !empty($checkAuthority)) {

                        if ($checkAuthority->role == "admin" || $checkAuthority->role == "owner") {

                            $checkMember->delete();
                            // increment by member 1
                            //updated on April 22
                            decrement('groups', ['id' => $request->community_id], 'member_count', 1);

                            DB::commit();

                            $updatedAuthority = GroupMember::where(['group_id' => $request->community_id, 'user_id' => $request->member_id, 'is_active' => 1])->first();

                            return $this->sendResponse($updatedAuthority, trans("message.remove_member_from_community"), 200);
                        }
                    } else {

                        return $this->sendResponsewithoutData(trans('messagge.access_denied'), 403);
                    }
                }
            }
        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "assign-role-in-community" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);

        }
    }

    #-------------- A S S I G N        R O L E         T O         C O M M U N I T Y      M E M B E R -------------#

    #-----------------  C O M M U N I T Y       U S E R     -------------------------------------#
    public function communityUsers(Request $request)
    {
        try {

            $authId             = Auth::id();

            $validator          = Validator::make($request->all(), [

                'community_id' => 'required|integer|exists:groups,id',
                'role' => 'nullable|integer|between:1,3'
            ], [
                'community_id.integer' => "Invalid community id",
                'role.integer' => "Invalid type"
            ]);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
                
            } else {
                // Check group member is exist or not
                $limit                  = 10;

                if ($request->has('limit') && !empty($request->limit)) {

                    $limit              =   $request->limit;
                }

                $groupMember            =   GroupMember::with(['groupUser' => function ($query) use ($authId) {

                    $query->select('id', 'name', 'user_name', 'profile', 'is_active')

                        ->with([
                            'user_medical_certificate' => function ($q) {

                                $q->select('id', 'medicial_degree_type', 'user_id');
                            },
                            'user_medical_certificate.medical_certificate' => function ($q) {

                                $q->select('id', 'name');
                            }
                        ])

                        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                            $query->where('user_id', $authId);
                        })
                        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                            $query->where('blocked_user_id', $authId);
                        });
                }]);

                $role                   =   "id";

                if ($request->has('role') && !empty($request->role)) {

                    $role               =   $request->role == 1 ? "admin" : "moderator";
                }

                $groupMember            =   $groupMember->where(['group_id' => $request->community_id, 'is_active' => 1])->whereNotNull('user_id')

                    //->orderByRaw(($request->role) ? 'FIELD(role,"' . $role . '") DESC' : $role . " desc")
                    ->orderByRaw('
                                                CASE 
                                                WHEN role = "owner" THEN 0
                                                WHEN role = "admin" THEN 1
                                                WHEN role = "moderator" THEN 2
                                                ELSE 3
                                            END
                                        ')
                    ->simplePaginate($limit);

                if ($groupMember) {

                    $groupMember->each(function ($query) {

                        if (isset($query->groupUser->profile) && !empty($query->groupUser->profile)) {

                            $query->groupUser->profile = addBaseUrl($query->groupUser->profile);
                        }
                    });
                }
                return $this->sendResponse($groupMember, trans("message.community_users"), 200);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "communityUsers" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #-----------------  C O M M U N I T Y       U S E R     -------------------------------------#





}
