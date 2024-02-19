<?php

namespace App\Http\Controllers\Api\Dater;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recruiter;
use Illuminate\Support\Facades\DB;
use Exception;  
use App\Http\Controllers\Api\BaseController;
use App\Models\MyTeam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\MyTeamMember;
use App\Models\MyRoster;
use carbon\Carbon;
use App\Models\User;
use App\Models\PartnerMatch;
use App\Models\Notification;
use App\Services\NotificationService;
class AddToRoster extends BaseController
{
    //
    protected $user, $authId,$notification;


    public function __construct(NotificationService $notification)
    {
        $this->notification         = $notification;
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });
    }


    public function addToRoster(Request $request){
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), ['id'=>'required|exists:my_team_members,id']);
    
            if ($validator->fails()) {
                return response()->json([
                    'success'   => 422,
                    'message'   => $validator->errors()->first(),
                ],422);
            }else{
    
                $authId         =  Auth::id();
                $authUser       =  Auth::user();
                $isExist        =  MyTeamMember::where(['id',$request->id,'member_id'=>$authId])->first();
                if(isset($isExist) && !empty($isExist)){
    
                    $roster     = MyRoster::where('user_id', $authId)
                                    ->where('roster_id', $isExist->dater_id)
                                    ->where('is_active', 1)
                                    ->exists();
    
                    if(!$roster){
    
                        $recruiter                      =   MyTeam::where(['id'=>$isExist->team_id,'member_id'=>$authId])->first();
                        $addToRoster                    =   new MyRoster();
                        $addToRoster->user_id           =   $authId;
                        $addToRoster->roster_id         =   $isExist->dater_id;
                        $addToRoster->recruiter_id      =   (isset($recruiter) && !empty($recruiter))?$recruiter->recruiter_id:null;
                        $addToRoster->my_team_member_id =   $isExist->id;
                        $addToRoster->save();
    
                       // Check if there's a mutual add to roster
                       $mutualSwipe = MyRoster::where('user_id', $isExist->dater_id)
                       ->where('roster_id', $authId)
                       ->where('is_active', 1)
                       ->exists();
    
                        if(isset($mutualSwipe) && !empty($mutualSwipe)){
    
                            $matched                        =   new PartnerMatch();
                            $matched->user1_id              =   $authId;
                            $matched->user1_id              =   $isExist->dater_id;
                            $matched->save();
                            // send push notification to both user for its match send the point to recruiter
                            $notification_type              =   trans('notification_message.new_match_found_type');
                            $notification_message           =   trans('notification_message.new_match_found');
                            $reciever                       =   User::find($isExist->dater_id, ['id','current_role_id', 'device_token', 'device_type']);
                            $this->notification->sendNotification(2,$reciever,$authUser,$notification_message,$notification_type);
                            $this->notification->sendNotification(2,$authUser,$reciever,$notification_message,$notification_type);
                        }
                        DB::commit();
                        return $this->sendResponsewithoutData(trans('message.add_to_roster'), 200);
                    }else{

                        return $this->sendResponsewithoutData(trans('message.already_exist_roster'), 200);

                    }
                }else{
    
                    return $this->sendResponsewithoutData(trans('message.no_roster_found'), 422);
    
                }
            }
        } catch (Exception $e) {
            
            DB::rollback();
            Log::error('Error caught: "addToEoster" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
