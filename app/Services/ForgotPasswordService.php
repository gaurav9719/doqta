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
use App\Models\PasswordResetToken;
use App\Mail\ForgotPasswordEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
/**
 * Class ForgotPasswordService.
 */
class ForgotPasswordService extends BaseController
{

    protected $authId, $notification;


    public function __construct(NotificationService $notification)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });
        $this->notification = $notification;
    }  


        /* send otp  */
        public function sendOtpToMail($checkEmail, $request)
        {
            DB::beginTransaction();

            try {
                $otp = rand(111111, 999999);
                $random = Str::random(40);
                $otp_expiry_time = Carbon::now()->addMinutes(10);
                $emailVerify = ['otp' => $otp, 'name' => $checkEmail->name];
                $SendOtp = PasswordResetToken::updateOrCreate(
                    ['email' => $request->email], // Primary key column
                    [
                        'token' => $random,
                        'otp' => $otp,
                        'otp_expiry_time' => $otp_expiry_time,
                        'created_at' => Carbon::now(),
                    ]
                );
            
                DB::commit();
                // Send an email with the OTP
                Mail::to($request->email)->send(new ForgotPasswordEmail($emailVerify));
                return $this->sendResponsewithoutData(trans('message.otp_sent_on_your_email'), 200);
            } catch (Exception $e) {
                // Rollback the transaction in case of an exception
                log::error("sendOtpToMail:-".$e->getMessage());
                DB::rollback();
                return $this->sendError([], trans('message.failed_to_send_email'));
            }
            
        }
        /* send otp  */
    

        #-------------------********* V E R I F Y       O T P  ********-----------------------#

        public function verifyOtp(Request $request)
        {
            DB::beginTransaction();
        
            try {

                $validation     =   Validator::make($request->all(),['otp'=>'required|integer|digits:6']);

                if($validation->fails()){
    
                    return $this->sendResponsewithoutData($validation->errors()->first(), 422);
    
                }else{

                    $otpExist       = PasswordResetToken::where('email', $request->email)->first();
        
                    if (!$otpExist || empty($otpExist->otp) || empty($otpExist->otp_expiry_time)) {
    
                        return $this->sendResponsewithoutData(trans('message.invalid_email'), 400);
    
                    }
            
                    $expiryTime = strtotime($otpExist->otp_expiry_time);
                    $currentTime = strtotime(Carbon::now());
            
                    if ($currentTime >= $expiryTime) {
                        // Clear expired OTP
                        $otpExist->update([
                            'token' => null,
                            'otp_expiry_time' => null,
                            'otp' => null,
                        ]);
                        DB::commit();
                        return $this->sendResponsewithoutData(trans('message.otp_expired'), 400);
                    }
            
                    if ($otpExist->otp != $request->otp) {
                        return $this->sendResponsewithoutData(trans('message.invalid_otp'), 400);
                    }
            
                    DB::commit();
                    return $this->sendResponse($otpExist->token, trans('message.otp_verified'), 200);
                }
            } catch (Exception $e) {
                // Rollback the transaction in case of an exception
                log::error("verifyOtp:-".$e->getMessage());
                DB::rollback();
                return $this->sendError([], $e->getMessage());
            }
        }
        #-------------------********* V E R I F Y       O T P  ********-----------------------#


    #-----------------------************ R E S E T       P A S S W O R D ***********------------------------------#   
    public function ResetPassword($request, $checkEmail)
    {
        DB::beginTransaction();
        try {
            $validation                           =   Validator::make($request->all(),['password'=>'required|min:8|string|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/','confirm_password'=>'required|same:password','token'=>'required|']);
            if($validation->fails()){

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }else{

                $resetPassword                    =   PasswordResetToken::where(['email' => $request->email,'token'=>$request->token])->first();
                if ((isset($resetPassword) && !empty($resetPassword)) && !empty($resetPassword['token'])) {
                    //check validation of expiry time of reset password
                    $setUserPassword              =   User::where('email',$request->email)->first();
                    $setUserPassword->password    =    Hash::make($request->password);
                    if ($setUserPassword->save()) {

                        $resetPassword->token             =  null;
                        $resetPassword->otp_expiry_time   =  null;
                        $resetPassword->otp               =  null;
                        $resetPassword->save();
                        DB::commit();
                        #send notification
                        $receiver= User::find($setUserPassword->id);
                        $sender= User::where('role', 3)->first();
                        $sender = isset($sender) ? $sender : $receiver;
                        $data=["message"=> trans('notification_message.password_changed_successfully_message')];
                        $this->notification->sendNotificationNew($sender, $receiver, trans('notification_message.password_changed_successfully_type'), $data);
                        return $this->sendResponsewithoutData(trans('message.changed_password'), 200);
                        
                    } else {
                        return $this->sendResponsewithoutData(trans('message.password_not_updated'), 200);
                    }
                } else {

                    return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 400);
                }
            }
            
        } catch (Exception $e) {
            log::error("reset_password:-".$e->getMessage());
            // Rollback the transaction in case of an exception
            DB::rollback();
            return $this->sendError([], $e->getMessage());
        }
    }
}
