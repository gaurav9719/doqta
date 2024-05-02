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
    
                $isTrail                              =     $request->is_trial_period;
                $startdate                            =     utc_time_conversion($request->start_date);
                $enddate                              =     utc_time_conversion($request->end_date);
                $startdateTimeStamp                   =     strtotime($startdate);
                $enddateTimeStamp                     =     strtotime($enddate);
                $nowTimeStamp                         =     strtotime(Carbon::now()->format('Y-m-d H:i:s'));
                //create new plan 
                $addPayment                           =     new PaymentHistory();
                $addPayment->transaction_id           =     $request->transaction_id;
                $addPayment->amount                   =     $request->amount;
                $addPayment->payment_by               =     1; // 1 app in purchas, 2=stripe, 3=paypal, 4=google_pay,
                $addPayment->is_trial_plan            =     $request->is_trial_period;
                $addPayment->purchased_device         =     $request->purchased_device;
                $addPayment->user_id                  =     $userId;
                $addPayment->save();
                $plan_purchased_id                    =     $addPayment->id;
                $checkIfExistPlan                     =     UserPlan::where(['user_id'=>$userId,'is_active'=>1])->first();
                
                if($enddateTimeStamp > $nowTimeStamp){
                   
                    if(isset($checkIfExistPlan) && !empty($checkIfExistPlan)){
    
                        $user_plan                       =   new UserPlan();
                        $user_plan->transaction_id      =   $request->transaction_id;
                        $user_plan->user_id              =   $userId;
                        $user_plan->payment_status       =   1;
                        $user_plan->start_date           =   $startdate;
                        $user_plan->expiry_date             =   $enddate;
                        $user_plan->is_active            =   1;
                        $user_plan->purchased_device     =     $request->purchased_device;
                        $user_plan->save();
    
                    }else{              //update lastest one
 
                        $checkIf                       =     UserPlan::where(['user_id'=>$userId,'is_active'=>0])->latest()->first();

                        if(isset($checkIf) && !empty($checkIf)){

                            $checkIf->transaction_id       =   $request->transaction_id;
                            $checkIf->user_id              =   $userId;
                            $checkIf->payment_status       =   1;
                            $checkIf->start_date           =   $startdate;
                            $checkIf->expiry_date             =   $enddate;
                            $checkIf->is_active            =   1;
                            $checkIf->save();
                        }else{
                            $user_plan                       =   new UserPlan();
                            $user_plan->transaction_id      =   $request->transaction_id;
                            $user_plan->user_id              =   $userId;
                            $user_plan->payment_status       =   1;
                            $user_plan->start_date           =   $startdate;
                            $user_plan->expiry_date             =   $enddate;
                            $user_plan->is_active            =   1;
                            $user_plan->purchased_device     =     $request->purchased_device;
                            $user_plan->save();
                        }
                    }
                }
                
            }
        } catch (Exception $e) {
            
            
            Log::error('Error caught: "transaction" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------  A P P        I N     P U R C H A S E     -------------------#
















    #------------------------  S T R I P E  -----------------------------











}
