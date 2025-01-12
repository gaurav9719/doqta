<?php

namespace App\Services\Recruiter;
use App\Models\Recruiter;
use Illuminate\Support\Facades\DB;
use Exception;  
use App\Http\Controllers\Api\BaseController;
use App\Models\MyTeam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\MyTeamMember;
use App\Models\MyRoster;
use carbon\Carbon;
use App\Models\User;
use App\Models\PartnerMatch;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Models\RecruiterBench;
use App\Models\Stat;
use App\Models\UserSwipe;
/**
 * Class AddToMemberBench.
 */
class AddToMemberBench extends BaseController
{
    protected $user, $authId,$notification;
    public function __construct(NotificationService $notification)
    {
        $this->notification         = $notification;
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });
    }
 #------------- ADD/REJECT     R E C R U I T ------------------------#
    public function addToTeamBench($request) {
        try {
            if ($request->isMethod('post')) {        #------------- U P D A T E       U S E R  ------------#
                $validator = Validator::make($request->all(), [
                    'type' => 'required|integer|between:0,1',
                    'user_id' => 'required|exists:users,id',
                    'team_id' => 'required_if:type,==,1|array',
                    'team_id.*' => 'required_if:type,==,1|integer|exists:my_teams,id',
                ],['team_id.required_if'=>"Team id required",'team_id.*.exists'=>"invalid team id",'team_id.*.integer'=>"invalid team id"]);

                if ($validator->fails()) {
                    // If validation fails, return error response
                    return $this->sendResponsewithoutData(validationErrorsToString($validator->errors()), 422);
    
                } else {
                    // If validation passes, proceed with the operation
                    $authUser   = Auth::user();
                    $type       = $request->type;
                    if ($type == 1) {       // Adding to team member
    
                        return $this->addToMember($request,$authUser);
    
                    } elseif ($type == 0) { // Adding to bench
                        
                        return $this->addToBench($request,$authUser);
                    }
                }
            }
            if ($request->isMethod('get')) {    #------------- G E T    R A N D O M     U S E R  ------------#
                return $this->getRecruiter($request);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "addToTeamBench" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400); // Return error response
        }
    }
    #------------------------------******* E N D ******------------------------------# 

    

    #------------------------ A D D        T O      M E M B E R  -------------------#
    public function addToMember($request,$authUser){
        DB::beginTransaction();
        try {
            $isExist      = MyTeam::where('recruiter_id', $authUser->id)
            ->whereIn('id', $request->team_id)
            ->count();
            if ($isExist != count($request->team_id)) {
                // If any team ID doesn't exist, handle the error here
                return $this->sendResponsewithoutData("One or more team IDs do not exist for the current user", 403);
                
            }else{
                $teamIds           =    $request->team_id;
                $UserId            =    $request->user_id;
                for ($i=0; $i < count($request->team_id); $i++) { 

                    $isExist      = MyTeam::where(['recruiter_id'=>$authUser->id,'id'=>$teamIds[$i]])->first();
                    if($isExist->member_id==$request->user_id){

                        DB::rollBack();
                        return $this->sendResponsewithoutData(trans('message.same_user'), 403);
                    }
                    $alreadyAdded = MyTeamMember::where(['team_id' => $teamIds[$i], 'dater_id' => $UserId])->count();

                    if ($alreadyAdded > 0) {
                        // User is already added to the team
                        return $this->sendResponsewithoutData(trans('message.already_exist_in_member_list'), 400);
    
                    } else {
                        // Add the user to the team's member list
                        $addToTeam                  = new MyTeamMember();
                        $addToTeam->team_id         = $teamIds[$i];
                        $addToTeam->member_id       = $isExist->member_id;
                        $addToTeam->dater_id        = $UserId;
                        $addToTeam->recruiter_type  = $isExist->team_type;
                        $addToTeam->is_active       = 1;
                        $addToTeam->save();
                        //----------------  A D D       D A T A     T O     S W I P E        T A B L E ------------ //
                        UserSwipe::updateOrCreate(['swiping_user_id'=>$authUser->id,'swiped_user_id'=>$UserId,'role_id'=>$authUser->current_role_id],['swipe_type'=>1,'is_active'=>1]);
                        //---------- SEND PUSH NOTIFICATION TO DATER THAT NEW MEMBER ADDED IN YOUR LIST---------------#
                        $reciever                           =       User::select('id','current_role_id', 'device_token', 'device_type')->where("id", $isExist->member_id)->first();
                        $notification_type                  =       trans('notification_message.received_new_dater_message_type');
                        $notification_message               =       trans('notification_message.received_new_dater_message');
                        $this->notification->pushNotificationOnly($reciever,$notification_message,$notification_type);
                        #-------------------- S E N D       P U S H     N O T I F I C A T I O N  -------------------#
                        DB::commit(); // Commit transaction
                        return $this->getRecruiter($request);
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "addToMember" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------------------------- E N D  -------------------------------------#


    #-------------------- A D D         T O      B E N C H -------------------------#
    public function addToBench($request,$authUser){
        DB::beginTransaction();
        try {
            RecruiterBench::updateOrCreate(
                ['user_id' => $authUser->id, 'rejectd_user_id' => $request['user_id']],
                ['is_active' => 1]
            );
            //----------------  A D D       D A T A     T O     S W I P E        T A B L E ------------ //
            UserSwipe::updateOrCreate(['swiping_user_id'=>$authUser->id,'swiped_user_id'=>$request['user_id'],'role_id'=>$authUser->current_role_id],['swipe_type'=>0,'is_active'=>1]);
            DB::commit(); // Commit transaction
            return $this->getRecruiter($request);
            // return $this->sendResponsewithoutData(trans('message.added_to_bench'), 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "addToMember" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);   
        }
    }
    #-------------------- ***** E N D  *******-------------------------#


    #-----------------   G E T        R E C R U I T E R  -----------------#

    public function getRecruiter($request ){
        $randomUser = $this->getRandomUser($request);
        if(!isset($randomUser->id) && empty($randomUser->id)){

            $randomUser = $this->getRandomUser($request , 1);
        }
        return $this->sendResponse($randomUser, trans("message.random_user"), 200);
    }
    public function getRandomUser($request,$type=''){

        $authUser           =      Auth::user();
        $userId             =      Auth::id();
        // $distance           =      10;
        $distance           =       $request->input('distance', 10);
        $limit              =       $request->input('limit', 10);
        $randomUser                 =       User::select('id','name','user_name','email','dob','reference_code','current_role_id','lat','long','gender','profile_pic', DB::raw("round(3959 * acos(cos(radians('" . $authUser->lat . "'))* cos(radians(`lat`))* cos(radians(`long`)- radians('" . $authUser->long . "'))+ sin(radians('" . $authUser->lat . "'))* sin(radians(`lat`))),2) AS distance"))
        ->whereHas('user_roles', function ($query) {
            $query->where('role_id', 2);
        })->where(['is_active' => 1])->where("id", "<>", $authUser->id);

        if(empty($type) || $type == "") {

            $randomUser= $randomUser->whereNotExists(function ($subquery) use ($userId,$authUser) {
                $subquery->select(DB::raw(1))
                    ->from('user_swipes')
                    ->whereRaw("swiping_user_id = '" . $userId . "' AND swiped_user_id = users.id AND role_id = '".$authUser->current_role_id."'");
            });
        }
        $randomUser=$randomUser->with(['portfolio', 'user_states'])
        ->having('distance', '<=', $distance)
        ->inRandomOrder()
        ->first();
        if ($randomUser) {
            // Update image URL in portfolio
            $randomUser->portfolio->each(function ($profile) {

                if ($profile->image) {
                    $profile->image = asset('storage/' . $profile->image);
                }
            });
            $randomUser->user_states->each(function ($userStats) {
                if ($userStats->id) {
                    $stat                = Stat::find($userStats->id);
                    $userStats->question = $stat->question ?? null;
                    $userStats->min_value = $stat->min_value ?? 0;
                    $userStats->max_value = $stat->max_value ?? 0;
                }
            });
            // Calculate user's age
            if(isset($randomUser->profile_pic) && !empty($randomUser->profile_pic)){

                $randomUser->profile_pic = asset('storage/' . $randomUser->profile_pic);
            }
            $randomUser->age = Carbon::parse($randomUser->dob)->age;
        }
        return $randomUser;
    }
    #------------------------------ E N D -------------------------------#
}
