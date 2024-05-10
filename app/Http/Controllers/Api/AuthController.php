<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\UserRegister;
use App\Http\Requests\LoginUser;
use App\Models\User;
use App\Models\UserDevice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\RegisterUserService;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use App\Http\Requests\verifyEmail;
use App\Services\VerifyEmail as verifyEmailService;
use App\Http\Requests\ForgotPassword;
use App\Http\Requests\Social_login;
use App\Services\ForgotPasswordService;
use Laravel\Passport\Token;
use Laravel\Passport\RefreshToken;

class AuthController extends BaseController
{

    protected $signUpService, $verifyEmail,$forgotPassword;

    public function __construct(RegisterUserService $signUpUser,verifyEmailService $verifyEmail,ForgotPasswordService $forgotPassword)
    {
        $this->signUpService = $signUpUser;
        $this->verifyEmail = $verifyEmail;
        $this->forgotPassword = $forgotPassword;
    }
    //
    #----------********   S I G N      U P     N E W       U S E R  *********----------#   
    public function signUp(UserRegister $request)
    {
        try {

            return $this->signUpService->signUpUser($request);

        } catch (Exception $e) {

            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------------------********* E N D*********  --------------------------------#


    #------------------------********   L O G I N      U S E R  *********------------------#   

    public function signIn(LoginUser $request)
    {
        try {

            return $this->signUpService->signIn($request);
            
        } catch (Exception $e) {

            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------------------********* E N D*********  --------------------------------#

    #--------------------------********** L O G O U T ********* --------------------------#
    public function logout(Request $request)
    {
        try {
            $accessToken = Auth::user()->token();
            DB::table('oauth_refresh_tokens')
                ->where('access_token_id', $accessToken->id)
                ->update([
                    'revoked' => true
                ]);
            $accessToken->revoke();
            return $this->sendResponsewithoutData(trans('message.logout'), 200);
        } catch (Exception $e) {
            Log::error('Error caught: "logout" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #--------------------------**********    E N D    ********* --------------------------#

    public function qr(Request $request){

        return colorFullQr(1);
    }

    public function matchQr(Request $request){

        $rq     =       "https://rosterapp/match?u=eyJpdiI6IkEvSVlyZXRzMmQ3QUJvMVhVc1kyOXc9PSIsInZhbHVlIjoibGxhNzk5eUNyZTg1aFdDVE9sQzN5Zz09IiwibWFjIjoiZDYzNzdjOTUxNmQxNDA1YTBjY2FiNzUyZGM1M2NhMTQzZmI2OWFhMDNmNmQzZGY5MzAwYmJkOWZiNGNiYTY2YyIsInRhZyI6IiJ9";
        $urlParts = parse_url($rq);
        parse_str($urlParts['query'], $queryParams);
        if(isset($queryParams['u']) && !empty($queryParams['u'])){
            $decryptedIdWithExtra = Crypt::decrypt($queryParams['u']);
            $originalId = substr($decryptedIdWithExtra, 4);
            dd($originalId);
        }else{
            dd("invalid");
        }
    }


    public function verifyEmail(verifyEmail $request){

        $request['user_id']     =   Auth::id();
        $isAleardyVerified      =   User::find(Auth::id());
        if(isset($isAleardyVerified) && $isAleardyVerified->is_email_verified==1){
            return $this->sendResponsewithoutData(trans('message.already_verified_email'), 403);
        }else{
            return $this->verifyEmail->sendResendCode($request);
        }
    }

    #-------------- F O R G O T     P A S S W O R D ------------------#

    public function forgotPassword(ForgotPassword $request)
    {
        try {
            // Check if a user with the given email exists
            $checkEmail = User::where(['email' => $request->email])->first();

            if (isset($checkEmail) && !empty($checkEmail)) {

                if ($request->type == 1) {                  // Send forgot password request

                    return $this->forgotPassword->sendOtpToMail($checkEmail, $request);

                } elseif ($request->type == 2) {                        // Match OTP code and compare time

                    return $this->forgotPassword->verifyOtp($request);
                    
                } elseif ($request->type == 3) {                // Reset password

                    return $this->forgotPassword->ResetPassword($request, $checkEmail);
                    
                } elseif ($request->type == 4) {

                    return $this->resendOtpToMail($checkEmail, $request);
                }
            } else {
                return $this->sendResponsewithoutData("Invalid email!", 400);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "forgot password" ' . $e->getMessage());
            return $this->sendError([], $e->getMessage());
        }
    }
    #----------------------------------------------------------------------

    #----------------********* S O C I A L      L O G I N ********-----------------#
    public function socialLogin(Social_login $request)
    {
        DB::beginTransaction();
        try {
            $userCheck                     =           User::where('social_id', $request->social_id)->orwhere('email', $request->email)->first();

            if (is_null($userCheck)) {                       #---- new user login ----# 

                $social_data                = new User();
                $social_data->social_id     = $request->social_id;
                $social_data->device_type   = $request->device_type;
                $social_data->device_token  = $request->device_token;
                $social_data->login_type    = $request->login_type;
                if (isset($request->name) && !empty($request->name)) {

                    $social_data->name = $request->name;
                }

                if (isset($request->email) && !empty($request->email)) {

                    $social_data->email     = $request->email;
                    $social_data->is_email_verified     = 1;
                }

                $social_data->save();
                //saved user token
                $userID                     =   $social_data->id;
                UserDevice::where(["device_token" => $request->device_token])->delete();
                $UserDevice                 =   new UserDevice();
                $UserDevice->user_id        =   $userID;
                $UserDevice->device_type    =   $request->device_type;
                $UserDevice->device_token   =   $request->device_token;
                $UserDevice->save();
                // $isCustomerCreated          =   createStripeCustomer($userID);
                
            } else {                            #--- already in database ----#

                // need to check account status
                if($userCheck->status!=1){

                    return $this->sendResponsewithoutData(trans('message.account_deleted_or_inactive'), 400);
                }

                if (isset($userCheck->email) && !empty($userCheck->email)) {

                    if ($userCheck->email == $request->email) {

                        $userCheck->social_id = $request->social_id;
                    }
                }

                if (isset($userCheck->social_id) && !empty($userCheck->social_id)) {

                    if ($userCheck->social_id == $request->social_id) {

                        if (empty($userCheck->email)) {

                            $userCheck->email = $request->email;
                        }
                    }
                }

                if (empty($userCheck->name) || $userCheck->name == null) {

                    if (isset($request->name) && !empty($request->name)) {

                        $userCheck->name = $request->name;
                    }
                    
                }

                $userCheck->device_type     = $request->device_type;
                $userCheck->device_token    = $request->device_token;
                $userCheck->login_type      = $request->login_type;
                $userCheck->save();
                $userID                     =   $userCheck->id;
                UserDevice::where(["device_token" => $request->device_token])->delete();
                $UserDevice                 =   new UserDevice();
                $UserDevice->user_id        =   $userID;
                $UserDevice->device_type    =   $request->device_type;
                $UserDevice->device_token   =   $request->device_token;
                $UserDevice->save();
                //saved user token
                
            }
            DB::commit();
            $user                       =      User::find($userID);
            $user->token                =      $user->createToken(env('PASSPORT_SECURITY_TOKEN'))->accessToken;
            return response()->json(['status' => 200, 'message' => 'User Login successfully', 'data' => $user], 200);

        } catch (Exception $e) {
            Log::error('Error caught: "social_login" ' . $e->getMessage());
            DB::rollback();
            return $this->sendError([], $e->getMessage());
        }
    }
    #----------------********* S O C I A L      L O G I N ********-----------------#


    #**********-------------  D E L E T E       A C C O U N T ----------------- **********#
    public function deleteAccount(Request $request)
    {
        DB::beginTransaction();
        try {

            $myuser_id                  =                Auth::id();
            $hasDeleted                 =                User::find($myuser_id);
            
            if ($hasDeleted) {

                if($hasDeleted->is_active!=1){

                    return $this->sendResponsewithoutData("User already Deleted!", 400);

                }
                $hasDeleted->email      =               null;
                $hasDeleted->user_name  =               "Deleted user";
                $hasDeleted->name       =               "Deleted user";
                $hasDeleted->social_id  =               null;
                $hasDeleted->is_active  =               2;
                $hasDeleted->save();
                $tokens =  $hasDeleted->tokens->pluck('id');
                Token::whereIn('id', $tokens)
                    ->update(['revoked'=> true]);
                RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);
                DB::commit();
                // Perform standard logout logic (e.g., clearing session)
                return $this->sendResponsewithoutData("User Deleted Successfully!", 200);
                
            } else {

                return $this->sendResponsewithoutData("User not Deleted!", 400);
            }
            //udpate is_active status
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "deleted_account" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #**********-------------  D E L E T E       A C C O U N T ----------------- **********#

}
