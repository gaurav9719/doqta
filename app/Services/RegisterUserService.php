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
/**
 * Class RegisterUserService.
 */
class RegisterUserService extends BaseController
{
    protected $getUser;
    protected $user, $authId,$notification,$rosterAi,$verify_email;


    public function __construct(GetUserService $user,RosterAiTrigger $rosterAi ,VerifyEmail $verify_email)
    {
        $this->getUser  =  $user;
        $this->rosterAi = $rosterAi;
        $this->verify_email = $verify_email;
    }  

    public function signUpUser($request){
        DB::beginTransaction();
        try {
            
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

            $checkStatus = User::where(['email' => $request->email])->whereNotIn('register_role_type', [1,4])->first();
            
            if (isset($checkStatus) && !empty($checkStatus)) {

                // Check the user's status
                if ($checkStatus['is_active'] == 0) {
                    // User is inactive
                    return $this->sendError("Your account is not active!", [], 400);

                } elseif ($checkStatus['is_active'] == 2) {
                    // User account is deleted
                    return $this->sendError("Your account is deleted!", [], 400);

                } elseif ($checkStatus['is_active'] == 1) {
                    // User account is active
                    if (auth()->attempt(['email' => $request->email, 'password' => $request->password])) {
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
                        #------- check if portfolio doesnot exist-----------#
                        $position                   =       1;
                        $isPortfolioExist           =       UserPortfolio::where('user_id',$userId)->count();
                        if($isPortfolioExist==0) {
                            for ($i = 0; $i < 5; $i++) {

                                UserPortfolio::create([
                                    'user_id' => $userId,
                                    'image' => null,
                                    'position' => $position,
                                    // Add more fields as needed with null values
                                ]);
                                $position++;
                            }
                        }
                        #------- check if portfolio doesnot exist-----------#

                        // Commit the transaction
                        DB::commit();
                        // add queue 
                        $this->rosterAi->RosterAiFinder(Auth::user(),$userId);
                        $loginUser   =   $this->getUser->getAuthUser($userId);
                        //dd($loginUser);
                        // Return a success response with user details
                        return response()->json(['status' => 200, 'message' => (trans('message.login')), 'data' => $loginUser]);
                        
                    } else {
                        // Invalid email or password
                        return $this->sendError("Invalid email or password!", [], 400);
                    }
                }
            } else {
                // Invalid email or password
                return $this->sendError("Invalid email or password!", [], 400);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "signIn" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------************* E N D ********** ----------------#
}
