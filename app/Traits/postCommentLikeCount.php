<?php
namespace App\Traits;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait postCommentLikeCount {

    public function postLikeCount($post_id) {

        $userId                 =       Auth::id();
        $postLike['total_likes_count']           =       PostLike::where(['post'=>$post_id])->count();
        $hasLiked               =       PostLike::where(['post'=>$post_id,'user_id'=>$userId])->first();
        $postLike['is_liked']   =       (isset($hasLiked) && !empty($hasLiked))?1:0;
        $postLike['reaction']   =       (isset($hasLiked) && !empty($hasLiked))?$hasLiked->reaction:0;



        $isExist          =     GroupMember::where(['id'=>$community_id,'user_id'=>$userId,'is_active'=>1])->exists();
        return ($isExist)?1:0;
        
    }




}