<?php

namespace App\Services\Dater;
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
use App\Models\UserBench;
use App\Models\UserSwipe;

/**
 * Class AddToRosterBench.
 */
class AddToRosterBench extends BaseController
{
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
    #----------------- A D D    T O     R O S T E R     B E N C H --------------------#
    public function addToRosterBench($request){

        try {

            $validator = Validator::make($request->all(), [
                'type' => 'required|integer|between:0,1',
                'user_id' => 'required|exists:users,id',
                'id' => 'required|integer|exists:my_team_members,id',
            ],['type.between'=>"Invalid type"]);

            if ($validator->fails()) {

                return response()->json([
                    'success' => 422,
                    'message' => $validator->errors()->first(),
                ], 422);

            } else {
                $authId     = Auth::id();
                $authUser   = Auth::user();
                if ($request->type == 1) {        #****-----------------ADD TO ROSTER----------------- ****#

                    return $this->addToRoster($request,$authId,$authUser);

                } elseif ($request->type == 0) {  #****-----------------ADD TO BENCH------------------ ****#

                    return $this->addToBench($request,$authUser);
                }
            }
        } catch (Exception $e) {
            Log::error('Error caught: "addToRoster" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #----------------------------------- E N D -----------------------------------#
    

    #---------------- A D D      T O      R O S T E R  ---------------------#
    public function addToRoster($request,$authId,$authUser){
        // ADD TO ROSTER
        DB::beginTransaction();
        try {
            $isExist            = MyTeamMember::where(['id' => $request->id, 'member_id' => $authId])->first();

            if (isset($isExist) && !empty($isExist)) {

                $roster         =   MyRoster::where('user_id', $authId)->where('roster_id', $isExist->dater_id)
                                    ->where('is_active', 1)
                                    ->exists();

                if (!$roster) {
                    $recruiter                 = MyTeam::where(['id' => $isExist->team_id, 'member_id' => $authId])->first();
                    $addToRoster               = new MyRoster();
                    $addToRoster->user_id      = $authId;
                    $addToRoster->roster_id    = $isExist->dater_id;
                    $addToRoster->recruiter_id = (isset($recruiter) && !empty($recruiter)) ? $recruiter->recruiter_id : null;
                    $addToRoster->my_team_member_id = $isExist->id;
                    $addToRoster->save();


                    #----------  A D D   E N T R Y      T O     U S E R      S W I P E -------#

                    UserSwipe::updateOrCreate(['swiping_user_id'=>$authId,'swiped_user_id'=>$isExist->dater_id,'role_id'=>$authUser->current_role_id],['swipe_type'=>1,'is_active'=>1]);
                    //---------- SEND PUSH NOTIFICATION TO DATER THAT NEW MEMBER ADDED IN YOUR LIST---------------#
                    $reciever                           =       User::select('id','current_role_id', 'device_token', 'device_type')->where("id", $isExist->member_id)->first();
                    $notification_type                  =       trans('notification_message.received_new_dater_message_type');
                    $notification_message               =       trans('notification_message.received_new_dater_message');

                    #--------------------- S W I P E        U S E R  ----------------------------#

                    $mutualSwipe                        =       MyRoster::where('user_id', $isExist->dater_id)
                                                                ->where('roster_id', $authId)
                                                                ->where('is_active', 1)
                                                                ->first();
                    if (isset($mutualSwipe) && !empty($mutualSwipe)) {
                        
                        $matched            = new PartnerMatch();
                        $matched->user1_id = $authId;
                        $matched->user2_id = $isExist->dater_id;
                        $matched->matched_on= Carbon::now();
                        $matched->save();

                        #------- send notification to both user when mutual  match found --------#
                        $notification_type = trans('notification_message.new_match_found_type');
                        $notification_message = trans('notification_message.new_match_found');
                        $reciever = User::find($isExist->dater_id, ['id', 'current_role_id', 'device_token', 'device_type']);
                        $this->notification->sendNotification(2, $reciever, $authUser, $notification_message, $notification_type);
                        $this->notification->sendNotification(2, $authUser, $reciever, $notification_message, $notification_type);
                        #------- send notification to both user when mutual  match found --------#

                        MyTeamMember::whereIn('id', [$mutualSwipe->my_team_member_id, $isExist->id])->update(['request_status' => 3]);
                        MyRoster::whereIn('id', [$addToRoster->id, $mutualSwipe->id])->update(['match_id' => $matched->id]);
                    } else {

                        MyTeamMember::where(['id' => $request->id, 'member_id' => $authId])->update(['request_status' => 1]);
                    }
                    DB::commit();
                    return $this->sendResponsewithoutData(trans('message.add_to_roster'), 200);
                } else {
                    return $this->sendResponsewithoutData(trans('message.already_exist_roster'), 400);
                }
            } else {
                return $this->sendResponsewithoutData(trans('message.no_roster_found'), 422);
            }
        }
        catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "addToRoster" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);

        }
    }
    #------------------------------- E N D  --------------------------------#

    #-----------------------  ADD TO BENCH --------------------------------#
    public function addToBench($request,$authUser){
        DB::beginTransaction();
        try {
            $user_id         = $authUser->id;
            $request_user_id = $request['user_id'];

            UserBench::updateOrCreate(
                ['user_id' => $user_id, 'rejectd_user_id' => $request_user_id],
                ['is_active' => 1]
            );

            MyRoster::where(function ($query) use ($user_id, $request_user_id) {
                $query->where(['user_id' => $user_id, 'roster_id' => $request_user_id])
                    ->orWhere(['user_id' => $request_user_id, 'roster_id' => $user_id]);
            })->update(['is_active' => 0]);

            PartnerMatch::where(function ($query) use ($user_id, $request_user_id) {
                $query->where(['user1_id' => $user_id, 'user2_id' => $request_user_id])
                    ->orWhere(['user2_id' => $request_user_id, 'user1_id' => $user_id]);
            })->update(['is_active' => 0]);

            DB::commit();   // Commit transaction
            return $this->sendResponsewithoutData(trans('message.added_to_bench'), 200);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "addToBench" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------------------  E N D ------------------------------------#
}
