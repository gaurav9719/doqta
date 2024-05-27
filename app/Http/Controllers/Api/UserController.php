<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Job_status;
use App\Models\ActivityLog;
use App\Models\BlockedUser;
use App\Models\Notification;
use App\Models\UserFollower;
use Illuminate\Http\Request;
use App\Services\GetUserService;
use App\Models\EmailChangeRequest;
use Illuminate\Support\Facades\DB;
use App\Services\UserProfileUpdate;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\UpdateProfileValidation;
use App\Mail\ChangeEmailRequest;
use App\Models\emailPasswordChangeLogs;

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
                    $receiver = User::find($user_id);
                    $sender = User::where('role', 3)->first();
                    $sender = isset($sender) ? $sender : $receiver;
                    $data                             =       ["message" => trans('notification_message.password_changed_successfully_message')];
                    $this->notification_sent->sendNotificationNew($sender, $receiver, trans('notification_message.password_changed_successfully_type'), $data);
                    DB::commit();
                    return $this->sendResponsewithoutData(trans('message.password_changed'), 200);
                } else {

                    return $this->sendResponsewithoutData(trans('message.incorrect_old_password'), 422);
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "change_password" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }
    #------------------- C H A N G E        P A S S W O R D  --------------------#


    #---------*************--------E D I T     P R O F I L E---------*********** --------#

    public function update_profile(UpdateProfileValidation $request)
    {
        $authId                 =   Auth::id();
        return $this->userProfile->edit_profile($request, $authId);
    }
    #---------*************--------E D I T     P R O F I L E---------*********** --------#


    #-----------------************ G E T        P R O F I L E  *************---------------------#
    public function getUserProfile(Request $request)
    {

        $validate= Validator::make($request->all(), [
            'type' => 'nullable|integer|between:1,2',
            'limit'=> 'nullable|integer',
        ]);
        if($validate->fails()){
            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }
        
        $authId             =   Auth::id();
        if(isset($request->type) && $request->type == 2){
            
            $limit = isset($request->limit) ? $request->limit : 10;
            $activity =ActivityLog::where('user_id', $authId)->simplePaginate($limit);
            return $this->sendResponse($activity, "Your Activity", 200);
        }
        if (isset($request->user_id) && !empty($request->user_id)) {

            $getUser        =   $request->user_id;
        } else {

            $getUser        =   $authId;
        }

        if (User::where(['id' => $getUser, 'is_active' => 1])->exists()) {

            $userProfile        =   $this->getUser->getUser($getUser, $authId);

            return $this->sendResponse($userProfile, trans("message.user_profile"), 200);
        } else {
            return $this->sendError(trans('message.invalidUser'), [], 422);
        }
    }
    #----------------------------*************** E N D ************* ----------------------------#

    #--------------------- G E T        U S E R         P O S T     ----------------------------#
    public function getUserPost(Request $request)
    {

        $validator                 =      Validator::make($request->all(), ['user_id' => 'required|integer|exists:users,id']);

        if ($validator->fails()) {

            return $this->sendResponsewithoutData($validator->errors()->first(), 422);
        } else {
            $authId             =   Auth::id();
            $getUser            =   $request->user_id;
            if (User::where(['id' => $getUser, 'is_active' => 1])->exists()) {
                //check user is blocked or not
                if ($authId != $getUser) {
                    $isBlocked = BlockedUser::where(function ($query) use ($authId, $getUser) {
                        // Check if the exact combination exists
                        $query->where(['user_id' => $authId, 'blocked_user_id' => $getUser])
                            ->orWhere(['user_id' => $getUser, 'blocked_user_id' => $authId]);
                    })->exists();
                    if ($isBlocked) {
                        return $this->sendError(trans('message.something_went_wrong'), [], 403);
                    } else {

                        $isSupporting   =   UserFollower::where(['user_id' => $getUser, 'follower_user_id' => $authId, 'status' => 2])->exists();

                        if (!$isSupporting) {

                            return $this->sendError(trans('message.you_are_not_supporting'), [], 403);
                        }
                    }
                }
                $limit          =   10;
                if (isset($request->limit) && !empty($request->limit)) {
                    $limit      =   $request->limit;
                }
                return $this->getUser->getUserPosts($getUser, $authId, $limit);
            } else {
                return $this->sendError(trans('message.invalidUser'), [], 422);
            }
        }
    }
    #--------------------- G E T        U S E R         P O S T     ----------------------------#

    
    #------------------  E M A I L       C H A N G E         R E Q U E S T ---------------------#
    public function requestEmailChange(Request $request)
    {
        DB::beginTransaction();

        try {

            // Validate request data
            $validator = Validator::make($request->all(), [
                'new_email' => 'required|email|unique:users,email',
                'type' => 'required|integer|between:1,2',
                'otp' => 'required_if:type,2', // OTP is required if type equals 2
            ]);

            if ($validator->fails()) {
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            }

            $user = Auth::user();
            $newEmail = $request->new_email;
            $verificationCode = rand(111111, 999999);

            if ($request->type == 1) { // Send verification code to new email
                $emailChangeRequest = EmailChangeRequest::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'new_email' => $newEmail,
                        'verification_code' => $verificationCode,
                        'expires_at' => now()->addMinutes(10),
                    ]
                );
                DB::commit();
                Mail::to($newEmail)->send(new ChangeEmailRequest($verificationCode));

                return $this->sendResponsewithoutData(trans('message.sent_email_verification_code'), 200);

            } elseif ($request->type == 2) {            // Match the verification code


                $emailChangeRequest = EmailChangeRequest::where('new_email', $newEmail)->first();
                

                if (!$emailChangeRequest) {

                    return $this->sendResponsewithoutData(trans('message.invalid_email'), 400);
                }

                if (strtotime($emailChangeRequest->expires_at) < strtotime(Carbon::now())) {

                    $emailChangeRequest->delete();
                    return $this->sendResponsewithoutData(trans('message.otp_expired'), 400);
                }

                if ($emailChangeRequest->verification_code != $request->otp) {
                    return $this->sendResponsewithoutData(trans('message.invalid_otp'), 400);
                }

                $isEmailUsed            =   User::where('email', $newEmail)->exists();
                if ($isEmailUsed) {

                    return $this->sendResponsewithoutData(trans('message.email_already_used'), 400);
                }

                $oldEmail               = $user->email;
                $user->update(['email' => $newEmail]);

                emailPasswordChangeLogs::create([
                    'user_id' => $user->id,
                    'type' => 1, // Assuming 1 stands for email change
                    'old' => $oldEmail,
                    'new' => $newEmail,
                ]);

                $emailChangeRequest->delete();
                DB::commit();
                return $this->sendResponsewithoutData(trans('message.email_changed_successfully'), 200);
            }

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "requestEmailChange" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
}








}
