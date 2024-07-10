<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\Models\UserQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureUsage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    protected $limits = [
        'community_posts' => 2,
        'chatbot_messages' => 10,
        'journal_entries' => 2,
        'rewrite_with_ai' => 4,
        'friend_requests' => 10,
        'post_comments' => 20,
        'community_join_requests' => 5,
    ];

    public function handle($request, Closure $next, $feature)
    {
        $user = Auth::user();

        if ($user->plan_status == 1 || $user->plan_status == "1") {

            Log::info("middleware working");
            return $next($request);
        }
        $date       =   Carbon::today()->toDateString();

        $usage      =   UserQuota::firstOrCreate(['user_id' => $user->id, 'date' => $date]);
        if ($usage->$feature >= $this->limits[$feature]) {
            return response()->json([
                'status' => 406,
                'message' => "We’re so sorry, but your account has exceeded the maximum amount of {$feature} allowed per day.Please upgrade your account to premium to receive unlimited access to all of Doqta’s features.",

            ], 406);
        }

        return $next($request);
    }
}
