<?php

namespace App\Services;
use App\Models\User;
use App\Http\Requests\UserRegister;
use App\Http\Requests\LoginUser;
use App\Models\UserDevice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\GetUserService;
use App\Models\UserRole;
use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Api\PointSystem;
use App\Models\PointHistory;
use App\Models\Referral;
use Carbon\Carbon;
use App\Models\PointSystem as PointSystemModel;
use Illuminate\Support\Facades\Log;
use App\Models\UserPortfolio;
use App\Models\Recruiter;
use App\Models\MyTeam;
use Illuminate\Support\Str;
use App\Jobs\RosterAiJob;
use App\Models\Job_status;
use Illuminate\Support\Facades\Queue;

/**
 * Class RosterAiTrigger.
 */

class RosterAiTrigger
{
    function RosterAiFinder($authUser, $requestUserId){

        try {
            // Dispatch the RosterAiJob
        //   DB::enableQueryLog();
            $existingJobStatus = Job_status::where('user_id', $requestUserId)->where('is_running', true)->exists();
            // dd(DB::getQueryLog());
            if (!$existingJobStatus) {
                $jobId = uniqid(); // Generate a unique job ID
                // Dispatch the new queue job
                Job_status::create(['user_id' =>$requestUserId,'job_id' =>$jobId,'is_running' => true]);
                RosterAiJob::dispatch($authUser, $requestUserId,$jobId);

                // $userQueueJob = new RosterAiJob($authUser, $requestUserId ,$jobId);
                //  Queue::push($userQueueJob);
                // Record job status
               // dd($jobId);
                // DB::enableQueryLog();
                // dd(DB::getQueryLog());
                // You can return a success response or perform any other action here
               // return response()->json(['message' => 'User queue job dispatched successfully']);
            }
        } catch (Exception $e) {
            return $e;
           
        }
    }

    




}
