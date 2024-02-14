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
class InvitesContact extends BaseController
{
    //
    #----------------------- A D D      I N V I T E D       F R I E N D --------------------------#
    public function addInvitedFriend(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), ['reference_code' => 'required|exists:users,reference_code','role_id'=>'required|interger|between:2,3']);
            if ($validator->fails()) {
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            } else {
    
                $role           = $request->role;
                $user           = Auth::user();
                $referenceUser  = User::where('reference_code', $request->reference_code)->first();
    
                if(isset($referenceUser) && !empty($referenceUser)) {
                    //check the auth user current role
                    $addRecruiter                       =       new Recruiter();
    
                    if($request->role==2){                     // recruiter send the request to dater
    
                        $addRecruiter->dater_id         =       $user->id;
                        $addRecruiter->recruiter_id     =       $referenceUser->id;
    
                    }elseif ($request->role==3) {               // dater send the request to recruiter   
                       
                        $addRecruiter->dater_id         =       $referenceUser->id;
                        $addRecruiter->recruiter_id     =       $user->id;
                    }

                    $addRecruiter->recruiter_type       =       1;
                    $addRecruiter->save();
                    // send notification to dater 
    
                    $referral                           =        new Referral();
                    $referral->referrer_id              =        $referenceUser->id; // who made the refferal
                    $referral->referred_id              =        $user->id;         // who join the refferal
                    $referral->refered_on               =        Carbon::now();     // current utc date
                    $referral->type                     =        2; //1 referrel,2 invite
                    $referral->save();  
                    DB::commit();
                    return $this->sendResponsewithoutData(trans('message.added_successfully'), 200);
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
