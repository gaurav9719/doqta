<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use App\Models\Post;
use Carbon\Carbon;

/**
 * Class AddCommunityPost.
 */
class AddCommunityPost extends BaseController
{

    #*********-------     A D D        P O S T     ---------------********#
    public function addPost($request, $authId)
    {
        DB::beginTransaction();

        try {

            $post                    =      new Post();
            $post->user_id           =      $authId;
            $post->title             =      $request->title;
            $post->content           =      $request->content;
            if (isset($request->media_url) && !empty($request->media_url)) {

                $post->media_url     =      $request->media_url;
            }
            if (isset($request->group_id) && !empty($request->group_id)) {

                $post->group_id      =      $request->group_id;
            }

            if (isset($request->link) && !empty($request->link)) {

                $post->link      =      $request->link;
            }
            $post->post_type         =       $request->post_type;
            $post->post_category     =       $request->post_category;
            $post->save();
            DB::commit();
            return $this->getPost($post->id,trans("message.add_posted_successfully"));
        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "addPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #*******----------- A D D          P O S T  --------------***********#



    ##### ********* ------   E D I T      P O S T  ------ ******** ########

    public function editPost($request, $authId,$postId)
    {
        DB::beginTransaction();

        try {

            $editPost                    =     Post::find($postId);

            $editPost->user_id           =      $authId;

            if (isset($request->title) && !empty($request->title)) {

                $editPost->title     =      $request->title;
            }

            if (isset($request->content) && !empty($request->content)) {

                $editPost->content     =      $request->content;
            }
            
            if (isset($request->media_url) && !empty($request->media_url)) {

                $editPost->media_url     =      $request->media_url;
            }

            if (isset($request->group_id) && !empty($request->group_id)) {

                $editPost->group_id      =      $request->group_id;
            }

            if (isset($request->link) && !empty($request->link)) {

                $editPost->link         =      $request->link;
            }

            $editPost->post_type        =       $request->post_type;
            $editPost->post_category    =       $request->post_category;
            $editPost->save();
            DB::commit();
            return $this->getPost($postId,trans('message.update_post_successfully'));

        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "addPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    ##### ********* -------   E D I T      P O S T  ------ ******** ########


    










    #-------------  G E T   P O S T    B Y      I D  ------------------#

    // public function getPost($id)
    // {
    //     try {
    //         $post   =   Post::with('group_post', function ($group) {

    //             $group->select('name', 'description', 'cover_photo', 'member_count');
    //         }, 'post_user', function ($postUser) {

    //             $postUser->select('id', 'name', 'profile');
    //         })->where('id', $id)->first();

    //         if (isset($post) && !empty($post)) {

    //             if (isset($post->media_url) && !empty($post->media_url)) {

    //                 $post->media_url        =   asset('storage/' . $post->media_url);
    //             }

    //             if (isset($post->post_user) && !empty($post->post_user)) {

    //                 if (isset($post->post_user->profile) && !empty($post->post_user->profile)) {

    //                     $post->post_user->profile    =   asset('storage/' . $post->post_user->profile);
    //                 }
    //             }

    //             $post->postedAt            =   Carbon::parse($post->created_at)->diffForHumans();

    //             return $this->sendResponse($post, trans("message.add_posted_successfully"), 200);
    //         }
    //     } catch (Exception $e) {

    //         DB::rollback();
    //         Log::error('Error caught: "getPost" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);
    //     }
    // }

    public function getPost($id,$message)
    {
        try {
            $post = Post::with('group_post:name,description,cover_photo,member_count', 'post_user:id,name,profile')
                        ->find($id);

            if (!$post) {

                return $this->sendError('Post not found.', [], 404);
            }

            if ($post->media_url) {

                $post->media_url = asset('storage/' . $post->media_url);
            }

            if ($post->post_user && $post->post_user->profile) {

                $post->post_user->profile = asset('storage/' . $post->post_user->profile);
            }

            $post->postedAt = Carbon::parse($post->created_at)->diffForHumans();

            return $this->sendResponse($post, $message, 200);


            
        } catch (Exception $e) {

            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError('Error occurred while fetching post.', [], 500);
        }
    }

}
