<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentHistory;
use App\Models\UserPlan;
use Exception;
use App\Models\User;

/**
 * Class user_payment.
 */
class user_payment extends BaseController
{

    #------------  A P P        I N     P U R C H A S E     -------------------#
    public function AppInPurchase($request,$userId){

        try {

            $validator = Validator::make($request->all(), [
                'transaction_id' =>'required|string',
                'start_date' => 'required|date_format:Y-m-d H:i:s e',
                'end_date' => 'required|date_format:Y-m-d H:i:s e',
                'is_trial_period' =>'required|boolean',
                'purchased_device'=>'required|integer|between:1,2',
                'amount' => 'required|numeric|min:0', // Validation rules for 'amount' field
            ]);
                // Add custom rule for no special characters
            if ($validator->fails()) {
            
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {
                //check user already used trailed or not
                if($request->is_trial_period) {
                    $isTrailUsed        =       User::select(['id'=>$userId,'is_active'=>1,'is_trial_used'=>1])->exists();
                    if($isTrailUsed){
                        return $this->sendResponsewithoutData(trans('message.trail_already_used'), 403);
                    }
                }

                $startdate                            =     utc_time_conversion($request->start_date);
                $enddate                              =     utc_time_conversion($request->end_date);
                $startdateTimeStamp                   =     strtotime($startdate);
                $enddateTimeStamp                     =     strtotime($enddate);
                $nowTimeStamp                         =     strtotime(Carbon::now()->format('Y-m-d H:i:s'));
                $amount                               =     $request->amount;
                $transactionId                        =     $request->transaction_id;
                //check user transaction id with userid
                $isTransExist                         =   UserPlan::where('transaction_id',$transactionId)->first();
                if(isset($isTransExist) && !empty($isTransExist)){

                    if($isTransExist['user_id'] != $userId){

                        return $this->sendResponsewithoutData(trans('message.this_payment_account_already_used'), 403);

                    }else{

                        $isTransExist->start_date                   =     $startdate;
                        $isTransExist->expiry_date                  =     $enddate;
                        if($request->is_trial_period) {
                            $isTransExist->is_trial_plan            =     $request->is_trial_period;
                        }
                        $isTransExist->purchased_device             =     $request->purchased_device;

                        if($request->cancelled_period_at_end) {

                            $isTransExist->cancelled_period_at_end  =     $request->cancelled_period_at_end;
                        }
                        if($request->cancelled_period_end) {

                            $isTransExist->cancelled_period_end     =     utc_time_conversion($request->cancelled_period_end);
                        }
                        $isTransExist->is_active         =     ($enddateTimeStamp >= $nowTimeStamp)?1:0;
                        $isTransExist->save();
                        //subscription updated
                        $subscription                     =    $this->userCurrentPlans($userId);
                        $subs                             =   (isset($subscription)) && !empty($subscription)?$subscription:[];
                       return  $this->sendResponse($subs, trans('message.Plan_updated_successfully'),200);
                    }
                }else{                                      // add new subscription

                    $addPayment                           =     new PaymentHistory();
                    $addPayment->transaction_id           =     $transactionId;
                    $addPayment->amount                   =     $amount;
                    $addPayment->payment_by               =     1; // 1 app in purchas, 2=stripe, 3=paypal, 4=google_pay,
                    $addPayment->is_trial_plan            =     $request->is_trial_period;
                    $addPayment->purchased_device         =     $request->purchased_device;
                    $addPayment->user_id                  =     $userId;
                    $addPayment->payment_by             =       1;
                    $addPayment->save();

                    $user_plan                            =     new UserPlan();
                    $user_plan->transaction_id            =     $request->transaction_id;
                    $user_plan->user_id                   =     $userId;
                    $user_plan->payment_status            =     1;
                    $user_plan->start_date                =     $startdate;
                    $user_plan->expiry_date               =     $enddate;
                    $user_plan->is_active                 =     ($enddateTimeStamp >= $nowTimeStamp)?1:0;
                    $user_plan->purchased_device          =     $request->purchased_device;
                    $user_plan->save();
                    $subscription                         =    $this->userCurrentPlans($userId);
                    $subs                                 =   (isset($subscription)) && !empty($subscription)?$subscription:[];
                   return  $this->sendResponse($subs, trans('message.plan_activates_successfully'),200);
                }
            }
        } catch (Exception $e) {
            
            Log::error('Error caught: "subscription" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------  A P P        I N     P U R C H A S E     -------------------#



    public function userCurrentPlans($userId){


        $userPlans      =   UserPlan::where(['user_id'=>$userId,'is_active'=>1])->get();
        return $userPlans;

    }
















    #------------------------  S T R I P E  -----------------------------











}
