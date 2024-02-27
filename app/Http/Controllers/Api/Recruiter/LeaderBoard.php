<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\UserRegister;
use App\Http\Requests\LoginUser;
use App\Models\User;
use App\Models\UserDevice;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\RegisterUserService;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\MyTeam;
use App\Models\PointHistory;
class LeaderBoard extends BaseController
{

public function leaderBoard(Request $request) {
    try {
        $now = Carbon::now();
        $weekStartDate = $now->startOfWeek()->format('Y-m-d');
        $weekEndDate = $now->endOfWeek()->format('Y-m-d');
        $authUser = Auth::user();

        $myTeam = MyTeam::with(['team' => function ($query) {
                $query->select('id', 'name', 'user_name', 'profile_pic');
            }, 'team.portfolio'])
            ->whereHas('team', function ($query) {
                $query->where('is_active', 1);
            })
            ->where([
                "recruiter_id" => $authUser->id,
                'is_active' => 1
            ])
            ->select(['my_teams.*', DB::raw('IFNULL(SUM(point_histories.points),0) AS total_points_this_week')])
            ->leftJoin('point_histories', function ($join) use ($weekStartDate, $weekEndDate) {
                $join->on('point_histories.user_id', '=', 'my_teams.member_id')
                    ->whereBetween('point_histories.created_at', [$weekStartDate, $weekEndDate])
                    ->where('point_histories.role_id', 2);
            })
            ->groupBy('my_teams.id')
            ->orderByDesc('total_points_this_week')
            ->get();

        foreach ($myTeam as $key => $team) {
            $totalPoints = PointHistory::selectRaw('SUM(points) as total_point, point_id, point_systems.keyword')
                ->join('point_systems', 'point_systems.id', '=', 'point_histories.point_id')
                ->where('user_id', $team->member_id)
                ->whereBetween('point_histories.created_at', [$weekStartDate, $weekEndDate])
                ->where('role_id', 2)
                ->groupBy('point_id')
                ->get();
            $myTeam[$key]['team']['point_history'] = $totalPoints;
        }

        return $this->sendResponse($myTeam, trans("message.leader_board"), 200);
    } catch (Exception $e) {
        Log::error('Error caught: "leaderBoard" ' . $e->getMessage());
        return $this->sendError($e->getMessage(), [], 400);
    }
}


}
