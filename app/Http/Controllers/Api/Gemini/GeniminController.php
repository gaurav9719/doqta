<?php

namespace App\Http\Controllers\Api\Gemini;

use App\Http\Controllers\Controller;
use App\Models\PhysicalSymptom;
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
       
        // $result =  Gemini::generateText("summarize this picture in 200 words: https://storage.googleapis.com/generativeai-downloads/images/scones.jpg");
        // dd($result);
        try {
            $topic  = $request->topic;
            if(isset($topic) && !empty($topic)){
    
                // $result =  Gemini::generateText("provide list of atleast 20 symptoms name only for this  ".$topic." topic in json format only");
                $result  = Gemini::generateText("Provide the list of different types of pain or symptoms associated with this health ".$topic."topic  with the 'symptoms' key. Only include the names of the symptoms with related this topic, with a maximum of 20 with each symptoms name upto 20 characters only in  JSON object without extra spaces and quotes around the keys and any text outside the symptoms key:");

                if (strpos($result, '"""') !== false) {
                    
                   $jsonString          =   str_replace('"""', '', $result); // Removes all spaces

                } else{
                    
                    $jsonString         =   str_replace('"""', '', $result); 
                }
                
                $jsonData               =   json_decode($jsonString,true);

                foreach ($jsonData['symptoms'] as $value) {

                    $addPhysical                =   new PhysicalSymptom();
                    $addPhysical->symptom       =   $value;    
                    $addPhysical->topic_id      =   $topicId;    
                    $addPhysical->type          =   1;    
                    $addPhysical->save();
                }
            }
        } catch (Exception $e) {
           dd($e->getMessage());
        }
        
    }



    public function generateTopicName(Request $request){
       
        $topic  = $request->topic;
        if(isset($topic) && !empty($topic)){

            
            $result =  Gemini::generateText("Give me list of symptoms related to this topic only in json output: ".$topic);
            dd($result);
        }
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
    // $ch = curl_init();

    //     curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=AIzaSyA1onDS9RQCohCJa-B7rtBloSfzBZQWRC4");
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($ch, CURLOPT_POST, 1);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n      \"contents\": [{\n        \"parts\":[{\n          \"text\": \"Write a story about a magic backpack.\"}]}]}");

    //     $headers = array();
    //     $headers[] = 'Content-Type: application/json';
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    //     $result = curl_exec($ch);
    //     if (curl_errno($ch)) {
    //         echo 'Error:' . curl_error($ch);
    //     }
    //     curl_close($ch);
               


                #---------- stream model ----------------#

                // Define the request body
            $data = array(
                "contents" => array(
                    array(
                        "parts" => array(
                            array(
                                "text" => "Provide  back pain topic with the 'symptoms' key. Only include the names of the symptoms in array format with spaces and special symbol, "
                            )
                        )
                    )
                )
            );
                $ch = curl_init();



                curl_setopt($ch, CURLOPT_URL, ("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:streamGenerateContent?alt=sse&key=AIzaSyA1onDS9RQCohCJa-B7rtBloSfzBZQWRC4"));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
              

                $headers = array();
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Error:' . curl_error($ch);
                }
                curl_close($ch);
                $response=json_decode($result, true);
                // $responseText=$response['candidates'][0]['content']['parts'][0]['text'];
                // $result= json_decode($responseText);
              
                return response()->json($result);
    }
}