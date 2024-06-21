<?php

namespace App\Http\Controllers\Api\Quota;

use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Controller;
use App\Models\UserQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class QuotaController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId     =   Auth::id();
        $date       =   date('Y-m-d');
        // Get or create the quota record for today
        $quota      =   UserQuota::firstOrCreate(

            ['user_id' => $userId, 'date' => $date],

            [
                'community_posts' => 0,
                'chatbot_messages' => 0,
                'journal_entries' => 0,
                'rewrite_with_ai' => 0,
                'friend_requests' => 0,
                'post_comments' => 0,
                'community_join_requests' => 0
            ]
        );
        $userQuota      =   UserQuota::where(['user_id' => $userId, 'date' => $date])->first();
        return $this->sendResponse($userQuota, trans("message.user_quota"), 200);
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
        $userId         =   Auth::id();
        $date           =   date('Y-m-d');
        // Get or create the quota record for today
        $quota          =   UserQuota::firstOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'community_posts' => 0,
                'chatbot_messages' => 0,
                'journal_entries' => 0,
                'rewrite_with_ai' => 0,
                'friend_requests' => 0,
                'post_comments' => 0,
                'community_join_requests' => 0
            ]
        );
        $userQuota                  =   UserQuota::where(['user_id' => $userId, 'date' => $date])->first();
        $userQuota->rewrite_with_ai++;
        $userQuota->save();
        $userQuota                  =   UserQuota::where(['user_id' => $userId, 'date' => $date])->first();
        return $this->sendResponse($userQuota, trans("message.user_quota"), 200);
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
