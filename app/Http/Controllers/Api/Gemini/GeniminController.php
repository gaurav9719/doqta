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

    function analyzeJournalOld(Request $request){
        
       













        // Define your API key
        // $API_KEY = "AIzaSyCN9891vVrDvLHsQvZU9M2mv-9W85dOX8g";
        $API_KEY = "AIzaSyCsZ9bv-kGgJ8fU4XzSa1VtFR1HEW_6ITM";

        

        $API_ENDPOINT = "us-central1-aiplatform.googleapis.com";
        $PROJECT_ID = "aesthetic-root-421907";
        $LOCATION_ID = "us-central1";
        $MODEL_ID = "gemini-1.0-pro-002";

        

        // Define the URL
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $API_KEY;
        // $url="https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key=".$API_KEY;
        
        // Define the request body
        // $data = array(
        //     "contents" => array(
        //         array(
        //             "parts" => array(
        //                 array(
        //                     "text" => "Provide  back pain topic with the 'symptoms' key. Only include the names of the symptoms in array format with spaces and special symbol"
        //                 )
        //             )
        //         )
        //     )
        // );

        $data = array(
            "contents" => array(
                array(
                    "role" => "user",
                    "parts" => array(
                        array("text" => "Sugar Patient Journal"),
                        // array("text" => "Date:"),
                        array("text" => '{"insights":[{"date":"2024-04-29 07:20:14 AM","pain":"Very Severe Pain","mood":"Upset","feeling":"Angry,
                            Tense","symptom":"Poor sleep quality, Digestive issues","content":"I resisted temptation at a work event today and made healthy choices. My blood sugar readings have been stable over the past few days. Action: I will keep reminding myself of the positive effects of managing my diabetes. I will  also research some healthy dessert options to satisfy cravings in a healthier way"},{"date":"2024-04-29
                            07:21:55 AM","pain":"Very Severe Pain","mood":"Very Upset","feeling":"Angry","symptom":"Fatigue","content":"I recovered from my illness quickly, and my blood sugar levels are back in a healthy range"},{"date":"2024-04-29 07:22:07 AM","pain":"Very Severe Pain","mood":"Very
                            Upset","feeling":"Angry","symptom":"Fatigue","content":"I achieved my goal of losing 5 pounds this month. My doctor is pleased with my progress and says my overall health is improving"},{"date":"2024-04-29 07:22:23
                            AM","pain":"Very Severe Pain","mood":"Very Upset","feeling":"Angry","symptom":"Fatigue","content":"ss sad sad asdas
                            sad"},{"date":"2024-04-29 07:23:05 AM","pain":"Very Severe Pain","mood":"Very
                            Upset","feeling":"Angry","symptom":"Fatigue","content":"Managing my diabetes can be challenging, but I am learning and adapting. I am feeling positive about my health and future"}]}'),
                        // array("text" => "pain: none"),
                        // array("text" => "Feelings: Frustrated, Confused. My blood sugar reading this morning was higher than I'd like. I followed my meal plan yesterday, so I'm not sure what went wrong. I need to figure this out and get back on track. Action: I'll call my doctor's office to see if they can offer any suggestions. I'll also double-check my food portions and make sure I'm exercising regularly."),
                        // array("text" => "Date:"),
                        // array("text" => "May 8, 2024"),
                        // array("text" => "pain: moderate"),
                        // array("text" => "Feelings: Energetic, Motivated. I went for a walk this morning, and it felt great. Exercise seems to be helping me manage my blood sugar levels along with the medication and diet changes. Action: I'll try to incorporate more physical activity into my routine, even if it's just a short walk each day."),
                        // array("text" => "Date:"),
                        // array("text" => "May 10, 2024"),
                        // array("text" => "pain: severe"),
                        // array("text" => "Feelings: Proud, Satisfied. I resisted temptation at a work event today and made healthy choices. My blood sugar readings have been stable over the past few days. Action: I'll keep reminding myself of the positive effects of managing my diabetes. I'll also research some healthy dessert options to satisfy cravings in a healthier way."),
                        // array("text" => "Date:"),
                        // array("text" => "May 12, 2024"),
                        // array("text" => "pain: severe"),
                        // array("text" => "Feelings: Worried, Tired. I'm feeling a little under the weather and haven't been as active as usual. I'm concerned this might affect my blood sugar control. Action: I'll monitor my readings closely and check with my doctor if my symptoms worsen or if my blood sugar spikes significantly."),
                        // array("text" => "Date:"),
                        // array("text" => "May 14, 2024"),
                        // array("text" => "pain: moderate"),
                        // array("text" => "Feelings: Grateful, Relieved. I recovered from my illness quickly, and my blood sugar levels are back in a healthy range. Action: I'll take care of myself to prevent illness and prioritize healthy habits to maintain good blood sugar control."),
                        // array("text" => "Date:"),
                        // array("text" => "May 16, 2024"),
                        // array("text" => "pain: moderate"),
                        // array("text" => "Feelings: Inspired, Connected. I joined a diabetes support group online. Connecting with others who understand the challenges is motivating. Action: I'll actively participate in the support group and learn from others' experiences to continue managing my diabetes effectively."),
                        // array("text" => "Date:"),
                        // array("text" => "May 18, 2024"),
                        // array("text" => "pain: none"),
                        // array("text" => "Feelings: Excited, Accomplished. I achieved my goal of losing 5 pounds this month. My doctor is pleased with my progress and says my overall health is improving. Action: I'll continue with my healthy lifestyle choices and set new goals for the future."),
                        // array("text" => "Date:"),
                        // array("text" => "May 20, 2024"),
                        // array("text" => "pain: moderate"),
                        // array("text" => "Feelings: Hopeful, Determined. Managing my diabetes can be challenging, but I'm learning and adapting. I'm feeling positive about my health and future. Action: I'll keep track of my progress in this journal and celebrate my victories along the way."),

                        array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= insights and sugestions"),
                        array("text" => "provide result in json format not actally what i am giving input"),
                        array("text" => "give the keys values in array format, even if only one key is available. and give minimum  five points in each key"),
                        array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the journal entries'"),
                      //   array("text" => " send validated exact json format, not include '```json\n', '\' and '\n' like this"),
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
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode( $data));

        // Execute cURL request
        $response = curl_exec($curl);
        

        // Check for errors
        if ($response === false) {
            $error = curl_error($curl);
            echo "cURL Error: " . $error;
        } else {
            
            $response=json_decode($response, true);
            
            // return $response;
            $result=$response['candidates'][0]['content']['parts'][0]['text'];

            $finalResponse= $this->convertIntoJson($result);
            $resultd=json_decode($finalResponse,true);
           
            return response()->json([
                'status'=> 200,
                "message" => "Success",
                'data'=> $resultd], 200);

        }

        // Close cURL session
        curl_close($curl);
    }


    function convertIntoJson($text){
       

        // $text="```json\n{\n  \"insights\": [\n    \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\",\n    \"Exercise has a noticeable positive impact on blood sugar management.\",\n    \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\",\n    \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\",\n    \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\"\n  ],\n  \"suggestions\": [\n    \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\",\n    \"Incorporate regular physical activity, such as daily walks, into the routine.\",\n    \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\",\n    \"Monitor blood sugar closely during illness and seek medical attention if necessary.\",\n    \"Actively engage in diabetes support groups to learn from and share experiences with others.\"\n  ]\n}\n```";


        $text=str_replace('```json', '', $text);
        $text=str_replace('```JSON', '', $text);
        $text=str_replace('```', '', $text);

        return $text;
    }


    public function chat(){

        $chat = Gemini::startChat();

print $chat->sendMessage('hello',);
// echo "Hello World!";
// This code will print "Hello World!" to the standard output.

    }


}