<?php

use App\Models\BlockedUser;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\Comment;
use App\Models\PostLike;
use Illuminate\Support\Facades\Storage;

if (!function_exists('transformParentPostData')) {

    function transformParentPostData($post, $authId)
    {
        #------------ CHECK IF PARENT POST PRESENT -------------#
        if ($post->parent_post->post_user && $post->parent_post->post_user->profile) {

            $post->parent_post->post_user->profile =    addBaseUrl($post->parent_post->post_user->profile);
        }

        if (isset($post->parent_post->media_url) && !empty($post->parent_post->media_url)) {

            $post->parent_post->media_url        =       addBaseUrl($post->parent_post->media_url);
        }

        if (isset($post->parent_post->thumbnail) && !empty($post->parent_post->thumbnail)) {

            $post->parent_post->thumbnail        =       addBaseUrl($post->parent_post->thumbnail);
        }

        $isExist                                 =       IsPostLikedByUser($post->parent_post->id, $authId, 1);
        $post->parent_post->is_liked             =       $isExist['is_liked'];
        $post->parent_post->reaction             =       $isExist['reaction'];
        $post->parent_post->total_likes_count    =       $isExist['total_likes_count'];
        $post->parent_post->total_comment_count  =       $isExist['total_comment_count'];
        $isRepost                                =       Post::where(['parent_id' => $post->parent_post->id, 'user_id' => $authId, 'is_active' => 1])->exists();
        $post->parent_post->is_reposted          =       ($isRepost) ? 1 : 0;
        $post->parent_post->postedAt             =      time_elapsed_string($post->created_at);
        $post->parent_post->post_category_name   =      post_category($post->parent_post->post_category);
        return $post;
    }
}


if (!function_exists('transformPostData')) {

    function transformPostData($homeScreenPost, $authId)
    {
        if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

            $homeScreenPost->media_url      =  addBaseUrl($homeScreenPost->media_url);
        }

        if (isset($homeScreenPost->thumbnail) && !empty($homeScreenPost->thumbnail)) {

            $homeScreenPost->thumbnail      =  addBaseUrl($homeScreenPost->thumbnail);
        }

        if ($homeScreenPost->parent_post && $homeScreenPost->parent_post->post_user && $homeScreenPost->parent_post->post_user->profile) {

            $homeScreenPost->parent_post->post_user->profile = addBaseUrl($homeScreenPost->parent_post->post_user->profile);
        }

        if (isset($homeScreenPost->post_user) &&  !empty($homeScreenPost->post_user->profile)) {

            $homeScreenPost->post_user->profile      =  addBaseUrl($homeScreenPost->post_user->profile);
        }
        if ($homeScreenPost->group &&  $homeScreenPost->group->cover_photo) {

            $homeScreenPost->group->cover_photo      =  addBaseUrl($homeScreenPost->group->cover_photo);
        }
        $isExist                            =   IsPostLikedByUser($homeScreenPost->id, $authId,1);
        $homeScreenPost->is_liked           =   $isExist['is_liked'];
        $homeScreenPost->reaction           =   $isExist['reaction'];

        $homeScreenPost->total_likes_count  =       $isExist['total_likes_count'];
        $homeScreenPost->total_comment_count =       $isExist['total_comment_count'];
        $isRepost                           =   Post::where(['parent_id' => $homeScreenPost->id, 'user_id' => $authId, 'is_active' => 1])->exists();
        $homeScreenPost->is_reposted        =  ($isRepost) ? 1 : 0;
        $homeScreenPost->post_category_name = post_category($homeScreenPost->post_category);
        $homeScreenPost->postedAt           =   time_elapsed_string($homeScreenPost->created_at);
        #------------ parent post data-----------------#
        return $homeScreenPost;
    }
}

if (!function_exists('addBaseUrl')) {

    function addBaseUrl($cover_photo)
    {

        if (isset($cover_photo) && !empty($cover_photo)) {

            return (filter_var($cover_photo, FILTER_VALIDATE_URL)) ? $cover_photo : asset('storage/' . $cover_photo);
        } else {

            return null;
        }
    }
}

if (!function_exists('IsPostLikedByUser')) {
    function IsPostLikedByUser($postId, $authId, $type = "")
    {

        $isExist                    =   PostLike::where(['user_id' => $authId, 'post_id' => $postId])->first();
        $data['is_liked']           =   (isset($isExist) && !empty($isExist)) ? 1 : 0;
        $data['reaction']           =   (isset($isExist->reaction) && !empty($isExist->reaction)) ? $isExist->reaction : 0;
        $data['total_likes_count']  =   PostLike::where(['post_id' => $postId])->count();
        if (!empty($type)) {

            $data['total_comment_count']  =   Comment::where(['post_id' => $postId])->count();
        }
        $isRepost                  =   Post::where(['parent_id' => $postId, 'user_id' => $authId, 'is_active' => 1])->exists();
        $data['is_reposted']       =    ($isRepost) ? 1 : 0;
        return $data;
    }
}


if (!function_exists('IsPostAvailable')) {

    function IsPostAvailable($postId, $authId)
    {


        return Post::where(function ($query) use ($authId) {

            $query->whereDoesntHave('post_user.blockedBy', function ($query) use ($authId) {
                $query->where('user_id', $authId);
            })
                ->whereDoesntHave('post_user.blockedUsers', function ($query) use ($authId) {
                    $query->where('blocked_user_id', $authId);
                });


            $query->whereDoesntHave('group.groupOwner.blockedBy', function ($query) use ($authId) {
                $query->where('user_id', $authId);
            })
                ->whereDoesntHave('group.groupOwner.blockedUsers', function ($query) use ($authId) {
                    $query->where('blocked_user_id', $authId);
                });
        })->where(['id' => $postId, 'is_active' => 1])->first();
    }
}

if (!function_exists('IsUserBlocked')) {

    function IsUserBlocked($user_id, $authId)
    {

        return  User::where(function ($query) use ($authId) {
            $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {
                $query->where('user_id', $authId);
            })
                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
                    $query->where('blocked_user_id', $authId);
                });
        })
            ->where(['id' => $user_id, 'is_active' => 1])->first();
    }
}



if (!function_exists('IsCommunityOwnerBlocked')) {

    function IsCommunityOwnerBlocked($communityId, $authId)
    {
        return  Group::where(['id'=>$communityId,'is_active'=>1])

            ->whereHas('groupOwner', function ($query) use ($authId) {

                $query->where('is_active', 1) // Check if group owner is active

                ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                    $query->where('user_id', $authId);

                })
                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                    $query->where('blocked_user_id', $authId);

                });
            })->exists();
    }
}


if (!function_exists('checkUserNameAvailable')) {
    function checkUserNameAvailable($username, $authId)
    {
        return User::where('id','<>',$authId)->where('user_name',$username)->exists();

    }
}



if (!function_exists('removePictureFromFolder')) {
    function removePictureFromFolder($image)
    {
        if (isset($image) && !empty($image)) {
                            
            if (Storage::disk('public')->exists($image)) {

                Storage::disk('public')->delete($image); // delete file from specific disk e.g; s3, local etc

            }
        }
    }
}

if (!function_exists('isBlockedUser')) {
    function isBlockedUser($user1,$user2)
    {
        $isBlock    = BlockedUser::where(['user_id'=>$user1,'blocked_user_id'=>$user2])->exists();
        return ($isBlock)?1:0;
    }
}








