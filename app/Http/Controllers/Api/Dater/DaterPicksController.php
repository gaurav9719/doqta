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
use App\Services\Dater\AddToRosterBench;
use App\Services\GetUserService;
class DaterPicksController extends BaseController
{
    //

    protected $user, $addToRosterBench, $authId, $getUser;
    // protected $userProfile;
    public function __construct(AddToRosterBench $addToRosterBench, GetUserService $getUser)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });

        $this->addToRosterBench = $addToRosterBench;
        $this->getUser = $getUser;
    }

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

                return $this->addToRosterBench->addToRosterBench($request);

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

   

    public function homePicks($request, $authUser,$type) {
       
        $randomUsers        =   $this->showRandomUser($request,$authUser,$type);

        if(empty($randomUsers) || $randomUsers==null) {

            $randomUsers        =   $this->showRandomUser($request,$authUser,$type,1);

        }
        $message                =   ($type==1)?trans("message.home_picks"):trans("message.away_picks");
        return $this->sendResponse($randomUsers, $message, 200);
    }

    public function showRandomUser($request,$authUser,$type,$random_type=''){

        try {

            // dd($authUser->id);
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
                }, 'member.portfolio']);

                if(empty($random_type) || $random_type== ''){
                    $myPicker = $myPicker->whereDoesntHave('user_swipes', function ($subquery) use ($authUser) {
                    $subquery->where('swiping_user_id', $authUser->id)
                        ->whereColumn('swiped_user_id', 'my_team_members.dater_id')
                        ->where('role_id', $authUser->current_role_id);
                        // ->where('swipe_type',1); // show only bench record 
                    });
                }else{

                    $myPicker = $myPicker->whereHas('user_swipes', function ($subquery) use ($authUser) {
                        $subquery->where('swiping_user_id', $authUser->id)
                            ->whereColumn('swiped_user_id', 'my_team_members.dater_id')
                            ->where('role_id', $authUser->current_role_id)
                            ->where('swipe_type',0); // show only bench record 
                        });
                }
                
                $myPicker      =      $myPicker->inRandomOrder()->first();

            if ($myPicker) {

                $myPicker->age =     Carbon::parse($myPicker->member->dob)->age;
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
    
           
    
        } catch (Exception $e) {
            Log::error('Error caught: "homePicks" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
