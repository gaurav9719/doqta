<?php

namespace App\Http\Controllers\Api;

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
use App\Services\Dater\AddToRosterBench;
use App\Services\Recruiter\AddToMemberBench;
class AcceptBenchToUser extends BaseController
{
    //

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

#---------- COMMON API FOR addToMemberBench/addToRosterBench -------------# 
    public function AddToAcceptBench(Request $request){

        $authUser       =   Auth::user();

        if($authUser->current_role_id==2){  // dater profile used to add user to roster
          
            return $this->addRosterBench->addToRosterBench($request);
        }elseif ($authUser->current_role_id==3) { // recruiter profile dor add user to any team and bench

            return $this->addMemberBench->addToTeamBench($request);
        }else{
            return $this->sendResponsewithoutData(trans("message.invalidUser"), 403);
        }
    }
}
