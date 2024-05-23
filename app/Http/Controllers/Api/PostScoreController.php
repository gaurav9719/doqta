<?php

namespace App\Http\Controllers\Support;

use App\Models\Post;
use App\Models\PostLike;
use Vtiful\Kernel\Format;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GeminiAPI\Laravel\Facades\Gemini;
use App\Models\UserParticipantCategory;
use Illuminate\Support\Facades\Validator;

class PostScoreController extends Controller
{
    function calculateScore(Request $request){
        $validation= Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id'
        ]);
        if($validation->fails()){
            return sendResponse(400, $validation->errors()->first());
        }
        
        $post=Post::find($request->post_id);
        if(isset($post)){

            #User score
            $reaction   =PostLike::where('post_id', $post->id)->count();
            $like       =PostLike::where('post_id', $post->id)
            ->where(function($query){
                $query->where('reaction', 1)->orWhere('reaction', 2);
            })->count();
                if($reaction > 0){
                    $percent = ($like/$reaction)*100;
                }
                else{
                    $percent = 0;
                }
                $score = $this->userScore($percent);
                $data=[
                    'reaction'=>$reaction,
                    'like' => $like,
                    'percent' => number_format($percent, 2),
                    'score' => $score,
                    "maximum_score" => 3
                ];

                #Medical Professinals score
                $ids=PostLike::where('post_id', $post->id)->pluck('user_id')->toArray();
                $medicalProfessionalsId= UserParticipantCategory::whereIn('user_id', $ids)->where('participant_id', 3)->select('user_id')->distinct()->pluck('user_id')->toArray();
                $reactionM=PostLike::where('post_id', $post->id)->whereIn('user_id', $medicalProfessionalsId)->count();
                $likeM=PostLike::where('post_id', $post->id)
                ->whereIn('user_id', $medicalProfessionalsId)
                ->where(function($query){
                    $query->where('reaction', 1)->orWhere('reaction', 2);
                })->count();


                if($reactionM > 0){
                    $percentM = ($likeM/$reactionM)*100;
                }
                else{
                    $percentM = 0;
                }
                $scoreM = $this->medicalProfessionalsScore($percentM);
            
                $dataM=[
                    'reaction'=>$reactionM,
                    'like' => $likeM,
                    'percent' => number_format($percentM, 2),
                    'score' => $scoreM,
                    "maximum_score" => 5
                ];

                #Calculate Confidance Score from 
                $totalsore =$score+$scoreM;
                if($totalsore >= 6){
                    $scoreAI = $this->confidenceScoreAI($post->content);
                }

                if(!isset($scoreAI)){
                    $scoreAI = 0;
                }
                

                #Final Result
                $result=[
                    'obtained_score' => $score+$scoreM+$scoreAI,
                    'total_score' => 8,
                    'users_score' => $data,
                    'medical_professionals_score' => $dataM,
                    'confidence_score' => $scoreAI
                ];

            return sendResponse(200, "Success", $result);
                
        }
    }

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

    function confidenceScoreAI($post){
        
        if(isset($post) && !empty($post)){
            $question= "validate the post validity, and provide the score according to validaty of the post out of 2 in json formate (in 'validity' key), not reasons and other text at all, post is: '".$post."'";
            
            // Define your API key
            $apiKey = env('GEMINI_API_KEY');
            

            // Define the URL
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;

            // Define the request body
            $data = array(
                "contents" => array(
                    array(
                        "parts" => array(
                            array(
                                "text" => $question
                            )
                        )
                    )
                )
            );

            // Initialize cURL session
            $curl = curl_init($url);

            // Set cURL options
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

            // Execute cURL request
            $response = curl_exec($curl);
            

            // Check for errors
            if ($response === false) {
                // $error = curl_error($curl);
                // echo "cURL Error: " . $error;
                return 0;
            } else {
                
                $response=json_decode($response, true);
                $responseText=$response['candidates'][0]['content']['parts'][0]['text'];
                $result= json_decode($responseText);

                // return $result;
                if(isset($result)){
                    if(isset($result->validity)){
                        return $result->validity;
                    }
                }

                
            }

            // Close cURL session
            curl_close($curl);
        }
    }




    // function chat(Request $request){
        

    //     // Define your API key
    //     $apiKey = "AIzaSyCN9891vVrDvLHsQvZU9M2mv-9W85dOX8g";
        

    //     // Define the URL
    //     $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;

    //     // Define the request body
    //     $data = array(
    //         "contents" => array(
    //             array(
    //                 "parts" => array(
    //                     array(
    //                         "text" => $request->question
    //                     )
    //                 )
    //             )
    //         )
    //     );

    //     // Initialize cURL session
    //     $curl = curl_init($url);

    //     // Set cURL options
    //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    //     curl_setopt($curl, CURLOPT_POST, true);
    //     curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

    //     // Execute cURL request
    //     $response = curl_exec($curl);
        

    //     // Check for errors
    //     if ($response === false) {
    //         $error = curl_error($curl);
    //         echo "cURL Error: " . $error;
    //     } else {
            
    //         $response=json_decode($response, true);
    //         $responseText=$response['candidates'][0]['content']['parts'][0]['text'];
    //         // $result= json_decode($responseText);

    //         return $responseText;

    //         // return json_decode($responseText, true);
           
    //         // return response()->json([
    //         //     'status'=> 200,
    //         //     "message" => "Success",
    //         //     'data'=> $response['candidates'][0]['content']['parts'][0]['text']], 200);
    //     }

    //     // Close cURL session
    //     curl_close($curl);
    // }


    // function chat(){
    //     $client = Gemini::client();
    //     return $client;
    // }

}
