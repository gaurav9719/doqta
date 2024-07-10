<?php
namespace App\Traits;

use App\Models\GroupMember;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Group;
use App\Jobs\FeedPostNotification as feedPostionJob;
use Illuminate\Support\Facades\Log;

trait FeedPostNotification
{
    

    public function feedPostNotification($group_id,$post_id,$sender){

        $data['group_id']           =   $group_id;
        $data['post_id']            =   $post_id;
        $data['sender']             =   $sender;
        Log::info("trait FeedPostNotification");
        dispatch(new feedPostionJob($data));
        
    }



}
