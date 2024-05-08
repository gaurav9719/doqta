<?php

namespace App\Http\Controllers\Api\Likes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Services\Like\likesService;
use App\Traits\IsCommunityJoined;
use App\Models\ReportToComment;
class LikeController extends BaseController
{
    use IsCommunityJoined;

    protected $getUser ,$likeService;
    #--------------  S I G N U P        P R O C E S S  ------------------------#

    public function __construct(likesService $likeService)
    {
        
        $this->likeService = $likeService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(Request $request)
    {
        try {
           
            $validation = Validator::make($request->all(), [
                'like_type' => 'required|integer|between:1,2',
                'action' => 'required|integer|between:0,1',
                'post_id' => 'required|integer|exists:posts,id',
                'reaction' => 'required|integer|between:1,3',
                'comment_id' => $request->input('like_type') == 2 ? 'required|integer|exists:comments,id' : 'nullable|integer|exists:comments,id',
            ], [
                'reaction.integer' => 'Invalid reaction.',
                'reaction.between' => 'Invalid reaction.',
                'comment_id.required' => 'The comment_id field is required.',
            ]);

            if($validation->fails()){

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }else{

                $auth               =   Auth::user();
                $authId             =   Auth::id();
                $isExist            =   Post::select('group_id')->where(['id' => $request->post_id, 'is_active' => 1])->first();

                if (empty($isExist)) {

                    return $this->sendError(trans("message.no_post_found"), [], 422);

                } else {

                    //check community is joined or not

                    if($this->checkCommunityJoind($isExist->group_id)){

                        $likeType           =   $request->like_type;
    
                        if($likeType==1){           #------------ P O S T       L I K E     ---------------#
    
                            return $this->likeService->postLike($request,$authId);
    
                        }else{                      #------------ C O M M E N T         L I K E     ---------------#
    
                            return $this->likeService->commentLike($request,$authId);
    
                        }
                    }else{

                        return $this->sendError(trans("message.you_are_not_group_member"), [], 403);

                    }
                    
                }
            }
        }catch(Exception $e){

            Log::error('Error caught: "post and comment like" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    #------------  R E P O R T      C O M M E N T   -----------------_#

    public function reportComment(Request $request){

        DB::beginTransaction();
        try {
            $authId     = Auth::id();
            $validation = Validator::make($request->all(), [
                'post_id' => 'required|integer|exists:posts,id',
                'reason' => 'nullable|string',
                'comment_id' => 'required|integer|exists:comments,id',
            ], [
                'post_id.integer' => 'Invalid post.',
                'reason.between' => 'Invalid reaction.',
                'comment_id.integer' => 'Invalid comment.',
            ]);
            if($validation->fails()){

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }else{

                $data               =   [];
                $data['user_id']    =   $authId;
                $data['comment_id'] =   $request->comment_id;
                $data['post_id']    =   $request->post_id;
                // DB::table('report_to_comments')->insert($data);
                ReportToComment::create($data);
                DB::commit();
                return $this->sendResponsewithoutData(trans('message.reported_successfully_submitted'), 200);
            }
        }catch(Exception $e){
            DB::rollBack();
            Log::error('Error caught: "report to comment" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
