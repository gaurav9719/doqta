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
use App\Http\Requests\UpdateProfileValidation;
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

    public function edit_profile($request){

        try {

            DB::beginTransaction();
            $user_id                =       Auth::id();  
            $user_details           =       User::find($user_id);

            if(isset($request->profile) && !empty($request->profile)){

                $profile_image      =       upload_file($request->profile,'profile_pic');

                $user_details->profile  = $profile_image;
            }
            if(isset($request->cover) && !empty($request->cover)){

                $cover              =       upload_file($request->cover,'cover');

                $user_details->cover =      $cover;
            }

            if(isset($request->user_name) && !empty($request->user_name)){

                $user_details->user_name     =   $request->user_name;
            }

            if(isset($request->bio) && !empty($request->bio)){

                $user_details->bio     =   $request->bio;

            }
            $user_details->save();
            DB::commit();
            // return  $this->userProfile->UserProfileById($user_id,"","Profile updated successfully");

        }catch (Exception $e) {
            
            DB::rollback();

            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
