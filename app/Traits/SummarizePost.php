<?php
namespace App\Traits;

use App\Models\Post;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Laravel\Facades\Gemini;
trait SummarizePost
{
    

    public function summerize($postid){

        $postData       =           Post::where(['id'=>$postid])->first();
        $postData['']
        $result         =           Gemini::generateText('what is swift language ? write in 200 character only');
        dd($result);
    }



    

}
