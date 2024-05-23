<?php
namespace App\Traits;

use App\Models\Post;
use App\Models\PostLike;

trait CalculateScore
{


    #---------------  C A L C U L A T E     C O N F I D E N C E     S C O R E   O F  P O S T  -----------------#
    public function CalculateConfidenceScore($postId)
    {
        $post           =       Post::find($postId);
        if(isset($post) && !empty($post)){
            #User score
            $reaction   =      PostLike::where('post_id', $post->id)->count();
            $Userlike   =      PostLike::where('post_id', $post->id)
                                ->whereIn('reaction', [1, 2])
                                ->whereHas('checkUserRole', function ($query) {

                                    $query->whereIn('participant_id', [1, 2]);
                                })->count();
            if($reaction > 0){

                $uPercent = ($Userlike/$reaction)*100;
            }
            else{
                $uPercent = 0;
            }
            $uScore = $this->userScore($uPercent);
            #medical professionl
            $mFlike   =      PostLike::where('post_id', $post->id)
                                ->whereIn('reaction', [1, 2])
                                ->whereHas('checkUserRole', function ($query) {
                                    $query->whereIn('participant_id', [3]);
                                })->count();

            if($mFlike > 0){
                $mPercent = ($mFlike/$reaction)*100;
            }
            else{
                $mPercent = 0;
            }
            $mScore = $this->medicalProfessionalsScore($mPercent);

            #---------- update in post table -------------#
            $support_count          =       PostLike::where(['post_id', $post->id,'reaction'=>1])->count();
            $helpful_count          =       PostLike::where(['post_id', $post->id,'reaction'=>2])->count();
            $unhelpful_count        =       PostLike::where(['post_id', $post->id,'reaction'=>3])->count();
            $post->total_count      =       $reaction;
            $post->support_count    =       $reaction;
            $post->helpful_count    =       $reaction;
            $post->unhelpful_count  =       $reaction;
            

        }

    }
    #---------------  C A L C U L A T E     C O N F I D E N C E     S C O R E   O F  P O S T  -----------------#


    function userScore($percent){
        if($percent >= 80){
            return 3;
        }
        elseif($percent >= 53.33){
            return 2;
        }
        elseif($percent >= 26.66){
            return 1;
        }
        else{
            return 0;
        }
    }

    function medicalProfessionalsScore($percent){
        if($percent >= 70){
            return 5;
        }
        elseif($percent >= 56){
            return 4;
        }
        elseif($percent >= 42){
            return 3;
        }
        elseif($percent >= 28){
            return 2;
        }
        elseif($percent >= 14){
            return 1;
        }
        else{
            return 0;
        }
    }

    
}
