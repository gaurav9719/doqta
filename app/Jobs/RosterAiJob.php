<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
use App\Models\Stat;
use App\Services\Dater\AddToRosterBench;
use App\Services\GetUserService;
use App\Models\PartnerMatch;
use App\Models\UserRole;
use App\Models\UserPortfolio;
use App\Models\UserPreference;
use App\Models\Job_status;
use romanzipp\QueueMonitor\Traits\IsMonitored; // Import the IsMonitored trait

class RosterAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,IsMonitored;
    public $tries = 3;
    /**
     * Create a new job instance.
     */

     protected $authUser;
     protected $requestUserId;
     protected $jobId;
 
     public function __construct($authUser, $requestUserId,$jobId)
    {
        
        //
        $this->authUser = $authUser;
        $this->requestUserId = $requestUserId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{

            DB::enableQueryLog();
            $userId         = $this->authUser->id;
            $userPreference = UserPreference::where('user_id', $userId)->first();
            $teamName       = ($this->authUser->name) ? $this->authUser->name . "'s team" : "Roster user teams";
            $team           = MyTeam::updateOrCreate(
                ['recruiter_id' => 2, 'member_id' => $this->requestUserId, 'team_type' => 3],
                ['is_active' => 1, 'team_name' => $teamName]
            );
            
            $teamId         = $team->id;
            if ($userPreference) {
                // Extracted and optimized AI users query
                $aiUsers = User::select('id', DB::raw("round(3959 * acos(cos(radians('" . $this->authUser->lat . "'))* cos(radians(`lat`))* cos(radians(`long`)- radians('" . $this->authUser->long . "'))+ sin(radians('" . $this->authUser->lat . "'))* sin(radians(`lat`))),2) AS distance"))
                ->whereHas('SelectRecruitmentType', function ($query) {

                    $query->where('role_id', 2)->where('recruiter_type', 3);
                })
                    ->where(['is_active' => 1])
                    ->where("id", "<>", $userId)
                    ->whereNotExists(function ($subquery) use ($userId) {    
                        $subquery->select(DB::raw(1))
                            ->from('my_team_members')
                            ->whereRaw("member_id ='".$userId."' AND dater_id=id");
                    })
                    ->whereNotExists(function ($subquery) use ($userId) {    
                        $subquery->select(DB::raw(1))
                            ->from('user_block_lists')
                            ->whereRaw("(user_id = id AND blocked_user_id = '".$userId."') OR (user_id ='".$userId."' AND blocked_user_id = id)");
                    })->whereYear('dob', '>=', Carbon::now()->subYears($userPreference->age)->year)->whereYear('dob', '<=', Carbon::now()->subYears($userPreference->age + 2)->year)->having('distance', '<=', $userPreference->distance);
                    // ->whereYear('dob', '<=', Carbon::now()->subYears($userPreference->age)->year);
                   
                if ($userPreference->gender != 0) {
                    $aiUsers->where('gender', $userPreference->gender);
                }
                // Retrieve AI users and limit to 50
                $ghostUsers = $aiUsers->limit(50)->get();
                if ($ghostUsers->isNotEmpty()) {

                    foreach ($ghostUsers as $AIUser) {
                        $isExist = MyTeamMember::where(['member_id' => $userId, 'dater_id' => $AIUser->id])->exists();
                        if (!$isExist) {
                            $newTeamMember = new MyTeamMember();
                            $newTeamMember->team_id = $teamId;
                            $newTeamMember->member_id = $userId;
                            $newTeamMember->dater_id = $AIUser->id;
                            $newTeamMember->recruiter_type = 3;
                            $newTeamMember->is_active = 1;
                            $newTeamMember->save();
                        }
                    }
                }
                DB::table('job_statuses')->where('job_id', $this->jobId)->update(array('is_running' => 0));  

            }else{

                $jobStatus = Job_status::where('job_id', $this->jobId)->first();
                if ($jobStatus) {
                    $jobStatus->update(['is_running' => false]);
                }
            }
        }catch(Exception $e){

            Log::error('Error caught: "rosterAi" ' . $e->getMessage());
            dd($e->getMessage());
        }
    }
}
