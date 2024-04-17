<?php

namespace App\Services;

use App\Models\User;
use App\Models\Stat;
use App\Models\UserStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Middleware\Authenticate;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\Ethnicity;
use App\Models\Pronouns;
/**
 * Class GetUserService.
 */
class GetUserService extends BaseController
{

    public function getAuthUser($userId)
    {
        // $userDetail =   User::where($userId);
        $userDetail =   $this->getUser($userId);


        if (isset($userDetail) && !empty($userDetail)) {
            // Create a Passport token for the user
            $passport_token = $userDetail->createToken(env('PASSPORT_SECURITY_TOKEN'))->accessToken;
            // Update the user's token field with the generated Passport token
            $userDetail->token = $passport_token;
        }
        return $userDetail;
    }

    // public function getUser($userId){

    //     $userDetail         = User::where('id', $userId)->with('User_interest.interest','user_documents.document','userParticipant.participant', function($query){

    //         $query->select('id','name');

    //     })->withCount('userPost')->withCount('supporter')->with('userPost',function($query){

    //         $query->take(10); // Limiting the number of user posts to 10

    //     })->first();


    //     $participantIds = $userDetail->userParticipant->pluck('participant_id');

    //     if(isset($userDetail) && !empty($userDetail)){

    //         if(isset($userDetail->profile) && !empty($userDetail->profile)){

    //             $userDetail->profile =   asset('storage/'.$userDetail->profile);

    //         }

    //         if(isset($userDetail->cover) && !empty($userDetail->cover)){

    //             $userDetail->cover =   asset('storage/'.$userDetail->cover);
    //         }

    //         $userDetail->userPost->each(function($query){

    //             if(isset($query->media_url) && !empty($query->media_url)){

    //                 $query->media_url     =   asset('storage/'.$query->media_url);
    //             }

    //         });

    //     }
    //     return $userDetail;
    // }


    public function getUser($userId,$auth_user="")
    {

        $userDetail = User::with([

            'user_interest.interest' => function ($query) {
                $query->select('id', 'name', 'icon');
            },
            'user_documents'=>function($query){

                $query->select('id','user_id','document_type');

            },'user_documents.document'=>function($query){

                $query->select('id','name');
            },
            'userParticipant.participant' => function ($query) {

                $query->select('id', 'name', 'reason');
            },
            'userPost' => function ($query) {

                $query->take(10)->when(isset($query->media_url), function ($subQuery) {
                    $subQuery->update(['media_url' => asset('storage/' . $subQuery->media_url)]);
                });

            },'user_activities'
        ])->withCount(['userPost', 'supporter','supporting'])
        
        
        ->selectRaw('IF(EXISTS(SELECT 1 FROM user_followers WHERE user_id = ? AND follower_user_id = ?), 1, 0) AS is_supporting', [$userId, $auth_user])->where('id', $userId)->first();
    
        if ($userDetail) {

            $userDetail->user_interest->each(function ($userInterest) {

                $interest = $userInterest->interest;
                // Prepend asset path to the icon attribute
                $interest->icon = asset('storage/' . $interest->icon);

            });

            $ethnicity  =    Ethnicity::where('id',$userDetail->ethnicity)->first();
            if(isset($ethnicity) && !empty($ethnicity)){

                $userDetail->ethnicity_name  =   $ethnicity->name;
            }



            $pronouns   =   Pronouns::where('id',$userDetail->pronoun)->first();

            if(isset($pronouns) && !empty($pronouns)){

                $userDetail->pronouns_name  =   $pronouns->subjective."/".$pronouns->objective;

            }
            $userDetail->userPost->each(function ($user_post) {
            
                if(isset($user_post) && !empty($user_post)){
                    // Prepend asset path to the icon attribute
                    $user_post->media_url = ($user_post->media_url)?asset('storage/' . $user_post->media_url):null;
                }

            });

            // dd($userDetail->user_interest);
            // if(isset($userDetail->user_interest)){

            //     $userDetail->user_interest->each( function($query){

            //       dd($query);
            //     });

            //     // dd($userDetail->user_interest['interest']);

            //     $userDetail->user_interest['interest']['icon']  = asset('storage/' . $userDetail->user_interest['interest']['icon']);
            // }

            $userDetail->profile        = $userDetail->profile ? asset('storage/' . $userDetail->profile) : null;
            $userDetail->cover          = $userDetail->cover ? asset('storage/' . $userDetail->cover) : null;
        }
    
        return $userDetail;
    }
}
