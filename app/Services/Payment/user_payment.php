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
                'transaction_id'            => 'required|string',
                'original_transaction_id'   => 'required|string',
                'amount'                    => 'required|numeric|min:0', // Validation rules for 'amount' field
                'currency'                  => 'nullable|string',
                'currency_symbol'           => 'nullable|string',
                'start_date'                => 'required|date_format:Y-m-d H:i:s e',
                'end_date'                  => 'required|date_format:Y-m-d H:i:s e|after:start_date',
                'purchased_device'          => 'required|integer|between:1,2',
            ]);

            if ($validator->fails()) {
            
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
                
            }
            else {

                #check corporate plan active on user account
                $check2=UserPlan::where('user_id', $userId)->where('is_active' , 1)->first();
                if(isset($check2) && $check2->plan_details->type  == 4){
                    return $this->sendResponsewithoutData("Carporate plan already active on your account", 400);
                }

                $check=UserPlan::where('user_id',$userId)->first();
                if(isset($check) && $check->plan_details->type  > 1){
                    if($request->type == 1) {
                        return $this->sendResponsewithoutData("Trial plan available for first-time users only", 400);
                    }
                }
                

                #check user transaction already used
                $isPlanExist    =   UserPlan::where('original_transaction_id',$request->original_transaction_id)->first();
                if(isset($isPlanExist) && !empty($isPlanExist)){
                    
                    if($isPlanExist->user_id != $userId){
                        
                        return $this->sendResponsewithoutData(trans('message.this_payment_account_already_used'), 403);
                        
                    }
                    else{
                        
                        #update Existing plan
                        return $this->updateExistingPlan($request,$userId, $isPlanExist->id);
                    }
                }
                else{
                    #create new plan
                    return $this->createNewPlan($request,$userId);
                }
                
            }
        } catch (Exception $e) {
            
            Log::error('Error caught: "subscription" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }






    #================= UPDATE PLAN =================#
    function updateExistingPlan($request, $userId, $planId){
        $startdate                            =     utc_time_conversion($request->start_date);
        $enddate                              =     utc_time_conversion($request->end_date);
        $startdateTimeStamp                   =     strtotime($startdate);
        $enddateTimeStamp                     =     strtotime($enddate);
        $nowTimeStamp                         =     Carbon::now();
        $transactionId                        =     $request->transaction_id;

        #Update plan
        $user_plan                            =     UserPlan::find($planId);
        $user_plan->plan_id                   =     $request->plan_id;
        $user_plan->transaction_id            =     $transactionId;
        $user_plan->original_transaction_id   =     $request->original_transaction_id;
        $user_plan->user_id                   =     $userId;
        $user_plan->payment_status            =     1;
        $user_plan->start_date                =     $startdate;
        $user_plan->expiry_date               =     $enddate;
        $user_plan->cancelled_at              =     null;
        $user_plan->last_update               =     $nowTimeStamp;
        $user_plan->is_trial_plan             =     $request->type == 1 ? 1 : 0;
        $user_plan->is_active                 =     1;
        $user_plan->purchased_device          =     $request->purchased_device;
        $user_plan->save();
        
        #update payment History
        
        $payment    =     PaymentHistory::where('user_id', $userId)
        ->where('original_transaction_id', $request->original_transaction_id)
        ->where('user_plan_id', $user_plan->id)
        ->where('plan_id', $request->plan_id)
        ->where('start_date', $startdate)
        ->where('expiry_date', $enddate)
        ->where('is_active', 1)
        ->first();

        if(isset($payment)){
            $payment->transaction_id           =     $transactionId;
            $payment->amount                   =     $request->amount;
            $payment->currency                 =     $request->currency;
            $payment->currency_symbol          =     $request->currency_symbol;
            $payment->last_update              =     $nowTimeStamp;
            $payment->payment_by               =     1;        // 1 app in purchas, 2=stripe, 3=paypal, 4=google_pay,
            $payment->status                   =     2;        // 2 completed
            $payment->is_active                =     1;        // 2 completed
            $payment->is_trial_plan            =     $request->type == 1 ? 1 : 0;
            $payment->purchased_device         =     $request->purchased_device;
            $payment->save();
        }
        else{
            #mark as expired old payment history
            $oldPayment                        =     PaymentHistory::where('user_id', $userId)
            ->where('original_transaction_id', $request->original_transaction_id)
            ->where('is_active', 1)->first();
            if(isset($oldPayment)){
                if(Carbon::parse($oldPayment->expiry_date)->isPast()){
                    $oldPayment->is_active      = 3;  //expired
                }
                else{
                    $oldPayment->is_active      = 2;  //cancelled
                    $oldPayment->cancelled_at   = $nowTimeStamp;
                }
                $oldPayment->last_update        = $nowTimeStamp;
                $oldPayment->user_plan_id       = null;
                $oldPayment->save();
            }
            


            #create new payment history
            $addPayment                           =     new PaymentHistory();
            $addPayment->transaction_id           =     $transactionId;
            $addPayment->original_transaction_id  =     $request->original_transaction_id;
            $addPayment->user_id                  =     $userId;
            $addPayment->user_plan_id             =     $planId;
            $addPayment->plan_id                  =     $request->plan_id;
            $addPayment->amount                   =     $request->amount;
            $addPayment->currency                 =     $request->currency;
            $addPayment->currency_symbol          =     $request->currency_symbol;
            $addPayment->start_date               =     $startdate;
            $addPayment->expiry_date              =     $enddate;
            $addPayment->last_update              =     $nowTimeStamp;
            $addPayment->payment_by               =     1;        // 1 app in purchas, 2=stripe, 3=paypal, 4=google_pay,
            $addPayment->status                   =     2;        // 2 completed
            $addPayment->is_active                =     1;        // 2 completed
            $addPayment->is_trial_plan            =     $request->type == 1 ? 1 : 0;
            $addPayment->purchased_device         =     $request->purchased_device;
            $addPayment->save();

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





    #================= CREATE PLAN =================#
    function createNewPlan($request, $userId){

        $startdate                            =     utc_time_conversion($request->start_date);
        $enddate                              =     utc_time_conversion($request->end_date);
        $startdateTimeStamp                   =     strtotime($startdate);
        $enddateTimeStamp                     =     strtotime($enddate);
        $nowTimeStamp                         =     Carbon::now();
        $transactionId                        =     $request->transaction_id;

        #create plan
        $user_plan                            =     new UserPlan();
        $user_plan->plan_id                   =     $request->plan_id;
        $user_plan->transaction_id            =     $transactionId;
        $user_plan->original_transaction_id   =     $request->original_transaction_id;
        $user_plan->user_id                   =     $userId;
        $user_plan->payment_status            =     1;
        $user_plan->start_date                =     $startdate;
        $user_plan->expiry_date               =     $enddate;
        $user_plan->last_update               =     $nowTimeStamp;
        $user_plan->is_trial_plan             =     $request->type == 1 ? 1 : 0;
        $user_plan->is_active                 =     1;
        $user_plan->purchased_device          =     $request->purchased_device;
        $user_plan->save();
        
        #create new payment history
        $addPayment                           =     new PaymentHistory();
        $addPayment->transaction_id           =     $transactionId;
        $addPayment->original_transaction_id  =     $request->original_transaction_id;
        $addPayment->user_id                  =     $userId;
        $addPayment->user_plan_id             =     $user_plan->id;
        $addPayment->plan_id                  =     $request->plan_id;
        $addPayment->amount                   =     $request->amount;
        $addPayment->currency                 =     $request->currency;
        $addPayment->currency_symbol          =     $request->currency_symbol;
        $addPayment->start_date               =     $startdate;
        $addPayment->expiry_date              =     $enddate;
        $addPayment->last_update              =     $nowTimeStamp;
        $addPayment->payment_by               =     1;        // 1 app in purchas, 2=stripe, 3=paypal, 4=google_pay,
        $addPayment->status                   =     2;        // 2 completed
        $addPayment->is_active                =     1;        // 2 completed
        $addPayment->is_trial_plan            =     $request->type == 1 ? 1 : 0;
        $addPayment->purchased_device         =     $request->purchased_device;
        $addPayment->save();


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

    
    




    #================= CORPORATE PLAN =================#
    public function corporatePlan($request, $userId){
        try {
        $request=(object) $request;

        $user= User::find($userId);

        #check corporate plan active on user account
        $check1=UserPlan::where('user_id', $userId)->where('is_active' , 1)->first();
        if(isset($check1) && $check1->plan_details->type  == 4){
            return $this->sendResponsewithoutData("Carporate plan already active on your account", 400);
        }
        $check2 = CorporativePlanUser::where('corporate_email', $request->email)->where('is_verified', 1)->first();
        if ($check2) {
            return $this->sendResponsewithoutData( "Provided email already in use", 400);
        }

        #Send OTP
        if($request->action == 1){

            $email = explode('@', $request->email);
            $userdomain = $email[1];
            $domain = Domain::where('name', $userdomain)->first();
            // $expiry = Carbon::now()->addMinutes(10);
            // $otp = rand(123468, 999999);
            if ($domain) {

                return $this->sendOtp($request, $user->id, $domain->id);
                
            } 
            else {
                return $this->sendResponsewithoutData( "Access Denied! Your Company is not partner with us.", 400);
            }
        }

        #verify OTP
        elseif($request->action == 2){

            return $this->verifyOtp($request, $user->id);
            
        }
        } catch (Exception $e) {
                
            Log::error('Error caught: "subscription" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #---------------------- SEND OTP ----------------------#
    function sendOtp($request, $user_id, $domain_id){

        $expiry = Carbon::now()->addMinutes(10);
        $otp = rand(123468, 999999);

        $emp1 = CorporativePlanUser::where('user_id', $user_id)->first();
        if (isset($emp1)) {
            $emp = CorporativePlanUser::where('user_id', $user_id)->where('corporate_email', $request->email)->first();
            if (isset($emp)) {
                if ($emp->otp_expiry > Carbon::now()) {
                    return $this->sendResponsewithoutData("OTP already sent, please check your mail", 200);
                } else {
                    $emp->otp               = $otp;
                    $emp->otp_expiry        = $expiry;
                    $emp1->is_verified      = 0;
                    $emp1->is_active        = 1;
                    $emp->save();

                    Mail::to($request->email)->send(new CorporateEmailVerification($otp));

                    return $this->sendResponsewithoutData("OTP sent, please check your mail", 200);
                }
            } else {
                $emp1->domain_id        = $domain_id;
                $emp1->corporate_email  = $request->email;
                $emp1->is_verified      = 0;
                $emp1->is_active        = 1;
                $emp1->otp              = $otp;
                $emp1->otp_expiry       = $expiry;
                $emp1->save();

                Mail::to($request->email)->send(new CorporateEmailVerification($otp));

                return $this->sendResponsewithoutData( "OTP sent, please check your mail", 200);
            }

        } else {
            //send otp on provided mail
            
            $employee                   = new CorporativePlanUser;
            $employee->domain_id        = $domain_id;
            $employee->user_id          = $user_id;
            $employee->corporate_email  = $request->email;
            $employee->otp              = $otp;
            $employee->otp_expiry       = $expiry;
            $employee->is_verified      = 0;
            $employee->is_active        = 1;
            $employee->save();

            Mail::to($request->email)->send(new CorporateEmailVerification($otp));
            return $this->sendResponsewithoutData( "OTP sent, please check your mail", 200);
        }
    }

    #---------------------- VERIFY OTP ----------------------#
    function verifyOtp($request, $user_id){
        $user= User::find($user_id);
        $employee = CorporativePlanUser::where('user_id', $user->id)->where('is_active' , 1)->where('is_verified' , 0)->first();

        if(isset($employee)) {
            if ($employee->is_verified != 1) {
                if ($employee->otp_expiry > Carbon::now()) {
                    if ($employee->otp == $request->otp) {

                        $this->markAsExpiredPlan($user->id);

                        #Create or update Plan Order
                        UserPlan::updateOrCreate([
                            'user_id'                   =>  $user->id,
                        ],
                        [
                            'plan_id'                   =>  $request->plan_id,
                            'is_active'                 =>  1,
                            'is_trial_plan'             =>  0,
                            'start_date'                =>  Carbon::now(),
                            'expiry_date'               =>  null,
                            'transaction_id'            =>  null,
                            'original_transaction_id'   =>  null,
                            'cancelled_at'              =>  null,
                            'purchased_device'          =>  $request->purchased_device
                        ]);
                        $plan                      = UserPlan::where('user_id', $user->id)->where('plan_id', $request->plan_id)->first();
                        

                        $employee->user_plan_id = $plan->id;
                        $employee->otp          = null;
                        $employee->otp_expiry   = null;
                        $employee->is_verified  = 1;
                        $employee->is_verified  = 1;
                        $employee->is_active    = 1;
                        $employee->save();

                        $user->user_plan_id = $plan->id;
                        $user->plan_status = 1;
                        $user->save();


                        #get current subscription plan
                        $subscription                         =    $this->userCurrentPlans($user->id);
                        $subs                                 =   (isset($subscription)) && !empty($subscription) ? $subscription : [];
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





    public function userCurrentPlans($userId){

        $userPlans      =   UserPlan::where(['user_id'=>$userId,'is_active'=>1])->with('plan_details')->get();
        return $userPlans;

    }





    #================= MARK THE PLAN AS EXPIRED OR CANCELED =================#
    function markAsExpiredPlan($userId){
        $currentTime    = Carbon::now();
        $plan           = UserPlan::where('user_id', $userId)->where('is_active', 1)->whereDoesntHave('plan_details', function($query){
                                $query->where('type', 4);
                            })->first();

        if(isset($plan)){
            if(Carbon::parse($plan->expiry_date)->isPast()){
                $plan->is_active      = 3;  //expired
            }
            else{
                $plan->is_active      = 2;  //cancelled
                $plan->cancelled_at   = $currentTime;
            }
            $plan->last_update        = $currentTime;
            $plan->save();
            
            $payment = PaymentHistory::where('user_plan_id', $plan->id)
            ->where('is_active', 1)->first();
            if(isset($payment)){
                if(Carbon::parse($payment->expiry_date)->isPast()){
                    $payment->is_active      = 3;  //expired
                }
                else{
                    $payment->is_active      = 2;  //cancelled
                    $payment->cancelled_at   = $currentTime;
                }
                $payment->user_plan_id   = null;
                $payment->last_update    = $currentTime;
                $payment->save();
            }
            return true;
        }
        else{
            return false;
        }


    }












}
