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

        if(isset($userDetail) && !empty($userDetail)){
            // Create a Passport token for the user
            $passport_token = $userDetail->createToken(env('PASSPORT_SECURITY_TOKEN'))->accessToken;
            // Update the user's token field with the generated Passport token
            $userDetail->token = $passport_token;
        }
        return $userDetail;
    }

    public function getUser($userId){
        $userDetail = User::where('id', $userId)->first();
        
        if(isset($userDetail) && !empty($userDetail)){

            if(isset($userDetail->profile) && !empty($userDetail->profile)){
                
                $userDetail->profile =   asset('storage/'.$userDetail->profile);
                
            }

            if(isset($userDetail->cover) && !empty($userDetail->cover)){
                
                $userDetail->cover =   asset('storage/'.$userDetail->cover);
            }
        }
        return $userDetail;
    }
    
}
