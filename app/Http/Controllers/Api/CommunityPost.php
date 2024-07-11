<?php

namespace App\Http\Controllers\Api;

use App\Jobs\Summarize\CommentThreadSummary;
use Exception;
use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\Comment;
use App\Models\PostLike;
use App\Models\SavedPost;
use App\Models\UserQuota;
use App\Models\HiddenPost;
use App\Models\ReportPost;
use App\Models\ActivityLog;
use App\Models\GroupMember;
use App\Traits\CommonTrait;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Traits\IsCommunityJoined;
use App\Models\GroupMemberRequest;
use App\Services\AddCommunityPost;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AddCommunity;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\EditCommunity;
use App\Traits\postCommentLikeCount;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddPostRequest;
use App\Services\GetCommunityService;
use App\Services\NotificationService;
use App\Http\Requests\EditCommunityPost;
use Illuminate\Support\Facades\Validator;
use App\Jobs\DeleteJobs\PostRecalculation;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Validation\ValidationException;
use App\Traits\SummarizePost;
use App\Traits\CommentTrait\Comments;
use App\Jobs\CommentNotificaton\CommentNotificationJob;
use Illuminate\Routing\Controller\Middleware;
use App\Http\Middleware\CheckFeatureUsage;
class CommunityPost extends BaseController 
{
    use CommonTrait, IsCommunityJoined, postCommentLikeCount, SummarizePost, Comments;
    /**
     * Display a listing of the resource.
     */
    protected $addCommunityPost, $notification, $getCommunityPost;
    public function __construct(AddCommunityPost $addCommunityPost, NotificationService $notification, GetCommunityService $getCommunityPost)
    {

       $this->middleware('checkUserQuota:community_posts')->only(['store']);
        $this->addCommunityPost = $addCommunityPost;
        $this->notification     = $notification;
        $this->getCommunityPost = $getCommunityPost;
        
    }
    public function index(Request $request)
    {
        $limit          = 10;

        $authId         = Auth::id();

        if (isset($request->limit) && !empty($request->limit)) {

            $limit      = $request->limit;
        }

        // dd($authId);
        return $this->getCommunityPost->homeScreenComponent($request, $authId);
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
        $authId             =            Auth::id();
        
        $isGroupExist       =           Group::where(['id' => $request->community_id, 'is_active' => 1])->exists();

        if ($isGroupExist) {

            if (isset($request->community_id) && !empty($request->community_id)) {

                $isExist    =           GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId, 'is_active' => 1])->exists();

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
        $validation             =           Validator::make(['id' => $id], ['id' => 'required|integer|exists:groups,id', 'recent' => 'nullable|integer', 'trending' => 'nullable|integer|between:0,2', 'confidence' => "nullable|integer|in:1", "location" => 'nullable|integer|between:0,1', 'health_provider' => 'nullable|integer']);

        if ($validation->fails()) {

            return $this->sendResponsewithoutData($validation->errors()->first(), 422);
        }

        return $this->getCommunityPost($id, Auth::id(), $request, Auth::user());
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
    // public function destroy(string $id)
    // {
    //     DB::beginTransaction();

    //     try {

    //         $auth       = Auth::user();

    //         $authId     = Auth::id();

    //         if (isset($id) && !empty($id)) {

    //             $isExist = Post::where(['id' => $id, 'user_id' => $authId])->exists();

    //             if (!$isExist) {

    //                 return $this->sendError(trans("message.no_post_found"), [], 422);

    //             } else {

    //                 Post::where('id', $id)->orWhere('parent_id', $id)->update(['is_active' => 0]);
    //                 #delete notification & activity
    //                 $posted_in_community    = trans('notification_message.posted_in_community'); //10
    //                 $like_post_type         = trans('notification_message.like_post_type'); //11
    //                 $comment_on_post_type   = trans('notification_message.comment_on_post_type'); //12
    //                 $like_comment_post_type = trans('notification_message.like_comment_post_type'); //13
    //                 $comment_reply_type     = trans('notification_message.comment_reply_type'); //14
    //                 $reposted_post_type     = trans('notification_message.reposted_post_type'); //15
    //                 $nType                  = [$posted_in_community, $like_post_type, $comment_on_post_type, $like_comment_post_type, $comment_reply_type, $reposted_post_type];

    //                 #delete notification & activity
    //                 Notification::where('post_id', $id)->orWhere('parent_id', $id)->whereIn('notification_type', $nType)->delete();
    //                 ActivityLog::where('post_id', $id)->orWhere('parent_id', $id)->whereIn('action', $nType)->delete();
    //                 Notification::where('post_id', $id)->orWhere('parent_id', $id)->whereIn('notification_type', [$reposted_post_type, $posted_in_community])->delete();
    //                 ActivityLog::where('post_id', $id)->orWhere('parent_id', $id)->whereIn('action', [$reposted_post_type, $posted_in_community])->delete();
    //                 DB::commit();
    //                 return $this->sendResponsewithoutData(trans('message.post_deleted_successfully'), 200);
    //             }
    //         } else {

    //             return $this->sendError(trans("message.post_id_required"), [], 422);
    //         }
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error caught: "get community" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);
    //     }
    // }

    #-----------------------  D E L E T E      P O S T          --------------------------------#
    // public function destroy(string $id)
    // {
    //     DB::beginTransaction();

    //     try {

    //         $authId             =       Auth::id();

    //         if (empty($id)) {

    //             return $this->sendError(trans("message.post_id_required"), [], 422);
    //         }

    //         $isExist            =       Post::where(['id' => $id, 'user_id' => $authId])->first();

    //         if (empty($isExist)) {
    //             //check group member owner and moderator
    //             $PostData       =   Post::where('id',$id)->first();
    //             //check group member
    //             $isExist        =   GroupMember::where(['user_id'=>$authId,'group_id'=>$PostData->group_id])->whereIn('role',['owner','moderator'])->first();

    //             if(isset($isExist) && !empty($isExist)){

    //                 Post::where('id', $id)->orWhere('parent_id', $id)->update(['is_active' => 0]);

    //             }else{

    //                 return $this->sendError(trans("message.cannot_delete_post"), [], 403);
    //             }

    //         }else{

    //             Post::where('id', $id)->orWhere('parent_id', $id)->update(['is_active' => 0]);

    //         }
    //         // Define notification types
    //         $nType = [
    //             trans('notification_message.posted_in_community'), //10
    //             trans('notification_message.like_post_type'),   //11
    //             trans('notification_message.comment_on_post_type'), //12
    //             trans('notification_message.like_comment_post_type'), //13
    //             trans('notification_message.comment_reply_type'), //14
    //             trans('notification_message.reposted_post_type') //15
    //         ];

    //         // Delete notifications and activity logs

    //         DB::table('notifications')->where(function ($query) use ($id) {

    //             $query->where('post_id', $id)->orWhere('parent_id', $id);

    //         })->whereIn('notification_type', $nType)->delete();

    //         ActivityLog::where(function ($query) use ($id, $nType) {

    //             $query->where('post_id', $id)->orWhere('parent_id', $id)->whereIn('action', $nType);
    //         })->delete();

    //         DB::commit();

    //         dispatch(new PostRecalculation($isExist->group_id));

    //         return $this->sendResponsewithoutData(trans('message.post_deleted_successfully'), 200);

    //     } catch (Exception $e) {

    //         DB::rollBack();

    //         Log::error('Error caught: "destroy post" ' . $e->getMessage());

    //         return $this->sendError($e->getMessage(), [], 400);
    //     }
    // }


    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {

            $authId         =       Auth::id();
            
            if (empty($id)) {

                return $this->sendError(trans("message.post_id_required"), [], 422);
            }

            // Check if the post exists and the user is authorized to delete it
            $post           =   Post::where('id', $id)

                              ->where('user_id', $authId)

                ->orWhere(function ($query) use ($id, $authId) {

                    // Check if the user is an owner or moderator of the group
                    $query->where('id', $id)

                    
                        ->whereExists(function ($query) use ($authId) {

                            $query->select(DB::raw(1))

                                ->from('group_members')

                                ->whereColumn('group_members.group_id', 'posts.group_id')

                                ->where('group_members.user_id', $authId)

                                ->whereIn('group_members.role', ['owner', 'moderator']);
                        });
                })->first();

            if (empty($post)) {

                return $this->sendError(trans("message.cannot_delete_post"), [], 403);
            }

            // Soft delete the post and its children
            Post::where('id', $id)->orWhere('parent_id', $id)->update(['is_active' => 0]);

            // Define notification types
            $nType = [
                trans('notification_message.posted_in_community'),       // 10
                trans('notification_message.like_post_type'),             // 11
                trans('notification_message.comment_on_post_type'),       // 12
                trans('notification_message.like_comment_post_type'),     // 13
                trans('notification_message.comment_reply_type'),         // 14
                trans('notification_message.reposted_post_type')          // 15
            ];

            // Delete related notifications
            DB::table('notifications')
                ->where(function ($query) use ($id) {
                    $query->where('post_id', $id)
                        ->orWhere('parent_id', $id);
                })
                ->whereIn('notification_type', $nType)
                ->delete();

            // Delete related activity logs
            ActivityLog::where(function ($query) use ($id, $nType) {
                $query->where('post_id', $id)
                    ->orWhere('parent_id', $id)
                    ->whereIn('action', $nType);
            })->delete();

            DB::commit();

            // Dispatch a job for post recalculation
            dispatch(new PostRecalculation($post->group_id));

            return $this->sendResponsewithoutData(trans('message.post_deleted_successfully'), 200);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error caught: "destroy post" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #-----------------------  D E L E T E      P O S T         --------------------------------#


    #--------------------- R E S H A R E             P O S T     -------------------#

    // public function resharePost(Request $request)
    // {
    //     DB::beginTransaction();

    //     try {

    //         $validation         =       Validator::make($request->all(), ['post_id' => 'required|integer|exists:posts,id']);

    //         if ($validation->fails()) {

    //             return $this->sendResponsewithoutData($validation->errors()->first(), 422);

    //         } else {

    //             $auth            =      Auth::user();
    //             $authId          =      Auth::id();
    //             $isExist         =      IsPostAvailable($request->post_id,$authId);

    //             if (empty($isExist) || $isExist == null) {

    //                 return $this->sendError(trans("message.no_post_found"), [], 422);

    //             } else {

    //                 $isJoined = $this->checkCommunityJoind($isExist->group_id);

    //                 if (!$isJoined) {

    //                     return $this->sendError(trans("message.please_join_community"), [], 403);
    //                 }

    //                 if (isset($isExist->parent_id) && !empty($isExist->parent_id)) {

    //                     $parent_id = $isExist->parent_id;

    //                 } else {

    //                     $parent_id = $isExist->id;
    //                 }
    //                 $post       =              Post::where(['parent_id' => $parent_id, 'user_id' => $authId])->first();

    //                 if (isset($post) && !empty($post)) {
    //                     // Record exists, delete it
    //                     $post->delete();
    //                     $action = 0;
    //                     decrement('posts', ['id' => $request->post_id], 'repost_count', 1); //decrement post
    //                     DB::commit();
    //                     $repostId = $parent_id;
    //                 } else {
    //                     // check i am the community member or not 
    //                     $isGroupMember = GroupMember::where(['group_id' => $isExist->group_id, 'user_id' => $authId, 'is_active' => 1])->exists();

    //                     if ($isGroupMember) {

    //                         $rePost = new Post();
    //                         $rePost->parent_id = $parent_id;
    //                         $rePost->user_id = $authId;
    //                         $rePost->title = $isExist->title;
    //                         $rePost->content = $isExist->content;
    //                         $rePost->media_url = $isExist->media_url;
    //                         $rePost->thumbnail = $isExist->thumbnail;
    //                         $rePost->link = $isExist->link;
    //                         $rePost->post_type = $isExist->post_type;
    //                         $rePost->group_id = $isExist->group_id;
    //                         $rePost->save();
    //                         $repostId = $rePost->id;
    //                         $action = 1;
    //                         //increment the like by one
    //                         increment('posts', ['id' => $parent_id], 'repost_count', 1);
    //                         #send notification
    //                         $group = Group::find($isExist->group_id);
    //                         $sender = Auth::user();
    //                         $receiver = User::find($isExist->user_id);
    //                         $message = $sender->name . " reposted your post in " . $group->name;
    //                         $data = [
    //                             "message" => $message,
    //                             "post_id" => $rePost->id,
    //                             "community_id" => $isExist->group_id
    //                         ];


    //                         $postUser       =   Post::select('user_id')->where('id',$parent_id)->first();
    //                         if($postUser->user_id!=$authId){

    //                             $this->notification->sendNotificationNew($sender, $receiver, trans('notification_message.reposted_post_type'), $data);
    //                         }

    //                         #-------  A C T I V I T Y -----------# 17 may
    //                         $activity = new ActivityLog();
    //                         $activity->user_id = $authId;
    //                         $activity->community_id = $group->id;
    //                         $activity->post_id = $rePost->id;
    //                         $activity->parent_id = $parent_id;
    //                         $activity->action_details = "Reposted the post in " . $group->name;
    //                         $activity->action = trans('notification_message.reposted_post_type');    //Reposted the post
    //                         $activity->save();
    //                         #-------  A C T I V I T Y -----------#
    //                         DB::commit();
    //                     } else {
    //                         return $this->sendError(trans("message.not_community_member"), [], 403);
    //                     }
    //                 }
    //                 // return $this->getPost($repostId, $authId, ($action == 0) ? trans('message.repost_removed_successfully') : trans('message.reposted'));
    //                 return $this->getPostNew($repostId, $authId, ($action == 0) ? trans('message.repost_removed_successfully') : trans('message.reposted'));
    //             }
    //         }
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error caught: "resharePost" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);
    //     }
    // }

    #------------------------------   R E S H A R E       P O S T     -----------------------------#

    public function resharePost(Request $request)
    {
        DB::beginTransaction();

        try {

            $validation                 =           Validator::make($request->all(), [

                'post_id' => 'required|integer|exists:posts,id'
            ]);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            }

            $auth                       =           Auth::user();

            $authId                     =           Auth::id();

            $isExist                    =           IsPostAvailable($request->post_id, $authId);

            if (empty($isExist)) {

                return $this->sendError(trans("message.no_post_found"), [], 422);
            }

            if (!$this->checkCommunityJoind($isExist->group_id)) {

                return $this->sendError(trans("message.please_join_community"), [], 403);
            }

            $parent_id          =   $isExist->parent_id ?? $isExist->id;

            $existingPost       =   Post::where(['parent_id' => $parent_id, 'user_id' => $authId])->first();

            if ($existingPost) {

                $existingPost->delete();

                decrement('posts', ['id' => $request->post_id], 'repost_count', 1);

                DB::commit();

                $action         =   0;

                $repostId       =   $parent_id;
            } else {

                if (GroupMember::where(['group_id' => $isExist->group_id, 'user_id' => $authId, 'is_active' => 1])->exists()) {

                    $rePost             =    new Post();

                    $rePost->parent_id  =   $parent_id;

                    $rePost->user_id    =   $authId;

                    $rePost->title      =   $isExist->title;

                    $rePost->content    =   $isExist->content;

                    $rePost->media_url  =   $isExist->media_url;

                    $rePost->thumbnail  =   $isExist->thumbnail;

                    $rePost->link       =   $isExist->link;

                    $rePost->post_type  =   $isExist->post_type;

                    $rePost->group_id   =   $isExist->group_id;

                    $rePost->post_category = $isExist->post_category;

                    $rePost->media_type =   $isExist->media_type;

                    $rePost->lat        =   $isExist->lat;

                    $rePost->long       =   $isExist->long;

                    $rePost->save();

                    increment('posts', ['id' => $parent_id], 'repost_count', 1);

                    $group              =   Group::find($isExist->group_id);

                    $sender             =   Auth::user();

                    $receiver           =   User::find($isExist->user_id);

                    $message            =   "{$sender->name} reposted your post in {$group->name}";

                    $data               =   [

                        "message" => $message,

                        "post_id" => $rePost->id,

                        "community_id" => $isExist->group_id,

                        'parent_id' => $request->post_id
                    ];

                    $postUser           =   Post::select('user_id')->where('id', $rePost->parent_id)->first();

                    if ($postUser->user_id != $authId) {

                        $this->notification->sendNotificationNew($sender, $receiver, trans('notification_message.reposted_post_type'), $data);
                    }

                    logActivity($authId, $rePost, $parent_id, $isExist->group_id);

                    DB::commit();

                    $repostId           =   $rePost->id;

                    $action             =   1;
                } else {

                    return $this->sendError(trans("message.not_community_member"), [], 403);
                }
            }

            return $this->getPostNew($repostId, $authId, $action == 0 ? trans('message.repost_removed_successfully') : trans('message.reposted'));
        } catch (Exception $e) {

            DB::rollBack();

            Log::error('Error in resharePost: ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #--------------------- ***************  E N D  ******************---------------#


    #------------------------- H I D E      P O S T     ------------------------------#

    public function hideSavePost(Request $request)
    {
        DB::beginTransaction();

        try {

            $validation                 =       Validator::make(
                $request->all(),
                [
                    'post_id' => 'required|integer|exists:posts,id',

                    'type' => 'required|integer|between:0,2'

                ],
                [
                    'post_id.*' => 'Invalid post',

                    'type.*' => 'Invalid type'
                ]
            );

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            }

            $type                       =       $request->type;

            $authId                     =       Auth::id();

            $post                       =       Post::find($request->post_id);

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
                case 2: // unhide the post

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

            $validation                         =           Validator::make(
                $request->all(),
                [

                    'post_id' => 'required|integer|exists:posts,id',
                ],

                [
                    'post_id.*' => 'Invalid post',

                ]
            );

            if ($validation->fails()) {

                throw new ValidationException($validation);
            }

            $authId                             =           Auth::id();

            $post                               =           Post::find($request->post_id);

            if (!$post || !$post->is_active) {

                throw new Exception(trans('message.no_post_found'), 422);
            }

            $data                               =           [];

            if (isset($request->report_title) && !empty($request->report_title)) {

                $data                           =           ['report_title' => $request->report_title];
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
    #--------------------********   R E P O R T     P O S T   ********------------------------#



    #----------------------- ############C O M M E N T     O N     P O S T############ ----------------------#
    public function addComment(Request $request)
    {
        DB::beginTransaction();

        try {

            $validation                             =       Validator::make(
                $request->all(),
                [

                    'post_id' => 'required|integer|exists:posts,id',

                    'parent_comment_id' => 'nullable|exists:comments,id',

                    'comment' => "required",

                    'comment_type' => "nullable|between:1,4",

                    'mention_user_id' => 'nullable|integer|exists:users,id'

                ],
                [
                    'post_id.integer' => 'Invalid post',

                    'parent_id.*' => "Invalid comment id",

                    'comment_type.between' => "Invalid comment type",

                    'mention_user_id.integer' => "Invalid mention id",

                ]
            );
            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            }

            $authId                     =   Auth::id();

            $post                       =   Post::find($request->post_id);

            if (!$post || !$post->is_active) {

                throw new Exception(trans('message.no_post_found'), 422);
            }

            // check i am group member or not 
            $isMember                   =   GroupMember::where(['group_id' => $post->group_id, 'user_id' => $authId, 'is_active' => 1])->exists();

            if (!$isMember) {

                return $this->sendError(trans('message.you_are_not_group_member'), [], 201);
            }

            $addComment                 =   new Comment();

            $addComment->user_id        =   $authId;

            $addComment->post_id        =   $request->post_id;

            $group                      =   Group::find($post->group_id);

            if (isset($request->parent_comment_id) && !empty($request->parent_comment_id)) {

                $addComment->parent_id  = $request->parent_comment_id;
                #notification data preparation
                $parentComment          =   Comment::find($request->parent_comment_id);

                $sender                 =   Auth::user();

                $receiver               =   User::find($parentComment->user_id);

                $message                =   "**{$sender->user_name}** replied to your comment on post: ** {$post->title}**";

                // $activityLogMessage     =   "Replied the comment in " . $group->name;
                $activityLogMessage     =   "**{$sender->user_name}** replied to the comment on post: ** {$post->title}**";

                $type                   =   trans('notification_message.comment_reply_type');
            } else {

                #notification data preparation
                $sender                 =   Auth::user();

                $receiver               =   User::find($post->user_id);

                $title                  =   $post->title;

                $message                =   "**{$sender->user_name}** commented on your post: ** {$post->title}**";

                $activityLogMessage     =   "**{$sender->user_name}** commented on post: ** {$post->title}**";

                $type                   =   trans('notification_message.comment_on_post_type');
            }
            if (isset($request->comment_type) && !empty($request->comment_type)) {

                $addComment->comment_type       =   $request->comment_type;
            }

            if (isset($request->mention_user_id) && !empty($request->mention_user_id)) {

                $addComment->mention_user_id    =   $request->mention_user_id;
            }

            $addComment->comment                =   $request->comment;

            $addComment->is_comment_flag        =   strlen($request->comment) > 75 ? 1 : 0; // Determine is_comment_flag based on comment length
            $addComment->save();

            $commentId                          =   $addComment->id;

            #--------------  RECORD USER QUOTA PER DAY-------------#
            if (isset($commentId) && !empty($commentId)) {

                $quotaUpdated               = UserQuota::updateQuota($authId, 'post_comment');
            }
            #--------------  RECORD USER QUOTA PER DAY-------------#
            $request['comment_id']      =    $commentId;
            #----------- R E C O R D        A C T I V I T Y -------------#
            $activityType               =   $type;

            $addActivityLog             =   new ActivityLog();

            $addActivityLog->user_id    =   $authId;

            $addActivityLog->post_id    =   $post->id;

            $addActivityLog->community_id = $post->group_id;

            $addActivityLog->comment_id =   $commentId;

            $addActivityLog->action     =   $activityType;

            $addActivityLog->parent_id  =   isset($request->parent_comment_id) ? $request->parent_comment_id : null;

            $addActivityLog->action_details = $activityLogMessage;

            $addActivityLog->save();
            #send notification

            $data                       = [
                "message" => $message,

                "post_id" => $post->id,

                "community_id" => $post->group_id,

                "comment_id" => $commentId,

                "parent_id" => isset($request->parent_comment_id) ? $request->parent_comment_id : null
            ];

            if ($sender->id != $receiver->id) {

                $this->notification->sendNotificationNew($sender, $receiver, $type, $data);
            }

            DB::commit();

            //  send notification to all user who ever comment on post

            dispatch(new CommentNotificationJob($sender, $data, $type));

            #-------- generate comment thread summary----------#
            dispatch(new CommentThreadSummary($request->post_id, $commentId));
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

            $validation             =       Validator::make(
                $request->all(),
                [

                    'post_id' => 'required|integer|exists:posts,id'
                ],

                [
                    'post_id.integer' => 'Invalid post'
                ]
            );

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            }

            $authId             =           Auth::id();

            // return $this->addCommunityPost->getComments($request, $authId); //service
            return $this->getComments($request, $authId);   // trait

        } catch (Exception $e) {

            Log::error('Error caught: "addComment" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------------ G E T       P O S T         C O M M E N T -------------------------#

    #----------------------------- G E T       S A V E D    P O S T  ------------------------------------#
    public function savedPosts(Request $request)
    {
        try {

            $limit          =       $request->input('limit', 10);

            $authId         =       Auth::id();

            $savedPosts     =       SavedPost::with([

                'post.post_user:id,name,profile',

                'post.group:id,name,description,cover_photo,post_count,created_by'
            ])
                ->whereHas('post', function ($query) use ($authId) {

                    $query->whereHas('post_user', function ($query) use ($authId) {

                        $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                            $query->where('user_id', $authId);
                        })
                            ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                                $query->where('blocked_user_id', $authId);
                            });
                    })->whereHas('group', function ($query) use ($authId) {

                        $query->where('is_active', 1);
                    });

                    #--------- c o m m e n t   on       jun 28 --------------#

                    // ->whereHas('group', function ($query) use ($authId) {

                    //     $query->whereDoesntHave('groupOwner.blockedBy', function ($query) use ($authId) {

                    //         $query->where('user_id', $authId);

                    //     })

                    //     ->whereDoesntHave('groupOwner.blockedUsers', function ($query) use ($authId) {

                    //         $query->where('blocked_user_id', $authId);

                    //     });
                    // });

                    #--------- c o m m e n t   on       jun 28 --------------#
                })
                ->whereNotExists(function ($query) use ($authId) {

                    $query->select(DB::raw(1))

                        ->from('hidden_posts')

                        ->whereColumn('hidden_posts.post_id', '=', 'saved_posts.post_id')

                        ->where('hidden_posts.user_id', '=', $authId);
                })
                ->addSelect([

                    'is_liked' => function ($query) use ($authId) {

                        $query->selectRaw('IF(EXISTS(SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = saved_posts.post_id AND comment_id IS NULL), 1, 0)', [$authId]);
                    }
                ])
                ->where('user_id', $authId)

                ->orderByDesc('id')

                ->simplePaginate($limit);

            if (isset($savedPosts[0]) && !empty($savedPosts[0])) {

                $savedPosts->each(function ($savedPost) use ($authId) {

                    if (isset($savedPost->post->media_url) && !empty($savedPost->post->media_url)) {

                        $savedPost->post->media_url                 =       addBaseUrl($savedPost->post->media_url);
                    }

                    if (isset($savedPost->post->thumbnail) && !empty($savedPost->post->thumbnail)) {

                        $savedPost->post->thumbnail                 =       addBaseUrl($savedPost->post->thumbnail);
                    }

                    if (isset($savedPost->post->post_user) && !empty($savedPost->post->post_user)) {

                        if (isset($savedPost->post->post_user->profile) && !empty($savedPost->post->post_user->profile)) {

                            $savedPost->post->post_user->profile     =      addBaseUrl($savedPost->post->post_user->profile);
                        }
                    }
                    //check repost or not 
                    $isRepost                                        =      Post::where(['parent_id' => $savedPost->post_id, 'user_id' => $authId, 'is_active' => 1])->exists();
                    $savedPost->is_reposted                          =      ($isRepost) ? 1 : 0;
                });
            }
            return $this->sendResponse($savedPosts, trans('message.saved_posts'), 200);
        } catch (Exception $e) {

            Log::error('Error caught: "addComment" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------- G E T       S A V E D    P O S T  ------------------------------------#


    #----------------------------   Delete Comment -----------------------------------#
    public function deleteComment(Request $request)
    {
        $validate               =           Validator::make($request->all(), [

            'comment_id' => 'required|exists:comments,id',
        ]);
        if ($validate->fails()) {

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }


        $userId                         =           Auth::id();

        $comment                        =           Comment::where('id', $request->comment_id)->where('is_active', 1)->first();

        if (isset($comment) && $comment->user_id == $userId) {

            #delete comment replies Logs and notification
            $comment_reply_type         =           trans('notification_message.comment_reply_type');

            ActivityLog::where('action', $comment_reply_type)->where('parent_id', $comment->id)->delete();

            Notification::where('notification_type', $comment_reply_type)->where('parent_id', $comment->id)->delete();

            $comment->delete();

            $commentCount               =           Post::select('comment_count')->where('id', $comment->post_id)->first();

            if ($commentCount->comment_count > 0) {

                decrement('posts', ['id' => $request->post_id], 'comment_count', 1); //decrement post
            }

            $activity                   =           ActivityLog::where('post_id', $comment->post_id)->where('comment_id', $comment->id)->first();

            if (isset($activity)) {

                $activity->delete();
            }

            return $this->sendResponsewithoutData(trans('message.comment_deleted'), 200);
        } else {

            return $this->sendResponsewithoutData(trans('message.comment_not_found'), 400);
        }
    }
    #----------------------------   Delete Comment -----------------------------------#



    #---------------  S H A R E         P O S T      I N    C H A T    ----------------#
    public function sharePost(Request $request)
    {
        try {

            $validate                           =       Validator::make(
                $request->all(),
                [

                    'type' => 'required|integer|between:1,2',
                    'post_id' => ['required_if:type,1', 'integer', 'exists:posts,id'],
                    'user_id' => ['required_if:type,2', 'integer', 'exists:users,id'],
                    'receiver_id' => 'required|exists:users,id',
                ],

                ['post_id.required_if' => "post id requierd", 'user_id.required_if' => "user id requierd"]
            );

            if ($validate->fails()) {

                return $this->sendResponsewithoutData($validate->errors()->first(), 422);
            } else {

                $myId                       =   Auth::id();

                $reciever                   =   $request->receiver_id;

                if ($request->type == 1) {      //share post

                    $postData               =   IsPostAvailable($request->post_id, $myId);

                    if (empty($postData)) {

                        return response()->json(['status' => 422, 'message' => "Invalid post."], 422);
                    }
                    // if ($myId == $reciever) {

                    //     return response()->json(['status' => 403, 'message' => "You are not allowed to message yourself."], 403);
                    // }
                    // return $this->sharePostInChat($request, $myId, $reciever);
                } else {                     // share user profile

                    $userData               =       IsUserBlocked($request->user_id, $myId);

                    if (empty($userData)) {

                        return response()->json(['status' => 422, 'message' => "Invalid user."], 422);
                    }
                }
                return $this->shareInChat($request, $myId, $reciever);
                //return $this->shareInChatNew($request, $myId, $reciever);
            }
        } catch (Exception $e) {


            Log::error('Error caught: "sharePost" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------  S H A R E         P O S T      I N    C H A T    ----------------#




    #=================== SUMMARIZE COMMENT 7 JUNE ========================#
    function summarizeComment(Request $request)
    {

        $a = $this->postSummaryInstruction(1);
        dd($a);
        $validate = Validator::make($request->all(), [

            'post_id' => 'required|integer|exists:posts,id',

        ]);

        if ($validate->fails()) {

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }
        $post       = Post::find($request->post_id);

        $comments = Comment::where('post_id', $request->post_id)->where('is_active', 1)->get();

        $data = array(["text" => "Post Title: $post->title"], ["text" => "Post Description: $post->content"]);

        if (count($comments) > 0) {

            foreach ($comments as $comment) {

                $details    = "Comment: $comment->comment";

                array_push($data, ['text' => $details]);
            }
        }



        array_push(
            $data,
            array("text" => "---------------------------------------------------------------------------"),
            array("text" => "Summrize the comment of the post in simple text language and easy to understand"),
            array("text" => "These comments are related to medical field, so summarize the comments accordingly"),
            array("text" => "give response in simple text, do not add headning or any style in the text"),
        );
        // return $data;

        $response = $this->summarizeCommentAi($data);
        return $response;
    }

    function summarizeCommentNew($post_id, $comment_id)
    {

        $comment       = Comment::where('id', $comment_id)->where('is_active', 1)->first();

        if (isset($comment) && !empty($comment)) {

            if (isset($comment->parent_id) && !empty($comment->parent_id)) {

                //comment
                $total_comment       = Comment::where('parent_id', $comment->parent_id)->where(['is_active' => 1, 'is_comment_flag' => 1])->count();

                if ($total_comment >= 2) {

                    $postData       =   Post::select('title', 'content')->where('id', $post_id)->first();
                    //summarize comments thread
                    if (isset($postData) && !empty($postData)) {

                        $data = array(["text" => "Post Title: $postData->title"], ["text" => "Post Description: $postData->content"]);

                        $totalComments       = Comment::where('parent_id', $comment->parent_id)->where(['is_active' => 1, 'is_comment_flag' => 1])->get();

                        foreach ($totalComments as $comment) {

                            $details    = "Comment: $comment->comment";

                            array_push($data, ['text' => $details]);
                        }
                    }
                }
            }
        }
        array_push(
            $data,
            array("text" => "---------------------------------------------------------------------------"),
            array("text" => "Summrize the comment of the post in simple text language and easy to understand"),
            array("text" => "These comments are related to medical field, so summarize the comments accordingly"),
            array("text" => "give response in simple text, do not add headning or any style in the text"),
        );
        // return $data;

        $response = $this->summarizeCommentAi($data);
        return $response;
    }



    function summarizeCommentAi($content, $count = 1)
    {

        if ($count > 3) {
            return null;
        }

        // Define your API key
        $API_KEY = "AIzaSyCN9891vVrDvLHsQvZU9M2mv-9W85dOX8g";
        // Define the URL
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key=" . $API_KEY;

        // return $data;
        $data = array(

            "system_instruction" => array("parts" => geminiInstruction(4)),
            "contents" => array(
                array(
                    "role" => "user",
                    "parts" => $content
                )
            )
        );
        // return $data;
        // Initialize cURL session
        $curl = curl_init($url);
        // Set cURL options
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        // Execute cURL request
        $response = curl_exec($curl);
        // Check for errors
        if ($response === false) {
            $error = curl_error($curl);
            $response = [
                'status' => 400,
                "message" => "Curl Error",
                "data" => $error,
            ];
            return $response;
        } else {
            // Close cURL session
            curl_close($curl);
            $response = json_decode($response, true);
            // return $response;
            try {
                if (isset($response['candidates']) && isset($response['candidates'][0]) && isset($response['candidates'][0]['content']) && isset($response['candidates'][0]['content']['parts']) && isset($response['candidates'][0]['content']['parts'][0]) && isset($response['candidates'][0]['content']['parts'][0]['text'])) {

                    $result = $response['candidates'][0]['content']['parts'][0]['text'];

                    $finalResponse = $this->convertIntoJson($result);
                    return $finalResponse;
                }
            } catch (Exception $e) {
                Log::error('Error while creating journal report: ' . $e->getMessage());
                return [
                    'status' => 400,
                    "message" => "Exception Error",
                    'data' => $e->getMessage()
                ];
            }
        }
    }

    public function textSum()
    {
        return $this->addCommunityPost->checkSum();
    }












    public function calculateScoreByAi(Request $request)
    {
        try {

            // $a = $this->commentThreadSummary(540, 568);
            // dd($a);
            if ($request->content && !empty($request->content)) {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.perplexity.ai/chat/completions",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => json_encode([
                        'model' => 'llama-3-sonar-small-32k-online',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => '

                            Instructions: You will be provided with posts from the Doqta health forum for the Black community. Your task is to thoroughly search for reputable medical sources (research papers, trusted health websites, etc.) that either support or refute the health information and advice contained in each post.
                            For each post, your output should follow this format:
                            <post> [User\'s original post text here] </post>
                            <medical_source_analysis> [Your analysis here, including:

                            A clear statement on whether you found sources supporting the medical claims made in the post

                            If sources were found:

                            An explanation of why those sources validate the health information shared

                            The specific sources cited (research papers, websites, etc.) with links

                            Relevant excerpts or summaries from the sources

                            If no supporting sources were found:

                            A statement clarifying that the post did not contain any verifiable medical information from trusted sources

                            Any other relevant analysis or context to assess the factual accuracy of the post\'s medical advice ] </medical_source_analysis>

                            Guidelines:

                            Focus solely on analyzing the health/medical aspects of each post. Ignore any non-medical statements.

                            Prioritize sources from authoritative medical journals, universities, health organizations, and government bodies when possible.

                            Provide links and citation details for all referenced sources.

                            Use clear, accessible language in your analysis to maximize understandability.

                            If multiple users discuss the same health topic across posts, you may reference and build upon your previous source analysis.

                            Maintain an objective, impartial tone focusing solely on the factual accuracy of claims.

                            Your role is to comprehensively validate or refute the medical advice provided in each Doqta user post by finding supporting or contradicting evidence from reputable sources. This will help ensure the community receives factual health guidance.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $request->content
                            ]
                        ]
                    ]),
                    CURLOPT_HTTPHEADER => [
                        "accept: application/json",
                        "authorization: Bearer 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",
                        "content-type: application/json"
                    ],
                ]);
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                // dd($response);

                if (!$err) {
                    $response_data = json_decode($response, true);
                    if (isset($response_data['choices'][0]['message']['content'])) {
                        $score = $response_data['choices'][0]['message']['content'];
                        return $this->sendResponse($score, trans('message.saved_posts'), 200);

                        if (is_numeric($score)) {
                            Log::info('is_numeric' . $score);
                            return $score;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Calculation score with AI failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
