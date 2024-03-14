<?php

namespace App\Services;
use App\Http\Requests\UserRegister;
use App\Http\Requests\LoginUser;
use App\Models\UserDevice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail as verify_email;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;

/**
 * Class VerifyEmail.
 */
class VerifyEmail extends BaseController
{
    protected $authId;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });
    }
    public function sendVerificationEmail($auth_id)
    {
        DB::beginTransaction();
        try {

            $user            =          User::where('id',$auth_id)->first();

            if(isset($user) && !empty($user)){
                $otp                        =          rand(1111, 9999);
                $user->otp                  =          $otp;
                $user->otp_expiry_time      =          Carbon::now()->addMinutes(10);
                $user->save();
                DB::commit();
                $emailVerify     =          array('otp' => $otp);
                Mail::to($user->email)->send(new verify_email($emailVerify));

                return $this->sendResponsewithoutData("OTP sent to your email!", 200);
                
            } else {

                return $this->sendResponsewithoutData("Something went wrong, Please try it againl!", 400);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "signUpUser" ' . $e->getMessage());
            DB::rollback();
            return $this->sendError([], $e->getMessage());
        }
    }

}
