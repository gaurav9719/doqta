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
class LikeController extends BaseController
{

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
        DB::beginTransaction();

        try {
           
            $validation = Validator::make($request->all(), [
                'like_type' => 'required|integer|between:1,2',
                'action' => 'required|integer|between:0,1',
                'post_id' => 'required|integer|exists:posts,id',
                'reaction' => 'required|integer|between:1,3',
                'comment_id' => $request->input('like_type') == 2 ? 'required|integer|exists:comments,id' : 'nullable|integer|exists:comments,id',
            ], [
                'reaction.*' => 'Invalid reaction.',
                'comment_id.required' => 'The comment_id field is required.',
            ]);

            if($validation->fails()){

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }else{

                $auth               =   Auth::user();
                $authId             =   Auth::id();
                $isExist            =   Post::where(['id' => $request->post_id, 'is_active' => 1])->exists();

                if (!$isExist) {

                    return $this->sendError(trans("message.no_post_found"), [], 422);

                } else {




                    $likeType           =   $request->like_type;

                    if($likeType==1){           #------------ P O S T       L I K E     ---------------#


                        $this->likeService->postLike();



                    }else{                      #------------ C O M M E N T         L I K E     ---------------#


                        $this->likeService->commentLike();

                    }
                }
            }
        }catch(Exception $e){


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
}
