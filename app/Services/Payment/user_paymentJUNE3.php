<?php

namespace App\Services\Payment;

use Exception;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\User;
use App\Models\Domain;
use App\Models\UserPlan;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;
use App\Models\CorporativePlanUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\CorporateEmailVerification;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;

/**
 * Class user_payment.
 */
class user_payment extends BaseController
{

    #------------  A P P        I N     P U R C H A S E     -------------------#
    public function AppInPurchase($request,$userId){
        try {
            $validator = Validator::make($request->all(), [
                'transaction_id'    => 'required|string',
                'signature'         => 'nullable|string',
                'currency'          => 'nullable|string',
                'currency_symbol'   => 'nullable|string',
                'start_date'        => 'required|date_format:Y-m-d H:i:s e',
                'end_date'          => 'required|date_format:Y-m-d H:i:s e|after:start_date',
                'purchased_device'  => 'required|integer|between:1,2',
                'amount'            => 'required|numeric|min:0', // Validation rules for 'amount' field
            ]);
                // Add custom rule for no special characters
            if ($validator->fails()) {
            
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {
                //check user already used trailed or not
                if($request->type == 1) {

                    $isTrailUsed        =       User::where(['id'=>$userId,'is_active'=>1,'is_trial_used'=>1])->exists();

                    if($isTrailUsed){

                        return $this->sendResponsewithoutData(trans('message.trail_already_used'), 403);
                    }

                    $check              =       UserPlan::where('user_id',$userId)->first(); // if paid plan then not use trail plan

                    if($check){

                        return $this->sendResponsewithoutData("Trial plan available for first-time users only", 400);
                    }
                }

                //check user transaction id with userid
                $isTransExist                         =   UserPlan::where('transaction_id',$request->transaction_id)->first();

                if(isset($isTransExist) && !empty($isTransExist)){
                    
                    if($isTransExist['user_id'] != $userId){
                        
                        return $this->sendResponsewithoutData(trans('message.this_payment_account_already_used'), 403);
                        
                    }
                }
                
                $startdate                            =     utc_time_conversion($request->start_date);
                $enddate                              =     utc_time_conversion($request->end_date);
                $startdateTimeStamp                   =     strtotime($startdate);
                $enddateTimeStamp                     =     strtotime($enddate);
                $nowTimeStamp                         =     strtotime(Carbon::now()->format('Y-m-d H:i:s'));
                $amount                               =     $request->amount;
                $transactionId                        =     $request->transaction_id;
                    
                
                #create plan
                $user_plan                            =     new UserPlan();
                $user_plan->plan_id                   =     $request->plan_id;
                $user_plan->transaction_id            =     $request->transaction_id;
                $user_plan->user_id                   =     $userId;
                $user_plan->payment_status            =     1;
                $user_plan->start_date                =     $startdate;
                $user_plan->expiry_date               =     $enddate;
                $user_plan->is_trial_plan             =     $request->type == 1 ? 1 : 0;
                $user_plan->is_active                 =     1;
                $user_plan->purchased_device          =     $request->purchased_device;
                $user_plan->save();
                
                #create payment
                $addPayment                           =     new PaymentHistory();
                $addPayment->transaction_id           =     $transactionId;
                $addPayment->user_id                  =     $userId;
                $addPayment->user_plan_id             =     $user_plan->id;
                if(isset($request->signature) && !empty($request->signature)){

                    $addPayment->signature            =     encrypt($request->signature);
                }
                $addPayment->amount                   =     $amount;

                $addPayment->currency                 =     (isset($request->currency) && !empty($request->currency))?$request->currency:null;
                $addPayment->currency_symbol          =     (isset($request->currency_symbol) && !empty($request->currency_symbol))?$request->currency_symbol:null;

                $addPayment->payment_by               =     1;        // 1 app in purchas, 2=stripe, 3=paypal, 4=google_pay,
                $addPayment->status                   =     2;        // 2 completed
                $addPayment->is_trial_plan            =     $request->type == 1 ? 1 : 0;
                $addPayment->purchased_device         =     $request->purchased_device;
                $addPayment->save();

                if($request->type != 1){

                    $trial_plan                      =  UserPlan::where('user_id', $userId)->where('is_trial_plan', 1)->where('is_active', 1)->first(); //check trail is activate if find deactivate the plan

                    if(isset($trial_plan)){

                        $trial_plan->is_active      = 2;
                        $trial_plan->cancelled_at   = Carbon::now();
                        $trial_plan->save();
                    }
                }

                
                $user=User::find($userId);
                if($request->type == 1){
                    
                    $user->is_trial_used = 1;
                }
                $user->user_plan_id  = $user_plan->id;
                $user->plan_status   = 1;
                $user->save();
                

                #get current subscription plan
                $subscription                         =    $this->userCurrentPlans($userId);
                $subs                                 =   (isset($subscription)) && !empty($subscription)?$subscription:[];
                return  $this->sendResponse($subs, trans('message.plan_activates_successfully'),200);
                
            }
        } catch (Exception $e) {
            
            Log::error('Error caught: "subscription" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------  A P P        I N     P U R C H A S E     -------------------#


    #-------------C O R P O R A T E    P L A N ------------------#
    #Corporate Plan
    public function corporatePlan($request){

        try {

        $request    =       (object) $request;
        $user       =       User::find(Auth::id());
        #Send OTP
        if($request->action == 1){

            $check2 = CorporativePlanUser::where('corporate_email', $request->email)->where('is_verified', 1)->first();

            if ($check2) {

                return $this->sendResponsewithoutData("Provided email already in use", 400);
            }

            $email          = explode('@', $request->email);
            $domain         = $email[1];
            $check          = Domain::where('name', $domain)->first();
            $expiry         = Carbon::now()->addMinutes(10);
            $otp            = rand(123468, 999999);
            if ($check) {

                $emp1 = CorporativePlanUser::where('user_id', $user->id)->first();
                if (isset($emp1)) {
                    $emp = CorporativePlanUser::where('user_id', $user->id)->where('corporate_email', $request->email)->first();
                    if (isset($emp)) {
                        if ($emp->otp_expiry > Carbon::now()) {
                            return $this->sendResponsewithoutData("OTP already sent, please check your mail", 200);
                        } else {
                            $emp->otp = $otp;
                            $emp->otp_expiry = $expiry;
                            $emp->save();

                            Mail::to($request->email)->send(new CorporateEmailVerification($otp));

                            return $this->sendResponsewithoutData("OTP sent, please check your mail", 200);
                        }
                    } else {
                        $emp1->domain_id = $check->id;
                        $emp1->corporate_email = $request->email;
                        $emp1->otp = $otp;
                        $emp1->otp_expiry = $expiry;
                        $emp1->save();

                        Mail::to($request->email)->send(new CorporateEmailVerification($otp));

                        return $this->sendResponsewithoutData( "OTP sent, please check your mail", 200);
                    }

                } else {
                    //send otp on provided mail
                    
                    $employee = new CorporativePlanUser;
                    $employee->domain_id = $check->id;
                    $employee->user_id = $user->id;
                    $employee->corporate_email = $request->email;
                    $employee->otp = $otp;
                    $employee->otp_expiry = $expiry;
                    $employee->save();

                    Mail::to($request->email)->send(new CorporateEmailVerification($otp));
                    return $this->sendResponsewithoutData( "OTP sent, please check your mail", 200);
                }
            } else {

                return $this->sendResponsewithoutData( "No corporate plan available.", 400);
            }
        }

        #verify OTP
        elseif($request->action == 2){

            $employee       = CorporativePlanUser::where('user_id', $user->id)->first();

            if (isset($employee)) {

                if ($employee->is_verified != 1) {

                    if ($employee->otp_expiry > Carbon::now()) {

                        if ($employee->otp == $request->otp) {
                            #Create Plan Order
                            $order                      = new UserPlan;
                            $order->user_id             = $user->id;
                            $order->plan_id             = $request->plan_id;
                            $order->is_active           = 1;
                            $order->is_trial_plan       = 0;
                            $order->start_date          = Carbon::now();
                            $order->purchased_device    = $request->purchased_device;
                            $order->save();

                            $employee->user_plan_id = $order->id;
                            $employee->otp          = null;
                            $employee->otp_expiry   = null;
                            $employee->is_verified  = 1;
                            $employee->is_active    = 1;
                            $employee->save();


                            $user->user_plan_id = $order->id;
                            $user->plan_status = 1;
                            $user->save();

                            #cancel trail plan if active
                            $trial_plan                     = UserPlan::where('user_id', $user->id)->where('is_trial_plan', 1)->where('is_active', 1)->first();

                            if(isset($trial_plan)){

                                $trial_plan->is_active      = 2;
                                $trial_plan->cancelled_at   = Carbon::now();
                                $trial_plan->save();
                            }


                            #get current subscription plan
                            $subscription                         =    $this->userCurrentPlans($user->id);
                            $subs                                 =   (isset($subscription)) && !empty($subscription)?$subscription:[];
                            return  $this->sendResponse($subs, trans('message.plan_activates_successfully'),200);

                            // return $this->sendResponsewithoutData( "OTP verified!, Corporate membership plan activated on your account", 400);
                        } else {
                            return $this->sendResponsewithoutData( "invalid OTP", 400);
                        }
                    } else {
                        return $this->sendResponsewithoutData( "OTP expired", 400);
                    }
                } else {
                    return $this->sendResponsewithoutData("Corporate membership already activated on your account", 400);
                }
            }
            else {
                return $this->sendResponsewithoutData("Something went wrong", 400);
            }
        }
        } catch (Exception $e) {
                
            Log::error('Error caught: "subscription" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    public function userCurrentPlans($userId){

        $userPlans      =   UserPlan::where(['user_id'=>$userId,'is_active'=>1])->with('plan_details')->get();
        return $userPlans;

    }
















    #------------------------  S T R I P E  -----------------------------











}
