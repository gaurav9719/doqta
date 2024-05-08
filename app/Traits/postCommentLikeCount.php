<?php
namespace App\Traits;

use App\Models\CommentLike;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait postCommentLikeCount {

    public function postLikeCount($post_id) {

        $userId                 =       Auth::id();
        $postLike['total_likes_count']           =       PostLike::where(['post_id'=>$post_id])->count();
        $hasLiked               =       PostLike::where(['post_id'=>$post_id,'user_id'=>$userId])->first();
        $postLike['is_liked']   =       (isset($hasLiked) && !empty($hasLiked))?1:0;
        $postLike['reaction']   =       (isset($hasLiked) && !empty($hasLiked))?$hasLiked->reaction:0;
        return $postLike;
        
    }

    public function commentLikeCount($comment_id) {

        $userId                         =       Auth::id();
        $postLike['total_likes_count']  =       CommentLike::where(['comment_id'=>$comment_id])->count();
        $hasLiked                       =       CommentLike::where(['comment_id'=>$comment_id,'user_id'=>$userId])->first();
        $postLike['is_liked']           =       (isset($hasLiked) && !empty($hasLiked))?1:0;
        $postLike['reaction']           =       (isset($hasLiked) && !empty($hasLiked))?$hasLiked->reaction:0;
        return $postLike;
        
    }
    public function addBaseInImage($cover_photo){
        
        return (filter_var($cover_photo, FILTER_VALIDATE_URL))? $cover_photo : asset('storage/'.$cover_photo);
    }

}