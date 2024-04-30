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
use App\Models\Post;
use Carbon\Carbon;
use App\Models\PostLike;
use App\Models\UserFollower;
use Exception;
use Illuminate\Support\Facades\Log;


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
            },'user_medical_certificate'=>function($query){

                $query->select('id','user_id','medicial_degree_type','specialty','medicial_document','verified_status','is_active');
            },

            'user_medical_certificate.medical_certificate'=>function($query){

                $query->select('id', 'name', 'type');

            },

            'user_medical_certificate.speciality'=>function($query){

                $query->select('id', 'name', 'type');
                
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
        
        // ->selectRaw('IF(EXISTS(SELECT 1 FROM user_followers WHERE user_id = ? AND follower_user_id = ?), 1, 0) AS is_supporting', [$userId, $auth_user])->where('id', $userId)->first();

        
        ->where('id', $userId)->first();
        if ($userDetail) {

                $isSupporting               =   UserFollower::where(['user_id'=>$userId , 'follower_user_id'=>$auth_user])->first();

                $userDetail->is_supporting  =   (isset($isSupporting) && !empty($isSupporting))?$isSupporting->status:0;

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

            if(isset($userDetail->user_medical_certificate) && !empty($userDetail->user_medical_certificate)){

                $userDetail->user_medical_certificate->each(function ($user_medical) {
            
                    if(isset($user_medical->medicial_document) && !empty($user_medical->medicial_document)){
                        // Prepend asset path to the icon attribute
                        $user_medical->medicial_document = asset('storage/' . $user_medical->medicial_document);
                    }
                });
            }
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



    #--------------------  G E T        U S E R         P O S T  ----------------------#
    public function getUserPosts($userId,$authId,$limit){
        try {
            if($userId!=$authId){
                $isSupporting   =   UserFollower::where(['user_id'=>$userId,'follower_user_id'=>$authId])->exists();
                if(!$isSupporting){
                    return $this->sendError(trans('message.you_are_not_supporting'), [], 403);
                }
            }
            $posts          =   Post::whereHas('post_user', function($query) {
                $query->where('is_active', 1);
            })
            ->with([
                'group:id,name,description,cover_photo,post_count',
                'post_user:id,user_name,name,profile'
            ])
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->whereNotExists(function ($query) use ($authId) {

                $query->select(DB::raw(1))
                    ->from('hidden_posts')
                    ->whereColumn('hidden_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                    ->where('hidden_posts.user_id', '=', $authId); // Check if the current user has reported the post 
    
            })
            ->whereNotExists(function ($query) use ($authId) {
                $query->select(DB::raw(1))
                    ->from('report_posts')
                    ->where('report_posts.post_id', '=', 'posts.id') // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                    ->where('report_posts.user_id', '=', $authId); // Check if the current user has reported the post
            })
            ->whereNotExists(function ($query) use ($authId) {
    
                $query->select(DB::raw(1))
                    ->from('blocked_users')
                    ->where('user_id', '=', $authId) // Assuming 'post_id' is the column name for the post's ID in the 'report_posts' table
                    ->where('blocked_users.blocked_user_id','=','posts.user_id'); // Check if the current user has reported the post
            })
            ->addSelect(['is_liked' => function ($query) use ($authId) {
    
                $query->selectRaw('IF(EXISTS(SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = posts.id AND comment_id IS NULL), 1, 0)', [$authId]);
    
            }]);
    
            $posts = $posts->orderByDesc('id')->simplePaginate($limit);
    
            if (!empty($posts)) {
    
                foreach ($posts as $groupPost) {
    
                    $media_url = isset($groupPost->media_url) ? asset('storage/' . $groupPost->media_url) : '';
                    $cover_photo = isset($groupPost->group) && isset($groupPost->group->cover_photo) ?
                        (filter_var($groupPost->group->cover_photo, FILTER_VALIDATE_URL) ? $groupPost->group->cover_photo : asset('storage/' . $groupPost->group->cover_photo)) : '';
                    $profile = isset($groupPost->post_user) && isset($groupPost->post_user->profile) ?
                        (filter_var($groupPost->post_user->profile, FILTER_VALIDATE_URL) ? $groupPost->post_user->profile : asset('storage/' . $groupPost->post_user->profile)) : '';
            
                    $groupPost->media_url = $media_url;
                    $groupPost->group->cover_photo = $cover_photo;
                    $groupPost->post_user->profile = $profile;
                    // $groupPost->postedAt     =   Carbon::parse($groupPost->created_at)->diffForHumans();
                    $groupPost->postedAt     =   time_elapsed_string($groupPost->created_at);


                    
                    $isRepost                =   Post::where(['parent_id'=>$groupPost->id,'user_id'=>$authId,'is_active'=>1])->exists();
                    $groupPost->is_reposted  =   ($isRepost)?1:0;
                    $isExist                 =   PostLike::where(['user_id'=>$authId,'post_id'=>$groupPost->id])->first();
                    $groupPost->reaction     =   (isset($isExist) && !empty($isExist))?$isExist->reaction:0;
                }
            }
            return $this->sendResponse($posts, trans("message.user.posts"), 200);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "getUserPosts" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }
}
