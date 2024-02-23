<?php

namespace App\Services\Recruiter;
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
use App\Models\RecruiterBench;
/**
 * Class AddToMemberBench.
 */
class AddToMemberBench extends BaseController
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
 #------------- ADD/REJECT     R E C R U I T ------------------------#
    public function addToTeamBench($request) {

        try {
            // Validate incoming request data
            $validator = Validator::make($request->all(), [
                'type' => 'required|integer|between:0,1',
                'user_id' => 'required|exists:users,id',
                'team_id' => 'required_if:type,==,1|integer|exists:my_teams,id'
            ],['team_id.required_if'=>"Team id required"]);
            if ($validator->fails()) {
                // If validation fails, return error response
                return $this->sendResponsewithoutData(validationErrorsToString($validator->errors()), 422);

            } else {
                // If validation passes, proceed with the operation
                $authUser   = Auth::user();
                $type       = $request->type;
                if ($type == 1) {       // Adding to team member

                   return $this->addToMember($request,$authUser);

                } elseif ($type == 0) { // Adding to bench
                  
                    return $this->addToBench($request,$authUser);
                }
            }
        } catch (Exception $e) {
            Log::error('Error caught: "addToTeamBench" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400); // Return error response
        }
    }
    #------------------------------******* E N D ******------------------------------# 

    #------------------------ A D D        T O      M E M B E R  -------------------#
    public function addToMember($request,$authUser){
        
        DB::beginTransaction();
        try {
            $isExist = MyTeam::where(['id' => $request->team_id, 'recruiter_id' => $authUser->id])->first();

            if (isset($isExist) && !empty($isExist)) {

                if($isExist->member_id==$request->user_id){

                    return $this->sendResponsewithoutData(trans('message.same_user'), 403);
                }
                // Check if the user is already added to the team
                $alreadyAdded = MyTeamMember::where(['team_id' => $request->team_id, 'dater_id' => $request->user_id])->count();
                if ($alreadyAdded > 0) {
                    // User is already added to the team
                    return $this->sendResponsewithoutData(trans('message.already_exist_in_member_list'), 400);

                } else {
                    // Add the user to the team's member list
                    $addToTeam = new MyTeamMember();
                    $addToTeam->team_id = $request->team_id;
                    $addToTeam->member_id = $isExist->member_id;
                    $addToTeam->dater_id = $request->user_id;
                    $addToTeam->recruiter_type = $isExist->team_type;
                    $addToTeam->is_active = 1;
                    $addToTeam->save();
                    DB::commit(); // Commit transaction
                    return $this->sendResponsewithoutData(trans('message.added_to_member_list'), 200);
                }
            } else {
                // Team not found
                return $this->sendResponsewithoutData(trans('message.no_team_found'), 400);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "addToMember" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------------------------- E N D  -------------------------------------#

    #-------------------- A D D         T O      B E N C H -------------------------#
    public function addToBench($request,$authUser){
        DB::beginTransaction();
        try {

            RecruiterBench::updateOrCreate(
                ['user_id' => $authUser->id, 'rejectd_user_id' => $request['user_id']],
                ['is_active' => 1]
            );
            DB::commit(); // Commit transaction
            return $this->sendResponsewithoutData(trans('message.added_to_bench'), 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "addToMember" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);   
        }
    }
    #-------------------- A D D         T O      B E N C H -------------------------#
}
