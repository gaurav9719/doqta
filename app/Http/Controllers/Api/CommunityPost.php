<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AddCommunity;
use App\Http\Requests\EditCommunity;
use App\Http\Requests\AddPostRequest;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\GroupMemberRequest;
use App\Models\Post;
use Exception;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\AddCommunityPost;
use App\Services\GetCommunityService;
use App\Http\Requests\EditCommunityPost;
use App\Models\PostLike;
use App\Models\HiddenPost;
use App\Models\SavedPost;
use App\Models\ReportPost;
use App\Models\ActivityLog;
use Illuminate\Validation\ValidationException;
use App\Models\Comment;
use App\Models\Notification;
use App\Traits\CommonTrait;
use App\Traits\postCommentLikeCount;
use App\Traits\IsCommunityJoined;

class CommunityPost extends BaseController
{
    use CommonTrait, IsCommunityJoined, postCommentLikeCount;
    /**
     * Display a listing of the resource.
     */
    protected $addCommunityPost, $notification, $getCommunityPost;
    public function __construct(AddCommunityPost $addCommunityPost, NotificationService $notification, GetCommunityService $getCommunityPost)
    {
        $this->addCommunityPost = $addCommunityPost;
        $this->notification = $notification;
        $this->getCommunityPost = $getCommunityPost;
    }
    public function index(Request $request)
    {
        $limit = 10;
        $authId = Auth::id();
        if (isset($request->limit) && !empty($request->limit)) {

            $limit = $request->limit;
        }
        return $this->getCommunityPost->homeScreen($request, $authId);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddPostRequest $request)
    {
        $authId = Auth::id();
        //check if you are the member of 
        //check group is active or not
        $isGroupExist = Group::where(['id' => $request->community_id, 'is_active' => 1])->exists();
        if ($isGroupExist) {

            if (isset($request->community_id) && !empty($request->community_id)) {

                $isExist = GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId, 'is_active' => 1])->exists();

                if (!$isExist) {

                    return $this->sendError(trans("message.not_group_member"), [], 403);
                }
            }
            return $this->addCommunityPost->addPost($request, $authId);

        } else {

            return $this->sendError(trans("message.invalid_group"), [], 403);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        $validation = Validator::make(['id' => $id], ['id' => 'required|integer|exists:groups,id']);

        if ($validation->fails()) {

            return $this->sendResponsewithoutData($validation->errors()->first(), 422);
        }
        // return $this->addCommunityPost->getCommunityPost($id, Auth::id(), $request);
        return $this->getCommunityPost($id, Auth::id(), $request);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EditCommunityPost $request, string $id)
    {
        //
        $authId         = Auth::id();
        
        $isExist        = Post::whereHas('group_post', function ($query) {

            $query->where('is_active', 1);

        })->where(['id' => $id, 'user_id' => $authId])->exists(); // check post is your or not

        if ($isExist) {   // edit the post

            return $this->addCommunityPost->editPost($request, $authId, $id);

        } else {        //invalid post

            return $this->sendError(trans("message.invalid_post"), [], 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    #-------------------    D E L E T E          P O S T  ------------------------#
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $auth = Auth::user();
            $authId = Auth::id();
            if (isset($id) && !empty($id)) {
                $isExist = Post::where(['id' => $id, 'user_id' => $authId])->exists();
                if (!$isExist) {

                    return $this->sendError(trans("message.no_post_found"), [], 422);
                } else {

                    Post::where('id', $id)->orWhere('parent_id', $id)->update(['is_active' => 0]);

                    #delete notification & activity
                    $posted_in_community = trans('notification_message.posted_in_community'); //10
                    $like_post_type = trans('notification_message.like_post_type'); //11
                    $comment_on_post_type = trans('notification_message.comment_on_post_type'); //12
                    $like_comment_post_type = trans('notification_message.like_comment_post_type'); //13
                    $comment_reply_type = trans('notification_message.comment_reply_type'); //14
                    $reposted_post_type = trans('notification_message.reposted_post_type'); //15
                    $nType = [$posted_in_community, $like_post_type, $comment_on_post_type, $like_comment_post_type, $comment_reply_type, $reposted_post_type];

                    #delete notification & activity
                    Notification::where('post_id', $id)->orWhere('parent_id', $id)->whereIn('notification_type', $nType)->delete();
                    ActivityLog::where('post_id', $id)->orWhere('parent_id', $id)->whereIn('action', $nType)->delete();
                    Notification::where('post_id', $id)->orWhere('parent_id', $id)->whereIn('notification_type', [$reposted_post_type, $posted_in_community])->delete();
                    ActivityLog::where('post_id', $id)->orWhere('parent_id', $id)->whereIn('action', [$reposted_post_type, $posted_in_community])->delete();
                    DB::commit();
                    return $this->sendResponsewithoutData(trans('message.post_deleted_successfully'), 200);
                }
            } else {

                return $this->sendError(trans("message.post_id_required"), [], 422);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "get community" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------    D E L E T E          P O S T  ------------------------#

    #--------------------- L I K E      P O S T  ------------------------------#
    public function like(Request $request)
    {
        DB::beginTransaction();
        try {

            $validation = Validator::make($request->all(), ['post_id' => 'required|integer|exists:posts,id', 'reaction' => 'required|integer|between:1,3', 'comment_id' => 'nullable|exists:comments,id'], ['reaction.*' => "invalid reaction"]);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            } else {

                $auth = Auth::user();
                $authId = Auth::id();
                $isExist = Post::where(['id' => $request->post_id, 'is_active' => 1])->exists();

                if (!$isExist) {

                    return $this->sendError(trans("message.no_post_found"), [], 422);

                } else {

                    $post = PostLike::where(['post_id' => $request->post_id, 'user_id' => $authId])->first();
                    if ($post) {
                        if ($post['reaction'] == $request->reaction) {      // same reaction then delete
                            // Record exists, delete it
                            $post->delete();
                            $deleteCondition = ['post_id' => $request->post_id, 'user_id' => $authId, 'action' => 1];
                            if (isset($request->comment_id) && !empty($request->comment_id)) {

                                $deleteCondition['comment_id'] = $request->comment_id;
                            }
                            ActivityLog::where($deleteCondition)->delete();
                            $action = 0;
                            decrement('posts', ['id' => $request->post_id], 'like_count', 1); //decrement post
                            DB::commit();
                        } else {
                            $post->reaction = $request->reaction;
                            $post->save();
                            $action = 1;
                            DB::commit();
                        }
                    } else {

                        $newPost = new PostLike();
                        $newPost->post_id = $request->post_id;
                        $newPost->user_id = $authId;

                        if (isset($request->comment_id) && !empty($request->comment_id)) {

                            $newPost->comment_id = $request->comment_id;
                        }
                        $newPost->reaction = $request->reaction;
                        $newPost->save();

                        #----------- R E C O R D        A C T I V I T Y -------------#
                        $group_post = Post::select('group_id', 'user_id')->where(['id' => $request->post_id])->first();

                        // $groupData                   =    Group::where('id', $group->group_id);
                        $addActivityLog = new ActivityLog();
                        $addActivityLog->user_id = $authId;
                        $addActivityLog->post_id = $request->post_id;
                        $addActivityLog->community_id = $group_post->group_id;

                        if (isset($request->comment_id) && !empty($request->comment_id)) {

                            $addActivityLog->comment_id = $request->comment_id;
                        }

                        $addActivityLog->action = 1; //like
                        $addActivityLog->action_details = "liked coummunity post";
                        $addActivityLog->save();
                        DB::commit();
                        #----------- R E C O R D        A C T I V I T Y -------------#

                        $reciever = User::select('id', 'device_type')->where("id", $group_post->user_id)->first();
                        $sender = User::select('id', 'device_token', 'device_type')->where("id", $authId)->first();
                        $notification_type = trans('notification_message.post_liked_message_type');
                        $notification_message = trans('notification_message.post_liked_message');

                        $this->notification->sendNotification($reciever, $sender, $notification_message, $notification_type);

                        #------------  S E N D           N O T I F I C A T I O N --------------#
                        $action = 1;
                        //increment the like by one
                        increment('posts', ['id' => $request->post_id], 'like_count', 1);
                        DB::commit();
                    }
                    return $this->getPost($request->post_id, $authId, trans('message.post_liked'));
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "like post" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #--------------------- L I K E      P O S T  ------------------------------#

    #--------------------- R E S H A R E             P O S T     -------------------#

    public function resharePost(Request $request)
    {
        DB::beginTransaction();

        try {

            $validation = Validator::make($request->all(), ['post_id' => 'required|integer|exists:posts,id']);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            } else {

                $auth = Auth::user();
                $authId = Auth::id();
                $isExist = Post::where(['id' => $request->post_id, 'is_active' => 1])->first();

                if (empty($isExist) || $isExist == null) {

                    return $this->sendError(trans("message.no_post_found"), [], 422);
                } else {

                    $isJoined = $this->checkCommunityJoind($isExist->group_id);

                    if (!$isJoined) {

                        return $this->sendError(trans("message.please_join_community"), [], 403);
                    }

                    if (isset($isExist->parent_id) && !empty($isExist->parent_id)) {

                        $parent_id = $isExist->parent_id;
                    } else {

                        $parent_id = $isExist->id;
                    }
                    $post = Post::where(['parent_id' => $parent_id, 'user_id' => $authId])->first();

                    if (isset($post) && !empty($post)) {
                        // Record exists, delete it
                        $post->delete();
                        $action = 0;
                        decrement('posts', ['id' => $request->post_id], 'repost_count', 1); //decrement post
                        DB::commit();
                        $repostId = $parent_id;
                    } else {
                        // check i am the community member or not 
                        $isGroupMember = GroupMember::where(['group_id' => $isExist->group_id, 'user_id' => $authId, 'is_active' => 1])->exists();
                        if ($isGroupMember) {

                            $rePost = new Post();
                            $rePost->parent_id = $parent_id;
                            $rePost->user_id = $authId;
                            $rePost->title = $isExist->title;
                            $rePost->content = $isExist->content;
                            $rePost->media_url = $isExist->media_url;
                            $rePost->link = $isExist->link;
                            $rePost->post_type = $isExist->post_type;
                            $rePost->group_id = $isExist->group_id;
                            $rePost->save();
                            $repostId = $rePost->id;
                            $action = 1;
                            //increment the like by one
                            increment('posts', ['id' => $parent_id], 'repost_count', 1);
                            #send notification
                            $group = Group::find($isExist->group_id);
                            $sender = Auth::user();
                            $receiver = User::find($isExist->user_id);
                            $message = $sender->name . " reposted your post in " . $group->name;
                            $data = [
                                "message" => $message,
                                "post_id" => $rePost->id,
                                "community_id" => $isExist->group_id
                            ];
                            $this->notification->sendNotificationNew($sender, $receiver, trans('notification_message.reposted_post_type'), $data);

                            #-------  A C T I V I T Y -----------# 17 may
                            $activity = new ActivityLog();
                            $activity->user_id = $authId;
                            $activity->community_id = $group->id;
                            $activity->post_id = $rePost->id;
                            $activity->parent_id = $parent_id;
                            $activity->action_details = "Reposted the post in " . $group->name;
                            $activity->action = trans('notification_message.reposted_post_type');    //Reposted the post
                            $activity->save();
                            #-------  A C T I V I T Y -----------#
                            DB::commit();
                        } else {
                            return $this->sendError(trans("message.not_community_member"), [], 403);
                        }
                    }
                    return $this->getPost($repostId,$authId,($action == 0) ? trans('message.repost_removed_successfully') : trans('message.reposted'));
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "resharePost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #--------------------- ***************  E N D  ******************---------------#


    #------------------------- H I D E      P O S T     ------------------------------#

    public function hideSavePost(Request $request)
    {
        DB::beginTransaction();

        try {
            $validation = Validator::make($request->all(), [
                'post_id' => 'required|integer|exists:posts,id',
                'type' => 'required|integer|between:0,2'
            ], [
                'post_id.*' => 'Invalid post',
                'type.*' => 'Invalid type'
            ]);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            }

            $type = $request->type;
            $authId = Auth::id();
            $post = Post::find($request->post_id);

            if (!$post || !$post->is_active) {

                return $this->sendResponsewithoutData(trans('message.no_post_found'), 422);
            }
            switch ($type) {

                case 0: // Hide the post
                    $message = trans('message.hide_post_successfully');
                    HiddenPost::updateOrCreate(['user_id' => $authId, 'post_id' => $request->post_id]);
                    break;
                case 1: // Save the post
                    //check post is hidden for you
                    $isHide = HiddenPost::where(['user_id' => $authId, 'post_id' => $request->post_id])->exists();
                    if ($isHide) {

                        $message = trans('message.hidden_post_cannot_saved');
                    } else {

                        $message = trans('message.saved_post_successfully');
                        SavedPost::updateOrCreate(['user_id' => $authId, 'post_id' => $request->post_id]);
                    }
                    break;
                case 2: // Save the post

                    $isHide = HiddenPost::where(['user_id' => $authId, 'post_id' => $request->post_id])->first();
                    if (isset($isHide) && !empty($isHide)) {
                        $message = trans('message.unhide_post_successfully');
                        $isHide->delete();
                    } else {
                        $message = trans('message.no_post_found');
                    }
                    break;
                default:
                    return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 422);
            }

            DB::commit();
            return $this->sendResponse(intVal($type), $message, 200);
        } catch (ValidationException $e) {

            DB::rollBack();

            return $this->sendResponsewithoutData($e->errors()['first'], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "hideSavePost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------------------- *********  E N D   ******** ----------------------------#

    #--------------------********   R E P O R T     P O S T   ********------------------------#
    public function reportPost(Request $request)
    {

        DB::beginTransaction();

        try {
            $validation = Validator::make($request->all(), [
                'post_id' => 'required|integer|exists:posts,id',
            ], [
                'post_id.*' => 'Invalid post',
            ]);

            if ($validation->fails()) {

                throw new ValidationException($validation);
            }

            $authId = Auth::id();
            // $post       = Post::where('id', $request->post_id)->where('is_active', 1)->first();

            $post = Post::find($request->post_id);

            if (!$post || !$post->is_active) {

                throw new Exception(trans('message.no_post_found'), 422);
            }
            $data = [];
            if (isset($request->report_title) && !empty($request->report_title)) {

                $data = ['report_title' => $request->report_title];
            }

            ReportPost::updateOrCreate(
                ['user_id' => $authId, 'post_id' => $request->post_id],
                [$data]
            );

            DB::commit();
            return $this->sendResponsewithoutData(trans('message.report_to_post_successfully'), 200);
        } catch (ValidationException $e) {

            DB::rollBack();
            Log::error('Error caught: "reportPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "reportPost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #--------------------********  R E P O R T      P O S T  *********------------------------#



    #----------------------- ############C O M M E N T     O N     P O S T############ ----------------------#
    public function addComment(Request $request)
    {
        DB::beginTransaction();

        try {

            $validation = Validator::make($request->all(), [

                'post_id' => 'required|integer|exists:posts,id',
                'parent_comment_id' => 'nullable|exists:comments,id',
                'comment' => "required",
                'comment_type' => "nullable|between:1,4",
                'mention_user_id' => 'nullable|integer|exists:users,id'
            ], [
                'post_id.integer' => 'Invalid post',
                'parent_id.*' => "Invalid comment id",
                'comment_type.between' => "Invalid comment type",
                'mention_user_id.integer' => "Invalid mention id",
            ]);
            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            }
            $authId = Auth::id();
            $post = Post::find($request->post_id);

            if (!$post || !$post->is_active) {

                throw new Exception(trans('message.no_post_found'), 422);
            }
            // check i am group member or not 
            $isMember = GroupMember::where(['group_id' => $post->group_id, 'user_id' => $authId, 'is_active' => 1])->exists();

            if (!$isMember) {

                return $this->sendError(trans('message.you_are_not_group_member'), [], 201);
            }
            $addComment = new Comment();
            $addComment->user_id = $authId;
            $addComment->post_id = $request->post_id;
            $group = Group::find($post->group_id);

            if (isset($request->parent_comment_id) && !empty($request->parent_comment_id)) {

                $addComment->parent_id = $request->parent_comment_id;
                #notification data preparation
                $parentComment = Comment::find($request->parent_comment_id);
                $sender = Auth::user();
                $receiver = User::find($parentComment->user_id);
                $message = $sender->name . " replied to your comment in : " . $group->name;
                $activityLogMessage = "Replied the comment in " . $group->name;
                $type = trans('notification_message.comment_reply_type');
            } else {
                #notification data preparation
                $sender = Auth::user();
                $receiver = User::find($post->user_id);
                $title = $post->title;
                $message = $sender->name . " " . trans('notification_message.comment_on_post') . " " . $title;
                $activityLogMessage = "Comment on post: " . $title;
                $type = trans('notification_message.comment_on_post_type');
            }

            if (isset($request->comment_type) && !empty($request->comment_type)) {

                $addComment->comment_type = $request->comment_type;
            }
            if (isset($request->mention_user_id) && !empty($request->mention_user_id)) {

                $addComment->mention_user_id = $request->mention_user_id;
            }

            $addComment->comment = $request->comment;
            $addComment->save();
            $commentId = $addComment->id;
            $request['comment_id'] = $commentId;
            #----------- R E C O R D        A C T I V I T Y -------------#
            $activityType = $type;
            $addActivityLog = new ActivityLog();
            $addActivityLog->user_id = $authId;
            $addActivityLog->post_id = $post->id;
            $addActivityLog->community_id = $post->group_id;
            $addActivityLog->comment_id = $commentId;
            $addActivityLog->action = $activityType;
            $addActivityLog->parent_id = isset($request->parent_comment_id) ? $request->parent_comment_id : null;
            $addActivityLog->action_details = $activityLogMessage;
            $addActivityLog->save();

            #send notification
            $data = [
                "message" => $message,
                "post_id" => $post->id,
                "community_id" => $post->group_id,
                "comment_id" => $addComment->id,
                "parent_id" => isset($request->parent_comment_id) ? $request->parent_comment_id : null

            ];
            if ($sender->id != $receiver->id) {

                $this->notification->sendNotificationNew($sender, $receiver, $type, $data);
            }
            DB::commit();
            #-----------        R E C O R D        A C T I V I T Y  -------------#
            return $this->addCommunityPost->getCommentById($request, $authId, trans('message.add_comment'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "addComment" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------------------------------######### E N D ######## -------------------------------------------#

    #------------------ G E T       P O S T         C O M M E N T -------------------------#

    public function comments(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [

                'post_id' => 'required|integer|exists:posts,id'
            ], [
                'post_id.integer' => 'Invalid post'
            ]);

            if ($validation->fails()) {
                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            }
            $authId = Auth::id();
            return $this->addCommunityPost->getComments($request, $authId);
        } catch (Exception $e) {

            Log::error('Error caught: "addComment" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------------ G E T       P O S T         C O M M E N T -------------------------#

    #---------- G E T       S A V E D    P O S T  ------------------------------------#
    public function savedPosts(Request $request)
    {
        try {

            $limit = 10;

            if (isset($request->limit) && !empty($request->limit)) {
                $limit = $request->limit;
            }
            $authId = Auth::id();
            $savedPosts = SavedPost::with([
                'post.post_user:id,name,profile',
                //'group:id,name,description,cover_photo,post_count',
            ])
                ->whereNotExists(function ($query) use ($authId) {
                    $query->select(DB::raw(1))
                        ->from('hidden_posts')
                        ->whereColumn('hidden_posts.post_id', '=', 'saved_posts.post_id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                        ->where('hidden_posts.user_id', '=', $authId); // Check if the current user has reported the post 
                })

                ->addSelect([
                    'is_liked' => function ($query) use ($authId) {

                        $query->selectRaw('IF(EXISTS(SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = saved_posts.post_id AND comment_id IS NULL), 1, 0)', [$authId]);
                    }
                ])->where(['user_id' => $authId])->orderByDesc('id')->simplePaginate($limit);

            if (isset($savedPosts[0]) && !empty($savedPosts[0])) {

                $savedPosts->each(function ($savedPost) use ($authId) {

                    if (isset ($savedPost->post->media_url) && !empty ($savedPost->post->media_url)) {

                        $savedPost->post->media_url = asset('storage/' . $savedPost->post->media_url);
                    }
                    if (isset ($savedPost->post->post_user) && !empty ($savedPost->post->post_user)) {
                        if (isset ($savedPost->post->post_user->profile) && !empty ($savedPost->post->post_user->profile)) {
                            $savedPost->post->post_user->profile = asset('storage/' . $savedPost->post->post_user->profile);
                        }
                    }
                    //check repost or not 
                    $isRepost = Post::where(['parent_id' => $savedPost->post_id, 'user_id' => $authId, 'is_active' => 1])->exists();
                    $savedPost->is_reposted = ($isRepost) ? 1 : 0;
                });
            }
            return $this->sendResponse($savedPosts, trans('message.saved_posts'), 200);
        } catch (Exception $e) {
            Log::error('Error caught: "addComment" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------- G E T       S A V E D    P O S T  ------------------------------------#


    #---------------   Delete Comment --------------------#
    function deleteComment(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'comment_id' => 'required|exists:comments,id',
        ]);
        if ($validate->fails()) {
            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }
        $userId = Auth::id();
        $comment = Comment::where('id', $request->comment_id)->where('is_active', 1)->first();
        if (isset($comment) && $comment->user_id == $userId) {

            #delete comment replies Logs and notification
            $comment_reply_type = trans('notification_message.comment_reply_type');
            ActivityLog::where('action', $comment_reply_type)->where('parent_id', $comment->id)->delete();
            Notification::where('notification_type', $comment_reply_type)->where('parent_id', $comment->id)->delete();

            $comment->delete();
            $commentCount = Post::select('comment_count')->where('id', $comment->post_id)->first();
            if ($commentCount->comment_count > 0) {
                decrement('posts', ['id' => $request->post_id], 'comment_count', 1); //decrement post
            }
            $activity = ActivityLog::where('post_id', $comment->post_id)->where('comment_id', $comment->id)->first();
            if (isset($activity)) {
                $activity->delete();
            }
            return $this->sendResponsewithoutData(trans('message.comment_deleted'), 200);
        } else {
            return $this->sendResponsewithoutData(trans('message.comment_not_found'), 400);
        }
    }


    #---------------  S H A R E         P O S T      I N    C H A T    ----------------#
    public function sharePost(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'type' => 'required|integer|between:1,2',
            'post_id' => ['required_if:type,1','integer', 'exists:posts,id'],
            'user_id' => ['required_if:type,2','integer', 'exists:users,id'],
            'receiver_id' => 'required|exists:users,id',
        ],['post_id.required_if'=>"post id requierd",'user_id.required_if'=>"user id requierd"]);

        if ($validate->fails()) {

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);

        } else {
            $myId     = Auth::id();
            $reciever = $request->receiver_id;
            if($request->type==1){      //share post
                $postData = Post::where(['id' => $request->post_id, 'is_active' => 1])->first();

                if (empty($postData)) {
    
                    return response()->json(['status' => 422, 'message' => "Invalid post."], 422);
                }
                // if ($myId == $reciever) {
    
                //     return response()->json(['status' => 403, 'message' => "You are not allowed to message yourself."], 403);
                // }
                // return $this->sharePostInChat($request, $myId, $reciever);
            }else {                     // share user profile
                
                $userData = User::where(['id' => $request->user_id, 'is_active' => 1])->first();

                if (empty($userData)) {
    
                    return response()->json(['status' => 422, 'message' => "Invalid user."], 422);
                }
                // return $this->shareUserInChat($request, $myId, $reciever);
            }

            return $this->shareInChat($request, $myId, $reciever);
            
        }
    }
    #---------------  S H A R E         P O S T      I N    C H A T    ----------------#

}
