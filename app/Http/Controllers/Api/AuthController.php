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


class AuthController extends BaseController
{

    protected $signUpService;

    public function __construct(RegisterUserService $signUpUser)
    {
        $this->signUpService = $signUpUser;
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
}
