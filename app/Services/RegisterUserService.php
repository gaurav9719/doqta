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
use App\Services\RosterAiTrigger;
use App\Services\VerifyEmail;
use App\Traits\CommonTrait;
/**
 * Class RegisterUserService.
 */
class RegisterUserService extends BaseController
{
    use CommonTrait;
    protected $getUser;
    protected $user, $authId,$notification,$rosterAi,$verify_email;

    public function __construct(GetUserService $user,RosterAiTrigger $rosterAi ,VerifyEmail $verify_email)
    {
        $this->getUser      =  $user;
        $this->rosterAi     = $rosterAi;
        $this->verify_email = $verify_email;
    }  

    public function signUpUser($request){

        DB::beginTransaction();

        try {

            $existingUser = User::where('email', $request['email'])->lockForUpdate()->exists();
        
            if ($existingUser) {

                return response()->json(['status' => 400, 'message' => 'This email address is already registered.']);

            }
            $user = new User();
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->dob = $request->dob;
            $user->device_type = $request->device_type;
            $user->device_token = $request->device_token;
            $user->lat = $request->lat;
            $user->long = $request->long;
            $user->save();
            $userID = $user->id;
            UserDevice::where(["device_token" => $request->device_token])->delete();
            $UserDevice = new UserDevice();
            $UserDevice->user_id     = $userID;
            $UserDevice->device_type = $request->device_type;
            $UserDevice->device_token = $request->device_token;
            $UserDevice->save();
            $this->createByDefaultJournal($userID); #------- create default journal ------____#
            #----------  S E N D        V E R I F I C A T I O N          E M A I L ---------------#
            $this->verify_email->sendVerificationEmail($userID);
            #----------  S E N D        V E R I F I C A T I O N          E M A I L ---------------#
            DB::commit();
            $userData   =   $this->getUser->getAuthUser($userID);
            
            return $this->sendResponse($userData, trans("message.register"), 200);
        } catch (Exception $e) { 
            DB::rollback();
            Log::error('Error caught: "signUpUser" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------********** L O G I N ********* ----------------#

    public function signIn(Request $request){

        try {

            $checkStatus = User::where(['email' => $request->email])->orWhere(['user_name'=>$request->email])->first();

            if (isset($checkStatus) && !empty($checkStatus)) {
                // Check the user's status
                if ($checkStatus['is_active'] == 0) {
                    // User is inactive
                    return $this->sendError(trans('message.account_not_active'), [], 403);

                } elseif ($checkStatus['is_active'] == 2) {
                    // User account is deleted
                    
                    return $this->sendError(trans('message.account_deleted'), [], 422);

                } elseif ($checkStatus['is_active'] == 1) {

                    // User account is active
                    
                    if (auth()->attempt(['email' => $checkStatus->email, 'password' => $request->password])) {
                        // Authentication successful
                        $userId = Auth::id();
                        $user = User::find($userId);
                        $user->device_type = $request->device_type;
                        $user->device_token = $request->device_token;
                        $user->login_type = 0;
                        $user->save();

                        // D E V I C E      T O K E N 

                        UserDevice::where(["device_token" => $request->device_token])->delete();
                        $UserDevice                 =   new UserDevice();
                        $UserDevice->user_id        =   $userId;
                        $UserDevice->device_type    =   $request->device_type;
                        $UserDevice->device_token   =   $request->device_token;
                        $UserDevice->save();
                        // Commit the transaction
                        DB::commit();
                        $loginUser   =   $this->getUser->getAuthUser($userId);
                        return response()->json(['status' => 200, 'message' => (trans('message.login')), 'data' => $loginUser]);
                        
                    } else {
                        // Invalid email or password
                        return $this->sendError(trans('message.invalidCredentials'), [], 400);
                    }
                }
            } else {
                // Invalid email or password
                return $this->sendError(trans('message.invalidCredentials'), [], 400);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "signIn" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------************* E N D ********** ----------------#
}
