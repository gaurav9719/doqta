<?php

namespace App\Http\Controllers\Api\Gemini;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Gemini\Client;
// use Gemini\Gemini;
use GeminiAPI\Resources\Parts\TextPart;
// use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\Laravel\Facades\Gemini;

use Exception;
class GeniminController extends Controller
{
    //
    protected $aiKey;

    public function __construct() {

        $this->aiKey          =        env('GEMINI_KEY');
        
    }

    public function summerize(Request $request){
       
        $result =  Gemini::generateText("summarize this picture in 200 words: https://storage.googleapis.com/generativeai-downloads/images/scones.jpg");
        dd($result);
    }

    public function summarizeImage($image){
        
        $imageUrl = 'https://img.freepik.com/free-vector/lord-ganpati-ganesh-chaturthi-beautiful-green-leaf-holiday-card-background_1035-24526.jpg?t=st=1715776671~exp=1715780271~hmac=08a96db443c0a6e982b845eceafca33fd6e06cccd81e5c55018e611735603737&w=740';

        try {
            $summary = Gemini::generateTextUsingImage('image/jpeg',base64_encode(file_get_contents($imageUrl)), 'Summrize this image in maximum 200 characters');
            echo "Summarized Text: " .$summary;
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }







public function generateContentWithCurl(){
    $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=AIzaSyA1onDS9RQCohCJa-B7rtBloSfzBZQWRC4");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n      \"contents\": [{\n        \"parts\":[{\n          \"text\": \"Write a story about a magic backpack.\"}]}]}");

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
                dd($result);


                #---------- stream model ----------------#
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, ("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:streamGenerateContent?alt=sse&key=AIzaSyA1onDS9RQCohCJa-B7rtBloSfzBZQWRC4"));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "{ \"contents\":[{\"parts\":[{\"text\": \"Write a story about a magic backpack.\"}]}]}");

                $headers = array();
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Error:' . curl_error($ch);
                }
                dd($result);
                curl_close($ch);
    }
}