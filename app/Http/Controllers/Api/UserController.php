<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserPreferenceValidation;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\UserPreference;
use Illuminate\Support\Facades\DB;
use App\Services\GetUserService;
use App\Services\UserProfileUpdate;
use Illuminate\Support\Facades\Validator;
use App\Models\UserRole;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use App\Models\UserStat;
use App\Models\Job_status;
use App\Models\MyTeam;
use App\Models\MyTeamMember;
use Carbon\Carbon;
class UserController extends BaseController
{
    //

    protected $user, $userProfile, $authId, $getUser;
    // protected $userProfile;
    public function __construct(UserProfileUpdate $userProfile, GetUserService $getUser)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });

        $this->userProfile = $userProfile;
        $this->getUser = $getUser;
    }

    public function update_profile(Request $request)
    {
        $validator = Validator::make($request->all(), ['type' => 'required']);

        if ($validator->fails()) {

            return $this->sendResponsewithoutData(getErrorAsString($validator->errors()), 422);
            
        } else {
            $type = $request->type;
            if ($type == 1) {           #---- update recruitment type for invite friend-------#
                // return $this->addRecuitmentType($request);
                return $this->userProfile->addRecuitmentType($request);

            } elseif ($type == 2) {    #---- update user preference type for invite friend-------#

                return $this->userProfile->updateUserPrefences($request);
            } elseif ($type == 3) {

                return $this->userProfile->addStatistics($request);
            }
        }
    }

    #---------******** U P D A T E      U S E R     P R E F E R E N C E S *********---------#
    public function updateUserPreferences(UserPreferenceValidation $request)
    {
        return $this->userProfile->updateUserPrefences($request);
    }
    #----------------------------------- E  N  D -------------------------------------------#

    #---------------------------  S W I T C H        U S E R --------------------------------#
    public function switchUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), ['role_id' => 'required|integer|between:2,3']);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData(getErrorAsString($validator->errors()), 422);

            } else {
                
                UserRole::updateOrCreate(['user_id' => $this->authId, 'role_id' => $request['role_id']]);
                User::where('id', $this->authId)->update(['current_role_id' => $request['role_id']]);
                DB::commit();
                $userData = $this->getUser->getUser($this->authId);
                return $this->sendResponse($userData, trans("message.switch_user"), 200);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "switchUser" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #---------------------------------------- E N D -----------------------------------------#

    public function checkAiFInder(Request $request){
        try{
            $authUser       = Auth::user();
            $userId         = $authUser->id;
            $userPreference = UserPreference::where('user_id', $userId)->first();
           
            $teamName       = ($authUser->name) ? $authUser->name . "'s team" : "Roster user teams";
           
            $team           = MyTeam::updateOrCreate(
                ['recruiter_id' => 2, 'member_id' => $userId, 'team_type' => 3],
                ['is_active' => 1, 'team_name' => $teamName]
            );
            
            $teamId         = $team->id;
            // DB::enableQueryLog();
            if ($userPreference) {
                // Extracted and optimized AI users query
                    $aiUsers = User::select('id', DB::raw("round(3959 * acos(cos(radians('" . $authUser->lat . "'))* cos(radians(`lat`))* cos(radians(`long`)- radians('" . $authUser->long . "'))+ sin(radians('" . $authUser->lat . "'))* sin(radians(`lat`))),2) AS distance"))
                    ->whereHas('SelectRecruitmentType', function ($query) {

                        $query->where('role_id', 2)->where('recruiter_type', 3);
                    })


                    ->where(['is_active' => 1])

                    ->where("id", "<>", $userId)
                    ->whereNotExists(function ($subquery) use ($userId) {    
                        $subquery->select(DB::raw(1))
                            ->from('my_team_members')
                            ->whereRaw("member_id ='".$userId."' AND dater_id=id");
                    })
                    ->whereNotExists(function ($subquery) use ($userId) {    
                        $subquery->select(DB::raw(1))
                            ->from('user_block_lists')
                            ->whereRaw("(user_id = id AND blocked_user_id = '".$userId."') OR (user_id ='".$userId."' AND blocked_user_id = id)");
                    })
                    ->whereYear('dob', '>=', Carbon::now()->subYears($userPreference->age + 1)->year)->whereYear('dob', '<=', Carbon::now()->subYears($userPreference->age - 1)->year)->having('distance', '<=', $userPreference->distance);                if ($userPreference->gender != 0) {
                        $aiUsers= $aiUsers->where('gender', $userPreference->gender);
                    }
                
                // Retrieve AI users and limit to 50
                $ghostUsers = $aiUsers->limit(50)->get();
                   
                if ($ghostUsers->isNotEmpty()) {
                    foreach ($ghostUsers as $AIUser) {
                        $isExist = MyTeamMember::where(['member_id' => $userId, 'dater_id' => $AIUser->id])->exists();

                        if (!$isExist) {

                            $newTeamMember = new MyTeamMember();
                            $newTeamMember->team_id = $teamId;
                            $newTeamMember->member_id = $userId;
                            $newTeamMember->dater_id = $AIUser->id;
                            $newTeamMember->recruiter_type = 3;
                            $newTeamMember->is_active = 1;
                            $newTeamMember->save();
                        }
                    }
                }
                $jobStatus = Job_status::where('job_id', 1)->first();
                if ($jobStatus) {
                    $jobStatus->update(['is_running' => false]);
                }
            }else{
                $jobStatus = Job_status::where('job_id', 1)->first();
                if ($jobStatus) {
                    $jobStatus->update(['is_running' => false]);
                }
            }
        }catch(Exception $e){

            Log::error('Error caught: "rosterAi" ' . $e->getMessage());
            dd($e->getMessage());
        }
    }


}
