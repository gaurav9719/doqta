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
use App\Models\Gender;
use App\Models\Pronouns;
use App\Models\Post;
use Carbon\Carbon;
use App\Models\PostLike;
use App\Models\UserFollower;
use Exception;
use Illuminate\Support\Facades\Log;

use App\Traits\postCommentLikeCount;
use App\Traits\IsLikedPostComment;
/**
 * Class GetUserService.
 */
class GetUserService extends BaseController
{
 use postCommentLikeCount,IsLikedPostComment;
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
        $userDetail = User::where(function($q) use($auth_user){

            $q->whereDoesntHave('blockedUsers', function ($query) use ($auth_user) {
                // Check if the user is not blocked by the authenticated user
                $query->where('blocked_user_id', $auth_user);
            })
            ->whereDoesntHave('blockedBy', function ($query) use ($auth_user) {
                // Check if the user has not blocked the authenticated user
                $query->where('user_id', $auth_user);
            });
            
        })->with([

            'user_interest.interest' => function ($query) {

                $query->select('id', 'name', 'icon');
            },
            'user_documents'=>function($query){

                $query->select('id','user_id','document','document_type');

            },'user_documents.document_type'=>function($query){

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
        ])->withCount(['userPost', 'supporter','supporting'])

        ->where('id', $userId)->first();

        if ($userDetail) {
                #-- check gender -------#
                $userDetail->gender_name   =    null;

                if(isset($userDetail->gender) && !empty($userDetail->gender)){

                    $gender  =  Gender::select('name')->where('id',$userDetail->gender)->first();

                    $userDetail->gender_name =  $gender->name??null;
                }
                $isSupporting               =   UserFollower::where(['user_id'=>$userId , 'follower_user_id'=>$auth_user])->first();

                $userDetail->is_supporting  =   (isset($isSupporting) && !empty($isSupporting))?$isSupporting->status:0;

                $userDetail->user_interest->each(function ($userInterest) {

                $interest = $userInterest->interest;
                // Prepend asset path to the icon attribute
                $interest->icon = asset('storage/' . $interest->icon);

            });

            if(isset($userDetail->user_documents) && !empty($userDetail->user_documents[0])){

                $userDetail->user_documents->each(function ($user_document) {

                    $document                   = $user_document->document;
                    // Prepend asset path to the icon attribute
                    $user_document->document    = $this->addBaseInImage($document);
                });
            }
            $ethnicity  =    Ethnicity::where('id',$userDetail->ethnicity)->first();
            if(isset($ethnicity) && !empty($ethnicity)){

                $userDetail->ethnicity_name  =   $ethnicity->name;
            }
            $pronouns   =   Pronouns::where('id',$userDetail->pronoun)->first();
            if(isset($pronouns) && !empty($pronouns)){

                // $userDetail->pronouns_name  =   $pronouns->subjective."/".$pronouns->objective."/".$pronouns->possessive;

                $pronouns = (object) [
                    'subjective' => $pronouns->subjective,
                    'objective' => $pronouns->objective,
                    'possessive' => $pronouns->possessive
                ];
                // Join non-empty pronoun parts directly with "/" separator
                $userDetail->pronouns_name = implode('/', array_filter((array) $pronouns));
                

            }
            // $userDetail->userPost->each(function ($user_post) {
            
            //     if(isset($user_post) && !empty($user_post)){
            //         // Prepend asset path to the icon attribute
            //         $user_post->media_url = ($user_post->media_url)?asset('storage/' . $user_post->media_url):null;
            //     }
            // });

            if(isset($userDetail->user_medical_certificate) && !empty($userDetail->user_medical_certificate)){

                $userDetail->user_medical_certificate->each(function ($user_medical) {
            
                    if(isset($user_medical->medicial_document) && !empty($user_medical->medicial_document)){
                        // Prepend asset path to the icon attribute
                        $user_medical->medicial_document = $this->addBaseInImage($user_medical->medicial_document);
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
            $userDetail->profile        = $this->addBaseInImage($userDetail->profile);
            $userDetail->cover          = $this->addBaseInImage($userDetail->cover);
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
            ])->with(['parent_post', 'parent_post.post_user'=>function($query){
                $query->select('id','name','user_name','profile');

            },'parent_post.group'=>function($query){

                $query->select('id','name','description','created_by');
                
            }])
            ->withCount('total_comment')
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
            })->orderByDesc('id')->simplePaginate($limit);
    
            if (!empty($posts)) {
    
                foreach ($posts as $groupPost) {

                    if(isset($groupPost->media_url) && !empty($groupPost->media_url)){

                        $groupPost->media_url      =  $this->addBaseInImage($groupPost->media_url);
                    }

                    if(isset($groupPost->thumbnail) && !empty($groupPost->thumbnail)){

                        $groupPost->thumbnail      =  $this->addBaseInImage($groupPost->thumbnail);
                    }

                    if(isset($groupPost->group) && !empty($groupPost->group->cover_photo)){

                        $groupPost->group->cover_photo      =  $this->addBaseInImage($groupPost->group->cover_photo);
                    }

                    if(isset($groupPost->post_user) && !empty($groupPost->post_user->profile)){

                        $groupPost->post_user->profile =  $this->addBaseInImage($groupPost->post_user->profile);
                    }
                    $groupPost->postedAt            =   time_elapsed_string($groupPost->created_at);
                    $isRepost                       =   Post::where(['parent_id'=>$groupPost->id,'user_id'=>$authId,'is_active'=>1])->exists();
                    $groupPost->is_reposted         =   ($isRepost)?1:0;
                    $isExist                         =   $this->IsPostLiked($groupPost->id, $authId);
                    $groupPost->is_liked             =   $isExist['is_liked'];
                    $groupPost->reaction             =   $isExist['reaction'];
                    $groupPost->total_likes_count    =   $isExist['total_likes_count'];


                    #--------- parent post ----------#
                    if(isset($groupPost->parent_post) && !empty($groupPost->parent_post)){


                        if ($groupPost->parent_post->post_user && $groupPost->parent_post->post_user->profile) {
        
                            $groupPost->parent_post->post_user->profile       = $this->addBaseInImage($groupPost->parent_post->post_user->profile);
                        }
                        if (isset($groupPost->parent_post->media_url) && !empty($groupPost->parent_post->media_url)) {
        
                            $groupPost->parent_post->media_url        =       $this->addBaseInImage($groupPost->parent_post->media_url);
                        }

                        if (isset($groupPost->parent_post->thumbnail) && !empty($groupPost->parent_post->thumbnail)) {
        
                            $groupPost->parent_post->thumbnail        =       $this->addBaseInImage($groupPost->parent_post->thumbnail);
                        }

                        $isExist                                      =       $this->IsPostLiked($groupPost->id, $authId,1);
                        $groupPost->parent_post->is_liked             =       $isExist['is_liked'];
                        $groupPost->parent_post->reaction             =       $isExist['reaction'];
                        $groupPost->parent_post->total_likes_count    =       $isExist['total_likes_count'];
                        $groupPost->parent_post->total_comment_count  =       $isExist['total_comment_count'];
                        $isRepost                                     =       Post::where(['parent_id'=>$groupPost->parent_post->id,'user_id'=>$authId,'is_active'=>1])->exists();
                        $groupPost->parent_post->is_reposted          =      ($isRepost)?1:0;
                    }
                    #--------- parent post ----------#
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
