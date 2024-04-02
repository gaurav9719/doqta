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
use App\Jobs\SendVerificationEmailJob;
use App\Services\GetUserService;

/**
 * Class VerifyEmail.
 */
class VerifyEmail extends BaseController
{
    protected $authId, $getUserProfile;
    public function __construct(GetUserService $getUserProfile)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });
        $this->getUserProfile= $getUserProfile;
    }
    public function sendVerificationEmail($auth_id,$data="")
    {
        try {
            $user            =          User::where('id',$auth_id)->first();

            if(isset($user) && !empty($user)){

                $otp                        =          rand(1111, 9999);
                $user->otp                  =          $otp;
                $user->otp_expiry_time      =          Carbon::now()->addMinutes(10);
                $user->save();
                $emailVerify                =          array('otp' => $otp,'email'=>$user->email);
                SendVerificationEmailJob::dispatch($emailVerify);

               // return $this->sendResponsewithoutData(trans('message.something_went_wrong'));
                
            } else {

               // return $this->sendResponsewithoutData("Something went wrong, Please try it againl!", 422);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "sendVerificationEmail" ' . $e->getMessage());
           
            return $this->sendError([], $e->getMessage());
        }
    }


    public function sendResendCode($request){

        try {

            if($request['type']==1){

                return $this->resendCode($request);

            }else{

                return $this->verifyEmail($request);

            }
        } catch (Exception $e) {
            
            Log::error('Error caught: "sendVerificationEmail" ' . $e->getMessage());

            return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 422);
        }
    }


    #----------------  R E S E N D          C O D E     -------------------#
    public function resendCode($request){

        try{

            $user            =          User::where(['id'=>$request['user_id']])->first();

            if(isset($user) && !empty($user)){
                $otp                        =          rand(1111, 9999);
                $user->otp                  =          $otp;
                $user->otp_expiry_time      =          Carbon::now()->addMinutes(10);
                $user->save();
                $emailVerify                =          array('otp' => $otp,'email'=>$user->email);

                SendVerificationEmailJob::dispatch($emailVerify);

                return $this->sendResponsewithoutData(trans('message.sent_email_verification_code'), 200);

            } else {

                return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 422);
            }
        } catch (Exception $e) {
            
            Log::error('Error caught: "sendVerificationEmail" ' . $e->getMessage());

            return $this->sendResponsewithoutData(trans('message.something_went_wrong'),422 );
        }
    }


    #------------------          V E R I F Y    O T P     -----------------------------#

    public function verifyEmail($request){
        DB::beginTransaction();
        try {

            $user                   =          User::where(['id'=>$request['user_id']])->first();

            if(isset($user) && !empty($user)){

                if(isset($user->otp) && isset($user->otp)){

                    $expiryTime     =           strtotime($user->otp_expiry_time);
                    $currentTime    =           strtotime(Carbon::now());

                    if ($currentTime < $expiryTime) {
    
                        if($user['otp'] == $request->otp) {
                         
                            $user->otp                      =  null;
                            $user->otp_expiry_time          =  null;
                            $user->email_verified_at        =  Carbon::now();
                            $user->is_email_verified        =  1;
                            $user->save();
                            DB::commit();
                            $userData                       =   $this->getUserProfile->getAuthUser($user->id);
                            return $this->sendResponse($userData,trans('message.email_verified'), 200);

                        } else {

                            return $this->sendResponsewithoutData(trans('message.invalid_otp'), 422);
                        }

                    } else {

                        $user->otp                      =  null;
                        $user->otp_expiry_time          =  null;
                        $user->email_verified_at        =  null;
                        $user->save();
                        DB::commit();
                        return $this->sendResponsewithoutData(trans('message.otp_expired'), 421);
                    }
                }else{

                    return $this->sendResponsewithoutData(trans('message.invalid_otp'), 422);
                }
            }else{

                return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 421);
            }     
        } catch (Exception $e) {
            // Rollback the transaction in case of an exception
            Log::error('Error caught: "verifyEmail" ' . $e->getMessage());
            DB::rollback();
            return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 421);
        }
    }
    #------------------          V E R I F Y    O T P     -----------------------------#

















    /* verify otp  */
    // public function verifyOtp($auth_id)
    // {
    //     DB::beginTransaction();

    //     if(empty($data['otp_code'])){

    //         return $this->sendResponsewithoutData(, 200);

                
    //     }


    //     try {





    //         $user                           =          User::where(['id'=>$auth_id])->first();
    //         if(isset($user) && !empty($user)){




    //         }else{

    //             return $this->sendResponsewithoutData("something_went_wrong", 422);



    //         }




    //         if ($otpExist) {

    //             if (empty($otpExist['token']) || empty($otpExist['otp_expiry_time'])) {

    //                 return $this->sendResponsewithoutData("Invalid OTP!", 422);
    //             } else {

    //                 $expiryTime     = strtotime($otpExist['otp_expiry_time']);

    //                 $currentTime    = strtotime(Carbon::now());

    //                 if ($currentTime < $expiryTime) {

    //                     if ($otpExist['token'] == $request->otp) {
    //                         // Update OTP fields
    //                         $otpExist->token             =  null;
    //                         $otpExist->otp_expiry_time   =  $otp_expiry_time = Carbon::now()->addMinutes(10);
    //                         $otpExist->type              =  $request->type;

    //                         if ($otpExist->save()) {

    //                             DB::commit();

    //                             return $this->sendResponsewithoutData("OTP verified!", 200);
    //                         } else {

    //                             return $this->sendResponsewithoutData("OTP expired, please try again!", 422);
    //                         }
    //                     } else {

    //                         return $this->sendResponsewithoutData("Invalid OTP!", 422);
    //                     }
    //                 } else {
    //                     // Clear expired OTP
    //                     $otpExist->token = null;
    //                     $otpExist->otp_expiry_time = null;
    //                     $otpExist->type = null;
    //                     $otpExist->save();
    //                     DB::commit();
    //                     return $this->sendResponsewithoutData("OTP expired, please try again!", 422);
    //                 }
    //             }
    //         } else {

    //             return $this->sendResponsewithoutData("Invalid OTP!", 422);
    //         }
    //     } catch (Exception $e) {
    //         // Rollback the transaction in case of an exception
    //         DB::rollback();
    //         return $this->sendError([], $e->getMessage());
    //     }
    // }
    /* verify otp  */
















}
