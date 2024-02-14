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
        if(isset($user) && !empty($user)){
            $userDetail = User::where('id',$userId);
            if($user->current_role_id==2){  // dater

                $userDetail  = $userDetail->with(['userPreferences','user_states']);

            }elseif ($user->current_role_id==3) {  
        

            }
            $userDetail =   $userDetail->first();
            //dd($userDetail);
            if( $user->current_role_id=="2" && isset($userDetail->user_states[0]) ){
                //$user_stat =   $userDetail->user_states;
                
                foreach ($userDetail->user_states as $key => $statistic) {
                    $stat   =   Stat::where('id',$statistic->stat_id)->first();

                    if(isset($stat) && !empty($stat)){

                        $userDetail->user_states[$key]['question'] = $stat->question;
                        $userDetail->user_states[$key]['min_value'] = $stat->min_value;
                        $userDetail->user_states[$key]['max_value'] = $stat->max_value;
                    }
                }
            }
            return $userDetail;
        }
    }
}
