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

class InvitesContact extends BaseController
{
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
                    //check the auth user current role
                    if($role==3){                     // recruiter send the request to dater

                        $daterId                        =       $user->id;
                        $recruiterId                    =       $referenceUser->id;
                     
                    }elseif($role==2) {               // dater send the request to recruiter   

                        $daterId                        =       $referenceUser->id;
                        $recruiterId                    =       $user->id;
                      
                    }
                    //check if already recruiter or not
                    $isAlreadyRecruiter                 =       Recruiter::where(['dater_id'=>$daterId,'recruiter_id'=>$recruiterId,'status'=>1])->count();

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
                        DB::commit();
                        return $this->sendResponsewithoutData(trans('message.added_successfully'), 200);
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
