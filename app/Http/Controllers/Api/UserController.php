<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Models\Job_status;
use App\Models\ActivityLog;
use App\Models\BlockedUser;
use App\Models\Notification;
use App\Models\UserFollower;
use Illuminate\Http\Request;
use App\Mail\ChangeEmailRequest;
use App\Services\GetUserService;
use App\Models\EmailChangeRequest;
use App\Traits\IsLikedPostComment;
use Illuminate\Support\Facades\DB;
use App\Services\UserProfileUpdate;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\postCommentLikeCount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;
use App\Models\emailPasswordChangeLogs;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\UpdateProfileValidation;

class UserController extends BaseController
{
    //

    use postCommentLikeCount,IsLikedPostComment;
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
            'user_id'=> 'required|integer|exists:users,id',
        ]);
        if($validate->fails()){

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }

        $authId             =   Auth::id();
        if(isset($request->type) && $request->type == 2){
            
            $limit      =   isset($request->limit) ? $request->limit : 10;

            $activity   =    $this->getActivity($request,$limit);

            return $this->sendResponse($activity, "Your Activities", 200);
        }

        if (isset($request->user_id) && !empty($request->user_id)) {

            $getUser        =   $request->user_id;

        } else {

            $getUser        =   $authId;
        }

        if (User::where(['id' => $getUser, 'is_active' => 1])->exists()) {

            $userProfile        =   $this->getUser->getUser($getUser, $authId);
            // dd($userProfile);
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

    function getActivity($request,$limit){


        $loginId         =   Auth::id();
        $userId          =   $request->user_id;
        $userData        =   User::where(['is_active'=>1,'id'=>$userId])->first();
        //dd($userData['user_name']);
        // if(empty($userData)){



        // }
        $user_name     =    ($loginId == $request->user_id)? "You" :$userData['user_name'];
        // dd($user_name);
        $types=[10,11,12,13,14,15];
        $actions= [
            10 => "posted in the community",
            11 => "liked this post",
            12 => "Commented on this post",
            13 => "liked comment of this post",
            14 =>"replied comment of this post",
            15 => "reposted the post",
        ];
        

        $caseStatement = "CASE";
        foreach ($actions as $type => $message) {
            $caseStatement .= " WHEN action = $type THEN '$message'";
        }
        $caseStatement .= " ELSE 'Your action' END AS action_message";
        
        $activities =ActivityLog::select('id','user_id','post_id','community_id','like_id','community_member_id','parent_id','action','action_details','is_active','created_at','updated_at','comment_id')
            ->selectRaw($caseStatement)
            ->where('user_id', $userId)
            ->whereIn('action', $types)
            ->with(['post_details'=> function($query) use($userId){
                
                $query->with(['post_user'=>function($query){

                    $query->select('id','name','user_name','profile');
                    },
                    'group'=>function($query){

                        $query->select('id','name','description','cover_photo','member_count','post_count','created_by');
                    },
                    'comment' => function($query) use ($userId){
                        $query->where('user_id', $userId);
                    },
                    'parent_post' => function ($query) {
                        $query->select('*')
                            ->where('is_active', 1)
                            ->with([
                                'post_user' => function ($query) {
                                    $query->select('id', 'name','user_name', 'profile');
                                }
                            ]);
                    },'parent_post.group'=>function($query){

                        $query->select('id','name','description','created_by');
                    }
                ])->withCount(['total_likes','total_comment']);
            },'user'=>function($q){
                $q->select('id','name','user_name','profile');
            }])
            ->orderBy('id', 'desc')
            ->simplePaginate($limit);

            $activities->each(function ($homeScreenPost) use($loginId) {

                if (isset($homeScreenPost->post_details->media_url) && !empty($homeScreenPost->post_details->media_url)) {

                    $homeScreenPost->post_details->media_url      =  $this->addBaseInImage($homeScreenPost->post_details->media_url);
                }

                if ($homeScreenPost->post_details->parent_post && $homeScreenPost->post_details->parent_post->post_user && $homeScreenPost->post_details->parent_post->post_user->profile) {

                    $homeScreenPost->post_details->parent_post->post_user->profile = $this->addBaseInImage($homeScreenPost->post_details->parent_post->post_user->profile);
                }

                if (isset($homeScreenPost->post_details->post_user) &&  !empty($homeScreenPost->post_details->post_user->profile)) {

                    $homeScreenPost->post_details->post_user->profile      =  $this->addBaseInImage($homeScreenPost->post_details->post_user->profile);
                }
                if ($homeScreenPost->post_details->group &&  $homeScreenPost->post_details->group->cover_photo) {

                    $homeScreenPost->post_details->group->cover_photo      =  $this->addBaseInImage($homeScreenPost->post_details->group->cover_photo );
                }

                if (isset($homeScreenPost->user) && !empty($homeScreenPost->user->profile)) {

                    $homeScreenPost->user->profile      =  $this->addBaseInImage($homeScreenPost->user->profile);
                }

                
                $isExist                                       =   $this->IsPostLiked($homeScreenPost->post_details->id, $loginId);
                $homeScreenPost->post_details->is_liked        =   $isExist['is_liked'];
                $homeScreenPost->post_details->reaction        =   $isExist['reaction'];
                $isRepost                                      =   Post::where(['parent_id'=>$homeScreenPost->post_details->id,'user_id'=>$loginId,'is_active'=>1])->exists();
                $homeScreenPost->post_details->is_reposted     =  ($isRepost)?1:0;
                $homeScreenPost->post_details->postedAt        =   time_elapsed_string($homeScreenPost->post_details->created_at);


                #------------ parent post data-----------------#
                if(isset($homeScreenPost->post_details->parent_post) && !empty($homeScreenPost->post_details->parent_post)){

                    if (isset($homeScreenPost->post_details->parent_post->media_url) && !empty($homeScreenPost->post_details->parent_post->media_url)) {

                        $homeScreenPost->post_details->parent_post->media_url   =  $this->addBaseInImage($homeScreenPost->post_details->parent_post->media_url);
                    }
                    $isExist                                      =   $this->IsPostLiked($homeScreenPost->post_details->parent_post->id, $loginId);
                    $homeScreenPost->post_details->parent_post->is_liked        =   $isExist['is_liked'];
                    $homeScreenPost->post_details->parent_post->reaction        =   $isExist['reaction'];
                    $homeScreenPost->post_details->parent_post->total_likes_count =   $isExist['total_likes_count'];
                    $homeScreenPost->post_details->parent_post->total_comment_count =   Comment::where('post_id',$homeScreenPost->post_details->parent_post->id)->count();
                    $isRepost                                     =   Post::where(['parent_id'=>$homeScreenPost->post_details->parent_post->id,'user_id'=>$loginId,'is_active'=>1])->exists();
                    $homeScreenPost->post_details->parent_post->is_reposted     =  ($isRepost)?1:0;
                    $homeScreenPost->post_details->parent_post->postedAt        =  time_elapsed_string($homeScreenPost->post_details->parent_post->created_at);
                }
            });

        return $activities;
    }




public function iosPush(){

    dd(IosPush("96fee2946e3214071409278ca8e3d337bc3aa5998e36c16efa8141929e230c5e","Hello sir",1,['name'=>"param"],$mood_icon = ''));
}



}
