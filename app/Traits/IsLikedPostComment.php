<?php
namespace App\Traits;

use App\Models\Post;
use App\Models\Comment;
use App\Models\PostLike;
use App\Models\CommentLike;

trait IsLikedPostComment
{
    
    public function IsCommentLiked($postId,$commentId,$authId){

        $isExist            =   CommentLike::where(['user_id' => $authId, 'post_id' => $postId, 'comment_id' => $commentId])->first();
        $data['is_liked']   =   (isset($isExist) && !empty($isExist)) ? 1 : 0;
        $data['reaction']   =   (isset($isExist) && !empty($isExist)) ? $isExist->reaction : 0;
        $data['total_likes_count']  =   CommentLike::where(['comment_id' => $commentId])->count();

        return $data;
    }

    public function IsPostLiked($postId,$authId,$type=""){

        $isExist                    =   PostLike::where(['user_id' => $authId, 'post_id' => $postId])->first();
        $data['is_liked']           =   (isset($isExist) && !empty($isExist)) ? 1 : 0;
        $data['reaction']           =   (isset($isExist->reaction) && !empty($isExist->reaction)) ? $isExist->reaction : 0;
        $data['total_likes_count']  =   PostLike::where(['post_id' => $postId])->count();
        if(!empty($type)){

            $data['total_comment_count']  =   Comment::where(['post_id' => $postId])->count();
        }
        $isRepost                  =   Post::where(['parent_id'=>$postId,'user_id'=>$authId,'is_active'=>1])->exists();
        $data['is_reposted']       =    ($isRepost)?1:0;
        return $data;
    }





    








}
