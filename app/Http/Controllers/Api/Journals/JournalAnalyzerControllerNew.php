<?php

namespace App\Http\Controllers\Api\Journals;

use PDF;
use Exception;
use Carbon\Carbon;
use App\Models\Journal;
use App\Models\AiThread;
use App\Models\AiMessage;
use App\Models\ChatInsight;
use Illuminate\Support\Str;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use App\Models\JournalReport;
use App\Models\JournalInsight;
use App\Models\ChatInsightEntry;
use App\Models\JournalInsideEntry;
use Illuminate\Support\Facades\Log;
use App\Traits\postCommentLikeCount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
//use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;

class JournalAnalyzerControllerNew extends BaseController
{
    use postCommentLikeCount;

    #make general report
    function generateReportNewCOmmentOn8July(Request $request) //COmmentOn8July
    {
        $validate               = Validator::make($request->all(), [

            'journal_ids'       => 'nullable|array',
            // 'journal_ids.*'     => 'distinct|exists:journals,id',
            'start_date'        => 'required|date_format:Y-m-d',
            'end_date'          => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'type'              => 'required|integer|between:1,2',
            'include_chat'      => 'required|integer|between:0,1',

        ]);

        if ($validate->fails()) {

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }

        $user_id                =   Auth::id();
        // dd($user_id);
        // dd($request->journal_ids);

        #Check if any journals belong to a different user
        if (isset($request->journal_ids) && count($request->journal_ids) > 0) {

            $invalidJournals    =   Journal::whereIn('id', $request->journal_ids)

                ->where('user_id', '<>', $user_id)

                ->count();

            if ($invalidJournals > 0) {

                return $this->sendResponsewithoutData("Invailed journals", 422);
            }
        }

        $start_time     = Carbon::parse($request->start_date)->startOfDay();
        $end_time       = Carbon::parse($request->end_date)->isToday() || Carbon::parse($request->end_date)->isFuture() ? Carbon::now() : Carbon::parse($request->end_date)->endOfDay();


        if (isset($request->journal_ids) && count($request->journal_ids) > 0) {

            $journal_ids = $request->journal_ids;
        } else {

            $journal_ids = Journal::where('user_id', $user_id)->pluck('id')->toArray();

            if (count($journal_ids) == 0) {

                return $this->sendResponsewithoutData("Journal not available", 422);
            }
        }

        $moodPain       =   $this->getInsightSymptoms($request);


        #check report available
        $availableReport = $this->checkReportAvailable($request, $user_id, $journal_ids, $start_time, $end_time);


        // $availableReport['moodPain']    =   isset($moodPain) ? $moodPain : null;
        // dd($availableReport);
        //  echo response()->json($availableReport, 200);

        if (isset($availableReport) && count($availableReport['data']) > 0) {


            $availableReport['moodPain']    =   isset($moodPain) ? $moodPain : null;

            return response()->json($availableReport, 200);
            // return response()->json([
            //     'status' => 200,
            //     'message' => $request->type == 1 ? trans('message.insight') : trans('message.report'),
            //     'data' =>  $availableReport,
            //     'moodPain' => isset($moodPain) ? $moodPain : null,

            // ], 200);
        }


        $request_ids = JournalEntry::whereIn('journal_id', $journal_ids)
            ->where('is_active', 1)
            ->whereBetween('created_at', [$start_time, $end_time])
            ->pluck('id')
            ->toArray();
        $data = array();


        foreach ($journal_ids as $journal_id) {

            $journal = Journal::find($journal_id);

            #check report available for request time
            $entries_id    = JournalEntry::where('journal_id', $journal->id)->where('is_active', 1)->whereBetween('created_at', [$start_time, $end_time])->pluck('id')->toArray();

            ///dd($entries_id);

            if (count($entries_id) > 0) {

                $journalData = array(['text' => "-------------------------------------------------------------------------"], ['text' => "Journal Name : $journal->title"], ['text' => "Disease: " . $journal->topic->name], ['text' => 'Journal Entries']);
                #preparing journal entries as input in array
                $entries = JournalEntry::where('journal_id', $journal->id)->whereBetween('created_at', [$start_time, $end_time])->with(['feeling', 'feeling_types.feeling_type_details', 'symptom.journalSymtom'])->get();

                foreach ($entries as $entry) {

                    #date
                    $date = Carbon::parse($entry->journal_on)->format('Y-m-d H:i A');
                    array_push($journalData, ['text' => "Date: $date"]);
                    #entry id
                    $details = "id: " . $entry->id;
                    #mood
                    $details = $details . ", Mood: " . $entry->feeling->name;
                    #feeling
                    $felling_types  = $entry->feeling_types->pluck('feeling_type_details.name')->implode(", ");
                    $details        = $details . ", Feelings: $felling_types";
                    #symptoms
                    $symptoms       = $entry->symptom->pluck('journalSymtom.symptom')->implode(", ");
                    $details        = $details . ", Symptoms: $symptoms";
                    #pain
                    $painScale = [
                        0 => "No Pain",
                        1 => "Mild Pain",
                        2 => "Discomforting Pain",
                        3 => "Moderate Pain",
                        4 => "Severe Pain",
                        5 => "Very Severe Pain",
                    ];

                    $pain       = $painScale[$entry->pain];
                    $details    = $details . ". Pain: $pain";

                    #description
                    $details    = $details . ". Description: $entry->content";
                    array_push($journalData, ['text' => $details]);
                }

                $data = array_merge($data, $journalData);
            }
        }

        if (count($data) > 0) {
            #----------  3 J U N -----------------#
            if (isset($request->include_chat) && $request->include_chat == 1) {

                $chat = $this->includeChat($request);

                if (isset($chat) && count($chat) > 0) {

                    $data = array_merge($data, $chat);
                }
            }
            #----------  3 J U N -----------------#
            // return $data;


            #Insides & Suggestion
            if ($request->type == 1) {

                array_push(
                    $data,
                    array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= insights and sugestions"),
                    array("text" => "provide result in json format"),
                    array("text" => "give the keys values in array format, even if only one key is available. and give minimum  five points in each key"),
                    array("text" => "also provide the ids in array on the basis of which that line has been made"),
                    array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the journal entries'"),
                    array("text" => "if Media link available in chat section, analyze the image and give response accordingly"),
                    array("text" => "format must be in this format => \n{\n  \"insights\": [\n    {\"text\": \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\", \"ids\":[12, 35 ,51, 64]},\n    {\"text\": \"Exercise has a noticeable positive impact on blood sugar management.\", \"ids\":[14, 37 ,53, 60, 68]},\n    {\"text\": \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\", \"ids\":[10, 32 ,51, 54, 64]},\n    {\"text\": \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\", \"ids\":[6, 22 ,47, 49, 54]},\n    {\"text\": \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\", \"ids\":[21, 37 ,41, 49, 53]}\n  ],\n  \"suggestions\": [\n    {\"text\": \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\", \"ids\":[12, 35 ,51, 64]},\n    {\"text\": \"Incorporate regular physical activity, such as daily walks, into the routine.\", \"ids\":[14, 37 ,53, 60, 68]},\n    {\"text\": \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\", \"ids\":[10, 32 ,51, 54, 64]},\n    {\"text\": \"Monitor blood sugar closely during illness and seek medical attention if necessary.\", \"ids\":[6, 22 ,47, 49, 54]},\n    {\"text\": \"Actively engage in diabetes support groups to learn from and share experiences with others.\", \"ids\":[21, 37 ,41, 49, 53]}\n  ]\n}\n"),
                    //array("text" => "format must be in this format => \n{\n  \"insights\": [\n    \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\",\n    \"Exercise has a noticeable positive impact on blood sugar management.\",\n    \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\",\n    \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\",\n    \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\"\n  ],\n  \"suggestions\": [\n    \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\",\n    \"Incorporate regular physical activity, such as daily walks, into the routine.\",\n    \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\",\n    \"Monitor blood sugar closely during illness and seek medical attention if necessary.\",\n    \"Actively engage in diabetes support groups to learn from and share experiences with others.\"\n  ]\n}\n"),
                );
                $report = $this->generateReportAI($data, 1);
            }

            #report
            elseif ($request->type == 2) {
                array_push(
                    $data,
                    array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= symptoms, mood, pain and questions_to_ask_your_doctor"),
                    array("text" => "provide result in json format"),
                    array("text" => "give the values of keys in array format not in string, even if only one value is available. and give minimum five points in each key"),

                    array("text" => "expain all point, do not give one word in the array"),
                    array("text" => "expain every symtoms, mood and pain. Do not give the same output as the input"),
                    array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the journal entries'"),
                    array("text" => "if Media link available in chat section, analyze the image and give response accordingly"),

                    array("text" => 'format must be in this format => { "symptoms": [ "Sweating of your palms before participating in social scenarios", "Shortness of breath while surrounded by others you are not familiar with, which worsens your asthma", "Rapid heartbeat in classroom and work settings before thinking about answering a question", "Thoughts and feelings of worthlessness, sadness, and low self-esteem" ], "mood": [ "20% of your emotions were positive, 10% of your emotions were neutral, and 70% of your emotions were negative", "Your most common negative emotions were sadness, anger, and frustration. You encountered these emotions in situations before and after approaching unfamiliar people.", "Your most common positive emotions were happiness and excitement. You encountered these emotions when you were with your friends and family" ], "pain": [ "On average, you experienced mild physical pain", "You experienced moderate pain relating to your shortness of breath", "You experienced mild pain related to your shaking" ], "questions_to_ask_your_doctor": [ "How can I modulate my emotions?", "How can I alleviate my physical symptoms, such as shortness of breath, shaking, and rapid heartbeat?" ] }'),
                );
                // return $data;
                $report = $this->generateReportAI($data, 2);
            }


            #insert in database if success
            if (isset($report['status']) && $report['status'] == "200") {

                $response = json_encode($report['data']);
                $data1   = [
                    'user_id'           => $user_id,
                    'request_ids'       => $request_ids,
                    'start_time'        => $start_time,
                    'end_time'          => $end_time,
                    'chat_ids_count'    => isset($chat_ids_count) ? $chat_ids_count : null,
                    'chat_start_id'     => isset($chat_start_id) ? $chat_start_id : null,
                    'chat_end_id'       => isset($chat_end_id) ? $chat_end_id : null,
                    'is_chat_included'  => $request->include_chat,
                    'type'              => $request->type,
                ];

                return $this->insertReport($response, $data1, $moodPain);
            } else {

                $report['moodPain'] = $moodPain;

                // dd($report);
                return response()->json($report, $report['status']);
            }
        } else {
            return $this->sendResponsewithoutData('No entry found in given journal', 400);
        }
    }


    function generateReportNew(Request $request)
    {
        $validate               = Validator::make($request->all(), [

            'journal_ids'       => 'nullable|array',
            // 'journal_ids.*'     => 'distinct|exists:journals,id',
            'start_date'        => 'required|date_format:Y-m-d',
            'end_date'          => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'type'              => 'required|integer|between:1,2',
            'include_chat'      => 'required|integer|between:0,1',

        ]);

        if ($validate->fails()) {

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }
        $user_id                =   Auth::id();
        $auth                   =   Auth::user();
        $authTimezone           =   $auth->timezone ?? 'UTC'; //'Asia/Kolkata'
        $authTimezone_offset    =   timezone_offset($authTimezone);
        #Check if any journals belong to a different user
        if (isset($request->journal_ids) && count($request->journal_ids) > 0) {

            $invalidJournals    =   Journal::whereIn('id', $request->journal_ids)

                ->where('user_id', '<>', $user_id)
                ->count();

            if ($invalidJournals > 0) {

                return $this->sendResponsewithoutData("Invailed journals", 422);
            }
        }

        $start_time     = Carbon::parse($request->start_date, $authTimezone)->startOfDay();

        $end_time       = Carbon::parse($request->end_date, $authTimezone)->isToday() || Carbon::parse($request->end_date, $authTimezone)->isFuture() ? Carbon::now() : Carbon::parse($request->end_date, $authTimezone)->endOfDay();


        if (isset($request->journal_ids) && count($request->journal_ids) > 0) {

            $journal_ids        =   $request->journal_ids;

        } else {

            $journal_ids        =   Journal::where('user_id', $user_id)->pluck('id')->toArray();

            if (count($journal_ids) == 0) {

                return $this->sendResponsewithoutData("Journal not available", 422);
            }
        }
        $moodPain              =   $this->getInsightSymptoms($request);
        #check report available
        $availableReport       =   $this->checkReportAvailable($request, $user_id, $journal_ids, $start_time, $end_time);
        // $availableReport['moodPain']    =   isset($moodPain) ? $moodPain : null;
        // dd($availableReport);
        //  echo response()->json($availableReport, 200);
        if (isset($availableReport) && count($availableReport['data']) > 0) {

            $availableReport['moodPain']    =   isset($moodPain) ? $moodPain : null;

            return response()->json($availableReport, 200);
            // return response()->json([
            //     'status' => 200,
            //     'message' => $request->type == 1 ? trans('message.insight') : trans('message.report'),
            //     'data' =>  $availableReport,
            //     'moodPain' => isset($moodPain) ? $moodPain : null,

            // ], 200);
        }


        $request_ids = JournalEntry::whereIn('journal_id', $journal_ids)
            ->where('is_active', 1)
            ->whereBetween('created_at', [$start_time, $end_time])
            ->pluck('id')
            ->toArray();
        $data = array();


        foreach ($journal_ids as $journal_id) {

            $journal = Journal::find($journal_id);

            #check report available for request time
            $entries_id    = JournalEntry::where('journal_id', $journal->id)->where('is_active', 1)->whereBetween('created_at', [$start_time, $end_time])->pluck('id')->toArray();

            ///dd($entries_id);

            if (count($entries_id) > 0) {

                $journalData = array(['text' => "-------------------------------------------------------------------------"], ['text' => "Journal Name : $journal->title"], ['text' => "Disease: " . $journal->topic->name], ['text' => 'Journal Entries']);
                #preparing journal entries as input in array
                $entries = JournalEntry::where('journal_id', $journal->id)->whereBetween('created_at', [$start_time, $end_time])->with(['feeling', 'feeling_types.feeling_type_details', 'symptom.journalSymtom'])->get();

                foreach ($entries as $entry) {

                    #date
                    $date   = Carbon::parse($entry->journal_on)->format('Y-m-d H:i A');

                    array_push($journalData, ['text' => "Date: $date"]);
                    #entry id
                    $details = "id: " . $entry->id;
                    #mood
                    $details = $details . ", Mood: " . $entry->feeling->name;
                    #feeling
                    $felling_types  = $entry->feeling_types->pluck('feeling_type_details.name')->implode(", ");
                    $details        = $details . ", Feelings: $felling_types";
                    #symptoms
                    $symptoms       = $entry->symptom->pluck('journalSymtom.symptom')->implode(", ");
                    $details        = $details . ", Symptoms: $symptoms";
                    #pain
                    $painScale = [
                        0 => "No Pain",
                        1 => "Mild Pain",
                        2 => "Discomforting Pain",
                        3 => "Moderate Pain",
                        4 => "Severe Pain",
                        5 => "Very Severe Pain",
                    ];

                    $pain       = $painScale[$entry->pain];
                    $details    = $details . ". Pain: $pain";

                    #description
                    $details    = $details . ". Description: $entry->content";
                    array_push($journalData, ['text' => $details]);
                }

                $data = array_merge($data, $journalData);
            }
        }

        if (count($data) > 0) {
            #----------  3 J U N -----------------#
            if (isset($request->include_chat) && $request->include_chat == 1) {

                $chat = $this->includeChat($request);

                if (isset($chat) && count($chat) > 0) {

                    $data = array_merge($data, $chat);
                }
            }
            #----------  3 J U N -----------------#
            // return $data;


            #Insides & Suggestion
            if ($request->type == 1) {

                array_push(
                    $data,
                    array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= insights and sugestions"),
                    array("text" => "provide result in json format"),
                    array("text" => "give the keys values in array format, even if only one key is available. and give minimum  five points in each key"),
                    array("text" => "also provide the ids in array on the basis of which that line has been made"),
                    array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the journal entries'"),
                    array("text" => "if Media link available in chat section, analyze the image and give response accordingly"),
                    array("text" => "format must be in this format => \n{\n  \"insights\": [\n    {\"text\": \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\", \"ids\":[12, 35 ,51, 64]},\n    {\"text\": \"Exercise has a noticeable positive impact on blood sugar management.\", \"ids\":[14, 37 ,53, 60, 68]},\n    {\"text\": \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\", \"ids\":[10, 32 ,51, 54, 64]},\n    {\"text\": \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\", \"ids\":[6, 22 ,47, 49, 54]},\n    {\"text\": \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\", \"ids\":[21, 37 ,41, 49, 53]}\n  ],\n  \"suggestions\": [\n    {\"text\": \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\", \"ids\":[12, 35 ,51, 64]},\n    {\"text\": \"Incorporate regular physical activity, such as daily walks, into the routine.\", \"ids\":[14, 37 ,53, 60, 68]},\n    {\"text\": \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\", \"ids\":[10, 32 ,51, 54, 64]},\n    {\"text\": \"Monitor blood sugar closely during illness and seek medical attention if necessary.\", \"ids\":[6, 22 ,47, 49, 54]},\n    {\"text\": \"Actively engage in diabetes support groups to learn from and share experiences with others.\", \"ids\":[21, 37 ,41, 49, 53]}\n  ]\n}\n"),
                    //array("text" => "format must be in this format => \n{\n  \"insights\": [\n    \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\",\n    \"Exercise has a noticeable positive impact on blood sugar management.\",\n    \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\",\n    \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\",\n    \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\"\n  ],\n  \"suggestions\": [\n    \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\",\n    \"Incorporate regular physical activity, such as daily walks, into the routine.\",\n    \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\",\n    \"Monitor blood sugar closely during illness and seek medical attention if necessary.\",\n    \"Actively engage in diabetes support groups to learn from and share experiences with others.\"\n  ]\n}\n"),
                );
                $report = $this->generateReportAI($data, 1);
            }

            #report
            elseif ($request->type == 2) {
                array_push(
                    $data,
                    array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= symptoms, mood, pain and questions_to_ask_your_doctor"),
                    array("text" => "provide result in json format"),
                    array("text" => "give the values of keys in array format not in string, even if only one value is available. and give minimum five points in each key"),

                    array("text" => "expain all point, do not give one word in the array"),
                    array("text" => "expain every symtoms, mood and pain. Do not give the same output as the input"),
                    array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the journal entries'"),
                    array("text" => "if Media link available in chat section, analyze the image and give response accordingly"),

                    array("text" => 'format must be in this format => { "symptoms": [ "Sweating of your palms before participating in social scenarios", "Shortness of breath while surrounded by others you are not familiar with, which worsens your asthma", "Rapid heartbeat in classroom and work settings before thinking about answering a question", "Thoughts and feelings of worthlessness, sadness, and low self-esteem" ], "mood": [ "20% of your emotions were positive, 10% of your emotions were neutral, and 70% of your emotions were negative", "Your most common negative emotions were sadness, anger, and frustration. You encountered these emotions in situations before and after approaching unfamiliar people.", "Your most common positive emotions were happiness and excitement. You encountered these emotions when you were with your friends and family" ], "pain": [ "On average, you experienced mild physical pain", "You experienced moderate pain relating to your shortness of breath", "You experienced mild pain related to your shaking" ], "questions_to_ask_your_doctor": [ "How can I modulate my emotions?", "How can I alleviate my physical symptoms, such as shortness of breath, shaking, and rapid heartbeat?" ] }'),
                );
                // return $data;
                $report = $this->generateReportAI($data, 2);
            }


            #insert in database if success
            if (isset($report['status']) && $report['status'] == "200") {

                $response = json_encode($report['data']);
                $data1   = [
                    'user_id'           => $user_id,
                    'request_ids'       => $request_ids,
                    'start_time'        => $start_time,
                    'end_time'          => $end_time,
                    'chat_ids_count'    => isset($chat_ids_count) ? $chat_ids_count : null,
                    'chat_start_id'     => isset($chat_start_id) ? $chat_start_id : null,
                    'chat_end_id'       => isset($chat_end_id) ? $chat_end_id : null,
                    'is_chat_included'  => $request->include_chat,
                    'type'              => $request->type,
                ];

                return $this->insertReport($response, $data1, $moodPain);
            } else {

                $report['moodPain'] = $moodPain;

                // dd($report);
                return response()->json($report, $report['status']);
            }
        } else {
            return $this->sendResponsewithoutData('No entry found in given journal', 400);
        }
    }


    #Generate Report 
    function generateReportAI($data, $type, $count = 1)
    {

        if ($count > 3) {

            $response = [
                'status' => 201,
                "message" => "Insufficent data",
                "data" => [],
            ];

            return $response;
        }

        // Define your API key
        $API_KEY = "AIzaSyCN9891vVrDvLHsQvZU9M2mv-9W85dOX8g";
        // Define the URL
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key=" . $API_KEY;

        // $instructions =      $this->geminiInstruction($type);
        $instructions =      geminiInstruction($type);
        // return $data;
        $data = array(

            "system_instruction" => array("parts" => $instructions),
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
            $response = [
                'status' => 400,
                "message" => "Curl Error",
                "data" => $error,
            ];

            return $response;
            // echo "cURL Error: " . $error;
        } else {
            // Close cURL session
            curl_close($curl);
            $response = json_decode($response, true);

            // return $response;
            try {

                if (isset($response['candidates']) && isset($response['candidates'][0]) && isset($response['candidates'][0]['content']) && isset($response['candidates'][0]['content']['parts']) && isset($response['candidates'][0]['content']['parts'][0]) && isset($response['candidates'][0]['content']['parts'][0]['text'])) {

                    $result = $response['candidates'][0]['content']['parts'][0]['text'];
                    $finalResponse = $this->convertIntoJson($result);
                    $finalResponse = json_decode($finalResponse, true);
                    // return $finalResponse;

                    if ($type == 1) {
                        if (isset($finalResponse['insights']) && isset($finalResponse['suggestions']) && count($finalResponse['insights']) > 0 && count($finalResponse['suggestions']) > 0) {
                            $validate = Validator::make($finalResponse, [
                                'insights.*.ids' => 'required|array|min:1|distinct',
                                'insights.*.ids.*' => 'required|integer|exists:journal_entries,id',
                                'suggestions.*.ids' => 'required|array|min:1|distinct',
                                'suggestions.*.ids.*' => 'required|integer|exists:journal_entries,id',
                            ]);
                            if ($validate->fails()) {
                                return $this->generateReportAI($data, $type, $count + 1);
                            }

                            $finalResult = [
                                'status' => 200,
                                "message" => "Insights & Suggestions generated successfully",
                                'data' => $finalResponse
                            ];
                            return $finalResult;
                        } else {

                            return $this->generateReportAI($data, $type, $count + 1);
                        }
                    } elseif ($type == 2) {
                        if (isset($finalResponse['symptoms']) && isset($finalResponse['mood']) && isset($finalResponse['pain']) && isset($finalResponse['questions_to_ask_your_doctor']) && count($finalResponse['symptoms']) > 0 && count($finalResponse['mood']) > 0 && count($finalResponse['pain']) > 0  && count($finalResponse['questions_to_ask_your_doctor']) > 0) {
                            $finalResult = [
                                'status' => 200,
                                "message" => "Report generated successfully",
                                'data' => $finalResponse
                            ];
                            return $finalResult;
                        } else {
                            // return $finalResponse;
                            return $this->generateReportAI($data, $type, $count + 1);
                        }
                    }
                } else {
                    return $this->generateReportAI($data, $type, $count + 1);
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



    function getInsightSymptoms($request)
    {
        $auth               =   Auth::User();
        $authId             =   Auth::id();
        $start_date         =   $request->start_date;
        $end_date           =   $request->end_date;
        $authTimezone       =   $auth->timezone ?? 'UTC';
        $authTimezone_offset =  timezone_offset($authTimezone);
        $dates              =   getDatesBetween($start_date, $end_date);
        $insight            =   array();

        if (isset($dates[0]) && !empty($dates[0])) {

            foreach ($dates as $date) {
                //========== AS UTC TIME ZONE   ======//
                $insights                   =   JournalEntry::with([

                    'feeling' => function ($query) {

                        $query->select('id', 'name');
                    }

                ])->select('id', 'feeling_id', 'pain')->where(['user_id' => $authId, 'is_active' => 1]);


                if (isset($request->journal_ids) && !empty($request->journal_ids)) {

                    $insights = $insights->whereIn('journal_id', $request->journal_ids);
                }

                $insights = $insights
                    ->select('*')
                    ->selectRaw("DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '$authTimezone_offset'), '%Y-%m-%d') AS local_created_date")
                    ->havingRaw("local_created_date = '$date' ")
                    ->orderByDesc('id')
                    ->get();

                if ($insights->isNotEmpty()) {

                    $query                  =   JournalEntry::where('is_active', 1);

                    if (!empty($request->journal_ids)) {

                        $query->whereIn('journal_id', $request->journal_ids);
                    }

                    $query      =     $query->select('*')
                    
                    ->selectRaw("DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '$authTimezone_offset'), '%Y-%m-%d') AS local_created_date")
                    ->havingRaw("local_created_date = '$date' ")
                    ->selectRaw('AVG(feeling_id) AS avg_mood, AVG(pain) AS avg_pain')
                    ->first();

                    $avg         =   ceil((isset($moodAvg) && !empty($moodAvg)) ? $moodAvg['avg_mood'] : 0);

                    $avg_pain    =   ceil((isset($moodAvg) && !empty($moodAvg)) ? $moodAvg['avg_pain'] : 0);

                    $insight[] = [
                        'date' => $date,
                        'count' => count($insights),
                        'avg_mood' => $avg,
                        'avg_pain' => $avg_pain,
                        'mood' => $insights[0]['feeling_id'],
                        'pain' => $insights[0]['pain'],
                        'mood_pain' => $insights,
                    ];
                }
            }
        }
        return $insight;
    }




















    function getInsightSymptomsOLd($request)
    {
        $authId             =   Auth::id();

        $start_date         =   $request->start_date;

        $end_date           =   $request->end_date;

        $dates              =   getDatesBetween($start_date, $end_date);

        $auth               =   Auth::User();


        $authTimezone       =   $auth->timezone ?? 'UTC';

        $authTimezone_offset =  timezone_offset($authTimezone);

        $dates              =   getDatesBetween($start_date, $end_date);

        $insight            =   array();

        if (isset($dates[0]) && !empty($dates[0])) {

            foreach ($dates as $date) {

                $insights                   =   JournalEntry::with([

                    'feeling' => function ($query) {

                        $query->select('id', 'name'); // Rename 'id' and 'name'
                    }

                ])->select('id', 'feeling_id', 'pain')->where(['user_id' => $authId, 'is_active' => 1]);

                if (isset($request->journal_ids) && !empty($request->journal_ids)) {

                    $insights = $insights->whereIn('journal_id', $request->journal_ids);
                }

                $insights                   =   $insights->whereDate('created_at', '=', $date)->orderByDesc('id')->get();

                if ($insights->isNotEmpty()) {

                    $query                  =   JournalEntry::where('is_active', 1);

                    if (!empty($request->journal_ids)) {

                        $query->whereIn('journal_id', $request->journal_ids);
                    }

                    $moodAvg    =   $query->whereDate('created_at', $date)

                        ->selectRaw('AVG(feeling_id) AS avg_mood, AVG(pain) AS avg_pain')
                        ->first();

                    $avg         =   ceil((isset($moodAvg) && !empty($moodAvg)) ? $moodAvg['avg_mood'] : 0);

                    $avg_pain    =   ceil((isset($moodAvg) && !empty($moodAvg)) ? $moodAvg['avg_pain'] : 0);

                    $insight[] = [
                        'date' => $date,
                        'count' => count($insights),
                        'avg_mood' => $avg,
                        'avg_pain' => $avg_pain,
                        'mood' => $insights[0]['feeling_id'],
                        'pain' => $insights[0]['pain'],
                        'mood_pain' => $insights,
                    ];
                }
            }
        }
        return $insight;
    }

    public function geminiInstruction($type)
    {

        if ($type == 1) {      // for journal insights

            $guidelines = [
                [
                    "text" => "System: You are now a health journal insights generator for Doqta, an AI-powered app that provides culturally relevant medical support for the Black community. Your role is to analyze users' health journal entries and generate insightful observations to help them and their doctors better understand their medical progress and needs. When generating insights from journal entries, follow these guidelines:"
                ],
                [
                    "text" => "Health Focus: Only provide insights from journal entries pertaining to medical conditions, symptoms, treatments, side effects, etc. Do not generate insights from non-health related entries."
                ],
                [
                    "text" => "User-Centric: Insights should be specific and personalized to the individual user's journal entries, experiences and health journey."
                ],
                [
                    "text" => "Simple Language: Explain insights using clear, easy-to-understand terminology that avoids complex medical jargon unless defining a term. Your aim is maximum clarity and relatability."
                ],
                [
                    "text" => "Cultural Relevance: Where applicable, incorporate culturally relevant contexts, considerations and phrasing that accounts for the Black community's experiences with healthcare."
                ],
                [
                    "text" => "Identify Trends: Analyze entries over time to detect patterns, progressions, regressions, correlations, etc. related to symptoms, treatments, side effects, lifestyle factors, etc."
                ],
                [
                    "text" => "Surface Discoveries: Highlight novel observations, potential causes/triggers, noticeable impacts, or areas that may need further exploration with their doctor."
                ],
                [
                    "text" => "Suggest Next Steps: Where appropriate, provide constructive recommendations for the user to discuss with their doctor, such as additional testing, treatment options, lifestyle changes, etc."
                ],
                [
                    "text" => "Empathetic Tone: Write with empathy, warmth and emotional intelligence befitting a caring, culturally competent health companion."
                ],
                [
                    "text" => "Concise Insights: Keep each insight focused and avoid repetitive or unnecessarily lengthy explanations. Be concise and write insights as bullets that are no longer than one sentence each."
                ],

                [
                    "text" => "Proper JSON Response: Keep json response proper without extra apostrophe extra ,Remove unnecessarily symbols."
                ]
            ];
        } elseif ($type == 2) {


            $guidelines = [
                [
                    "text" => "System: You are now a medical report generator for Doqta, tasked with creating detailed yet accessible reports to help users and their doctors facilitate high-quality, culturally competent care. Your reports will synthesize insights from the user's interactions with the Doqta AI companion and health journal entries. When generating user reports, follow these guidelines:"
                ],
                [
                    "text" => "Audience: The primary audience is the user's human doctor. Reports should be professional, objective, and centered on supporting productive patient-doctor conversations."
                ],
                [
                    "text" => "Simple Language: Use clear terminology avoiding unnecessary medical jargon. The goal is maximum understandability for users and doctors."
                ],
                [
                    "text" => "Cultural Competence: Incorporate culturally relevant context, considerations and phrasing that accounts for the Black community's healthcare experiences."
                ],
                [
                    "text" => "User Summary: Open with a concise background summarizing the user's key health condition(s), symptoms and primary concerns based on their Doqta activities."
                ],
                [
                    "text" => "Journal Insights: Analyze journal entries to identify patterns, progressions, triggers, impacts of lifestyle factors, novel observations, etc. related to their condition(s)."
                ],
                [
                    "text" => "Chatbot Review: Review chatbot conversations noting areas where the AI's advice resonated or conflicted with the user's experiences, beliefs and ability to adhere to recommendations."
                ],
                [
                    "text" => "Key Concerns: Highlight the user's most pressing unresolved health issues, worries and challenges based on their Doqta activities that may require additional discussion."
                ],
                [
                    "text" => "Potential Next Steps: Provide constructive recommendations for the doctor to consider, such as additional testing, treatment adjustments, lifestyle modifications, education, etc. tailored to this user's needs."
                ],
                [
                    "text" => "Suggested Questions: Develop a list of specific, thoughtful questions for the user to ask their doctor to become a more informed self-advocate in their care."
                ],
                [
                    "text" => "Empathetic Tone: Write with emotional intelligence and a caring, supportive voice appropriate for sensitive health discussions."
                ]
            ];
        }

        return $guidelines;
    }

    #---------------------- CHECK REPORT AVAILABLE ----------------------#
    public function checkReportAvailable($request, $user_id, $journal_ids, $start_time, $end_time)
    {

        $request_ids = JournalEntry::whereIn('journal_id', $journal_ids)

            ->where('is_active', 1)
            ->whereBetween('created_at', [$start_time, $end_time])
            ->pluck('id')
            ->toArray();

        $reports = JournalReport::where('user_id', $user_id)
            ->where('type', $request->type)
            ->where('report_type', 1)
            ->where('is_chat_included', $request->include_chat)
            ->get();

        $ids_count = count($request_ids);

        if ($ids_count > 0 && count($reports) > 0) {
            if (isset($request->include_chat) && $request->include_chat == 1) {
                # Get chat ids
                $inbox_ids = AiThread::where(function ($query) use ($user_id) {
                    $query->where('sender_id', $user_id)
                        ->orWhere('receiver_id', $user_id);
                })
                    ->pluck('id')
                    ->toArray();

                if (count($inbox_ids) > 0) {
                    $chat_request_ids = AiMessage::whereIn('inbox_id', $inbox_ids)
                        ->where(function ($query) use ($user_id) {
                            $query->where('is_user1_trash', '!=', $user_id)
                                ->orWhere('is_user2_trash', '!=', $user_id);
                        })
                        ->where('is_active', 1)
                        ->whereBetween('created_at', [$start_time, $end_time])
                        ->pluck('id')
                        ->toArray();

                    $chat_ids_count = count($chat_request_ids);
                    $chat_start_id = reset($chat_request_ids);
                    $chat_end_id = end($chat_request_ids);
                }

                if (isset($chat_ids_count) && isset($chat_start_id) && isset($chat_end_id) && $chat_ids_count > 0) {
                    foreach ($reports as $report) {
                        # ChatIds
                        $chatReportIds = AiMessage::whereIn('inbox_id', $inbox_ids)
                            ->where(function ($query) use ($user_id) {
                                $query->where('is_user1_trash', '!=', $user_id)
                                    ->orWhere('is_user2_trash', '!=', $user_id);
                            })
                            ->where('is_active', 1)
                            ->whereBetween('created_at', [$report->start_date, $report->end_date])
                            ->pluck('id')
                            ->toArray();

                        $reportIds = json_decode($report->ids, true);

                        if ($ids_count == $report->ids_count && $chat_ids_count == $report->chat_ids_count) {

                            if ($chat_start_id == $report->chat_start_id && $chat_end_id == $report->chat_end_id) {

                                if (empty(array_diff($request_ids, $reportIds)) && empty(array_diff($reportIds, $request_ids)) && empty(array_diff($chatReportIds, $chat_request_ids)) && empty(array_diff($chat_request_ids, $chatReportIds))) {
                                    $reportLink      =  JournalReport::select('pdf_link')->where('id', $report->id)->first();
                                    if ($request->type == 1) {
                                        $finalReport = $this->getInsights($report->id, 1);

                                        // return $finalReport;


                                        $typeMessage = $request->type == 1 ? trans('message.insight') : trans('message.report');
                                        return array(
                                            'status' => 200,
                                            'message' => $typeMessage,
                                            'data' => $finalReport,
                                            'pdf_link' => (isset($reportLink) && !empty($reportLink['pdf_link']) ? $this->addBaseInImage($reportLink['pdf_link']) : null)
                                        );
                                    } elseif ($request->type == 2) {

                                        $finalReport = json_decode($report->report, true);
                                        $typeMessage = $request->type == 1 ? trans('message.insight') : trans('message.report');

                                        $ar = array(
                                            'status' => 200,
                                            'message' => $typeMessage,
                                            'data' => $finalReport,
                                            'pdf_link' => (isset($reportLink) && !empty($reportLink['pdf_link']) ? $this->addBaseInImage($reportLink['pdf_link']) : null)
                                        );

                                        return $ar;

                                        // return $finalReport;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    return $this->CheckreportWithoutChat($request, $request_ids, $user_id);
                }
            } else {
                return $this->CheckreportWithoutChat($request, $request_ids, $user_id);
            }
        }
    }

    #---------------------- CHECK REPORT AVAILABLE WITHOUT CHAT ----------------------#
    function CheckreportWithoutChat($request, $request_ids, $user_id)
    {
        $reports  = JournalReport::where('user_id', $user_id)->where('type', $request->type)->where('report_type', 1)->where('is_chat_included', '<>', 1)->get();

        if (count($reports) > 0) {
            foreach ($reports as $report) {
                $reportIds = json_decode($report->ids);


                if (count($request_ids) == $report->ids_count) {
                    if (empty(array_diff($request_ids, $reportIds)) && empty(array_diff($reportIds, $request_ids))) {
                        $reportLink      =  JournalReport::select('pdf_link')->where('id', $report->id)->first();
                        if ($request->type == 1) {
                            $finalReport = $this->getInsights($report->id, 1);

                            return array(
                                'status' => 200,
                                'message' => trans('message.insight'),
                                'data' => $finalReport,
                                'pdf_link' => (isset($reportLink) && !empty($reportLink['pdf_link']) ? $this->addBaseInImage($reportLink['pdf_link']) : null)
                            );

                            // return $ar;
                            // return $finalReport;
                        } elseif ($request->type == 2) {
                            $finalReport     = json_decode($report->report, true);
                            return array(
                                'status' => 200,
                                'message' => trans('message.insight'),
                                'data' => $finalReport,
                                'pdf_link' => (isset($reportLink) && !empty($reportLink['pdf_link']) ? $this->addBaseInImage($reportLink['pdf_link']) : null)
                            );

                            // return $finalReport;
                        }
                    }
                }
            }
        }
    }

    #---------------------- INCLUDE CHAT ----------------------#
    function includeChat($request)
    {
        $myId = Auth::id();
        $inbox_ids = AiThread::where(function ($query) use ($myId) {
            $query->where('sender_id', $myId)
                ->orWhere('receiver_id', $myId);
        })
            ->pluck('id')
            ->toArray();

        if (count($inbox_ids) > 0) {

            #check report available for request time
            $start_time     = Carbon::parse($request->start_date)->startOfDay();
            $end_time       = Carbon::parse($request->end_date)->isToday() || Carbon::parse($request->end_date)->isFuture() ? Carbon::now() : Carbon::parse($request->end_date)->endOfDay();

            $messages = AiMessage::with(['sender' => function ($query) {
                $query->select('id', 'name', 'user_name');
            }])->where(function ($query) use ($myId) {
                $query->where('is_user1_trash', '!=', $myId)
                    ->orWhere('is_user2_trash', '!=', $myId);
            })->whereIn('inbox_id', $inbox_ids)->whereBetween('created_at', [$start_time, $end_time])->get();

            $chatData = array(['text' => "-------------------------------------------------------------------------"], ['text' => "Chat Data:"]);

            foreach ($messages as $message) {

                $date = Carbon::parse($message->created_at)->format('Y-m-d H:i A');
                $details = "Date:" . $date;
                $details .= ", Sender: " . $message->sender->name;
                $details .= ", Message: " . $message->message;
                if (isset($message->media) && !empty($message->media)) {
                    $media  =   $this->addBaseInImage($message->media);
                    $details .= ", Media link: " . $media;
                }
                array_push($chatData, ['text' => $details]);
            }

            return $chatData;
        }
    }

    #======================= GET JOURNAL ENTRIES DETAILS =============================#

    function viewInsightsEntries(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'type'              => 'required|integer|between:1,2',
        ]);

        if ($validate->fails()) {

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }
        if ($request->type == 1) {
            $validate = Validator::make($request->all(), [
                'id'                => 'required|integer|exists:journal_insights,id',
            ]);
            if ($validate->fails()) {
                return $this->sendResponsewithoutData($validate->errors()->first(), 422);
            }

            $response = JournalInsight::find($request->id);
            $ids = JournalInsideEntry::where('insight_id', $request->id)->pluck('entry_id')->toArray();
            $response['entries'] = [];
            if (count($ids) > 0) {
                $response['entries'] = JournalEntry::whereIn('id', $ids)->with([
                    'journal:id,title',
                    'feeling:id,name,feeling,selected',

                    'feeling_types' => function ($q) {

                        $q->select('id', 'journal_entry_id', 'feeling_type');
                    },
                    'feeling_types.feeling_type' => function ($q) {

                        $q->select('id', 'name');
                    }, 'feeling' => function ($q) {

                        $q->select('id', 'name');
                    }, 'symptom' => function ($q) {

                        $q->select('id', 'symptom_id', 'journal_entry_id');
                    },
                    'symptom.journalSymtom' => function ($q) {

                        $q->select('id', 'symptom');
                    }
                ])->get();
            }

            return $this->sendResponse($response, "Insight entries details", 200);
        } elseif ($request->type == 2) {
            $validate = Validator::make($request->all(), [
                'id'                => 'required|integer|exists:chat_insights,id',
            ]);
            if ($validate->fails()) {
                return $this->sendResponsewithoutData($validate->errors()->first(), 422);
            }

            $response               = ChatInsight::find($request->id);
            $ids                    = ChatInsightEntry::where('insight_id', $request->id)->pluck('entry_id')->toArray();

            $response['entries']    = AiMessage::whereIn('id', $ids)->with(['sender' => function ($query) {

                $query->select('id', 'name', 'user_name', 'profile');
            }])->get(['id', 'message', 'sender_id']);

            if (count($response['entries']) > 0) {
                foreach ($response['entries'] as $entry) {
                    $entry->sender->profile = $this->addBaseInImage($entry->sender->profile);
                }
            }
            // return $response;
            return $this->sendResponse($response, "Insight entries details", 200);
        }
    }


    function insertReport($report, $data1, $moodPain)
    {
        $user_id                = $data1['user_id'];
        $start_time             = $data1['start_time'];
        $end_time               = $data1['end_time'];
        $is_chat_included       = $data1['is_chat_included'];
        $request_type           = $data1['type'];
        $request_ids          = $data1['request_ids'];

        #if chat include
        if (isset($is_chat_included) && $is_chat_included == 1) {
            $inbox_ids = AiThread::where(function ($query) use ($user_id) {
                $query->where('sender_id', $user_id)
                    ->orWhere('receiver_id', $user_id);
            })
                ->pluck('id')
                ->toArray();

            if (count($inbox_ids) > 0) {
                $chat_request_ids    = AiMessage::whereIn('inbox_id', $inbox_ids)
                    ->where(function ($query) use ($user_id) {
                        $query->where('is_user1_trash', '!=', $user_id)->orWhere('is_user2_trash', '!=', $user_id);
                    })
                    ->where('is_active', 1)
                    ->whereBetween('created_at', [$start_time, $end_time])
                    ->pluck('id')->toArray();
                $chat_ids_count  = count($chat_request_ids);
                $chat_start_id   = reset($chat_request_ids);
                $chat_end_id     = end($chat_request_ids);
            }
        }
        $result = json_decode($report, true);

        // $newReport = JournalReport::where('user_id', $user_id)->where('type', $request_type)->where('report_type', 1)->whereDate('start_date', '=', $start_time)->whereDate('end_date', '=', $end_time)->where('is_chat_included', $is_chat_included)->first();
        // if (empty($newReport)) {
        $newReport                      = new JournalReport;

        // }
        // else{
        //     JournalInsight::where('report_id', $newReport->id)->delete();
        //     JournalInsideEntry::where('report_id', $newReport->id)->delete();
        // }
        $newReport->user_id             = $user_id;
        $newReport->start_date          = $start_time;
        $newReport->end_date            = $end_time;
        $newReport->ids_count           = count($request_ids);
        $newReport->start_id            = reset($request_ids);
        $newReport->end_id              = end($request_ids);
        $newReport->chat_ids_count      = isset($chat_ids_count) ? $chat_ids_count : null;
        $newReport->chat_start_id       = isset($chat_start_id) ? $chat_start_id : null;
        $newReport->chat_end_id         = isset($chat_end_id) ? $chat_end_id : null;
        $newReport->is_chat_included    = $is_chat_included;
        $newReport->report              = $report;
        $newReport->ids                 = json_encode($request_ids);
        $newReport->type                = $request_type;
        $newReport->report_type         = 1;
        $newReport->save();
        $reportId       =   $newReport->id;
        #----------- generate pdf -------------#
        $this->getJournalReport($reportId);
        #insights & suggestion
        if ($request_type == 1) {
            foreach ($result['insights'] as $insights) {

                $insig = JournalInsight::create([
                    'report_id' => $newReport->id,
                    'type'      => 1,
                    'details'   => $insights['text'],
                ]);

                foreach ($insights['ids'] as $id) {
                    JournalInsideEntry::create([
                        'report_id'     => $newReport->id,
                        'insight_id'    => $insig->id,
                        'entry_id'      => $id,
                    ]);
                }
            }

            foreach ($result['suggestions'] as $suggestion) {

                $sugg = JournalInsight::create([
                    'report_id' => $newReport->id,
                    'type'      => 2,
                    'details'   => $suggestion['text'],
                ]);

                foreach ($suggestion['ids'] as $id) {
                    JournalInsideEntry::create([
                        'report_id'     => $newReport->id,
                        'insight_id'    => $sugg->id,
                        'entry_id'      => $id,
                    ]);
                }
            }
            $finalReport =  $this->getInsights($reportId, 1); //type: 1=insights, 2=suggestion, 3=chat insights
            $pdfLink     =  JournalReport::select('pdf_link')->where('id', $reportId)->first();


            return response()->json([
                'status' => 200,
                'message' => trans('message.insight'),
                'data' =>  $finalReport,
                'moodPain' => $moodPain,
                'pdf_link' => (isset($pdfLink) && !empty($pdfLink['pdf_link'])) ?  $this->addBaseInImage($pdfLink->pdf_link) : null,
            ], 200);
        }

        #Journal report
        elseif ($request_type == 2) {
            $pdfLink     =  JournalReport::select('pdf_link')->where('id', $reportId)->first();
            return response()->json([
                'status' => 200,
                'message' => trans('message.report'),
                'data' =>  $result,
                'moodPain' => $moodPain,
                'pdf_link' => (isset($pdfLink) && !empty($pdfLink['pdf_link'])) ?  $this->addBaseInImage($pdfLink['pdf_link']) : null,
            ], 200);
        }
    }


    function getInsights($report_id, $type)
    {
        $data = [];
        if ($type == 1) {
            $data['insights'] = JournalInsight::where('report_id', $report_id)->where('type', 1)->get(['id', 'details']);
            $data['suggestions'] = JournalInsight::where('report_id', $report_id)->where('type', 2)->get(['id', 'details']);
        }
        return $data;
    }

    #----------   G E T         J O U R N A L       R E P O R T    ----------------__#
    //5 June 2023

    public function getJournalReport($reportId)
    {
        try {

            // Fetch the report by ID
            $report = JournalReport::find($reportId);
            // Check if the report exists
            if ($report) {
                // Generate PDF from view
                $pdf            =   PDF::loadView('Journal_report.journalReport', compact('report'));
                $currentYear    =   date('Y');
                $currentMonth   =   date('m');
                $directory      =   "uploads/pdf/{$currentYear}/{$currentMonth}";
                // Store the QR code image directly into the storage disk
                $filename       = $directory . '/' . Str::uuid() . '.pdf';
                Storage::disk('public')->put($filename, $pdf->output());
                // Return the path to the stored QR code image
                // Save PDF to storage and update the report's PDF link
                $report->pdf_link = $filename;
                $report->save();
            } else {
                Log::warning('Report not found: ID ' . $reportId);
                // return $this->sendError(trans('message.report_not_found'), [], 404);
            }
        } catch (Exception $e) {
            Log::error('Error in getJournalReport: ' . $e->getMessage() . ' at line ' . $e->getLine());
            // return $this->sendError(trans('message.something_went_wrong'), [], 500);
        }
    }


    public function checkPdf()
    {

        return $this->getJournalReport(4);
    }
}
