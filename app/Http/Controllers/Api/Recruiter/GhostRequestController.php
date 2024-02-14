<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\RecruiterRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AcceptRejectGhostRequest;
use App\Models\Recruiter;
use Carbon\Carbon;
use App\Models\MyTeam;
use App\Models\User;
use Illuminate\Support\Str;

class GhostRequestController extends BaseController
{
    //

    #-----------------  G E T    G H O S T    C O A C H     R E Q U E S T ------------------#
    public function ghostCoachRequest(Request $request)
    {
        try {
            $authUser = Auth::user();
            $limit = 10;
            // dd($authUser);
            if (isset($request["limit"]) && !empty($request["limit"])) {
                $limit = $request["limit"];
            }
            if ($authUser->current_role_id == 3) {          // 3 means recruiter

                $request = RecruiterRequest::with([
                    'requested_user' => function ($query) {

                        $query->select('id', 'email', 'profile_pic');

                    }
                ])->whereHas('requested_user', function ($query) {

                    $query->where('is_active', "=", "1");
                })
                    ->where('recruiter_id', $authUser->id)
                    ->where('is_active', 1)
                    ->simplePaginate($limit);

                return $this->sendResponse($request, trans('message.ghost_coach_request'), 200);

            } else {

                return $this->sendResponse([], trans('message.something_went_wrong'), 403);
            }

        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: ghostCoachRequest ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #--------------------------------------- E N D  ----------------------------------------#

    #----------  A C C E P T  / R E J E C T     C O A C H       R E Q U E S T -------------#
    public function acceptRejectGhostReq(AcceptRejectGhostRequest $request)
    {
        try {
            $ghostCoach=2;
            $authUser = Auth::user();
            // Check if the user is a recruiter
            if ($authUser->current_role_id != 3) {

                return $this->sendResponse([], trans('message.something_went_wrong'), 403);
            }
            // Fetch the recruiter request
            $recruiterRequest = RecruiterRequest::where(['recruiter_id' => $authUser->id, 'id' => $request->request_id])->first();
            // Check if the recruiter request exists
            if (!$recruiterRequest) {

                return $this->sendResponsewithoutData(trans('message.ghost_coach_request_not_found'), 404);

            }
            // Check if the recruiter request has already been processed
            if ($recruiterRequest->request_status != 0) {
                return $this->sendResponsewithoutData(trans('message.ghost_coach_already_processrequest'), 400);
            }

            DB::beginTransaction();

            // Update the recruiter request status
            $recruiterRequest->request_status = $request->action;
            $recruiterRequest->is_active = 0;
            $recruiterRequest->save();
            // Handle request acceptance
            if ($request->action == 1) {

                $responseMessage = trans('message.ghost_accept_request');
                $addRecruiter = new Recruiter();
                $addRecruiter->dater_id = $recruiterRequest->user_id;
                $addRecruiter->recruiter_id = $recruiterRequest->recruiter_id;
                $addRecruiter->recruiter_type = $ghostCoach; // ghost coach
               // $addRecruiter->request_on = $recruiterRequest->created_at->format('Y-m-d');
                $addRecruiter->save();
                #------------ A D D     D A T E R     T O     M Y       T E A M ------------#
                $member         =       User::select('name')->where('id', $recruiterRequest->user_id)->first();
                $myTeam         =       new MyTeam();
                $myTeam->recruiter_id=  $authUser->id;
                $myTeam->member_id=  $recruiterRequest->user_id;
                $myTeam->team_name=  (isset($member) && !empty($member)) ? Str::plural($member->name) . " Team" : "Roster's user Team" ;

                $myTeam->team_type=  $ghostCoach;
                $myTeam->save();
                #------------ A D D     D A T E R     T O     M Y       T E A M ------------#

            } else {

                $responseMessage = trans('message.ghost_reject_request');
                
            }

            DB::commit();
            return $this->sendResponsewithoutData($responseMessage, 200);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: ghostCoachRequest ' . $e->getMessage());
            return $this->sendResponse([], trans('message.something_went_wrong'), 400);
        }
    }
    #--------------------------------------- E N D   --------------------------------------#



}
