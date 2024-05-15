<?php
namespace App\Traits;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Laravel\Facades\Gemini;
trait SummarizePost
{
    

    public function summerize(){
       
        $result         =           Gemini::generateText('what is swift language ? write in 200 character only');
        dd($result);
    }



    

}
