<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController;

use Illuminate\Http\Request;
use App\Models\UserStat;
use App\Models\Stat;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddUserStaticsValidation;
class StatsController extends BaseController
{
    //

  
    protected $user, $userProfile, $authId, $getUser;
    // protected $userProfile;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });
        $this->authId = Auth::id();
    }

    #--------------  G E T      A L L       T H E      S T A T I S T I C S --------------------#
    public function Statistics(Request $request){

        try {
            $authId       =       Auth::id();
            $statistics         =       Stat::with('userStats')->where(['is_active'=>1])->get();

            if(isset($statistics) && !empty($statistics)){

                foreach ($statistics as $key => $statistic) {
                    
                    $isExist    =   UserStat::where(['user_id'=>$authId,'stat_id'=>$statistic->id,'is_active'=>1])->count();
                    $statistics[$key]['is_selected']= ($isExist>0)?1:0;
                }
            }
            return $this->sendResponse($statistics, trans("message.statistics"), 200);

        } catch (Exception $e) {

            Log::error('Error caught: "StatsController/Statistics" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);

        }
    }
    #----------------------------  ***********   E N D   ************-------------------------#

    #--------------------------  A D D      U S E R     S T A T I S T I C S  -----------------#
    public function addStatistics(AddUserStaticsValidation $request){


        
    }

    #-------------------------------------      E N D    -------------------------------------#






}
