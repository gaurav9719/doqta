<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyFastApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!in_array($request->headers->get('accept'), ['application/json', 'Application/Json'])){

            return response()->json(['message' => 'Invalid header type.'], 401);

        }

        $headerKey = $request->header('X-API-KEY');
        $apiKey = config('app.fast_api_key');
       

        if ($apiKey !== $headerKey) {

            return response()->json(['message' => 'Access denied.'], 403);
        }
    
        
        return $next($request);
    }
}
