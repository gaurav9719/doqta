<?php

namespace App\Http\Controllers\Api\Journals;

use Carbon\Carbon;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use App\Models\JournalReport;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;

class JournalAnalyzerController extends BaseController
{

    #make general report
    function generateReport(Request $request){

        $validate= Validator::make($request->all(), [
            'journal_id' => 'required|exists:journals,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'type'       => 'required|integer|between:1,2',
        ]);
        if($validate->fails()){
            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }

        $user_id=Auth::id();
        $journal= Journal::find($request->journal_id);
        
        if($journal->user_id != $user_id){
            return $this->sendResponsewithoutData("Invailed journal", 422);
        }

        $request_ids= JournalEntry::where('journal_id', $journal->id)->whereBetween('journal_on', [$request->start_date, $request->end_date])->pluck('id')->toArray();

        $reports= JournalReport::where('journal_id', $journal->id)->where('type', $request->type)->get();
        
        foreach($reports as $rep){
            
            $ids= JournalEntry::where('journal_id', $journal->id)->whereBetween('journal_on', [$rep->start_date, $rep->end_date])->pluck('id')->toArray();
            if (empty(array_diff($request_ids, $ids)) && empty(array_diff($ids, $request_ids))) {
                $response= json_decode($rep->report);
                $report= $request->type == 1 ? "Insights & Suggestions" : "Report";
                return $this->sendResponse($response, "$report generated successfully", 200);
            }
        }

        // $from   = Carbon::parse($request->start_date);
        // $to     = Carbon::parse($request->end_date);
        
        $data= array(['text'=>"Journal Name : $journal->title"],['text' => "Disease: ".$journal->topic->name], ['text' => 'Journal Entries']);
        
        #preparing journal entries as input in array
        $entries= JournalEntry::where('journal_id', $journal->id)->whereBetween('journal_on', [$request->start_date, $request->end_date])->with(['feeling','feeling_types.feeling_type_details', 'symptom.journalSymtom'])->get();
        
        foreach($entries as $entry){
            #date
            $date=Carbon::parse($entry->journal_on)->format('Y-m-d H:i A');
            array_push($data, ['text'=> "Date: $date"]);

            #mood
            $details= "Mood: ". $entry->feeling->name;

            #feeling
            $felling_types= $entry->feeling_types->pluck('feeling_type_details.name')->implode(", ");
            $details = $details.". Feelings: $felling_types";

            #symptoms
            $symptoms= $entry->symptom->pluck('journalSymtom.symptom')->implode(", ");

            $details = $details.". Symptoms: $symptoms";

            // $details = $details.". Symptoms: $felling_types";

            #pain
            $painScale = [
                0 => "No Pain",
                1 => "Mild Pain",
                2 => "Discomforting Pain",
                3 => "Moderate Pain",
                4 => "Severe Pain",
                5 => "Very Severe Pain",
              ];
                            
            $pain= $painScale[$entry->pain];
            $details = $details.". Pain: $pain";

            #description
            $details = $details.". Description: $entry->content";
            array_push($data, ['text'=> $details]);

        }
        #Insides & Suggestion
        if($request->type == 1){
            array_push($data, 
                    array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= insights and sugestions"),
                    array("text" => "provide result in json format"),
                    array("text" => "give the keys values in array format, even if only one key is available. and give minimum  five points in each key"),
                    array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the journal entries'"),
                    array("text" => "format must be in this format => \n{\n  \"insights\": [\n    \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\",\n    \"Exercise has a noticeable positive impact on blood sugar management.\",\n    \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\",\n    \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\",\n    \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\"\n  ],\n  \"suggestions\": [\n    \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\",\n    \"Incorporate regular physical activity, such as daily walks, into the routine.\",\n    \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\",\n    \"Monitor blood sugar closely during illness and seek medical attention if necessary.\",\n    \"Actively engage in diabetes support groups to learn from and share experiences with others.\"\n  ]\n}\n"),
                );
            $report = $this->generateReportAI($data, 1);

        }

        #report
        elseif($request->type == 2){
            array_push($data,
                    array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= symptoms, mood, pain and questions_to_ask_your_doctor"),
                    array("text" => "provide result in json format"),
                    array("text" => "give the values of keys in array format not in string, even if only one value is available. and give minimum  five points in each key"),
                    array("text" => "expain all point, do not give one word in the array"),
                    array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the journal entries'"),
                    array("text" => 'format must be in this format => { "symptoms": [ "Sweating of your palms before participating in social scenarios", "Shortness of breath while surrounded by others you are not familiar with, which worsens your asthma", "Rapid heartbeat in classroom and work settings before thinking about answering a question", "Thoughts and feelings of worthlessness, sadness, and low self-esteem" ], "mood": [ "20% of your emotions were positive, 10% of your emotions were neutral, and 70% of your emotions were negative", "Your most common negative emotions were sadness, anger, and frustration. You encountered these emotions in situations before and after approaching unfamiliar people.", "Your most common positive emotions were happiness and excitement. You encountered these emotions when you were with your friends and family" ], "pain": [ "On average, you experienced mild physical pain", "You experienced moderate pain relating to your shortness of breath", "You experienced mild pain related to your shaking" ], "questions_to_ask_your_doctor": [ "How can I modulate my emotions?", "How can I alleviate my physical symptoms, such as shortness of breath, shaking, and rapid heartbeat?" ] }'),
                );
            $report = $this->generateReportAI($data, 2);
        }
        
        #insert in database if success
        if(isset($report['status']) && $report['status'] == 200){
            $newReport              = new JournalReport;
            $newReport->journal_id  = $journal->id;
            $newReport->start_date  = $request->start_date;
            $newReport->end_date    = $request->end_date;
            $newReport->report      = json_encode($report['data']);
            $newReport->type        = $request->type;
            $newReport->save();

            return response()->json($report, 200);
        }
        else{
            return response()->json($report, 400);
        }
    }

    
    #Generate Report 
    Function generateReportAI($data, $type){

        // Define your API key
        $API_KEY = "AIzaSyCN9891vVrDvLHsQvZU9M2mv-9W85dOX8g";

        // Define the URL
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key=" . $API_KEY;
         
        // return $data;

        

        $data = array(
            "contents" => array(
                array(
                    "role" => "user",
                    "parts" => $data
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
            $error = curl_error($curl);
            echo "cURL Error: " . $error;
        } else {

            $response = json_decode($response, true);

            // return $response;
            try {
                $result = $response['candidates'][0]['content']['parts'][0]['text'];
                $finalResponse = $this->convertIntoJson($result);
                $finalResponse = json_decode($finalResponse, true);

                if($type == 1 || $type=3){

                    if (isset($finalResponse['insights']) && isset($finalResponse['suggestions']) && count($finalResponse['insights']) > 0 && count($finalResponse['suggestions']) > 0) {
                        // return ($finalResponse['insights']);
                        return $finalResponse;
                   
                    } else {

                        $this->generateReportAI($data, $type);
                       
                    }
                }
                elseif($type == 2){
                    if (isset($finalResponse['symptoms']) && isset($finalResponse['mood']) && isset($finalResponse['pain']) && isset($finalResponse['questions_to_ask_your_doctor']) && count($finalResponse['symptoms']) > 0 && count($finalResponse['mood']) > 0 && count($finalResponse['pain']) > 0  && count($finalResponse['questions_to_ask_your_doctor']) > 0) {
                        return [
                            'status' => 200,
                            "message" => "Report generated successfully",
                            'data' => $finalResponse
                        ];
    
                    } else {
                        return [
                            'status' => 400,
                            "message" => "Report generation failed",
                            'data' => $finalResponse
                        ];
                    }
                }

            } catch (\Exception $e) {
                Log::error('Error while creating journal report: ' . $e->getMessage());
                return [
                    'status' => 400,
                    "message" => "Exception Error",
                    'data' => $e->getMessage()
                ];
            }
        }

        // Close cURL session
        curl_close($curl);
    }


    function convertIntoJson($text)
    {
        // $text="```json\n{\n  \"insights\": [\n    \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\",\n    \"Exercise has a noticeable positive impact on blood sugar management.\",\n    \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\",\n    \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\",\n    \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\"\n  ],\n  \"suggestions\": [\n    \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\",\n    \"Incorporate regular physical activity, such as daily walks, into the routine.\",\n    \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\",\n    \"Monitor blood sugar closely during illness and seek medical attention if necessary.\",\n    \"Actively engage in diabetes support groups to learn from and share experiences with others.\"\n  ]\n}\n```";
        $text = str_replace('```JSON', '', $text);
        $text = str_replace('```json', '', $text);
        $text = str_replace('```', '', $text);

        return $text;
    }
}
