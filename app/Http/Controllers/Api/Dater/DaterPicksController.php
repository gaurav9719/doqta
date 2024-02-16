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

                $authUser       =       Auth::user();   
                $limit          =              10;
                if(isset($request->limit) && !empty($request->limit)){

                    $limit      =              $request->limit;
                }
              
                if($authUser->current_role_id==2){          // Dater

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
}
