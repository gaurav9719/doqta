<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\UserRegister;
use App\Http\Requests\LoginUser;
use App\Models\User;
use App\Models\UserDevice;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\RegisterUserService;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\MyTeam;
class LeaderBoard extends BaseController
{
    //

    #---------------------- G E T       L E A D E R     B O A R D --------------------------#
    public function leaderBoard(Request $request){
        
        try {

            $now            =   Carbon::now();
            $weekStartDate  =   $now->startOfWeek()->format('Y-m-d');
            $weekEndDate    =   $now->endOfWeek()->format('Y-m-d');
            $authUser       =   Auth::user();
            $myTeam         =   MyTeam::with(['team' => function ($query) {

                $query->select('id', 'name', 'user_name', 'profile_pic');
                
            }])
                ->whereHas('team', function ($query) {
                    $query->where('is_active', 1);
                })
                ->where([
                    "recruiter_id" => $authUser->id,
                    'is_active' => 1
                ])
                ->select(['*', DB::raw('(SELECT SUM(points) FROM point_histories WHERE user_id = my_teams.member_id AND DATE(created_at) BETWEEN ? AND ?) AS total_points_this_week')])
                ->setBindings([$weekStartDate, $weekEndDate])
                ->orderByDesc('total_points_this_week')
                ->get();

                




        } catch (Exception $e) {

            Log::error('Error caught: "leaderBoard" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
        
      

    }
    #------------------------------------ E N D  -------------------------------------------#
}
