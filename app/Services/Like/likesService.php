<?php

namespace App\Services\Like;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\PostLike;
use App\Models\CommentLike;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use Exception;
use App\Services\AddCommunityPost;
use App\Models\Comment;
use App\Traits\IsCommunityJoined;
/**
 * Class likesService.
 */
class likesService extends BaseController
{
    use IsCommunityJoined;

    protected $addCommunityPost, $notification, $getCommunityPost;
    public function __construct(AddCommunityPost $addCommunityPost)
    {
        $this->addCommunityPost         = $addCommunityPost;
    }

    #------------ P O S T       L I K E     --------------#
    public function postLike($request,$authId){
        DB::beginTransaction();
        try {
            $post               =   PostLike::where(['post_id' => $request->post_id, 'user_id' => $authId])->first();
            if($request->action==0){    // remove liked
                if(empty($post)){

                    return $this->sendError(trans('message.something_went_wrong'), [], 400);

                }else{

                    $post->delete();
                    decrement('posts', ['id' => $request->post_id], 'like_count', 1); //decrement post
                    post_reaction_count(0, $post->reaction, $request->post_id);
                    ActivityLog::where(['user_id'=>$authId,'post_id'=>$request->post_id,'action'=>1])->delete();
                    Notification::where(['sender_id'=>$authId,'post_id'=>$request->post_id,'notification_type'=>trans('notification_message.post_liked_message_type')])->delete();
                    return $this->addCommunityPost->getPost($request->post_id,$authId,trans('message.updated_successfully'));

                } 
               
            }else{ 
                                  // liked/update
                if(empty($post)){       // insert post like
                    $postLike= PostLike::create(['post_id' =>$request->post_id, 'user_id' => $authId,'reaction'=>$request->reaction]);
                    // Log::info('PostLike'.$postLike['id']);
                    $post_reaction_count = post_reaction_count(1, $request->reaction, $request->post_id);
                    $increment=increment('posts', ['id' => $request->post_id], 'like_count', 1); //decrement post
                    $group_post                  =    Post::select('group_id','user_id')->where(['id' => $request->post_id])->first();
                    $addActivityLog              =    new ActivityLog();
                    $addActivityLog->user_id     =    $authId;
                    $addActivityLog->post_id     =    $request->post_id;
                    $addActivityLog->community_id=    $group_post->group_id;

                    if(isset($request->comment_id) && !empty($request->comment_id)){

                        $addActivityLog->comment_id=    $request->comment_id;
                    }
                    $addActivityLog->action      =    1;    //like
                    $addActivityLog->action_details =  "liked coummunity post";
                    $addActivityLog->save();

                    #----------- R E C O R D        A C T I V I T Y -------------#
                    $reciever                           =       User::select('id', 'device_type')->where("id", $group_post->user_id)->first();
                    $sender                             =       User::select('id', 'device_token', 'device_type')->where("id", $authId)->first();
                    $notification_type                  =       trans('notification_message.post_liked_message_type');
                    $notification_message               =       trans('notification_message.post_liked_message');
                    // $this->notification->sendNotification($reciever,$sender,$notification_message,$notification_type);
                   // Log::info('commit');
                
                }else{    
                                            // update like
                    $oldreact           =   $post->reaction;
                    $post->reaction     =   $request->reaction;
                    $post->save();
                    if($oldreact!=$request->reaction){
                        post_reaction_count(0, $oldreact, $request->post_id);
                        post_reaction_count(1, $request->reaction, $request->post_id);
                    }
                }
                return $this->addCommunityPost->getPost($request->post_id,$authId,trans('message.post_liked'));
            }
        } catch (Exception $e) {
            Log::info('rollback');
            DB::rollBack();
            Log::error('Error caught: "postLike" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
        finally{

            DB::commit();
        }
    }
    #------------ P O S T       L I K E     --------------#

    #------------ C O M M E N T        L I K E     --------------#

    public function commentLike($request,$authId){
        DB::beginTransaction();
        try {

            $post               =   CommentLike::where(['post_id' => $request->post_id, 'comment_id'=>$request->comment_id,'user_id' => $authId])->first();

           
            if($request->action==0){    // remove liked

                if(empty($post)){

                    return $this->sendError(trans('message.something_went_wrong'), [], 400);
                    
                }else{

                    $post->delete();
                    return $this->addCommunityPost->getCommentById($request,$authId,trans('message.updated_successfully'));

                }
            }else{                      // liked/update

                if(empty($post)){       // insert comment like

                    CommentLike::create(['post_id' => $request->post_id, 'comment_id'=>$request->comment_id,'user_id' => $authId,'reaction'=>$request->reaction]);
                    $comment_user                       =       Comment::select('user_id')->where(['id' => $request->comment_id])->first();
                    $reciever                           =       User::select('id', 'device_type')->where("id", $comment_user->user_id)->first();
                    $sender                             =       User::select('id', 'device_token', 'device_type')->where("id", $authId)->first();
                    $notification_type                  =       trans('notification_message.post_liked_message_type');
                    $notification_message               =       trans('notification_message.post_liked_message');
                    // $this->notification->sendNotification($reciever,$sender,$notification_message,$notification_type);
                   

                }else{    
                                                                // update like
                    $oldreact                           =      $post->reaction;
                    $post->reaction                     =      $request->reaction;
                    $post->save();
                }
                return $this->addCommunityPost->getCommentById($request,$authId,trans('message.comment_liked'));
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "comment_like" ' . $e->getLine());
            return $this->sendError($e->getMessage(), [], 400);
        }finally{
            DB::commit();
        }
    }
    #------------ C O M M E N T       L I K E      --------------#



}
