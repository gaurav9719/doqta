<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recruiter;
use Illuminate\Support\Facades\DB;
use Exception;  
use App\Http\Controllers\Api\BaseController;
use App\Models\MyTeam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserBench;
use App\Models\RecruiterBench;
use Carbon\Carbon;
use App\Models\MyTeamMember;
use Illuminate\Support\Facades\Validator;


class AddToMember extends BaseController
{
    //
    
    #------------- ADD/REJECT     R E C R U I T ------------------------#
    public function addToTeamBench(Request $request) {
        DB::beginTransaction(); // Begin a database transaction
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
                if($authUser->current_role_id==2){

                    return $this->sendResponsewithoutData(trans("message.invalidUser"), 403);
                }
                if ($type == 1) {       // Adding to team member

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
                            $addToTeam->save();
                            DB::commit(); // Commit transaction
                            return $this->sendResponsewithoutData(trans('message.added_to_member_list'), 200);
                        }
                    } else {
                        // Team not found
                        return $this->sendResponsewithoutData(trans('message.no_team_found'), 400);
                    }
                } elseif ($type == 0) { // Adding to bench
                    // Update or create a record in the recruiter bench table
                    RecruiterBench::updateOrCreate(
                        ['user_id' => $authUser->id, 'rejectd_user_id' => $request['user_id']],
                        ['is_active' => 1]
                    );
                    DB::commit(); // Commit transaction
                    return $this->sendResponsewithoutData(trans('message.added_to_bench'), 200);
                }
            }
        } catch (Exception $e) {
            DB::rollBack(); // Rollback transaction in case of an exception
            Log::error('Error caught: "addBenchRecurit" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400); // Return error response
        }
    }
    
    #------------------------------******* E N D ******------------------------------# 
}
