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
use Illuminate\Validation\ValidationException;

class CommunityPost extends BaseController
{
    /**
     * Display a listing of the resource.
     */

    protected $addCommunityPost, $notification, $getCommunityPost;
    public function __construct(AddCommunityPost $addCommunityPost, NotificationService $notification, GetCommunityService $getCommunityPost)
    {
        $this->addCommunityPost         = $addCommunityPost;
        $this->notification             = $notification;
        $this->getCommunityPost         = $getCommunityPost;
    }
    public function index(Request $request)
    {
        $limit                  =       10;
        $authId                 =       Auth::id();
        // dd($authId);
        if (isset($request->limit) && !empty($request->limit)) {

            $limit              =       $request->limit;
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
        $authId             =   Auth::id();
        //check if you are the member of 
        if (isset($request->community_id) && !empty($request->community_id)) {
            $isExist        =   GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId])->exists();
            if (!$isExist) {
                return $this->sendError(trans("message.not_group_member"), [], 403);
            }
        }

        return $this->addCommunityPost->addPost($request, $authId);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        $authId     =   Auth::id();

        // if (isset($request->community_id) && !empty($request->community_id)) { //check you are group member or not

        //     $isExist        =   GroupMember::where(['group_id' => $request->community_id, 'user_id' => $authId])->exists();
        //     if (!$isExist) {

        //         return $this->sendError(trans("message.not_group_member"), [], 403);
        //     }
        // }

        $isExist    =   Post::where(['id' => $id, 'user_id' => $authId])->exists(); // check post is your or not

        if ($isExist) {   // edit the post

            return $this->addCommunityPost->editPost($request, $authId, $id);
        } else { //invalid post

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

            $auth               =   Auth::user();
            $authId             =   Auth::id();

            if (isset($id) && !empty($id)) {

                $isExist        =   Post::where(['id' => $id, 'user_id' => $authId])->exists();

                if (!$isExist) {

                    return $this->sendError(trans("message.no_post_found"), [], 422);

                } else {


                    Post::where('id', $id)->orWhere('parent_id', $id)->update(['is_active' => 0]);
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
    public function likePost(Request $request)
    {
        DB::beginTransaction();

        try {

            $validation         =   Validator::make($request->all(),['post_id'=>'required|integer|exists:posts,id']);

            if($validation->fails()){

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }else{

                $auth               =   Auth::user();
                $authId             =   Auth::id();
                $isExist            =   Post::where(['id' => $request->post_id, 'is_active' => 1])->exists();

                if (!$isExist) {

                    return $this->sendError(trans("message.no_post_found"), [], 422);

                } else {

                    $post       =   PostLike::where(['post_id' => $request->post_id, 'user_id' => $authId])->first();

                    if ($post) {
                        // Record exists, delete it
                        $post->delete();
                        $action =   0;
                        decrement('posts', ['id' => $request->post_id], 'like_count', 1); //decrement post
                        DB::commit();
                    } else {

                        $newPost            = new PostLike();
                        $newPost->post_id   = $request->post_id;
                        $newPost->user_id   = $authId;
                        $newPost->save();
                        $action =   1;
                        //increment the like by one
                        increment('posts', ['id' => $request->post_id], 'like_count', 1); 
                        DB::commit();
                    }
                    return $this->sendResponse($action, trans('message.update_successfully'), 200);
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
            
            $validation         =   Validator::make($request->all(),['post_id'=>'required|integer|exists:posts,id']);
            if($validation->fails()){

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }else{

                $auth               =   Auth::user();
                $authId             =   Auth::id();
                $isExist            =   Post::where(['id' => $request->post_id, 'is_active' => 1])->first();
    
                if (empty($isExist) || $isExist==null) {

                    return $this->sendError(trans("message.no_post_found"), [], 422);

                } else {

                    if(isset($isExist->parent_id) && !empty($isExist->parent_id)){

                        $parent_id     =   $isExist->parent_id;
                    }else{
                        
                        $parent_id     =   $isExist->id;
                    }
                 
                    $post       =   Post::where(['parent_id' => $parent_id, 'user_id' => $authId])->first();
                    //dd(DB::getQueryLog());
                    
                    if (isset($post) && !empty($post)) {
                        // Record exists, delete it
                        $post->delete();
                        $action                 =   0;
                        decrement('posts', ['id' => $request->post_id], 'repost_count', 1); //decrement post
                        DB::commit();
                        $repost               =   null;
                    } else {
                   
                        $rePost                =   new Post();
                        $rePost->parent_id     =   $parent_id;
                        $rePost->user_id       =   $authId;
                        $rePost->title         =   $isExist->title;
                        $rePost->media_url     =   $isExist->media_url;
                        $rePost->link          =   $isExist->link;
                        $rePost->post_type     =   $isExist->post_type;
                        $rePost->group_id      =   $isExist->group_id;


                        $rePost->save();
                        $repostId              =   $rePost->id;   
                        $action =   1;
                        //increment the like by one
                        increment('posts', ['id' => $parent_id], 'repost_count', 1);
                        DB::commit();
                        $repost = Post::where('id', $repostId)
                        ->with(['parent_post' => function ($query) {
                            $query->select('id', 'user_id', 'title', 'repost_count', 'like_count', 'comment_count', 'is_high_confidence')
                                ->where('is_active', 1)
                                ->with(['post_user' => function ($query) {
                                    $query->select('id', 'name', 'profile');
                                }]);
                        }])->first();

                        if ($repost && $repost->parent_post && $repost->parent_post->post_user && $repost->parent_post->post_user->profile) {
                            $repost->parent_post->post_user->profile = asset('storage/'.$repost->parent_post->post_user->profile);         
                        }
                    }
                    return $this->sendResponse($repost, ($action==0)?trans('message.repost_removed_successfully'):trans('message.reposted'), 200);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "like post" ' . $e->getMessage());
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
                'type' => 'required|integer|between:0,1'
            ], [
                'post_id.*' => 'Invalid post',
                'type.*' => 'Invalid type'
            ]);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }

            $type       =       $request->type;
            $authId     =       Auth::id();
            $post       =       Post::find($request->post_id);

            if (!$post || !$post->is_active) {

                return $this->sendResponsewithoutData(trans('message.no_post_found'), 422);

            }
            switch ($type) {

                case 0: // Hide the post
                    $message = trans('message.hide_post_successfully');
                    HiddenPost::updateOrCreate(['user_id' => $authId, 'post_id' => $request->post_id]);
                    break;
                case 1: // Save the post

                    $message = trans('message.saved_post_successfully');
                    SavedPost::updateOrCreate(['user_id' => $authId, 'post_id' => $request->post_id]);
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
    public function reportPost(Request $request){

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

            $authId             =   Auth::id();
            $post               =   Post::find($request->post_id);

            if (!$post || !$post->is_active) {

                throw new Exception(trans('message.no_post_found'), 422);
            }
            $data       =   [];
            if(isset($request->report_title) && !empty($request->report_title)){

                $data   =   ['report_title'=>$request->report_title];
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


}
