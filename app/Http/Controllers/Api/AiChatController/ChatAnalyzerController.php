<?php

namespace App\Http\Controllers\Api\AiChatController;

use App\Models\AiThread;
use App\Models\AiMessage;
use App\Models\ChatInsight;
use App\Models\ChatInsightEntry;
use App\Traits\CommonTrait;
use Illuminate\Http\Request;
use App\Models\JournalReport;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Traits\postCommentLikeCount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Api\Journals\JournalAnalyzerController;

class ChatAnalyzerController extends BaseController
{
    use postCommentLikeCount,CommonTrait;
    function insightsNew(Request $request){
           
        $validate = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);
    
        if($validate->fails()){

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }
        
        $myId               =       Auth::id();
        
        $inbox_ids          =       AiThread::where(function ($query) use ($myId) {

                                    $query->where('sender_id', $myId)
                                        ->orWhere('receiver_id', $myId);
                                    })->pluck('id')->toArray();
        
        if (count($inbox_ids) > 0) {
            
            #check report available for request time
            $start_time     = Carbon::parse($request->start_date)->startOfDay();
            $end_time       = Carbon::parse($request->end_date)->isToday() || Carbon::parse($request->end_date)->isFuture() ? Carbon::now() : Carbon::parse($request->end_date)->endOfDay();
            $request_ids    = AiMessage::whereIn('inbox_id', $inbox_ids)
                ->where(function ($query) use ($myId) {
                    $query->where('is_user1_trash', '!=', $myId)->orWhere('is_user2_trash', '!=', $myId);
                })
                ->where('is_active', 1)
                ->whereBetween('created_at', [$start_time, $end_time])
                ->pluck('id')->toArray();
    
            $ids_count  = count($request_ids);
            $start_id   = reset($request_ids);
            $end_id     = end($request_ids);
            $reports        = JournalReport::where('user_id', $myId)->where('report_type', 2)->get();
            if($ids_count > 10){ // TO SHOW MESSAGE INSUFFICENT
                if(count($reports) > 0){
                    
                    foreach($reports as $report){
                        $reportIds = json_decode($report->ids, true);
                        if($ids_count == $report->chat_ids_count  && $start_id == $report->chat_start_id && $end_id == $report->chat_end_id){
                                if (empty(array_diff($request_ids, $reportIds)) && empty(array_diff($reportIds, $request_ids))) {
                                    $finalReport = $this->getInsights($report->id);
                                    return $this->sendResponse($finalReport, trans('message.insight'), 200);
                            }
                        }
                    }
                }
                #end check report section
                
                $messages = AiMessage::with(['sender' => function ($query) {
                    $query->select('id', 'name', 'user_name', 'profile');
                }])->where(function ($query) use ($myId) {
                    $query->where('is_user1_trash', '!=', $myId)
                        ->orWhere('is_user2_trash', '!=', $myId);
                })->whereIn('inbox_id', $inbox_ids)->whereBetween('created_at', [$start_time, $end_time])->get();
    
    
                // return $messages;
                $chatData = [];
    
                foreach ($messages as $message) {
    
                    $date       =   Carbon::parse($message->created_at)->format('Y-m-d H:i A');
                    $details= "id: ".$message->id;
                    $details.= ", Date: ".$date;
                    $details.= ", Sender: ".$message->sender->name;
                    $details.= ", Message: ".$message->message;
                    if(isset($message->media) && !empty($message->media)){
                        $media  =   $this->addBaseInImage($message->media);
                        $details.= ", Media link: ".$media;
                    }
                    array_push($chatData, ['text'=> $details]);
                
                }
                // return $chatData;
                array_push($chatData, 
                array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= insights and top sugestions"),
                array("text" => "provide result in json format"),
                array("text" => "give the keys values in array format, even if only one key is available. and give minimum  five points in each key"),
                array("text" => "also provide the ids in array on the basis of which that line has been made"),
                array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the conversation'"),
                array("text" => "if Media link: available, analyze the image and give response accordingly"),
                array("text" => "format must be in this format => \n{\n  \"insights\": [\n    {\"text\": \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\", \"ids\":[12, 35 ,51, 64]},\n    {\"text\": \"Exercise has a noticeable positive impact on blood sugar management.\", \"ids\":[14, 37 ,53, 60, 68]},\n    {\"text\": \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\", \"ids\":[10, 32 ,51, 54, 64]},\n    {\"text\": \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\", \"ids\":[6, 22 ,47, 49, 54]},\n    {\"text\": \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\", \"ids\":[21, 37 ,41, 49, 53]}\n  ],\n  \"suggestions\": [\n    {\"text\": \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\", \"ids\":[12, 35 ,51, 64]},\n    {\"text\": \"Incorporate regular physical activity, such as daily walks, into the routine.\", \"ids\":[14, 37 ,53, 60, 68]},\n    {\"text\": \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\", \"ids\":[10, 32 ,51, 54, 64]},\n    {\"text\": \"Monitor blood sugar closely during illness and seek medical attention if necessary.\", \"ids\":[6, 22 ,47, 49, 54]},\n    {\"text\": \"Actively engage in diabetes support groups to learn from and share experiences with others.\", \"ids\":[21, 37 ,41, 49, 53]}\n  ]\n}\n"),
                );
                // return $chatData;
    
                $insight    = $this->generateReportAIChatTrait($chatData);
    
                if(isset($insight['status']) && $insight['status'] == 200){

                    $insight = json_encode($insight['data']);
                    $newReport=JournalReport::where('user_id', $myId)->where('report_type', 2)->whereDate('start_date', '=', $start_time)->whereDate('end_date', '=', $end_time)->first();
                    if(!isset($newReport)){
                        $newReport = new JournalReport;
                    }
                    $newReport->user_id         = $myId;
                    $newReport->start_date      = $start_time;
                    $newReport->end_date        = $end_time;
                    $newReport->report          = $insight;
                    $newReport->chat_ids_count  = $ids_count;
                    $newReport->chat_start_id   = $start_id;
                    $newReport->chat_end_id     = $end_id;
                    $newReport->ids             = json_encode($request_ids);
                    $newReport->type            = 1;
                    $newReport->report_type     = 2; //chat
                    $newReport->save();


                    #insights & suggestion
                    $insight = json_decode($insight, true);

                        foreach($insight['insights'] as $insights){
                
                            $insig = ChatInsight::create([
                                'report_id' => $newReport->id,
                                'type'      => 1,
                                'details'   => $insights['text'],
                            ]);
                
                            foreach($insights['ids'] as $id){
                                ChatInsightEntry::create([
                                    'report_id'     => $newReport->id,
                                    'insight_id'    => $insig->id,
                                    'entry_id'      => $id,
                                ]);
                            }
                        }
                
                        foreach($insight['suggestions'] as $suggestion){
                
                            $sugg = ChatInsight::create([
                                'report_id' => $newReport->id,
                                'type'      => 2,
                                'details'   => $suggestion['text'],
                            ]);
                        
                            foreach($suggestion['ids'] as $id){
                                ChatInsightEntry::create([
                                    'report_id'     => $newReport->id,
                                    'insight_id'    => $sugg->id,
                                    'entry_id'      => $id,
                                ]);
                            }
                        }
                        
                        $finalReport = $this->getInsights($newReport->id); //type: 1=insights, 2=suggestion, 3=chat insights

                        return response()->json([
                            'status' => 200,
                            'message' => trans('message.insight'),
                            'data' =>  $finalReport,
                        ], 200);

                }
                else{
    
                    return $this->sendResponse(null,'Insufficent data', 201);
                }
            }
            else{
                return $this->sendResponse(null,'No message found', 201);
            }
        }
        else{
            return $this->sendResponse(null,'No message found', 200);
        }
    }

    function getInsights($report_id){
        $data= [];
        $data['insights']= ChatInsight::where('report_id', $report_id)->where('type', 1)->get(['id', 'details']);
        $data['suggestions']= ChatInsight::where('report_id', $report_id)->where('type', 2)->get(['id', 'details']);
        return $data;
    }
}
