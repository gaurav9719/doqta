<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Models\UserDevice;
use App\Traits\CommonTrait;
use Laravel\Passport\Token;
use Illuminate\Http\Request;
use App\Traits\CalculateScore;
use App\Http\Requests\LoginUser;
use App\Services\GetUserService;
use App\Http\Requests\verifyEmail;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\RefreshToken;
use App\Http\Requests\Social_login;
use App\Http\Requests\UserRegister;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ForgotPassword;
use App\Services\RegisterUserService;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Crypt;
use App\Services\ForgotPasswordService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\Notifications;
use App\Http\Controllers\Api\BaseController;
use App\Jobs\CalculateScore\scoreCalculation;
// use App\Models\Notification;
use App\Services\VerifyEmail as verifyEmailService;


class AuthController extends BaseController
{
    use CommonTrait,CalculateScore;
    protected $getUser;

    protected $signUpService, $verifyEmail,$forgotPassword;

    public function __construct(RegisterUserService $signUpUser,verifyEmailService $verifyEmail,ForgotPasswordService $forgotPassword,GetUserService $user)
    {
        $this->getUser          =   $user;
        $this->signUpService    =   $signUpUser;
        $this->verifyEmail      =   $verifyEmail;
        $this->forgotPassword   =   $forgotPassword;
    }
    //
    #----------********   S I G N      U P     N E W       U S E R  *********----------#   
    public function signUp(UserRegister $request)
    {
        try {

            return $this->signUpService->signUpUser($request);

        } catch (Exception $e) {

            Log::error('Error caught: "signUpUser" ' . $e->getMessage());

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

            Log::error('Error caught: "signIn" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------------------********* E N D*********  --------------------------------#

    #--------------------------********** L O G O U T ********* --------------------------#
    public function logout(Request $request)
    {
        try {

            if(isset($request->device_token) && !empty($request->device_token)){

                UserDevice::where(['device_token'=>$request->device_token,'user_id'=>Auth::id()])->delete();
            }

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

  
    #------------- V E R I F Y      E M A I L ------------#
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
            // DB::enableQueryLog();
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
                $userId     =$social_data->id;
                $this->createByDefaultJournal($userId); #------- create default journal ------____#
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
                if($userCheck->is_active!=1){

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
            $loginUser                      =   $this->getUser->getAuthUser($userID);
            return response()->json(['status' => 200, 'message' => 'User Login successfully', 'data' => $loginUser], 200);

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
                $hasDeleted->user_name  =               "Deleted";
                $hasDeleted->name       =               "Deleted";
                $hasDeleted->social_id  =               null;
                $hasDeleted->profile    =               null;
                $hasDeleted->cover      =               null;
                $hasDeleted->is_active  =               2;
                $hasDeleted->save();
                $tokens                 =               $hasDeleted->tokens->pluck('id');
                Token::whereIn('id', $tokens)
                    ->update(['revoked'=> true]);
                RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);
                // Notification::where('sender_id',$myuser_id)->update(['is_active'=>0]);

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


    public function calculateScore(){

       // Generated @ codebeautify.org
      dispatch(new scoreCalculation(233));
    //    scoreCalculation::dispatch(233);
      
    //    $curl = curl_init();

    //    curl_setopt_array($curl, [
    //        CURLOPT_URL => "https://api.perplexity.ai/chat/completions",
    //        CURLOPT_RETURNTRANSFER => true,
    //        CURLOPT_ENCODING => "",
    //        CURLOPT_MAXREDIRS => 10,
    //        CURLOPT_TIMEOUT => 30,
    //        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //        CURLOPT_CUSTOMREQUEST => "POST",
    //        CURLOPT_POSTFIELDS => json_encode([
    //         'model' => 'llama-3-sonar-small-32k-online',
    //         'messages' => [
    //             [
    //                 'role' => 'system',
    //                 'content' => 'Please evaluate the following text for the amount of medical advice it contains and assign a confidence score based on the following criteria:
    //                     - 2 if the text contains 5 or more instances of medical advice.
    //                     - 1.5 if the text contains 3 to 4 instances of medical advice.
    //                     - 1 if the text contains 1 to 2 instances of medical advice.
    //                     - 0.5 if the text contains no or minimal medical advice.
    //                     -provide resonse only in integer, No text required or space
    //                     Follow these rules at all times:
    //                     1. Ignore Non-Medical Content: Disregard any parts of the text that do not provide medical advice or use non-medical terminology.
    //                     2. Identify Medical Advice: Look for statements that provide guidance on health, wellness, diet, exercise, symptoms, treatments, or medical conditions..
    //                     3. Use Medical Terminology: Consider terms such as "energy," "feel better," "body," "weight," "fit," "energetic," "diet," "exercise," "health," "wellness," "symptoms," "treatment," "medical condition," ,"realed to cure any disease",etc..
    //                     4. Refer users to healthcare professionals for diagnosis or treatment. Always \
    //                     encourage users to consult with a doctor or qualified healthcare provider \
    //                     for personal health concerns.
    //                     5. Avoid making predictions about health outcomes. Do not predict the course \
    //                     of diseases or the effectiveness of specific treatments for individuals.
    //                     6. Maintain neutrality and impartiality. Do not endorse specific healthcare \
    //                     products, services, or providers unless providing a list of options based \
    //                     on reputable sources.
    //                     7. Comply with privacy laws and regulations. Do not request, store, or process \
    //                     any personal health information (PHI).
    //                     8. Provide information that is up to date and cite sources when possible. Use \
    //                     only the most recent and reliable medical data and studies to inform \
    //                     responses.
    //                     9. Clarify that the LLM is not a substitute for professional medical advice. \
    //                     Always remind users that the information provided is for informational \
    //                     purposes only and not a replacement for professional judgement.
    //                     10. Be culturally sensitive and avoid assumptions. Tailor responses to be \
    //                         inclusive and respectful of different cultural backgrounds and health \
    //                         beliefs.\n,
    //                         if text is realted to any 10 give 0 only'
    //             ],
    //             [
    //                 'role' => 'user',
    //                 'content' => $content
    //             ]
    //         ]
    //        ]),
    //        CURLOPT_HTTPHEADER => [
    //            "accept: application/json",
    //            "authorization: Bearer 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",
    //            "content-type: application/json"
    //        ],
    //    ]);
   
    //    $response = curl_exec($curl);
    //    $err = curl_error($curl);
   
    //    curl_close($curl);
   
    //    if ($err) {
    //        echo "cURL Error #:" . $err;
    //    } else {
    //     $response_data = json_decode($response, true);
    //     // dd($response_data);
    //     // $answer = $response_data['choices'][0]['message']['content'];
    //     // echo "<p><strong>Answer:</strong> $answer</p>";
    //     $content = $response_data['choices'][0]['message']['content'];
    //     // return $this->sendResponse($response_data,"User Deleted Successfully!", 200);
    //     // Now $content contains the string you want to work with in PHP
    //     echo $content;
    //     // $response_data = json_decode($response);
    //     // $score = $response_data['choices'][0]['score'] * 2;
    //     // $answer = $response_data['choices'][0]['message']['content'];
    //     // echo "<p><strong>Score:</strong> $score/2</p>";
    //     // echo "<p><strong>Answer:</strong> $answer</p>";
    //    }
    }


    
}
