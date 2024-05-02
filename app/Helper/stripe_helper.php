<?php

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
function init_Stripe()
{
    try{

        return  new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));

    }catch(Exception $e){
        sleep(2);
        return  new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));

    }


}

if (!function_exists('createStripeCustomer')) {

    function createStripeCustomer($user_id)
    {
        try {

            $stripe                 =               init_Stripe();
            $user                   =               User::find($user_id);
            $user->country_name     =               "USA";
            $user->state_name       =               "California";
            $user->city_name        =               "Hollywood";
            $user->zipcode          =               33004;
            $customer               =               $stripe->customers->create([
                'name' =>      $user->user_name,
                'email' => $user->email,
                'description' => $user_id,

                'metadata' => ['user_id' => $user_id, 'role_id' => $user->user_role],
                'address' => [
                    'country' => $user->country_name,
                    'city'    => $user->city_name,
                    'state'   => $user->state_name,
                    'postal_code' => $user->zipcode
                ]
            ]);

            // dd($customer);
            $update                 =       User::where('id', $user_id)->update(['stripe_customer_id' => $customer->id]);
            return 200;

        } catch (Exception $e) {

            return $e->getMessage();
        }
    }
}




function stripeFindOneUpdateOrCreate($user_id)
{

    $user       =       User::find($user_id);

    $stripe     =       init_Stripe();

    if (!empty($user->stripe_id)) {

        $check_cutomore = $stripe->customers->retrieve(
            $user->stripe_id,
            []
        );

        if (isset($check_cutomore->id)) {

            return $user;
        }
    }

    $customore =  $stripe->customers->create([
        'name' => $user->name,
        'email' => $user->email,
        'description' => $user_id,
        'metadata' => ['user_id' => $user_id, 'role_id' => $user->role_id],
        'address' => [
            'city' => @$user->city,
            'country' => 'USA',
            'city' => @$user->city,
            'state'  => @$user->state,
            'postal_code' =>  @$user->pin_code
        ]
    ]);


    $update = User::where('id', $user_id)->update(['stripe_id' =>  $customore->id]);

    $final = User::find($user_id);
    return $final;
}
