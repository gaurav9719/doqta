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
use App\Models\User;
use App\Models\UserBench;
use App\Models\RecruiterBench;
use Carbon\Carbon;
class RecuitsController extends BaseController
{
    //
    #----------  G E T       R A N D O M        R E C R U I T  ------------------#
    public function recruits(Request $request){
        try {
           
            $authUser                              =      Auth::user();
            $userId                                =      Auth::id();
            $distance                              =      10;
            if(isset($request->distance) && !empty($request->distance)) {
            
                $distance                           =   $request->distance;
            }
            $limit=10;
            if(isset($request->limit) && !empty($request->limit)) {
            
                $limit                              =   $request->limit;
            }
            $randomUser                             =      User::select('*', DB::raw("round(3959 * acos(cos(radians('" . $authUser->lat . "'))* cos(radians(`lat`))* cos(radians(`long`)- radians('" . $authUser->long . "'))+ sin(radians('" . $authUser->lat . "'))* sin(radians(`lat`))),2) AS distance"))
           ->whereHas('user_roles', function ($query) {
                $query->where('role_id', 2);
            })
            ->where(['is_active' => 1])
            ->where("id", "<>", $authUser->id)
            ->whereNotExists(function ($subquery) use ($userId) {
                $subquery->select(DB::raw(1))
                    ->from('recruiter_benches')
                    ->whereRaw("user_id = '" . $userId . "' AND rejectd_user_id = id AND is_active = 1");
            })
            // ->whereExists(function ($query) {
            //     $query->select(DB::raw(1))
            //         ->from('user_stats')
            //         ->whereRaw('users.id = user_stats.user_id');
            // })
            ->with(['portfolio', 'user_stats'])
            ->having('distance', '<=', $distance)
            ->simplePaginate($limit);
            
            // if (isset($randomUser[0]) && !empty($randomUser[0])) {

            //     foreach ($randomUser as $key=>$user) {           // send request to ghost coach to join

            //         if(isset($user->portfolio[0]) && !empty($user->portfolio[0])){

            //             foreach ($user->portfolio as $key=> $profile) {
                    
            //                 if(isset($profile->image) && !empty($profile->image)){

            //                     $user->portfolio[$key]['image']= asset('storage/'.$profile->image);
            //                 }
            //             }
            //         }

            //         if(isset($user->dob) && !empty($user->dob)){

            //             $randomUser->age    = Carbon::parse($user->dob)->age;
            //         }
            //     }
            // }
            // return $this->sendResponse($randomUser, trans("message.random_user"), 200);

            if ($randomUser->isNotEmpty()) {
                foreach ($randomUser as $user) {
                    // Update image URL in portfolio
                    $user->portfolio->each(function ($profile) {
                        if ($profile->image) {
                            $profile->image = asset('storage/' . $profile->image);
                        }
                    });
                    // Calculate user's age
                    $user->age = Carbon::parse($user->dob)->age;
                }
            }
    
            // Return response
            return $this->sendResponse($randomUser, trans("message.random_user"), 200);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "recruits" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------------------- E N D ------------------------------------#



}
