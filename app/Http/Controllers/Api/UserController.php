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
                    $sender                           =       Auth::user();
                    $section                          =       $type;
                    $message                          =       trans('notification_message.password_changed_successfully_message');
                    $status                           =       $this->notification_sent->sendNotification($sender,$sender, $message,$section);
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
        return $this->userProfile->edit_profile($request);
    }
    #---------*************--------E D I T     P R O F I L E---------*********** --------#


    #-----------------************ G E T        P R O F I L E  *************---------------------#
    public function getUserProfile(Request $request){

        // $userProfile        =   User::where('is_active',1);

        $authId             =   Auth::id();

        if(isset($request->user_id) && !empty($request->user_id)){

            $getUser        =   $request->user_id;

        }else{

            $getUser        =   $authId;
        }


        $userProfile        =   $this->getUser->getUser($getUser,$authId);

        // if(isset($request->user_id) && !empty($request->user_id)){

        //     $userProfile    =   $userProfile->where('id',$request->user_id);

        // }else{

        //     $userProfile    =   $userProfile->where('id',$authId);
        // }
        // $userProfile        =  $userProfile->withCount('userPost')->withCount('supporter')->with('userPost',function($query){

        //     $query->take(10); // Limiting the number of user posts to 10

        // })->first();


        // if(isset($userProfile) && !empty($userProfile)){


        //     if(isset($userProfile->profile) && !empty($userProfile->profile)){

        //         $userProfile->profile       =   asset('storage/'.$userProfile->profile);
        //     }

        //     if(isset($userProfile->cover) && !empty($userProfile->cover)){

        //         $userProfile->cover       =   asset('storage/'.$userProfile->cover);
        //     }

        //     // dd($userProfile['userPost']);
        //     if(isset($userProfile['userPost']) && !empty($userProfile['userPost'])){

        //         // $userProfile->user_post->each(function ($post) {

        //         //     $post->media_url = optional($post->media_url)->assetUrl();

        //         // });
        //         // dd($userProfile['user_post']);

        //         $userProfile->userPost->each(function($query){
                
        //             if(isset($query->media_url) && !empty($query->media_url)){
    
        //                 $query->media_url     =   asset('storage/'.$query->media_url);
        //             }

        //         });
        //     }

        //     // add post





        // }






        return $this->sendResponse($userProfile, trans("message.statistics"), 200);

    }
    #----------------------------*************** E N D ************* ----------------------------#




}
