<?php

namespace App\Services;

use App\Models\User;
use App\Http\Requests\UserRegister;
use App\Http\Requests\LoginUser;
use App\Models\UserDevice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\GetUserService;
use App\Http\Controllers\Api\BaseController;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Validator;
use App\Models\UserRole;
use Illuminate\Support\Facades\Log;
use App\Models\UserStat;
use App\Models\UserRecruitmentChoice;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Models\Recruiter;
use App\Models\RecruiterRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Services\RosterAiTrigger;

/**
 * Class UserProfileUpdate.
 */
class UserProfileUpdate extends BaseController
{

    protected $user, $authId,$notification,$rosterAi;


    public function __construct(GetUserService $user,NotificationService $notification,RosterAiTrigger $rosterAi)
    {
        $this->user                 = $user;
        $this->notification         = $notification;
        $this->rosterAi             = $rosterAi;
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });
    }

    #*********** U P D A T E    U S E R   P R E F E N C E S **********#
    public function updateUserPrefences($request)
    {
        DB::beginTransaction();
        try {

            $validator = Validator::make(
                $request->all(),
                [
                    'distance' => 'required|numeric',
                    'age_preference' => 'required|numeric|between:18,100',
                    'gender_preference' => 'required|numeric|between:0,2',
                    'ghost_coach' => 'required|numeric|between:0,1',
                ],

                [
                    'age_preference.between' => 'Please choose the 18+ age range.',
                    'gender_preference.between' => 'Please choose gender preference.',
                    'ghost_coach.between' => 'Please choose ghost Yes or No.',
                ]
            );

            // Check if validation fails
            if ($validator->fails()) {
                // Return a JSON response with validation errors
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            } else {

                $userId = Auth::id();

                if (Auth::user()->current_role_id == 2) { //2 means dater

                    UserPreference::updateOrCreate(
                        ['user_id' => $userId],
                        ['distance' => $request->distance, 'age' => $request->age_preference, 'gender' => $request->gender_preference, 'ghost_coach' => $request->ghost_coach]
                    );
                    DB::commit();
                    $userData = $this->user->getUser($userId);
                    return $this->sendResponse($userData, trans("message.updatePrefences"), 200);
                } else {

                    return $this->sendResponsewithoutData(trans("message.invalidUser"), 403);
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "update user preference" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #************----------------- E N D ------------------ *************#


    #--------------------- A D D        R E C R U I T M E N T      T Y P E  --------------------------#
    public function addRecuitmentType($request)
    {
        DB::beginTransaction();

        try {

            $authUser   = Auth::user();
            $userId     = $authUser->id;
            $role       = $authUser->current_role_id;
            $validator = Validator::make(
                $request->all(),
                [
                    'recruitment_type' => $role == 2 ? 'required|integer|between:1,3' : 'required|integer|between:1,2'
                ],['between'=>"Invalid recruitment type"]);
        
            if ($validator->fails()) {
                return $this->sendResponsewithoutData(getErrorAsString($validator->errors()), 422);
            } else {
                if ($request->recruitment_type != 2) {

                    UserRecruitmentChoice::updateOrCreate(['user_id' => $userId, 'role_id' => $role,'recruiter_type'=>$request['recruitment_type']],['recruiter_type' => $request['recruitment_type']]);

                    if($request->recruitment_type==3){  // select the Roster Ai finder
                       
                       $this->rosterAi->RosterAiFinder($authUser,$userId);
                    }
                }
                if($request->recruitment_type == 2) {      #-------- DATER  and invite ghost coach------------#
                        // $unitOfMeasurement = "Miles";
                        // $unitOfMeasurement = ($unitOfMeasurement == 'kilometers') ? 6371 : 3959; // Conversion factor binding
                    if($role == 2){
                        $distance           =   50; // 50 miles
                        $limit              =   2;
                        $isSelected                                 =      UserRecruitmentChoice::where(['user_id' => $userId,'role_id' => 2,'recruiter_type'=>2])->count();
                        if($isSelected==0){
    
                            $ghostUsers                            =      User::select('id', DB::raw("round(3959 * acos(cos(radians('" . $authUser->lat . "'))* cos(radians(`lat`))* cos(radians(`long`)- radians('" . $authUser->long . "'))+ sin(radians('" . $authUser->lat . "'))* sin(radians(`lat`))),2) AS distance"))->whereHas('user_roles', function ($query) {
    
                                $query->where('role_id', 3)->where('is_ghost_coach',1);
    
                            })
                            ->where(['is_active' => 1])
                            ->where("id", "<>", $userId)
                            ->whereNotExists(function ($subquery) use($userId) {    #--- check in recruiter table if ghost coach is already assign
    
                                $subquery->select(DB::raw(1))
                                    ->from('recruiters')
                                    ->whereRaw("dater_id ='".$userId."' AND recruiters.recruiter_id=id");
                            })
                            ->whereNotExists(function ($subquery) use($userId) {    #--- check in recruiter_requests table if ghost coach is already receive the request
                                $subquery->select(DB::raw(1))
                                    ->from('recruiter_requests')
                                    ->whereRaw("user_id ='".$userId."' AND recruiter_id=id AND is_active=1");
                            })

                            //-------------- Check if not in bench TABLE -----------//
                            ->whereNotExists(function ($subquery) use($userId) {    #--- check in recruiter_requests table if ghost coach is already receive the request
                                $subquery->select(DB::raw(1))
                                    ->from('user_benches')
                                    ->whereRaw("user_id ='".$userId."' AND rejectd_user_id=id");
                            })
                            //---------------------  E N D  -------------------------//
                            ->having('distance', '<=', $distance)
                            ->limit($limit)
                            ->get();
                           
                            if (isset($ghostUsers[0]) && !empty($ghostUsers[0])) {
    
                                foreach ($ghostUsers as $ghost) {           // send request to ghost coach to join
    
                                    $notification_type                    =     $ghostUsers . trans('notification_message.send_ghost_coach_request_type');
                                    $notification_message                 =     $ghostUsers . trans('notification_message.send_ghost_coach_request_message');
                                    $reciever                             =     User::find($ghost->id, ['id','current_role_id', 'device_token', 'device_type']);
                                    //send request to recruiter to join as ghost coach
                                    $requestForGhost                      =     new RecruiterRequest();
                                    $requestForGhost->user_id             =     $userId;
                                    $requestForGhost->recruiter_id        =     $ghost->id;
                                    $requestForGhost->request_status      =     0;
                                    $requestForGhost->request_on          =     Carbon::now();
                                    if($requestForGhost->save()){
                                        $this->notification->sendNotification(3,$reciever,$authUser,$notification_message,$notification_type);
                                    }
                                }
                                UserRecruitmentChoice::updateOrCreate(['user_id' => $userId, 'role_id' => $role,'recruiter_type' => $request['recruitment_type']],['recruiter_type' => $request['recruitment_type']]);
    
                            }else{
                                return $this->sendResponsewithoutData(trans('message.ghost_not_found'), 400);
                            }
                        }
                    }else{  #---------- RECRUITER SIDE --------------#
                        
                        UserRole::updateOrCreate(['user_id' => $userId, 'role_id' => $role],['is_ghost_coach' => 1]);
                        UserRecruitmentChoice::updateOrCreate(['user_id' => $userId, 'role_id' => $role,'recruiter_type' => $request['recruitment_type']],['recruiter_type' => $request['recruitment_type']]);
                    }
                }
                DB::commit();

                $userData = $this->user->getUser($userId);
                return $this->sendResponse($userData, trans("message.updated_recuiter"), 200);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "addRecuitmentType" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------------------------  E N D   ----------------------------------#


    #--------------------------  A D D      U S E R     S T A T I S T I C S  -----------------#
    public function addStatistics($request)
    {
        $userId         = Auth::id();
        $authUser       = Auth::user();
        DB::beginTransaction();
        
        try {
            $validator = Validator::make($request->all(), ['statistics' => 'nullable|json','profile_pic'=>"nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048"]);
            // Check if validation fails
            if ($validator->fails()) {
                // Return a JSON response with validation errors
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);

            } else {

                #------------------- C H E C K      I F     R O L E      I S    R E C R U I T E R  -------------#
                
                // Validation passed, update user and user_role tables
                if(isset($request->statistics) && !empty($request->statistics)){

                    if($authUser->current_role_id==3){

                        return $this->sendResponsewithoutData(trans("message.invalidUser"), 403);
    
                    }

                    $statistics = json_decode($request->statistics, true);
                    $stats = [];
                    foreach ($statistics as $key => $statistic) {
    
                        if (!isset($statistic['id']) || !isset($statistic['answer'])) {
    
                            DB::rollback();
                            return $this->sendResponsewithoutData("Invalid json", 400);
                        }
    
                        UserStat::updateOrCreate(
                            ['user_id' => $userId, 'stat_id' => $statistic['id']],
                            ['answer' => $statistic['answer']]
                        );
                        $stats[] = $statistic['id'];
                    }
                    UserStat::where(['user_id' => $userId])->whereNotIn('stat_id', $stats)->delete();
                }

                // update picture
              
                if($request->hasFile('profile_pic')){ 

                    $profile    =   upload_file($request->profile_pic);
                    $user       =   User::find($userId);
                    $user->profile_pic = $profile;
                    $user->save();
                }
                // Check if the directory exists, if not, create it
                DB::commit();
                $userData = $this->user->getUser($userId);
                return $this->sendResponse($userData, trans("message.updated_successfully"), 200);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "addStatistics" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #-------------------------------------      E N D    -------------------------------------#
}
