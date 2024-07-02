<?php

namespace App\Http\Controllers\Api\Likes;

use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\Request;
use App\Models\ReportToComment;
use App\Traits\IsCommunityJoined;
use Illuminate\Support\Facades\DB;
use App\Services\Like\likesService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Models\Like;

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
    public function index(Request $request)
    {
        //

        $validation     =   Validator::make($request->all(), [
            'post_id' => 'required|integer|exists:posts,id'
        ]);
        
        if($validation->fails()){

            return $this->sendResponsewithoutData($validation->errors()->first(), 422);
        }

        $likes          =   PostLike::where('post_id', $request->post_id)->where('is_active', 1)->with('user_details:id,name,user_name,profile')->orderBy('created_at', 'desc')->get();

        return $this->sendResponse($likes, "Post Reactions", 200);
        
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
                'like_type' => 'required|integer|between:1,4',
                'action' => 'required|integer|between:0,1', // 0: remove, 1 add 
                'post_id' => 'required|integer|exists:posts,id',
                'reaction' => $request->input('action') == 1 ?'required|integer|between:1,3':'nullable',
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
    
                        }elseif ($likeType==2) {
                         
                            #------------ C O M M E N T         L I K E     ---------------#
                            return $this->likeService->commentLike($request,$authId);
    
                        }elseif ($likeType==3 || $likeType==4 ) {

                           #-------------------- T H R E A D    S U M M A R Y    L I K E --------------------#

                           return $this->likeService->likeSummary($request,$authId);
                        }
                    }else{

                        return $this->sendError(trans("message.you_are_not_group_member"), [], 201);

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
    public function show(Request $request,string $id)
    {
       
        if(!Post::where(['id'=>$id,'is_active'=>1])->exists()){

            return $this->sendResponsewithoutData("Invalid post", 422);

        }

        $authId                     =   Auth::id();

        $reaction['totalLike']      =   PostLike::where('post_id',$id)->count();
        $reaction['support']        =   PostLike::where(['post_id'=>$id,'reaction'=>1])->count();   //support
        $reaction['helpful']        =   PostLike::where(['post_id'=>$id,'reaction'=>2])->count();   //helpful
        $reaction['unhelpful']      =   PostLike::where(['post_id'=>$id,'reaction'=>3])->count();   //unhelpful
        $limit                      =   $request->input('limit',10);

        $totalLike                  =   PostLike::select('id','user_id','post_id','reaction','created_at')->where('post_id',$id)->with(['user'=>function($user) use($authId){

                                            $user->select('id','name','user_name','profile')

                                            ->with(['user_medical_certificate'=>function($q){

                                                $q->select('id', 'medicial_degree_type', 'user_id');

                                                },'user_medical_certificate.medical_certificate'=>function ($q) {

                                                    $q->select('id', 'name');
                                                }
                                            ])
                                       

                                        ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                                            $query->where('user_id', $authId);
                                        })
                                        ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
                    
                                            $query->where('blocked_user_id', $authId);
                                        });
                                    }]);

                                $totalLike=$totalLike->when($request->type, function($filter) use($request){

                                $filter->where('reaction',$request->type);

                            })->simplePaginate($limit);

        if(isset($totalLike[0]) && !empty($totalLike[0])){

            $totalLike->getCollection()->transform(function ($user) {

                if(isset($user) && !empty($user)){

                    $user->user->profile  = addBaseUrl($user->user->profile);
                }
                return $user;
            });

        }
        return response()->json(['status'=>200,'message'=>"Post reaction",'data'=>$totalLike,'reaction'=>$reaction]);
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
