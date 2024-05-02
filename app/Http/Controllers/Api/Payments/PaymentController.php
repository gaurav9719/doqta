<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;
use Stripe\Exception\CardException;
use Stripe\StripeClient;
use App\Services\NotificationService;
use App\Services\Payment\user_payment;

class PaymentController extends BaseController
{

    private $stripe;
    protected $notificationService,$userPayment;

    public function __construct(NotificationService $notification_service, user_payment $userPayment)
    {

        $this->notificationService = $notification_service;
        $this->userPayment = $userPayment;

        try{

            $this->stripe                 =    new StripeClient(env('STRIPE_SECRET_KEY'));
    
        }catch(Exception $e){
            
            sleep(2);
            $this->stripe                 =    new StripeClient(env('STRIPE_SECRET_KEY'));
        }
    }



    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        


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

                'type' =>'required|integer|between:1,3',
                'plan_id'=>'required|integer|between:1,3'
                ],
                ['type.integer'=>"invalid type"]);
                // Add custom rule for no special characters

            if ($validator->fails()) {
                
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {

                $authId          =      Auth::id();

                return  $this->userPayment->AppInPurchase($request,$authId);
               


            }
        }catch(Exception $e){

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
