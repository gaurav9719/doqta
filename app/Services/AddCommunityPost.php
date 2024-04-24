<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use App\Models\Group;
use App\Models\Post;
use Carbon\Carbon;
use App\Models\Comment;
use App\Models\PostLike;
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

            if ($request->hasFile('media')) {

                if(empty($request->media_type)){

                    return $this->sendError("media_type required", [], 400);
                }
                $post_image          =       $request->file('media');
                $Uploaded            =       upload_file($post_image, 'post_images');
                $post->media_url     =       $Uploaded;
                $post->media_type     =       $request->media_type;
            }

            // if (isset($request->group_id) && !empty($request->group_id)) {

            //     $post->group_id      =      $request->group_id;
            // }

            if (isset($request->link) && !empty($request->link)) {

                $post->link      =      $request->link;

            }
            if (isset($request->wrote_by) && !empty($request->wrote_by)) {

                $post->wrote_by      =      $request->wrote_by;
            }


            $post->group_id          =       $request->community_id;
            $post->post_type         =       $request->post_type; //normal,community
            $post->post_category     =       $request->post_category; //1: seeing advice, 2: giving advice, 3: sharing media	
            $post->save();
            DB::commit();
            // add increment to group post
            increment('groups', ['id' => $request->community_id], 'post_count', 1); 
            // add increment to group post
            DB::commit();

            return $this->getCommunityAndPost($request->community_id, $authId,trans("message.add_posted_successfully"),$request);

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
            return $this->getPost($postId,$authId,trans('message.update_post_successfully'),);

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
            $post = Post::with(['group'=>function($query){

                $query->select('id','name','description','cover_photo','post_count');

            }, 'post_user:id,name,profile'])

                        ->find($id);

            if (!$post) {

                return $this->sendError('Post not found.', [], 404);
            }

            if ($post->media_url) {

                $post->media_url = asset('storage/' . $post->media_url);
            }

            if (isset($post->group) && !empty($post->group)) {

                if(isset($post->group->cover_photo) && !empty($post->group->cover_photo)){

                    $post->group->cover_photo = asset('storage/' . $post->group->cover_photo);

                }
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

    #--------------  G E T           C O M M U N I T Y       P O S T    ----------------------#

    // public function getCommunityAndPost($community_id,$message="",$request="",){

    //     try {
    //         $limit              =    10;

    //         if(isset($request['limit']) && !empty($request['limit'])){

    //             $limit          =   $request['limit'];
    //         }
    //         // DB::enableQueryLog();
    //         $post               =   Post::whereHas('post_user', function($query){

    //                                     $query->where('is_active',1);

    //                                 })->with(['group'=>function($query){

    //                                     $query->select('id','name','description','cover_photo','post_count');

    //                                 }, 'post_user:id,name,profile'])->where(['group_id'=>$community_id,'is_active'=>1]);

    //                                 if(isset($request['post_category']) && !empty($request['post_category'])){

    //                                     //1: seeing advice, 2: giving advice, 3: sharing media	 
    //                                     $post       =   $post->where('post_category',$request['post_category']);
    //                                 }
    //         $post               =    $post->simplePaginate($limit);

    //         //  dd(DB::getQueryLog());
    //         if(isset($post[0]) && !empty($post[0])){

    //             $post->each(function($groupPost){

    //                 if ($groupPost->media_url) {

    //                     $groupPost->media_url = asset('storage/' . $groupPost->media_url);
    //                 }
        
    //                 if (isset($groupPost->group) && !empty($groupPost->group)) {
        
    //                     if(isset($groupPost->group->cover_photo) && !empty($groupPost->group->cover_photo)){
        
    //                         $groupPost->group->cover_photo = asset('storage/' . $groupPost->group->cover_photo);
    //                     }
    //                 }
        
    //                 if ($groupPost->post_user && $groupPost->post_user->profile) {

    //                     $groupPost->post_user->profile = asset('storage/' . $groupPost->post_user->profile);
    //                 }
        
    //                 $groupPost->postedAt = Carbon::parse($groupPost->created_at)->diffForHumans();
    //             });
    //         }
    //         // if (!$post) {

    //         //     return $this->sendError('Post not found.', [], 404);
    //         // }
    //         return $this->sendResponse($post, $message, 200);



    //     } catch (Exception $e) {

    //         Log::error('Error caught: "getPost" ' . $e->getMessage());
    //         return $this->sendError('Error occurred while fetching post.', [], 500);
    //     }


    // }

    // public function getCommunityAndPost($community_id, $message = "", $request = "") {
    //     try {
    //         $limit = $request['limit'] ?? 10;
    
    //         $posts = Post::where('group_id', $community_id)
    //             ->where('is_active', 1)
    //             ->whereHas('post_user', function ($query) {
    //                 $query->where('is_active', 1);
    //             })
    //             ->when(!empty($request['post_category']), function ($query) use ($request) {
    //                 $query->where('post_category', $request['post_category']);
    //             })
    //             ->with([
    //                 'group:id,name,description,cover_photo,post_count',
    //                 'post_user:id,name,profile'
    //             ])
    //             ->simplePaginate($limit);
    
    //             if($posts[0]){

    //                 $posts->each(function ($community_post) {
    //                     $this->processPostData($community_post);
    //                 });
    //             }
    
    //         return $this->sendResponse($posts, $message, 200);
    //     } catch (Exception $e) {
    //         Log::error('Error caught: "getPost" ' . $e->getMessage());
    //         return $this->sendError('Error occurred while fetching post.', [], 500);
    //     }
    // }
    
    // private function processPostData($community_post) {

    //     if ($community_post->media_url) {

    //         $community_post->media_url = $this->getAssetUrl($community_post->media_url);
    //     }
    
    //     if ($community_post->group && $community_post->group->cover_photo) {

    //         $community_post->group->cover_photo = $this->getAssetUrl($community_post->group->cover_photo);
    //     }
    
    //     if ($community_post->post_user && $community_post->post_user->profile) {

    //         $community_post->post_user->profile = $this->getAssetUrl($community_post->post_user->profile);
    //     }
    
    //     $community_post->postedAt = Carbon::parse($community_post->created_at)->diffForHumans();
    // }
    
    // private function getAssetUrl($path) {
    //     return asset('storage/' . $path);
    // }
    
    public function getCommunityAndPost($community_id, $authId,$message = "", $request = "") {
        try {

            $group      =   Group::withCount('groupMember')->find($community_id);

            $limit      =   10;
    
            if (isset($request['limit']) && !empty($request['limit'])) {

                $limit = $request['limit'];

            }
            $posts = Post::whereHas('post_user', function($query) {

                    $query->where('is_active', 1);
                })
                ->with([
                    'group:id,name,description,cover_photo,post_count',
                    'post_user:id,name,profile'

                ])
                ->where('group_id', $community_id)
                ->where('is_active', 1)
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('hidden_posts')
                        ->whereColumn('hidden_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('hidden_posts.user_id', '=', $authId); // Check if the current user has reported the post 

                })
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('report_posts')
                        ->where('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('report_posts.user_id', '=', $authId); // Check if the current user has reported the post
                })

                ->whereNotExists(function ($query) use ($authId) {

                    $query->select(DB::raw(1))
                    
                        ->from('blocked_users')

                        ->where('user_id', '=', $authId) // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('blocked_users.blocked_user_id','=','posts.user_id'); // Check if the current user has reported the post
                })
                ->addSelect(['is_liked' => function ($query) use ($authId) {
                    $query->selectRaw('IF(EXISTS(SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = posts.id AND comment_id IS NULL), 1, 0)', [$authId]);
                }]);

            if (isset($request['post_category_id']) && !empty($request['post_category_id'])) {

                $posts->where('post_category', $request['post_category_id']);
                
            }
    
            $posts = $posts->orderByDesc('id')->simplePaginate($limit);
    
            if (!empty($posts)) {
                foreach ($posts as $groupPost) {

                    $media_url = isset($groupPost->media_url) ? asset('storage/' . $groupPost->media_url) : '';
                    $cover_photo = isset($groupPost->group) && isset($groupPost->group->cover_photo) ?
                        (filter_var($groupPost->group->cover_photo, FILTER_VALIDATE_URL) ? $groupPost->group->cover_photo : asset('storage/' . $groupPost->group->cover_photo)) : '';
                    $profile = isset($groupPost->post_user) && isset($groupPost->post_user->profile) ?
                        (filter_var($groupPost->post_user->profile, FILTER_VALIDATE_URL) ? $groupPost->post_user->profile : asset('storage/' . $groupPost->post_user->profile)) : '';
            
                    $groupPost->media_url = $media_url;
                    $groupPost->group->cover_photo = $cover_photo;
                    $groupPost->post_user->profile = $profile;
                    $groupPost->postedAt = Carbon::parse($groupPost->created_at)->diffForHumans();
                }
            }

            if(isset($group) && !empty($group)){
                $group->cover_photo =  (isset($group->cover_photo) && !empty($group->cover_photo)) ? asset('storage/'.$group->cover_photo):"";
            }
    
            return response()->json(['status' =>200,'message'=>$message,'data'=>$posts, 'group'=>$group]);

          
        } catch (Exception $e) {
            Log::error('Error caught: "getPost" ' . $e->getMessage());
            return $this->sendError('Error occurred while fetching post.', [], 500);
        }
    }
    

    #------  G E T      A L L       C O M M U N I T Y       C O M M E N T S -------------#
    public function getComments($request, $authId){

        try {
            $groupId    =   Post::select('group_id')->find($request->post_id);
            
            if($groupId){
                
                $group      =   Group::withCount('groupMember')->find($groupId->group_id);
            }
            $limit      =   10;
    
            if (isset($request['limit']) && !empty($request['limit'])) {

                $limit = $request['limit'];

            }
            $comments = Comment::with(['commentUser' => function($query) {

                $query->select('id', 'name', 'user_name', 'profile');

            },
            'replies.commentUser'=>function($query){

                $query->select('id', 'name', 'user_name', 'profile');
                
            },'replies.replied_to'=>function($query){

                $query->select('id', 'name', 'user_name', 'profile');

            }])
            ->where('post_id', $request->post_id)

            ->whereNull('parent_id')

            ->whereNotExists(function ($query) use ($authId) {

                $query->select(DB::raw(1))

                    ->from('blocked_users')

                    ->where(function ($query) use ($authId) {
                        // Check if the authenticated user has blocked someone
                        $query->where('user_id', $authId)
                              ->whereColumn('blocked_users.blocked_user_id', 'comments.user_id');
                    })
                    ->orWhere(function ($query) use ($authId) {
                        // Check if the authenticated user has been blocked by someone
                        $query->where('blocked_user_id', $authId)
                              ->whereColumn('blocked_users.user_id', 'comments.user_id');
                    });
            })

            ->addSelect(['is_liked' => function ($query) use ($authId) {

                $query->selectRaw('IF(EXISTS(SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = comments.post_id AND comment_id = comments.id), 1, 0)', [$authId]);

            }])

            ->orderByDesc('id')->simplePaginate($limit);

            $comments->getCollection()->transform(function ($comment) use($authId) {

                if ($comment->commentUser && $comment->commentUser->profile) {

                    $comment->commentUser->profile = asset('storage/' . $comment->commentUser->profile);
                }

                if (isset($comment->replies[0]) && ($comment->replies[0])) {

                    $comment->replies->each(function($replies) use($authId){

                        $isExist            =   PostLike::where(['user_id'=>$authId,'post_id'=>$replies->post_id,'comment_id'=>$replies->id])->first();

                        $replies->is_liked  = (isset($isExist) && !empty($isExist))?1:0;
                        $replies->reaction  = (isset($isExist) && !empty($isExist))?$isExist->reaction:0;
                    });


                }

                $comment->postedAt = Carbon::parse($comment->created_at)->diffForHumans();

                return $comment;
            });

            // if(isset($comments[0]) && !empty($comments[0])){

            //     $comments->each(function($query){

            //         if(isset($query->comment_user) && !empty($query->comment_user->profile)){

            //             $query->comment_user->profile   =   asset('storage/'.$query->comment_user->profile);
            //         }
            //     });
            // }

            // if (!empty($comments)) {
                
            //     foreach ($comments as $groupPost) {

            //         $media_url = isset($groupPost->media_url) ? asset('storage/' . $groupPost->media_url) : '';
            //         $cover_photo = isset($groupPost->group) && isset($groupPost->group->cover_photo) ?
            //             (filter_var($groupPost->group->cover_photo, FILTER_VALIDATE_URL) ? $groupPost->group->cover_photo : asset('storage/' . $groupPost->group->cover_photo)) : '';
            //         $profile = isset($groupPost->post_user) && isset($groupPost->post_user->profile) ?
            //             (filter_var($groupPost->post_user->profile, FILTER_VALIDATE_URL) ? $groupPost->post_user->profile : asset('storage/' . $groupPost->post_user->profile)) : '';
            
            //         $groupPost->media_url = $media_url;
            //         $groupPost->group->cover_photo = $cover_photo;
            //         $groupPost->post_user->profile = $profile;
            //         $groupPost->postedAt = Carbon::parse($groupPost->created_at)->diffForHumans();
            //     }
            // }

            // if(isset($group) && !empty($group)){
            //     $group->cover_photo =  (isset($group->cover_photo) && !empty($group->cover_photo)) ? asset('storage/'.$group->cover_photo):"";
            // }
            if(isset($group) && !empty($group)){
                $group->cover_photo =  (isset($group->cover_photo) && !empty($group->cover_photo)) ? asset('storage/'.$group->cover_photo):"";
            }
            return response()->json(['status' =>200,'message'=>"comments",'data'=>$comments, 'group'=>$group]);

          
        } catch (Exception $e) {
            Log::error('Error caught: "getComments" ' . $e->getMessage());
            return $this->sendError('Error occurred while fetching post.', [], 500);
        }


    }
    #------  G E T      A L L       C O M M U N I T Y       C O M M E N T S -------------#


}
