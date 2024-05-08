<?php
namespace App\Traits;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait IsCommunityJoined {

    public function checkCommunityJoind($community_id) {

        $userId           =   Auth::id();
    
        $isExist          = GroupMember::where(['id'=>$community_id,'user_id'=>$userId,'is_active'=>1])->exists();
        return ($isExist)?1:0;
        
    }
}