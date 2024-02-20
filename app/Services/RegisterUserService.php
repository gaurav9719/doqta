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
/**
 * Class RegisterUserService.
 */
class RegisterUserService extends BaseController
{
    protected $getUser;

    public function __construct(GetUserService $user)
    {
        $this->getUser = $user;
    }

    public function signUpUser($request){

        DB::beginTransaction();

        try {

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->country_code = $request->country_code;
            $user->phone_no = $request->phone_no;
            $user->dob = $request->dob;
            $user->register_role_type = $request->user_role;
            $user->current_role_id = $request->user_role;
            $user->device_type = $request->device_type;
            $user->device_token = $request->device_token;
            $user->zipcode = $request->zip_code;
            $user->reference_code = generateReferCode();
            $user->gender   = $request->gender;
            $user->save();

            $userID = $user->id;
            UserDevice::where(["device_token" => $request->device_token])->delete();
            $UserDevice = new UserDevice();
            $UserDevice->user_id = $userID;
            $UserDevice->device_type = $request->device_type;
            $UserDevice->device_token = $request->device_token;
            $UserDevice->save();
            #------ ADD CURRENT ROLE IN USER ROLE TABLE------#
            $userRole          = new UserRole();
            $userRole->user_id = $userID;
            $userRole->role_id = $request->user_role;
            $userRole->save();
            #------ ADD CURRENT ROLE IN USER ROLE TABLE------#

            if(isset($request->reference_code) && !empty($request->reference_code)) {   // Add user to referece table and point in his account

                $referrerId                 =   User::select('id')->where(['reference_code'=>$request->reference_code])->first(); // check reference code user
                
                if(isset($referrerId) && !empty($referrerId)) {

                    $reference                  =   new  Referral();
                    $reference->referrer_id     =   $referrerId['id'];
                    $reference->referred_id     =   $userID;
                    $reference->refered_on      =   carbon::now();

                    if($reference->save()){
                        // give point to referred user
                        $role                       =       ($request->user_role == '2')?3:2; 
                        $point_id                   =       ($request->user_role == '2')?6:1; //6 invite recruiter//1 invite dater
                        $pointSystem                =       PointSystemModel::find($point_id)->first();
                        $point                      =       ($pointSystem)?$pointSystem->point:1;
                        $pointHistory               =       new PointHistory();
                        $pointHistory->role_id      =       $role;
                        $pointHistory->user_id      =       $referrerId['id'];
                        $pointHistory->reference_user_id =  $userID;
                        $pointHistory->point_id     =       $point_id;
                        $pointHistory->points       =       $point;

                        if($pointHistory->save()){

                            incrementByPoint($referrerId['id'],$role,$point);
                        }
                    } 
                }
            }
            #------------- A D D    U S E R     P I C T U R E  ------------------#
            $position       =       1;
            for ($i = 0; $i < 5; $i++) {
                UserPortfolio::create([
                    'user_id' => $userID,
                    'image' => null,
                    'position' => $position,
                    // Add more fields as needed with null values
                ]);
                $position++;
            }
            #------------- A D D    U S E R     P I C T U R E  ------------------#

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
