<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\Recruiter;
use App\Models\Referral;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\MyTeam;
use Illuminate\Support\Str;
use App\Services\NotificationService;

class InvitesContact extends BaseController
{


    protected $notification;
    public function __construct(NotificationService $notification)
    {
        $this->notification         = $notification;
    }

    //
    #----------------------- A D D      I N V I T E D       F R I E N D --------------------------#
    public function addInvitedFriend(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), ['reference_code' => 'required|exists:users,reference_code','role_id'=>'required|integer|between:2,3'],['role_id.between'=>"Invalid role"]);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);

            } else {
    
                $role           = $request->role_id;
                $user           = Auth::user();
                // $daterId="";
                // $recruiterId="";
                $referenceUser  = User::where('reference_code', $request->reference_code)->first();

                if(isset($referenceUser) && !empty($referenceUser)) {

                    #---------- CHECK USER AND INVITE USER COULD NOT BE SAME

                    if($user->id== $referenceUser->id) {

                        return $this->sendResponse([], trans('message.something_went_wrong'), 403);

                    }else{

                        //check the auth user current role
                        if($role==3){                               // recruiter send the request to dater
    
                            $daterId                        =       $user->id;
                            $recruiterId                    =       $referenceUser->id;
                        }elseif($role==2) {               // dater send the request to recruiter   
    
                            $daterId                        =       $referenceUser->id;
                            $recruiterId                    =       $user->id;
                          
                        }
                        //check if already recruiter or not
                        $isAlreadyRecruiter                 =       Recruiter::where(['dater_id'=>$daterId,'recruiter_id'=>$recruiterId])->count();
    
                        if($isAlreadyRecruiter>0){
    
                            return $this->sendResponsewithoutData(trans('message.already_in_list'), 400);
    
                        }else{
    
                            $addRecruiter                       =       new Recruiter();
                            $addRecruiter->dater_id             =       $daterId;
                            $addRecruiter->recruiter_id         =       $recruiterId;
                            $addRecruiter->recruiter_type       =       1;
                            $addRecruiter->save();
                            $recruiter                          =       $addRecruiter->id;
                            // send notification to dater 
    
                            $referral                           =        new Referral();
                            $referral->referrer_id              =        $referenceUser->id; // who made the refferal
                            $referral->referred_id              =        $user->id;         // who join the refferal
                            $referral->refered_on               =        Carbon::now();     // current utc date
                            $referral->type                     =        2; //1 referrel,2 invite
                            $referral->save();  
    
                            // ADD TO TEAM MEMBER DATABASE
    
                            $member                             =       User::where('id', $daterId)->first();
                            $myTeam                             =       new MyTeam();
                            $myTeam->recruiter_id               =       $recruiterId;
                            $myTeam->member_id                  =       $daterId;
                            $myTeam->team_name                  =       (isset($member) && !empty($member)) ? Str::plural($member->name) . " Team" : "Roster's user Team" ;
                            $myTeam->team_type                  =       1;
                            $myTeam->save();
    
                            // send notification to recruiter for joining the team 
    
                            $reciever                           =       User::select('id','current_role_id', 'device_token', 'device_type')->where("id", $recruiterId)->first();
                            $sender                             =       User::select('id','current_role_id', 'device_token', 'device_type')->where("id", $daterId)->first();
                            $notification_type                  =       trans('notification_message.new_member_added_in_team');
                            $notification_message               =       trans('notification_message.new_match_found');
                            $this->notification->sendNotification(2,$reciever,$sender,$notification_message,$notification_type);
                            DB::commit();
                            return $this->sendResponsewithoutData(trans('message.added_successfully'), 200);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "addInvitedFriend" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #----------------------------------------- E N D  --------------------------------------------#


}
