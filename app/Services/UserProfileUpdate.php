<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStat;
use App\Models\Recruiter;
use App\Models\UserDevice;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\UsersInterest;
use App\Models\UserPreference;
use App\Http\Requests\LoginUser;
use App\Models\RecruiterRequest;
use App\Services\GetUserService;
use App\Services\RosterAiTrigger;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UserRegister;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\UserRecruitmentChoice;
use App\Services\NotificationService;
use App\Models\UserParticipantCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
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

    public function edit_profile($request,$user_id){

        try {


            DB::beginTransaction();
            $user_details               =       User::find($user_id);

            if(isset($request->user_name) && !empty($request->user_name)){

                $isExist                =  checkUserNameAvailable($request->user_name,$user_id);

                if($isExist){

                    return $this->sendResponsewithoutData("user name already in use",409);

                }
                $user_details->user_name =   $request->user_name;
            }

            if(isset($request->profile) && !empty($request->profile)){
                
                $profile_image          =       upload_file($request->profile,'profile_pic');
                removePictureFromFolder($user_details->profile);
                $user_details->profile  =       $profile_image;
            }

            if(isset($request->cover) && !empty($request->cover)){

                $cover              =       upload_file($request->cover,'cover');
                removePictureFromFolder($user_details->cover);
                $user_details->cover =      $cover;
            }

            
            if(isset($request->bio) && !empty($request->bio)){

                $user_details->bio     =   $request->bio;

            }
            if($request->is_public == 0 && $request->is_public != null || $request->is_public == 1){

                $user_details->is_public     =   $request->is_public;

            }

            if($request->is_muted == 0 && $request->is_muted != null || $request->is_muted == 1){

                $user_details->is_muted     =   $request->is_muted;

            }
            if(isset($request->name) && !empty($request->name)){

                $user_details->name     =   $request->name;

            }

            if(isset($request->dob) && !empty($request->dob)){

                $user_details->dob     =  Carbon::createFromFormat('m/d/Y', $request->dob)->format('Y-m-d');

            }

            if(isset($request->gender) && !empty($request->gender)){

                $user_details->gender     =   $request->gender;
            }

            if(isset($request->ethnicity) && !empty($request->ethnicity)){

                $user_details->ethnicity     =   $request->ethnicity;

            }
            if(isset($request->pronoun) && !empty($request->pronoun)){

                $user_details->pronoun     =   $request->pronoun;

            }
             //11 june 
            if(isset($request->reasons) && count($request->reasons)>0 ){
                
                $existsReasons  =   UserParticipantCategory::where('user_id', $user_id)->pluck('participant_id')->toArray();
                
                $deleteReasons  =   array_diff($existsReasons, $request->reasons);
                $insertReasons  =   array_diff($request->reasons, $existsReasons);

                if(count($deleteReasons) > 0){

                    UserParticipantCategory::where('user_id', $user_id)->whereIn('participant_id', $deleteReasons)->delete();
                }
                if(count($insertReasons) > 0){

                    foreach ($insertReasons as $resons) {

                        UserParticipantCategory::updateOrCreate(
                            ['user_id' => $user_id, 'participant_id' => $resons],
                            ['is_active' => 1]
                        );
    
                    }
                }
            }
            if(isset($request->interest) && count($request->interest)>0 ){

                $existsIntrests=UsersInterest::where('user_id', $user_id)->pluck('interest_id')->toArray();

                $deleteIntrest= array_diff($existsIntrests, $request->interest);

                $insertIntersts=array_diff($request->interest, $existsIntrests);

                if(count($deleteIntrest) > 0){

                    UsersInterest::where('user_id', $user_id)->whereIn('interest_id', $deleteIntrest)->delete();
                }
                if(count($insertIntersts) > 0){

                    foreach ($insertIntersts as $interest_id) {

                        UsersInterest::updateOrCreate(
                            ['user_id' => $user_id, 'interest_id' => $interest_id],
                            ['is_active' => 1]
                        );
                    }
                }
            }
            $user_details->save();
            DB::commit();
            $getUser    =   $this->user->getUser($user_id,$user_id);
            return $this->sendResponse($getUser, trans("message.updated_success_common"), 200);

        }catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
