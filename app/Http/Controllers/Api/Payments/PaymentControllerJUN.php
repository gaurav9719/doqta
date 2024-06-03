<?php

namespace App\Http\Controllers\Api\Payments;

use Exception;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\Domain;
use App\Models\UserPlan;
use Stripe\StripeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CorporativePlanUser;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\CardException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;
use App\Services\Payment\user_payment;
use App\Mail\CorporateEmailVerification;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;

class PaymentController extends BaseController
{


    protected $notificationService,$userPayment;

    public function __construct(NotificationService $notification_service, user_payment $userPayment)
    {

        $this->notificationService = $notification_service;
        $this->userPayment = $userPayment;

    }



    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authID = Auth::id();
        $validate=Validator::make($request->all(), [
            'type' => 'required|integer|between:1,2'
        ]);
        if($validate->fails()){
            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }

        if($request->type == 1){
            $plan=UserPlan::where('user_id', $authID)->where('is_active', 1)->with('plan_details')->first();
            if(isset($plan)){
                return $this->sendResponse($plan, "Your Current Plan", 200);
            }else{
                return $this->sendResponsewithoutData("No active plan on your account", 200);
            }
        }
        elseif($request->type == 2){
            $allPlan = Plan::where('is_active', 1)->with('features')->get();
            return $this->sendResponse($allPlan, "All plans", 200);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $validator           =      Validator::make($request->all(), [
                'plan_id'=>'required|integer|exists:plans,id',
                'type'   => 'required|integer|between:1,4',
                ],
                ['type.integer'=>"invalid type"]);
                // Add custom rule for no special characters

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);

            }
            #check any paid plan active on account
            $authId          =      Auth::id();
            $check           =      UserPlan::where('user_id', $authId)->where('is_trial_plan', 0)->where('is_active', 1)->first();

            if(isset($check)){

                $check->plan_details;

                return $this->sendResponse($check, $check['plan_details']['name']." Plan already active on your account", 200);

            }
            
            #check plan type match with selected plan
            
            if(Plan::find($request->plan_id)->type != $request->type){

                return $this->sendResponsewithoutData("Invalid plan type ", 422);
            }
            
            #Corporate plan
            if($request->type == 4){

                $validation = Validator::make($request->all(), [

                    //'email' => 'required|email:rfc,dns',
                    'email' => 'required|email',
                    'action' => 'required|integer|between:1,2',
                    'purchased_device'  => 'required|integer|between:1,2',

                ]);
                if ($validation->fails()) {

                    return $this->sendResponsewithoutData($validation->errors()->first(), 400);
                }

                return $this->userPayment->corporatePlan($request->all());
            }
            else {

                return  $this->userPayment->AppInPurchase($request,$authId);

            }
            

        }
        catch(Exception $e){

            DB::rollback();
            Log::error('Error caught: "signUpUser" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


        #Corporate Plan
        // function corporatePlan($request){
        //     $request=(object) $request;
    
        //     $user= Auth::user();
    
        //     $check1 = UserPlan::where('user_id', $user->id)->where('is_active', 1)->first();
    
        //     if ($check1) {
        //         $plan = Plan::find($check1->plan_id);
        //         return $this->sendResponsewithoutData("$plan->name plan already activated on your account", 400);
        //     }
    
        //     #Send OTP
        //     if($request->action == 1){
        //         $check2 = CorporativePlanUser::where('corporate_email', $request->email)->where('is_verified', 1)->first();
        //         if ($check2) {
        //             return $this->sendResponsewithoutData( "Provided email already in use", 400);
        //         }
    
        //         $email = explode('@', $request->email);
        //         $domain = $email[1];
        //         $check = Domain::where('name', $domain)->first();
        //         $expiry = Carbon::now()->addMinutes(10);
        //         $otp = rand(123468, 999999);
        //         if ($check) {
        //             $emp1 = CorporativePlanUser::where('user_id', $user->id)->first();
        //             if (isset($emp1)) {
        //                 $emp = CorporativePlanUser::where('user_id', $user->id)->where('corporate_email', $request->email)->first();
        //                 if (isset($emp)) {
        //                     if ($emp->otp_expiry > Carbon::now()) {
        //                         return $this->sendResponsewithoutData("OTP already sent, please check your mail", 200);
        //                     } else {
        //                         $emp->otp = $otp;
        //                         $emp->otp_expiry = $expiry;
        //                         $emp->save();
    
        //                         Mail::to($request->email)->send(new CorporateEmailVerification($otp));
    
        //                         return $this->sendResponsewithoutData("OTP sent, please check your mail", 200);
        //                     }
        //                 } else {
        //                     $emp1->domain_id = $check->id;
        //                     $emp1->corporate_email = $request->email;
        //                     $emp1->otp = $otp;
        //                     $emp1->otp_expiry = $expiry;
        //                     $emp1->save();
    
        //                     Mail::to($request->email)->send(new CorporateEmailVerification($otp));
    
        //                     return $this->sendResponsewithoutData( "OTP sent, please check your mail", 200);
        //                 }
    
        //             } else {
        //                 //send otp on provided mail
                        
        //                 $employee = new CorporativePlanUser;
        //                 $employee->domain_id = $check->id;
        //                 $employee->user_id = $user->id;
        //                 $employee->corporate_email = $request->email;
        //                 $employee->otp = $otp;
        //                 $employee->otp_expiry = $expiry;
        //                 $employee->save();
    
        //                 Mail::to($request->email)->send(new CorporateEmailVerification($otp));
        //                 return $this->sendResponsewithoutData( "OTP sent, please check your mail", 200);
        //             }
        //         } else {
        //             return $this->sendResponsewithoutData( "Access Denied! Your Company is not partner with us.", 400);
        //         }
        //     }
    
        //     #verify OTP
        //     elseif($request->action == 2){
        //         $employee = CorporativePlanUser::where('user_id', $user->id)->first();
        //         if (isset($employee)) {
        //             if ($employee->is_verified != 1) {
        //                 if ($employee->otp_expiry > Carbon::now()) {
        //                     if ($employee->otp == $request->otp) {
        //                         #Create Plan Order
        //                         $order                      = new UserPlan;
        //                         $order->user_id             = $user->id;
        //                         $order->plan_id             = $request->plan_id;
        //                         $order->is_active           = 1;
        //                         $order->is_trial_plan       = 0;
        //                         $order->start_date          = Carbon::now();
        //                         $order->purchased_device    = $request->purchased_device;
        //                         $order->save();
    
        //                         $employee->user_plan_id = $order->id;
        //                         $employee->otp          = null;
        //                         $employee->otp_expiry   = null;
        //                         $employee->is_verified  = 1;
        //                         $employee->is_verified  = 1;
        //                         $employee->is_active    = 1;
        //                         $employee->save();
    
    
        //                         $user->user_plan_id = $order->id;
        //                         $user->plan_status = 1;
        //                         $user->save();
    
        //                         return $this->sendResponsewithoutData( "OTP verified!, Corporate membership plan activated on your account", 400);
        //                     } else {
        //                         return $this->sendResponsewithoutData( "invalid OTP", 400);
        //                     }
        //                 } else {
        //                     return $this->sendResponsewithoutData( "OTP expired", 400);
        //                 }
        //             } else {
        //                 return $this->sendResponsewithoutData("Corporate membership already activated on your account", 400);
        //             }
        //         }
        //         else {
        //             return $this->sendResponsewithoutData("Something went wrong", 400);
        //         }
        //     }
        // }




    #------------------------------  S T R I P E    ------------------------#
    
    // public function payment(Request $request)
    // {
    //     try {
    //         $transferGId                 =           "plan-".transferGroupId();
    //         $validator                   =            Validator::make($request->all(), ['plan_id' => 'required|integer|exists:business_campaigns,id', 'payment_amount' => 'required']);
    //         if ($validator->fails()) {

    //             return $this->sendResponsewithoutData(getErrorAsString($validator->errors()), 400);

    //         } else {
    //              //check payment is done and business is the owner of the campaign or not
    //             $login_user               =   Auth::user();
        
             

              
                               
    //                             if($request->payment_amount==$campaign->total_amount){

    //                                     $influencer       =   User::find($campaign->selected_user_id);
            
    //                                     $meta_data        =   array('business_id' => $login_user->id, 'user_name' => $login_user->first_name, 'user_email' => $login_user->email, 'influencer_id' => $influencer->id, 'influencer_name' => $influencer->first_name, 'influencer_email' => $influencer->email, 'apply_date' => $campaign->apply_date, 'promotor_picked' => $campaign->promotor_picked, 'post_date' => $campaign->post_date, 'privacy_type' =>  $campaign->privacy_type,'campaignId'=>$campaign_id,'sub_total'=>$campaign->price,'total'=>$campaign->total_amount,'service_percentage'=>$campaign->service_percentage,'service_amount'=>$campaign->service_amount);
                
    //                                 $response       =     $this->stripe->checkout->sessions->create([
                    
    //                                     'line_items' => [
    //                                         [
    //                                             'price_data' => [
    //                                                 'currency' => 'usd',
    //                                                 'unit_amount' => $request->payment_amount * 100,
    //                                                 'product_data' => [

    //                                                     'name' => 'Campaign payment'
                    
    //                                                 ],
    //                                             ],
    //                                             'quantity' => 1,
                                               
    //                                         ],
    //                                     ],

    //                                     'customer' => $login_user->stripe_customer_id,
    //                                     'metadata' => $meta_data,
    //                                     'mode' => 'payment',

                            
    //                                     'success_url' => url('campaign/success?success_id={CHECKOUT_SESSION_ID}&user_id=' . Crypt::encrypt(Auth::id()) . '&id=' . Crypt::encrypt($campaign_id)),
                    
    //                                     'cancel_url' =>  url('campaign/cancel?success_id={CHECKOUT_SESSION_ID}'),
    //                                 ]);
                    
    //                                 return $this->sendResponse($response, "Campaign payment", 200);
                                    
    //                             }else{      #--------- payment is not equal to campaign amount

    //                                 return $this->sendResponsewithoutData("Payment amount is incorrect", 422);


    //                             }
                          
        
                       
    //                 }else{  // when campaign is not completed

    //                     return $this->sendResponsewithoutData("The campaign is still ongoing.", 422);

    //                 }

    //             }else{
    //                 return $this->sendResponsewithoutData("Something went wrong", 422);

    //             }
    //         }
    //     } catch (Exception $e) {

    //         return $this->sendError($e->getMessage(), [], 400);

    //     }
    // }


    //PAYMENT SUCCESS FUNCTION
    // public function success(Request $request)
    // {
    //     try {

    //         $session           =           $this->stripe->checkout->sessions->retrieve($request->success_id);

    //         if (!isset($session->id)) {

    //             return view('stripe.error',compact('date',$session));

    //         }

    //         $userID              =       Crypt::decrypt($request->user_id);
    //         $campaignID          =       Crypt::decrypt($request->id);
    //         $influencerId        =       $session->metadata->influencer_id;
    //         $check               =        Transaction::where(['payment_id' =>$session->payment_intent, 'user_id' => $userID])->first();

    //         if (isset($check->id) && !empty($check->id)) {

    //             $check['payment_message']= "Payment is already done";
    //             return view('stripe.payment_message',compact('check',$check));
    //             // return redirect("login")->with('error','Payment Already done');
    //         }

    //         $campaign                     =   Business_campaign::find($campaignID);
    //         $campaign->payment_status     =   1;
    //         $campaign->save();

    //         // if($campaign->privacy_type==2){         //private request

    //         //     if(isset($campaign->request_user_id) && !empty($campaign->request_user_id)){

    //         //         $isRequestExist  =  Campaign_request::where(['campaign_id'=>$campaignID,'user_id'=>$campaign->request_user_id])->first();

    //         //         if(isset($isRequestExist) && !empty($isRequestExist)){

    //         //             $isRequestExist->is_active         =   1;
    //         //             $isRequestExist->save();
    //         //             $requestedID                       =    $isRequestExist->id;
    //         //             // send push notificatin to user

    //         //         }else{  // enter data and send notification

    //         //             $RequestCampaign                =   new Campaign_request();
    //         //             $RequestCampaign->campaign_id   =   $campaignID;
    //         //             $RequestCampaign->user_id       =   $campaign->request_user_id;
    //         //             $RequestCampaign->requested_by  =   $userID;
    //         //             $RequestCampaign->request_type  =   2;
    //         //             //for payment we are adding this 
    //         //             $RequestCampaign->is_active     =   1;
    //         //             $RequestCampaign->save();
    //         //             $requestedID                      =  $RequestCampaign->id;

    //         //         }
    //         //         $sender                            =   User::find($userID);
    //         //         $reciever                          =   User::find($campaign->request_user_id);
    //         //         $myName                            =   $sender->first_name;
    //         //         $message                           =   $myName." "."has sent you invitation for the campaign"; 
    //         //         $section                           =   3;
    //         //         $status                            =   $this->notificationService->sendNotification($reciever,$sender,$message,$section,$campaignID,$requestedID);

    //         //         // S E N D      P U S H     N O T I F I C A T Is O N     T O     R E Q U E S T E D   U S E R 
    //         //     }
    //         // }

    //         $updatedCampaign              =   Business_campaign::find($campaignID);
    //         $transaction                  =   new Transaction();
    //         $amount_paid                  =   str_replace( ',', '', $session->amount_total );
    //         $transaction->amount          =   number_format((float)$amount_paid/100, 2, '.', '');
    //         $transaction->user_id         =   $userID;
    //         $transaction->receiver_id     =   $updatedCampaign->selected_user_id;
    //         $transaction->payment_type    =   1;
    //         $transaction->source_type     =   1;    //1 stripe ,2 paypal
    //         $transaction->status          =   1;    
    //         $transaction->payment_id      =   $session->payment_intent;
    //         $transaction->payment_status  =   1;
    //         $transaction->campaign_id     =   $campaignID;
    //         $transaction->promotor_picked =   $session->metadata->promotor_picked;
    //         $transaction->post_date       =   $session->metadata->post_date;

    //         if($transaction->save()){       // save in notification table

    //             $transactionID            =    $transaction->id;

    //             $sender                            =   User::find($userID);
    //             $reciever                          =   User::find($userID);
    //             $myName                            =   $sender->first_name;
    //             $message                           =   "You payement is successfully for the campaign #".$campaignID; 
    //             $section                           =   3;
    //             $status                            =   $this->notificationService->sendNotification($reciever,$sender,$message,$section,$campaignID);
    //         }

    //         //TRANSFER TO INFLUENCER
    //         $influencer                        =        User::find($influencerId);
    //         try {

    //             $transfer                      =        $this->stripe->transfers->create([
    //                                                         'amount' => $session->metadata->sub_total*100,
    //                                                         'currency' => "USD",
    //                                                         // 'source_transaction' => '{{CHARGE_ID}}',
    //                                                         'destination' => $influencer->stripe_account_id]);
    //             if($transfer){

    //                 $transaction                  =   new Transaction();
    //                 $amount_paid                  =   str_replace( ',', '', $transfer->amount );
    //                 $transaction->amount          =   number_format((float)$amount_paid/100, 2, '.', '');
    //                 $transaction->user_id         =   $influencerId;
    //                 $transaction->receiver_id     =   $influencerId;
    //                 $transaction->payment_type    =   2;    //1 for payment,2 for transfer
    //                 $transaction->source_type     =   1;    //1 stripe ,2 paypal
    //                 $transaction->status          =   1;    
    //                 $transaction->transfer_id     =   $transfer->id;
    //                 $transaction->payment_status  =   1;
    //                 $transaction->campaign_id     =   $campaignID;
    //                 $transaction->promotor_picked =   $session->metadata->promotor_picked;
    //                 $transaction->post_date       =   $session->metadata->post_date;
    //                 if($transaction->save()){       // save in notification table

    //                     // update  the origin transcation
    //                     Transaction::where('id',$transactionID)->update(['is_transfered'=>1]);

    //                 }
    //             }

								
    //         } catch (\Stripe\Exception\ApiErrorException $e) {

    //             $return_array =  json_encode([
    //                 "status" => $e->getHttpStatus(),
    //                 "type" => $e->getError()->type,
    //                 "code" => $e->getError()->code,
    //                 "param" => $e->getError()->param,
    //                 "message" => $e->getError()->message,
    //             ]);

    //             // dd($transfer);
    //             $transaction                  =   new Transaction();
    //             $transaction->amount          =   $session->metadata->sub_total;
    //             $transaction->user_id         =   $influencerId;
    //             $transaction->receiver_id     =   $influencerId;
    //             $transaction->payment_type    =   2;    //1 for payment,2 for transfer
    //             $transaction->source_type     =   1;    //1 stripe ,2 paypal
    //             $transaction->status          =   0;    
    //             $transaction->payment_status  =   3; // 0pending,1:done,2 refund.3 failed
    //             $transaction->campaign_id     =   $campaignID;
    //             $transaction->promotor_picked =   $session->metadata->promotor_picked;
    //             $transaction->post_date       =   $session->metadata->post_date;
    //             $transaction->cancel_reason   =   $return_array; 
    //             $transaction->save();     // save in notification table
    //         }

    //         return view('payment_success.payment_success');

    //     } catch (Exception $e) {
            
    //         return view('stripe.error');
            
    //     }
    // }
    //   PAYMENT SUCCESS FUNCTION






}
