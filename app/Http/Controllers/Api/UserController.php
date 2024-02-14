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



}
