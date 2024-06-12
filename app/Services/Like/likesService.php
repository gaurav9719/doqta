<?php

namespace App\Services\Like;

use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\Comment;
use App\Models\PostLike;
use App\Models\ActivityLog;
use App\Models\CommentLike;
use App\Models\SummaryLike;
use App\Models\Notification;
use Gemini\Foundation\Request;
use App\Traits\IsCommunityJoined;
use App\Jobs\AiScoreCalculatedJob;
use App\Services\AddCommunityPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\postCommentLikeCount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Traits\CalculateScore;
/**
 * Class likesService.
 */
class likesService extends BaseController
{
    use IsCommunityJoined, postCommentLikeCount,CalculateScore;

    protected $addCommunityPost, $notification, $getCommunityPost;
    public function __construct(AddCommunityPost $addCommunityPost, NotificationService $notification)
    {
        $this->addCommunityPost         = $addCommunityPost;
        $this->notification = $notification;
    }
    #--------------  S I G N U P        P R O C E S S  ------------------------#
    #------------ P O S T       L I K E     --------------#
    public function postLikeOLD($request, $authId)
    {
        DB::beginTransaction();
        try {
            
            $post               =   PostLike::where(['post_id' => $request->post_id, 'user_id' => $authId])->first();

            if ($request->action == 0) {    // remove liked

                if (empty($post)) {
                    return $this->sendError(trans('message.something_went_wrong'), [], 400);

                } else {

                    $post->delete();
                    #--- jun 4 ----#
                    //decrement('posts', ['id' => $request->post_id], 'like_count', 1); //decrement post
                    //post_reaction_count(0, $post->reaction, $request->post_id);
                    DB::commit();
                    dispatch(new AiScoreCalculatedJob($request->post_id));
                    ActivityLog::where(['user_id' => $authId, 'post_id' => $request->post_id, 'action' => 1])->delete();
                    Notification::where(['sender_id' => $authId, 'post_id' => $request->post_id, 'notification_type' => trans('notification_message.post_liked_message_type')])->delete();
                    $data                   =   $this->postLikeCount($request->post_id);
                    DB::commit();
                    return $this->sendResponse($data, trans('message.updated_successfully'), 200);
                }
            } else {
                // liked/update
                if (empty($post)) {       // insert post like
                   
                    $postLike                       =   PostLike::create(['post_id' => $request->post_id, 'user_id' => $authId, 'reaction' => $request->reaction]);
                    $post_reaction_count            =   post_reaction_count(1, $request->reaction, $request->post_id);
                    // $increment = increment('posts', ['id' => $request->post_id], 'like_count', 1); //decrement post
                    $group_post                     =    Post::select('group_id', 'user_id','title')->where(['id' => $request->post_id])->first();
                    $title                          =    $group_post->title;
                    $addActivityLog                 =    new ActivityLog();
                    $addActivityLog->user_id        =    $authId;
                    $addActivityLog->post_id        =    $request->post_id;
                    $addActivityLog->community_id   =    $group_post->group_id;
                    if (isset($request->comment_id) && !empty($request->comment_id)) {

                        $addActivityLog->comment_id =    $request->comment_id;
                    }
                    $type                           =     trans('notification_message.like_post_type');
                    $addActivityLog->action         =    1;    //like
                    $addActivityLog->action_details =    "liked coummunity post " . $title;
                    $addActivityLog->save();
                    #----------- R E C O R D        A C T I V I T Y -------------# 17 may
                    $addActivityLog                 =    new ActivityLog();
                    $addActivityLog->user_id        =    $authId;
                    $addActivityLog->post_id        =    $request->post_id;
                    $addActivityLog->community_id   =    $group_post->group_id;
                    $addActivityLog->action         =    $type;    //like
                    $addActivityLog->action_details =  "Liked the coummunity post: " . $title;
                    $addActivityLog->save();
                    #send notification
                    $sender                         =   Auth::user();
                    $receiver                       =   User::find($group_post->user_id);
                    $group                          =   Group::find($group_post->group_id);
                    $message                        =   $sender->user_name . " liked your post: " . $title;
                    $data          =   [
                        "message"       =>  $message,
                        "post_id"       =>  $request->post_id,
                        "community_id"  =>  $group->id,
                        "like_id"       =>  $postLike->id
                    ];

                    if($authId!=$group_post->user_id){  //it will send if post is not posted by me

                        $this->notification->sendNotificationNew($sender, $receiver, $type, $data);
                    }

                } else {
                    // update like
                    $oldreact           =   $post->reaction;
                    $post->reaction     =   $request->reaction;
                    $post->save();
                    #---- no need to store this ----__#

                    // if ($oldreact != $request->reaction) {

                    //     post_reaction_count(0, $oldreact, $request->post_id);
                    //     post_reaction_count(1, $request->reaction, $request->post_id);
                    // }
                    
                    #---- no need to store this ----__#
                }
                $data                   =   $this->postLikeCount($request->post_id);
                // dd($this->CalculateConfidenceScore($request->post_id));
                dispatch(new AiScoreCalculatedJob($request->post_id));
                DB::commit();
                return $this->sendResponse($data, trans('message.post_liked'), 200);
                // return $this->addCommunityPost->getPost($request->post_id,$authId,trans('message.post_liked'));
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "postLike" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        } finally {

            DB::commit();
        }
    }
    #------------ P O S T       L I K E     --------------#
    public function postLike($request, $authId)
    {
        try {

            DB::beginTransaction();
            
            $post   =       PostLike::where(['post_id' => $request->post_id, 'user_id' => $authId])->first();
    
            if ($request->action == 0) { // remove liked

                if (empty($post)) {

                    return $this->sendError(trans('message.something_went_wrong'), [], 400);

                } else {

                    $post->delete();
                    // Remove associated activity log and notification
                    $this->cleanupLikeActivityAndNotification($authId, $request->post_id);
                }
            } else {
                // liked/update
                if (empty($post)) {
                    // Insert new like
                    $postLike = PostLike::create([
                        'post_id' => $request->post_id,
                        'user_id' => $authId,
                        'reaction' => $request->reaction
                    ]);
    
                    // Update activity log and send notification
                    $this->updateLikeActivityAndNotification($authId, $request->post_id, $postLike);
                } else {
                    // Update existing like
                    $post->reaction = $request->reaction;
                    $post->save();
                }
            }
    
            // Get updated like count
            $data = $this->postLikeCount($request->post_id);
            DB::commit();
            dispatch(new AiScoreCalculatedJob($request->post_id));
            return $this->sendResponse($data, trans('message.updated_successfully'), 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "postLike" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    
    // Helper functions to update activity log and send notification
    private function updateLikeActivityAndNotification($authId, $postId, $postLike)
    {
        $groupPost          = Post::select('group_id', 'user_id', 'title')->find($postId);
        $title              = $groupPost->title;
    
        // Add activity log
        $addActivityLog = new ActivityLog();
        $addActivityLog->user_id = $authId;
        $addActivityLog->post_id = $postId;
        $addActivityLog->community_id = $groupPost->group_id;
        $addActivityLog->action = 1; // like
        $addActivityLog->action_details = "liked community post " . $title;
        $addActivityLog->save();
    
        // Send notification
        $sender     = Auth::user();
        $receiver   = User::find($groupPost->user_id);
        $group      = Group::find($groupPost->group_id);
        // $message = $sender->user_name . " liked your post: " . $title;
        $message    = "liked your post: " . $title;

        $data = [
            "message" => $message,
            "post_id" => $postId,
            "community_id" => $group->id,
            "like_id" => $postLike->id
        ];
    
        if ($authId != $groupPost->user_id) { // Send notification if post is not posted by the liker
            $this->notification->sendNotificationNew($sender, $receiver, trans('notification_message.like_post_type'), $data);
        }
    }
    
    private function cleanupLikeActivityAndNotification($authId, $postId)
    {
        ActivityLog::where(['user_id' => $authId, 'post_id' => $postId, 'action' => 1])->delete();
        Notification::where(['sender_id' => $authId, 'post_id' => $postId, 'notification_type' => trans('notification_message.post_liked_message_type')])->delete();
    }
    

    #------------ P O S T       L I K E     --------------#


    #------------ C O M M E N T        L I K E     --------------#

    public function commentLike($request, $authId)
    {
        DB::beginTransaction();
        try {
            $post               =   CommentLike::where(['post_id' => $request->post_id, 'comment_id' => $request->comment_id, 'user_id' => $authId])->first();

            if ($request->action == 0) {    // remove liked
                if (empty($post)) {
                    return $this->sendError(trans('message.something_went_wrong'), [], 400);
                } else {
                    $post->delete();
                    #remove notification
                    
                    Notification::where(['sender_id' => $authId,'notification_type' => trans('notification_message.like_comment_post_type'), 'comment_id'=>$request->comment_id ])->delete();

                    $data                   =   $this->commentLikeCount($request->comment_id);
                    return $this->sendResponse($data, trans('message.updated_successfully'), 200);
                }
            } else {                      // liked/update

                if (empty($post)) {       // insert comment like

                    $like         =         CommentLike::create(['post_id' => $request->post_id, 'comment_id' => $request->comment_id, 'user_id' => $authId, 'reaction' => $request->reaction]);
                    $comment_user  =       Comment::select('user_id','post_id')->where(['id' => $request->comment_id])->first();
                    $sender        =       User::find($authId);
                    $receiver      =       User::find($comment_user->user_id);
                    $post1         =       Post::find($comment_user->post_id);
                    $group         =       Group::find($post1->group_id);
                    // $message       =       $sender->name . " liked your comment in: " . $group->name;
                    $message       =       "liked your comment in: " . $group->name;

                    #-------  T R A C K       A C T V I T Y -----------#
                    $addActivityLog              =    new ActivityLog();
                    $addActivityLog->user_id     =    $authId;
                    $addActivityLog->post_id     =    $request->post_id;

                    if (isset($request->comment_id) && !empty($request->comment_id)) {

                        $addActivityLog->comment_id =    $request->comment_id;
                    }
                    $addActivityLog->action         =    1;    //like
                    $addActivityLog->action_details =  "liked comment in " . $group->name;
                    $addActivityLog->save();

                    #-------  T R A C K       A C T V I T Y -----------#
                    $type                           =    trans('notification_message.like_comment_post_type');
                    $addActivityLog                 =    new ActivityLog();
                    $addActivityLog->user_id        =    $authId;
                    $addActivityLog->post_id        =    $request->post_id;
                    $addActivityLog->comment_id     =    $request->comment_id;
                    $addActivityLog->action         =    $type;    //like
                    $addActivityLog->action_details =  "Liked the comment in " . $group->name;
                    $addActivityLog->save();

                    #-------  T R A C K       A C T V I T Y -----------#
                   #send notification
                   $data          =   [
                    "message"               =>  $message,
                    "post_id"               =>  $request->post_id,
                    "community_id"          =>  $post1->group_id,
                    "comment_id"            =>  $request->comment_id,
                    "comment_like_id"       =>  $like->id];
                    if($sender->id != $receiver->id){

                        $this->notification->sendNotificationNew($sender,$receiver,$type,$data);
                    }
                    #send notification
    
                } else {
                    // update like
                    $oldreact                  =      $post->reaction;
                    $post->reaction            =      $request->reaction;
                    $post->save();
                }
                // return $this->addCommunityPost->getCommentById($request,$authId,trans('message.comment_liked'));
                $data                   =   $this->commentLikeCount($request->comment_id);
                return $this->sendResponse($data, trans('message.comment_liked'), 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "comment_like" ' . $e->getLine());
            return $this->sendError($e->getMessage(), [], 400);
        } finally {
            DB::commit();
        }
    }
    #------------ C O M M E N T       L I K E      --------------#



    #---------------- L I K E       P O S T     S U M M A R Y   -------------------#
    public function likeSummary($request, $authId)
    {
        DB::beginTransaction();
        try {

            $likeType = $request->like_type;
            $action = $request->action;
            $postId = $request->post_id;
            $userId = $authId;
            $reaction = $request->reaction;
            $commentId = $request->comment_id;
    
            // Determine type based on like_type
            $type = ($likeType == 3) ? 1 : 2;
    
            // Retrieve existing like
            $post = SummaryLike::where(['post_id' => $postId, 'user_id' => $userId, 'type' => $type])->first();
    
            if ($action == 0) { // Remove like
                if (empty($post)) {
                    return $this->sendError(trans('message.something_went_wrong'), [], 400);
                } else {
                    $post->delete();
                    return $this->sendResponse([], trans('message.updated_successfully'), 200);
                }
            } else { // Add or update like
                if (empty($post)) { // Insert new like
                    $data = [
                        'post_id' => $postId,
                        'user_id' => $userId,
                        'reaction' => $reaction,
                        'type' => $type
                    ];
    
                    if ($likeType != 3) {
                        $data['comment_id'] = $commentId;
                    }
    
                    $like = SummaryLike::create($data);
                    $likeId = $like->id;
                } else { // Update existing like
                    $post->reaction = $reaction;
                    $post->save();
                    $likeId = $post->id;
                }
    
                $threadSummary = SummaryLike::find($likeId);
                return $this->sendResponse($threadSummary, trans('message.updated_successfully'), 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "summary like" ' . $e->getLine());
            return $this->sendError($e->getMessage(), [], 400);
        } finally {
            DB::commit();
        }
    }
    
    #---------------- L I K E       P O S T     S U M M A R Y   -------------------#




}
