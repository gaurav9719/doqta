<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;


class VerifyAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (Auth::check()) {
            // Check if user account is verified
            if (Auth::user()->is_email_verified==0) {

                return response()->json(['status'=>403, 'message' => 'Email not verified. Access denied.'], 403);
            }
        }
        return $next($request);
    }
}
