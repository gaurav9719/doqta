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


class CommunityPost extends BaseController
{
    /**
     * Display a listing of the resource.
     */

    protected $addCommunityPost, $notification , $getCommunityPost;
    public function __construct(AddCommunityPost $addCommunityPost, NotificationService $notification,GetCommunityService $getCommunityPost)
    {
        $this->addCommunityPost         = $addCommunityPost;
        $this->notification             = $notification;
        $this->getCommunityPost         = $getCommunityPost;
    }
    public function index(Request $request)
    {
        $limit                  =       10;
        $authId                 =       Auth::id();

        if(isset($request->limit) && !empty($request->limit)){

            $limit              =       $request->limit;
        }

        return $this->getCommunityPost->homeScreen($request,$authId);




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

        return $this->addCommunityPost->addPost($request,$authId);
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
