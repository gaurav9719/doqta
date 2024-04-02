<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;

class Notifications extends BaseController
{
    //
    public function notifications(Request $request){
        DB::beginTransaction();
        try {

            $limit      =   10;
            if(isset($request->limit) && !empty($request->limit)){
                $limit  =   $request->limit;
            }
            $authUser   =   Auth::user();
            $notifications = Notification::with(['sender' => function ($query) {
                $query->select('id', 'name', 'profile_pic');

            }])
            ->where(['receiver_id'=>$authUser->id,'role_id'=>$authUser->current_role_id])
            ->orderByDesc('id')
            ->simplePaginate($limit);
            DB::commit();
            // $notification=  Notification::where(['receiver_id'=>$authUser->id])->simpplePaginate($limit);
            return $this->sendResponse($notifications, trans('message.notifications'), 200);

        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "notifications" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);            
        }
    }
}
