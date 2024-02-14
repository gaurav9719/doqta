<?php

namespace App\Services;
use App\Models\User;
use App\Models\Stat;
use App\Models\UserStat;
/**
 * Class GetUserService.
 */
class GetUserService
{


    public function getAuthUser($userId){

        // $userDetail =   User::where($userId);
        $userDetail =   $this->getUser($userId);
        // Create a Passport token for the user
        $passport_token = $userDetail->createToken(env('PASSPORT_SECURITY_TOKEN'))->accessToken;
        // Update the user's token field with the generated Passport token
        $userDetail->token = $passport_token;
        return $userDetail;

    }

    public function getUser($userId){

        $user       = User::select('current_role_id')->where('id',$userId)->first();

        if(isset($user)){

            $userDetail = User::where('id',$userId);

            if($user->current_role_id==2){  // dater
                // $userDetail  = $userDetail->with(['userPreferences'=>function($user){
                //     return $user->select('distance','age','gender','ghost_coach');
                // }]);
                // $userDetail  = $userDetail->with(['userPreferences','userStats.statistic']);
                $userDetail  = $userDetail->with(['userPreferences','userStats']);
            }elseif ($user->current_role_id==3) {  
        



            }
            $userDetail =   $userDetail->first();

            if(isset($userDetail) && !empty($userDetail)){
            
                // dd($userDetail);
                if(isset($userDetail->userStats) && !empty($userDetail->userStats)){

                    $user_stat =   $userDetail->userStats;

                    foreach ($user_stat as $key => $statistic) {
                       
                        $stat   =   Stat::where('id',$statistic->stat_id)->first();

                        if(isset($stat) && !empty($stat)){

                            $user_stat[$key]['question'] = $stat->question;
                            $user_stat[$key]['min_value'] = $stat->min_value;
                            $user_stat[$key]['max_value'] = $stat->max_value;

                        }
                    }
                }
            }
            return $userDetail;
        }  
    }
}
