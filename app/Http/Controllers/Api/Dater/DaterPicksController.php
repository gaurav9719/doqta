<?php

namespace App\Http\Controllers\Api\Dater;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recruiter;
use Illuminate\Support\Facades\DB;
use Exception;  
use App\Http\Controllers\Api\BaseController;
use App\Models\MyTeam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\MyTeamMember;
use App\Models\MyRoster;
use carbon\Carbon;
use App\Models\User;
use App\Models\Stat;
class DaterPicksController extends BaseController
{
    //

    #-----------  G E T     H O M E / A W A Y / M Y  R O S T E R     P I C K S --------------#

 

    public function datersPicks(Request $request) {

        try {
            
            $validator      = Validator::make($request->all(), ['type'=>'required|integer|between:1,3'],['between'=>"Invalid type"]);

            if ($validator->fails()) {  

                return response()->json([
                    'success'   => 422,
                    'message'   => $validator->errors()->first(),
                ],422);

            }else{

                $authUser       =               Auth::user();   
                $limit          =              10;
                if(isset($request->limit) && !empty($request->limit)){

                    $limit      =              $request->limit;
                }
              
                if($authUser->current_role_id==2){              // Dater

                    if($request->type == 1 || $request->type == 2){ #-------- H O M E     P I C K S (1: INVITED FRIENDS) ----------#
                  

                        return $this->homeAwayPicks($request,$limit);

                    }elseif ($request->type == 3) { #--------- AWAY PICKS (2:GHOST COACH AND 3:ROSTER AI)
    
                        $myPicker       =             MyTeamMember::where(['member_id'=>$authUser->id,'is_active'=>1])->whereIn('recruiter_type',[2,3])->simplePaginate($limit);
                        
                    }
                    $typeMessages = [
                        1 => trans('message.home_picks'),    // A W A Y      P I C K S
                        2 => trans('message.away_picks'),    // H O M E      P I C K S
                        3 => trans('message.away_picks')     // M Y      R O S T E R
                    ];
                    $message            =           $typeMessages[$request->type] ?? ''; 
                    
                    
                }else{

                    
                }

               
            }
            
        } catch (Exception $e) {
            
            Log::error('Error caught: "datersPicks" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }        

    }

    #---------------------------------------- E N D -----------------------------------------#



    #--------------- GET HOME AND AWAY PICKS -----------------------#
    public function homeAwayPicks($request,$limit){


        $type = $request->type;
        $authUser = Auth::user();
    
        $myPicker = MyTeamMember::where('member_id', $authUser->id)
            ->where('is_active', 1);
        
        if ($type == 1) {
            $myPicker->where('recruiter_type', $request->type);
        } elseif ($type == 2) {
            $myPicker->whereIn('recruiter_type', [2, 3]);
        }
        
        $myPicker = $myPicker->with(['member' => function($query) {

            $query->select('id', 'name', 'email', 'dob', 'country_code', 'phone_no', 'gender', 'profile_pic');

        },'member.statistics'])->simplePaginate($limit);
        

        $myPicker->each(function ($picker) {

            //add recruited by
            $recruitedBy    =   "Recruited by ";
            if ($picker->recruiter_type==2) {
               
                $recruitedBy.="Ghost Coach";

            }elseif ($picker->recruiter_type==3) {
               
                $recruitedBy.="Roster AI Coach";
            }
            
            if($picker->recruiter_type==1){

                $recruiter      =   MyTeam::select('recruiter_id')->where('id', $picker->team_id)->first();
                if(isset($recruiter) && !empty($recruiter)){
    
                    $recruited  =   User::select('name')->where('id', $recruiter->recruiter_id)->first();
                    if(isset($recruited) && !empty($recruited)){
    
                        $recruitedBy.=$recruited->name;
    
                    }
                }
            }
            $picker->recruited_by = $recruitedBy;



            if ($picker->member) {
                $picker->member->age = Carbon::parse($picker->member->dob)->age;
            }

        });
        // dd($myPicker);
        return $this->sendError($myPicker, [], 400);
    }
    #--------------- GET HOME AND AWAY PICKS -----------------------#


    #-----------------------  M Y   R O S T E R  -----------------#
    public function myRoster($request, $limit){

        $authUser       =               Auth::user();  
        $myRoster       =               MyRoster::where(['user_id'=>$authUser->id,'is_active'=> 1])->with(['roster'])->simplePaginate($limit);
        
        
    }
    #------------------------   E N D   --------------------------# 


    #----------------- U S E R      P I C K S -------------------------#

    public function datersPick(Request $request) {
        try {
            
            $authUser   = Auth::user();
            if ($request->isMethod('post')) {        #------------- U P D A T E       U S E R  ROSTER/BENCH  ------------#
                $validator = Validator::make($request->all(), [
                    'type' => 'required|integer|between:0,1',
                    'user_id' => 'required|exists:users,id',
                    'team_id' => 'required_if:type,==,1|array',
                    'team_id.*' => 'required_if:type,==,1|integer|exists:my_teams,id',
                ],['team_id.required_if'=>"Team id required",'team_id.*.exists'=>"invalid team id",'team_id.*.integer'=>"invalid team id"]);

                if ($validator->fails()) {
                    // If validation fails, return error response
                    return $this->sendResponsewithoutData(validationErrorsToString($validator->errors()), 422);
    
                } else {
                    // If validation passes, proceed with the operation
                   
                    $type       = $request->type;
                    if ($type == 1) {       // Adding to team member
    
                        return $this->addToMember($request,$authUser);
    
                    } elseif ($type == 0) { // Adding to bench
                        
                        return $this->addToBench($request,$authUser);
                    }
                }
            }
            if ($request->isMethod('get')) {    #------------- G E T    D A T E R       P I C K S  ------------#

                return $this->userPicks($request, $authUser);

            }
            
        } catch (Exception $e) {
            
            Log::error('Error caught: "datersPicks" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }        

    }
    public function userPicks($request,$authUser){

        $validator = Validator::make($request->all(), [
            'pick_type' => 'required|integer|between:1,2'],['between'=>"Invalid picks type"]);

        if ($validator->fails()) {
            // If validation fails, return error response
            return $this->sendResponsewithoutData(validationErrorsToString($validator->errors()), 422);

        } else {

            if($request->pick_type == 1){           #--------- H O M E    P I C K S (INVITE FRIEND)

                return $this->homePicks($request,$authUser,1);

            }
            elseif ($request->pick_type == 2) {     #--------------- A W A Y    P I C K S (GHOST COACH/ROSTER AI)

                return $this->homePicks($request,$authUser,2);
                

            }
        }
    }

    #------------  H O M E      P I C K S -------------#
    // public function homePicks($request, $authUser){

    //     try {

    //         $myPicker           =   MyTeamMember::where('member_id', $authUser->id)

    //         ->where('is_active', 1)->where('recruiter_type', 1)->with(['member' => function($query) {

    //             $query->select('id', 'name', 'email', 'dob', 'country_code', 'phone_no', 'gender', 'profile_pic','dob');

    //         },'member.portfolio'])->whereNotExists(function ($subquery) use ($authUser) {
    //             $subquery->select(DB::raw(1))
    //                 ->from('user_swipes')
    //                 ->whereRaw("swiping_user_id = '" . $authUser->id . "' AND swiped_user_id = my_team_members.dater_id AND role_id = '".$authUser->current_role_id."'");
    //         })->first();

    //         if (isset($myPicker) && !empty($myPicker)) {
    //             // Update image URL in portfolio
    //             if(isset($myPicker->member) && !empty($myPicker->member)) {
    //                 $myPicker->age = Carbon::parse($myPicker->member->dob)->age;

    //                 $myPicker->member->portfolio->each(function ($profile) {

    //                     if ($profile->image) {
    //                         $profile->image = asset('storage/' . $profile->image);
    //                     }
    //                 });
    
    //                 $myPicker->member->user_states->each(function ($userStats) {
    //                     if ($userStats->id) {
    //                         $stat                = Stat::find($userStats->id);
    //                         $userStats->question = $stat->question ?? null;
    //                         $userStats->min_value = $stat->min_value ?? 0;
    //                         $userStats->max_value = $stat->max_value ?? 0;
    //                     }
    //                 });
    //                 // Calculate user's age
    //                 if(isset($myPicker->profile_pic) && !empty($myPicker->profile_pic)){
    
    //                     $myPicker->profile_pic = asset('storage/' . $myPicker->profile_pic);
    //                 }
                    
    //                 if(isset($myPicker->member->profile_pic) && !empty($myPicker->member->profile_pic)){

    //                     $myPicker->member->profile_pic = asset('storage/' . $myPicker->member->profile_pic);
    //                 }
    //             }
    //         }
    //         return $this->sendResponse($myPicker, trans("message.home_picks"), 200);

    //     } catch (Exception $e) {

    //         Log::error('Error caught: "homePicks" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);
    //     }
    // }
    #-------------- ***** E N D ****** ----------------#

    public function homePicks($request, $authUser,$type) {
        try {

            $myPicker = MyTeamMember::where('member_id', $authUser->id)->where('is_active', 1);

                if($type==1){

                    $myPicker= $myPicker->where('recruiter_type', 1);
                    $message =trans("message.home_picks");

                }else{
                    $message =  trans("message.away_picks");
                    $myPicker=$myPicker->whereIn('recruiter_type', [2, 3]);
                    
                }
                $myPicker = $myPicker->with(['member' => function ($query) {
                    $query->select('id', 'name', 'email', 'dob', 'country_code', 'phone_no', 'gender', 'profile_pic');
                }, 'member.portfolio'])
                ->whereDoesntHave('user_swipes', function ($subquery) use ($authUser) {
                    $subquery->where('swiping_user_id', $authUser->id)
                        ->whereColumn('swiped_user_id', 'my_team_members.dater_id')
                        ->where('role_id', $authUser->current_role_id);
                })->first();

               
            if ($myPicker) {
                $myPicker->age = Carbon::parse($myPicker->member->dob)->age;
    
                $myPicker->member->portfolio->each(function ($profile) {
                    if ($profile->image) {
                        $profile->image = asset('storage/' . $profile->image);
                    }
                });
    
                $myPicker->member->user_states->each(function ($userStats) {
                    $stat = Stat::find($userStats->id);
                    $userStats->question = $stat ? $stat->question : null;
                    $userStats->min_value = $stat ? $stat->min_value : 0;
                    $userStats->max_value = $stat ? $stat->max_value : 0;
                });
    
                if ($myPicker->profile_pic) {
                    $myPicker->profile_pic = asset('storage/' . $myPicker->profile_pic);
                }
    
                if ($myPicker->member && $myPicker->member->profile_pic) {
                    $myPicker->member->profile_pic = asset('storage/' . $myPicker->member->profile_pic);
                }
            }
    
            return $this->sendResponse($myPicker, $message, 200);
    
        } catch (Exception $e) {
            Log::error('Error caught: "homePicks" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
