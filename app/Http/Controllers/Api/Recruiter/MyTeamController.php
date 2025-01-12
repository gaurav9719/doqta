<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recruiter;
use Illuminate\Support\Facades\DB;
use Exception;  
use App\Http\Controllers\Api\BaseController;
use App\Models\MyTeam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class MyTeamController extends BaseController
{
    //
    #-----------------------  G E T       M Y     T E A M    -------------------------#
    public function myTeam(Request $request){

        try {
            
            $authUser   =   Auth::user();

            if($authUser->current_role_id==3){          #------ recruiter ----- #

                $myTeam     =   MyTeam::with(['team'=>function($query){

                    $query->select('id','name','user_name','profile_pic');
    
                },'team.portfolio','team.userPreferences','team.statistics'])->whereHas('team',function ($query) {
    
                    $query->where('is_active',1);
    
                })->where(["recruiter_id"=>$authUser->id,'is_active'=>1])->get();

                if(isset($myTeam[0]) && !empty($myTeam[0])){

                    foreach ($myTeam as $team) {
                        // Update image URL in portfolio
                        $team->team->portfolio->each(function ($userImages) {

                            if ($userImages->image) {

                                $userImages->image = asset('storage/' . $userImages->image);

                            }
                        });

                        $team->team->each(function ($teamProfile) {

                            if ($teamProfile->profile_pic) {
                                $teamProfile->profile_pic = asset('storage/' . $teamProfile->profile_pic);
                            }
                        });
                    }
                }
                return $this->sendResponse($myTeam,trans('message.my_team'), 200);

            }else{

                return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 403);
            }

        } catch (Exception $e) {
            
            Log::error('Error caught: "myTeam" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-----------------------  G E T       M Y     T E A M    -------------------------#

}
