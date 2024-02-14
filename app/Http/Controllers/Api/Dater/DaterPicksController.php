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
                if($authUser->current_role_id==3){          // Dater

                    
                    
                    
                }else{

                    $limit          =              10;
                    if(isset($request->limit) && !empty($request->limit)){
    
                        $limit      =              $request->limit;
                    }
                }

                if($request->type == 1 || $request->type == 2){


                    $this->homeAwayPicks($request,$limit);

                    $myPicker       =             MyTeamMember::where(['member_id'=>$authUser->id,'recruiter_type'=>$request->type,'is_active'=>1]);

                }elseif ($request->type == 3) {

                    $myPicker       =             MyTeamMember::where(['member_id'=>$authUser->id,'is_active'=>1])->where(function($query) use ($request) {

                        $query->whereIn('recruiter_type',[2,3]);
                    });

                }elseif ($request->type == 3) {





                }
                $myPicker           =             MyTeamMember::where(['member_id'=>$authUser->id,'recruiter_type'=>$request->type,'is_active'=>1]);
                $typeMessages = [
                    1 => trans('message.home_picks'),    // A W A Y      P I C K S
                    2 => trans('message.away_picks'),    // H O M E      P I C K S
                    3 => trans('message.away_picks')     // M Y      R O S T E R
                ];
                $message            =           $typeMessages[$request->type] ?? '';
            }
            
        } catch (Exception $e) {
            
            Log::error('Error caught: "datersPicks" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }        

    }

    #---------------------------------------- E N D -----------------------------------------#



    #--------------- GET HOME AND AWAY PICKS -----------------------#
    public function homeAwayPicks($request,$limit){

        $type           =               $request->type;
        $authUser       =               Auth::user();  
        $myPicker       =               MyTeamMember::where(['member_id'=>$authUser->id,'is_active'=>1]);
        if($type==1){
            $myPicker   =               $myPicker->where(['recruiter_type'=>$request->type]);
        }elseif ($type==2) {
            $myPicker   =               $myPicker->where(function($query) use ($request) {
                $query->whereIn('recruiter_type',[2,3]);
            });
        }
        return $myPicker->simplePaginate($limit);
    }
    #--------------- GET HOME AND AWAY PICKS -----------------------#


    #-----------------------  M Y   R O S T E R  -----------------#
    public function myRoster($request, $limit){

        $authUser       =               Auth::user();  
        $myRoster       =               MyRoster::where(['user_id'=>$authUser->id,'is_active'=> 1])->with(['roster'])->simplePaginate($limit);
    }
    #------------------------   E N D   --------------------------# 
}
