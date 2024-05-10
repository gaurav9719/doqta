<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\GetUserService;
use App\Services\UserProfileUpdate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use App\Models\Job_status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Http\Requests\UpdateProfileValidation;
use App\Models\BlockedUser;
use App\Models\UserFollower;
class UserController extends BaseController
{
    //

    protected $user, $userProfile, $authId, $getUser, $notification_sent;
    // protected $userProfile;
    public function __construct(UserProfileUpdate $userProfile, GetUserService $getUser, NotificationService $notification_sent)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });

        $this->userProfile       = $userProfile;
        $this->getUser           = $getUser;
        $this->notification_sent = $notification_sent;
    }

    #------------------- C H A N G E        P A S S W O R D  --------------------#
    public function changePassword(Request $request)
    {
        try {

            DB::beginTransaction();

            $validator                 =      Validator::make($request->all(), ['old_password' => 'required', 'new_password' => 'required|min:8|string|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/']);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            } else {

                $user_id                              =      Auth::id();
                $user_password                        =      User::where('id', $user_id)->first()['password'];
                if (Hash::check($request->old_password, $user_password)) {

                    $new_password                     =      Hash::make($request->new_password);
                    $type                             =      trans('notification_message.password_changed_successfully');
                    User::where('id', $user_id)->update(array('password' => $new_password));

                    // $sender                           =       Auth::user();
                    // $section                          =       $type;
                    // $message                          =       trans('notification_message.password_changed_successfully_message');
                    // $status                           =       $this->notification_sent->sendNotification($sender,$sender, $message,$section);
                    #send notification
                    $adminExist                       =       User::select('id')->where('role',3)->first();
                    $sender                           =       (isset($adminExist) && ! empty($adminExist))?$adminExist->id:$user_id;
                    $receiver                         =       User::find($user_id);
                    $data                             =       ["message"=> trans('notification_message.password_changed_successfully_message')];
                    $this->notification_sent->sendNotificationNew($sender, $receiver, trans('notification_message.password_changed_successfully_type'), $data);
                    DB::commit();
                    return $this->sendResponsewithoutData(trans('message.password_changed'), 200);

                } else {
                    
                    return $this->sendResponsewithoutData(trans('message.incorrect_old_password'), 422);
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "notifications" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }
    #------------------- C H A N G E        P A S S W O R D  --------------------#


    #---------*************--------E D I T     P R O F I L E---------*********** --------#

    public function update_profile(UpdateProfileValidation $request)
    {
        $authId                 =   Auth::id();
        return $this->userProfile->edit_profile($request,$authId);
    }
    #---------*************--------E D I T     P R O F I L E---------*********** --------#


    #-----------------************ G E T        P R O F I L E  *************---------------------#
    public function getUserProfile(Request $request){

        $authId             =   Auth::id();

        if(isset($request->user_id) && !empty($request->user_id)){

            $getUser        =   $request->user_id;

        }else{

            $getUser        =   $authId;
        }

        if(User::where(['id'=>$getUser,'is_active'=>1])->exists()){
            
            $userProfile        =   $this->getUser->getUser($getUser,$authId);

            return $this->sendResponse($userProfile, trans("message.user_profile"), 200);

        }else{
            return $this->sendError(trans('message.invalidUser'), [], 422);

        }
    }
    #----------------------------*************** E N D ************* ----------------------------#

    #--------------------- G E T        U S E R         P O S T     ----------------------------#
    public function getUserPost(Request $request){

        $validator                 =      Validator::make($request->all(), ['user_id' => 'required|integer|exists:users,id']);

        if ($validator->fails()) {

            return $this->sendResponsewithoutData($validator->errors()->first(), 422);

        } else {
            $authId             =   Auth::id();
            $getUser            =   $request->user_id;
            if(User::where(['id'=>$getUser,'is_active'=>1])->exists()){
                //check user is blocked or not
                if($authId!=$getUser){
                    $isBlocked = BlockedUser::where(function ($query) use ($authId, $getUser) {
                        // Check if the exact combination exists
                        $query->where(['user_id' => $authId, 'blocked_user_id' => $getUser])
                              ->orWhere(['user_id' => $getUser, 'blocked_user_id' => $authId]);
                    })->exists();
                    if($isBlocked){
                        return $this->sendError(trans('message.something_went_wrong'), [], 403);
                    }else{

                        $isSupporting   =   UserFollower::where(['user_id'=>$getUser,'follower_user_id'=>$authId,'status'=>2])->exists();
                        
                        if(!$isSupporting){

                            return $this->sendError(trans('message.you_are_not_supporting'), [], 403);
                        }
                    }
                }
                $limit          =   10;
                if(isset($request->limit) && !empty($request->limit)){
                    $limit      =   $request->limit;
                }
                return $this->getUser->getUserPosts($getUser,$authId,$limit);
            }else{
                return $this->sendError(trans('message.invalidUser'), [], 422);
            }
        }
    }
    #--------------------- G E T        U S E R         P O S T     ----------------------------#



}
