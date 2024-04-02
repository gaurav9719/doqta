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
use App\Models\Stat;
use Illuminate\Support\Facades\Validator;
use App\Models\MyTeamMember;
use App\Services\Dater\AddToRosterBench;
use App\Services\Recruiter\AddToMemberBench;
class RecuitsController extends BaseController
{

    protected $user, $addRosterBench, $addMemberBench, $getUser,$authId;
    // protected $userProfile;
    public function __construct(AddToRosterBench $addToRosterBench, AddToMemberBench $addToMemberBench)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });

        $this->addRosterBench = $addToRosterBench;
        $this->addMemberBench = $addToMemberBench;
    }


    //
    #----------  G E T       R A N D O M        R E C R U I T  ------------------#
    public function recruits(Request $request){
        try {
           
       
            $authUser                              =      Auth::user();
            $userId                                =      Auth::id();
            
            $distance                              =      10;
            if(isset($request->distance) && !empty($request->distance)) {
            
                $distance                           =   $request->distance;
            }
            $limit=10;
            if(isset($request->limit) && !empty($request->limit)) {
            
                $limit                              =   $request->limit;
            }
            $randomUser                             =      User::select('id','name','user_name','email','dob','reference_code','current_role_id','lat','long','gender','profile_pic', DB::raw("round(3959 * acos(cos(radians('" . $authUser->lat . "'))* cos(radians(`lat`))* cos(radians(`long`)- radians('" . $authUser->long . "'))+ sin(radians('" . $authUser->lat . "'))* sin(radians(`lat`))),2) AS distance"))
           ->whereHas('user_roles', function ($query) {
                $query->where('role_id', 2);
            })
            ->where(['is_active' => 1])
            ->where("id", "<>", $authUser->id)


            // ->whereNotExists(function ($subquery) use ($userId) {

            //     $subquery->select(DB::raw(1))
            //         ->from('recruiter_benches')
            //         ->whereRaw("user_id = '" . $userId . "' AND rejectd_user_id = users.id AND is_active = 1");
            // })


            // ->whereExists(function ($query) {
            //     $query->select(DB::raw(1))
            //         ->from('user_stats')
            //         ->whereRaw('users.id = user_stats.user_id');
            // })

            ->whereNotExists(function ($subquery) use ($userId,$authUser) {

                $subquery->select(DB::raw(1))
                    ->from('user_swipes')
                    ->whereRaw("swiping_user_id = '" . $userId . "' AND swiped_user_id = users.id AND role_id = '".$authUser->current_role_id."'");
            })

            ->with(['portfolio', 'user_states'])
            ->having('distance', '<=', $distance)
            ->simplePaginate($limit);
            


            // if (isset($randomUser[0]) && !empty($randomUser[0])) {

            //     foreach ($randomUser as $key=>$user) {           // send request to ghost coach to join

            //         if(isset($user->portfolio[0]) && !empty($user->portfolio[0])){

            //             foreach ($user->portfolio as $key=> $profile) {
                    
            //                 if(isset($profile->image) && !empty($profile->image)){

            //                     $user->portfolio[$key]['image']= asset('storage/'.$profile->image);
            //                 }
            //             }
            //         }

            //         if(isset($user->dob) && !empty($user->dob)){

            //             $randomUser->age    = Carbon::parse($user->dob)->age;
            //         }
            //     }
            // }
            // return $this->sendResponse($randomUser, trans("message.random_user"), 200);

            if ($randomUser->isNotEmpty()) {
                foreach ($randomUser as $user) {
                    // Update image URL in portfolio
                    $user->portfolio->each(function ($profile) {
                        if ($profile->image) {
                            $profile->image = asset('storage/' . $profile->image);
                        }
                    });


                    $user->user_states->each(function ($userStats) {
                        if ($userStats->id) {

                            $stat                 =   Stat::where('id',$userStats->id)->first();
                            $userStats->question  =  (isset($stat) && !empty($stat->question)?$stat->question:null);
                            $userStats->min_value =  (isset($stat) && !empty($stat->min_value)?$stat->min_value:0);
                            $userStats->max_value =  (isset($stat) && !empty($stat->max_value)?$stat->max_value:0);
                        }
                    });
                    // Calculate user's age
                     if(isset($user->profile_pic) && !empty($user->profile_pic)){

                        $user->profile_pic = asset('storage/' . $user->profile_pic);
                    }
                    $user->age = Carbon::parse($user->dob)->age;
                }
            }
    
            // Return response
            return $this->sendResponse($randomUser, trans("message.random_user"), 200);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "recruits" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------------------- E N D ------------------------------------#




     #----------  G E T       R A N D O M        R E C R U I T  ------------------#
     public function recruitUser(Request $request){
        try {

            $authUser           =      Auth::user();
            $userId             =      Auth::id();
            // dd($authUser->current_role_id);
            if($authUser->current_role_id==2){              // dater profile used to add user to roster
          
                return $this->addRosterBench->addToRosterBench($request);
    
            }elseif ($authUser->current_role_id==3) {       // recruiter profile dor add user to any team and bench
    
                return $this->addMemberBench->addToTeamBench($request);
                
            }else{
    
                return $this->sendResponsewithoutData(trans("message.invalidUser"), 403);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "recruits" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------------------- E N D ------------------------------------#



    public function addToTeamBench($request) {

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




}
