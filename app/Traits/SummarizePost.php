<?php
namespace App\Traits;

use App\Models\Post;
use Exception;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

trait SummarizePost
{
    #--------------  S U M M A R I Z E      T H E       P O S T  ---------------------#
    public function summerize($postid){

        try {
           
            $postData                       =           Post::where(['id'=>$postid])->first();
            //dd($postData);
    
            if($postData['media_type']==0 || $postData['media_type']==2 || $postData['media_type']==3 || $postData['media_type']==2 ){                                           // text
                if(isset($postData['content']) && !empty($postData['content'])){
    
                    $prompt                 =           "summarize this text in 150 words: ".$postData['content'];
        
                    $summary                =           Gemini::generateText($prompt);
                }
    
            }elseif ($postData['media_type']==1) {                   // image
    
               $prompt                      =   "";
                if(isset($postData['content']) && !empty($postData['content'])){
    
                    $prompt                 =           "summarize this text and picture and combine into maximum 150 words text: ".$postData['content'];
    
                }else{
    
                    $prompt                 =           'summarize this image in maximum 150 characters';
    
                }
                if(isset($postData['media_url']) && !empty($postData['media_url'])){
    
                    $imageUrl               =           asset('storage/'.$postData['media_url']);
                   // dd($imageUrl);
                    $extension              =           File::extension($imageUrl);
                   // dd($extension);
                    $summary                =           Gemini::generateTextUsingImage('image/'.$extension,base64_encode(file_get_contents($imageUrl)), $prompt);
                }
            }
            $postData->summarize            =           $summary;
            $postData->save();
            Log::info("working summarize");
            return 200;

        } catch (Exception $e) {

            return 400;
           
        }





       

    }




}
