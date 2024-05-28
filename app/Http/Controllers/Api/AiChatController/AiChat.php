<?php

namespace App\Http\Controllers\Api\AiChatController;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\AiThread;
use App\Models\AiMessage;
use Illuminate\Http\Request;
use App\Models\JournalReport;
use App\Models\PinnedMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\postCommentLikeCount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Traits\CommonTrait;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\Laravel\Facades\Gemini;

class AiChat extends BaseController
{
    use postCommentLikeCount,CommonTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $myId                           =               Auth::id();
            // dd($myId);
            $limit                          =               10;
            if (isset($request->limit) && !empty($request->limit)) {

                $limit                      =               $request->limit;
            }
            $inbox                          =               AiThread::where(function ($query) use ($myId) {
                $query->where('sender_id', $myId)
                    ->orWhere('receiver_id', $myId);
            })->first();
            if (isset($inbox) && !empty($inbox)) {

                $inboxId                =             $inbox->id;
                $messages               =             AiMessage::with(['sender' => function ($query) {

                    $query->select('id', 'name', 'user_name', 'profile');
                }])->where(function ($query) use ($myId) {

                    $query->where('is_user1_trash', '!=', $myId)
                        ->orWhere('is_user2_trash', '!=', $myId);
                })->where('inbox_id', $inboxId)->orderByDesc('id')->simplePaginate($limit);

                if ($messages[0]) {
                    $messages->each(function ($result) {

                        if (isset($result->sender) && !empty($result->sender)) {

                            if (isset($result->sender->profile) && !empty($result->sender->profile)) {

                                $result->sender->profile        =   $this->addBaseInImage($result->sender->profile);
                            }
                        }
                        if (isset($result->media) && !empty($result->media)) {

                            $result->media                      =   $this->addBaseInImage($result->media);
                        }
                        $result->time_ago                       =   time_elapsed_string($result->created);
                    });
                }
                $messages->setCollection($messages->getCollection()->reverse()->values());
                return $this->sendResponse($messages, "Chat History.", 200);
            } else {
                $inbox                          =               AiThread::where(function ($query) use ($myId) {
                    $query->where('sender_id', $myId)
                        ->orWhere('receiver_id', $myId);
                })->simplePaginate($limit);
                return $this->sendResponse($inbox, "Chat History.", 200);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "AICHATHISTORy" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator                          =       Validator::make($request->all(), [
                'messages' => 'required|json'
            ]);
            if ($validator->fails()) {
                // Handle validation failure
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            } else {
                $messages                         =         json_decode($request->messages, true);

                // Validate each message
                $validator = Validator::make($messages, [
                    '*.id' => 'required|string',
                    '*.message' => 'required|string',
                    '*.participant' => 'required|string',
                    '*.media'=>"nullable|"
                ]);

                if ($validator->fails()) {
                    return $this->sendResponsewithoutData($validator->errors()->first(), 422);
                }
                $myId                               =   Auth::id();
                $ai                                 =   User::select('id')->where(['role' => 4])->first();
                if (isset($ai) && !empty($ai)) {

                    $aiId                          =    $ai->id;
                } else {
                    //create ai
                    $addAi                         =    new User();
                    $addAi->name                   =    "AI";
                    $addAi->user_name              =    "AI";
                    $addAi->profile                =    'app_icon/ai.png';
                    $addAi->role                   =    4;
                    $addAi->save();
                    $aiId                          =   $addAi->id;
                }
                #-------- check thread is exist or not
                $inbox                              =               AiThread::where(function ($query) use ($myId) {
                    $query->where('sender_id', $myId)
                        ->orWhere('receiver_id', $myId);
                })->first();

                if (isset($inbox) && !empty($inbox)) {
                    $threadId                     =         $inbox->id;
                } else {                                          // create chat thread

                    $newThread                    =           new AiThread();
                    $newThread->sender_id         =           $myId;
                    $newThread->receiver_id       =           $aiId;

                    $newThread->save();
                    $threadId                     =          $newThread->id;
                }
                if (isset($threadId) && !empty($threadId)) {
                    $messages                         =         $request->messages;
                    $messages                         =         json_decode($messages, true);
                    if (isset($messages) && !empty($messages)) {
                        foreach ($messages as $message) {

                            $senderId                 =     ($message['participant'] == "user") ? $myId : $aiId;
                            AiMessage::create(['sender_id' => $senderId, 'message' => $message['message'], 'inbox_id' => $threadId,]);
                        }
                    }
                }
                DB::commit();
                return $this->sendResponsewithoutData(trans('message.saved_successfully'), 200);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "storeAiChat" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //




    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }




    public function chatLogs(Request $request)
    {
        try {
            $myId = Auth::id();

            $inbox = AiThread::select('id')->where(function ($query) use ($myId) {
                $query->where('sender_id', $myId)
                    ->orWhere('receiver_id', $myId);
            })->first();
            $limit = $request->get('limit', 10);

            if ($inbox) {

                $notifications = AiMessage::where('inbox_id', $inbox->id)
                    ->orderBy('created_at', 'desc')->with(['sender' => function ($query) {
                        $query->select('id', 'name', 'user_name', 'profile');
                    }])
                    ->paginate($limit);

                $groupedNotifications      = $notifications->getCollection()->groupBy(function ($date) {

                    if (isset($date->sender) && !empty($date->sender->profile)) {

                        $date->sender->profile = $this->addBaseInImage($date->sender->profile);
                    }

                    $notificationDate          = Carbon::parse($date->created_at)->startOfDay();
                    $today                     = Carbon::now()->startOfDay();
                    $yesterday                 = Carbon::yesterday()->startOfDay();
                    $startOfWeek               = Carbon::now()->startOfWeek();
                    $startOfLastWeek           = Carbon::now()->subWeek()->startOfWeek();
                    $endOfLastWeek             = Carbon::now()->subWeek()->endOfWeek();

                    if ($notificationDate->equalTo($today)) {
                        return 'Today';
                    } elseif ($notificationDate->equalTo($yesterday)) {
                        return 'Yesterday';
                    } elseif ($notificationDate->between($startOfWeek, $today)) {
                        return 'This Week';
                    } elseif ($notificationDate->between($startOfLastWeek, $endOfLastWeek)) {
                        return 'Last Week';
                    } else {
                        return 'Older';
                    }
                });

                $responseData = $groupedNotifications->map(function ($notificationsGroup, $date) {
                    return [
                        'message_on' => $date,
                        'message' => $notificationsGroup->map(function ($notification) {
                            return [
                                'id' => $notification->id,
                                'sender_id' => $notification->sender_id,
                                'message_type' => $notification->message_type,
                                'is_user1_trash' => $notification->is_user1_trash,
                                'is_user2_trash' => $notification->is_user2_trash,
                                'message' => $notification->message,
                                'mesage_type' => $notification->message_type,
                                'media' => (isset($notification->media) && !empty($notification->media))?$this->addBaseInImage($notification->media):null,
                                'created_at' => $notification->created_at,
                                'updated_at' => $notification->updated_at,
                                'time_ago' => time_elapsed_string($notification->created_at),
                                'sender' => $notification->sender,
                            ];
                        })
                    ];
                })->values(); // Ensure we return an indexed array, not associative

                $data = [
                    'current_page' => $notifications->currentPage(),
                    'data' => $responseData,
                    'first_page_url' => $notifications->url(1),
                    'from' => $notifications->firstItem(),
                    'next_page_url' => $notifications->nextPageUrl(),
                    'path' => $notifications->path(),
                    'per_page' => $notifications->perPage(),
                    'prev_page_url' => $notifications->previousPageUrl(),
                    'to' => $notifications->lastItem(),
                ];
                return $this->sendResponse($data, trans('message.chat_logs'), 200);
            } else {

                $inbox = AiThread::select('id')->where(function ($query) use ($myId) {
                    $query->where('sender_id', $myId)
                        ->orWhere('receiver_id', $myId);
                })->simplePaginate(1);
                return $this->sendResponse($inbox, trans('message.chat_logs'), 200);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "aiChatLogs" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #----------- PIN/UNPIN MESSAGE -------------------_#

    public function pinUnpinMessage(Request $request)
    {
        DB::beginTransaction();
        try {

            $validator                          =       Validator::make($request->all(), [
                'message_id' => 'required|integer|exists:ai_messages,id'
            ]);
            if ($validator->fails()) {
                // Handle validation failure
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            } else {

                $aiMessage                      = AiMessage::where('id', $request->message_id)
                    ->whereHas('thread', function ($query) {
                        $query->whereNotNull('id'); // Assuming 'id' should be present
                    })
                    ->first();
                if (isset($aiMessage) && !empty($aiMessage)) {

                    $authId                     =    Auth::id();
                    $pinnedMessage              =    PinnedMessage::where(['message_id' => $request->message_id, 'user_id' => $authId])->first();
                    if (isset($pinnedMessage) && !empty($pinnedMessage)) {
                        $pinnedMessage->delete();
                        $message                =    trans('message.unpinned_message');
                    } else {

                        $pinMessage             =    new PinnedMessage();
                        $pinMessage->message_id =    $request->message_id;
                        $pinMessage->user_id    =    $authId;
                        $pinMessage->save();
                        $message                =   trans('message.pinned_message');
                    }
                    DB::commit();
                    return $this->sendResponsewithoutData($message, 200);
                } else {

                    return $this->sendResponsewithoutData(trans('message.invalid_message'), 400);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "pinUnpin" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #----------- PIN/UNPIN MESSAGE -------------------_#



    #-------------------- G E T       A L L     P I N N E D         M E S S A G E   ---------------------_#
    public function pinnedMessage(Request $request)
    {

        try {

            $authId             =   Auth::id();
            $limit              =   $request->limit ?? 10;
            $pinnedMessage      =   PinnedMessage::where('user_id', $authId)->with(['message', 'message.sender' => function ($query) {

                $query->select('id', 'name', 'user_name', 'profile');

            }])->orderByDesc('created_at')->simplePaginate($limit);

            if (isset($pinnedMessage[0]) && !empty($pinnedMessage[0])) {
                $pinnedMessage->each(function ($query) {

                    if (isset($query->message) && !empty($query->message->sender)) {

                        if (isset($query->message->sender->profile) && !empty($query->message->sender->profile)) {

                            $query->message->sender->profile =  $this->addBaseInImage($query->message->sender->profile);
                        }
                    }
                });
            }
            return $this->sendResponse($pinnedMessage, trans('message.all_pinned_message'), 200);
        } catch (Exception $e) {

            Log::error('Error caught: "getPinnedMessage" ' . $e->getMessage());

            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------- G E T       A L L     P I N N E D         M E S S A G E   ---------------------_#



    #-------------  S T O R E       M E S S A G E --------------------#
    public function storeMessage(Request $request){

        DB::beginTransaction();

        try {

            $validator                          =       Validator::make($request->all(), [
                'message' => 'required',
                'participant' => 'required|string|in:user,system',
                'media'=>"nullable|mimes:jpg,jpeg,png,bmp,tiff"
            ]);
            if ($validator->fails()) {
                // Handle validation failure
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);

            } else {
    
                $myId                               =   Auth::id();
                $ai                                 =   User::select('id')->where(['role' => 4])->first();
                if (isset($ai) && !empty($ai)) {

                    $aiId                          =    $ai->id;

                } else {
                    //create ai
                    $addAi                         =    new User();
                    $addAi->name                   =    "AI";
                    $addAi->user_name              =    "AI";
                    $addAi->profile                =    'app_icon/ai.png';
                    $addAi->role                   =    4;
                    $addAi->save();
                    $aiId                          =   $addAi->id;
                }
                #-------- check thread is exist or not
                $inbox                              =               AiThread::where(function ($query) use ($myId) {
                    $query->where('sender_id', $myId)
                        ->orWhere('receiver_id', $myId);
                })->first();

                if (isset($inbox) && !empty($inbox)) {

                    $threadId                     =         $inbox->id;

                } else {                                          // create chat thread

                    $newThread                    =           new AiThread();
                    $newThread->sender_id         =           $myId;
                    $newThread->receiver_id       =           $aiId;
                    $newThread->save();
                    $threadId                     =          $newThread->id;
                }
                if (isset($threadId) && !empty($threadId)) {

                    $senderId                     =         ($request->participant == "user") ? $myId : $aiId;
                    $message                      =         ['sender_id' => $senderId,'participant'=>$request->participant, 'message' => $request->message, 'inbox_id' => $threadId];
                    if(isset($request->media) && !empty($request->media)){

                        $media                     =       upload_file($request->media,'ai_chat');
                        $message['media']          =       $media;
                        $message['message_type']   =       2;
                    }
                    AiMessage::create($message);
                }
                DB::commit();
                return $this->sendResponsewithoutData(trans('message.saved_successfully'), 200);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "storeAiChat" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    
    #-------------  S T O R E       M E S S A G E --------------------#

    #------------  C H A T      I N S I G H T      -------------------#

    // public function insights(Request $request){

    //     $validate= Validator::make($request->all(), [
           
    //         'start_date' => 'required|date_format:Y-m-d',
    //         'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
    //     ]);

    //     if($validate->fails()){

    //         return $this->sendResponsewithoutData($validate->errors()->first(), 422);
    //     }
       
    //     $myId                        =       Auth::id();
    //     $inbox                          =       AiThread::where(function ($query) use ($myId) {
    //         $query->where('sender_id', $myId)
    //             ->orWhere('receiver_id', $myId);
    //     })->first();

    //     if (isset($inbox) && !empty($inbox)) {

    //         $inboxId                =             $inbox->id;
    //         $messages               =             AiMessage::with(['sender' => function ($query) {

    //             $query->select('id', 'name', 'user_name', 'profile');
    //         }])->where(function ($query) use ($myId) {

    //             $query->where('is_user1_trash', '!=', $myId)
    //                 ->orWhere('is_user2_trash', '!=', $myId);
    //         })->where('inbox_id', $inboxId)->whereBetween(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"), [$request->start_date, $request->end_date])->get();

    //         $chatData = [];

    //         foreach ($messages as $message) {

    //             $chatData[] = [
    //                 'sender' => $message->participant,
    //                 'media' => (isset($message->media) && !empty($message->media))?$this->addBaseInImage($message->media):null,
    //                 'message' => $message->message_content,
    //                 'timestamp' => $message->created_at->toDateTimeString(),
    //             ];
    //         }
    //             array_push($chatData, 
    //                     array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= insights and top sugestions"),
    //                     array("text" => "provide result in json format"),
    //                     array("text" => "give the keys values in array format, even if only one key is available. and give minimum  five points in each key"),
    //                     array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the journal entries'"),
    //                     array("text" => "format must be in this format => \n{\n  \"insights\": [\n    \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\",\n    \"Exercise has a noticeable positive impact on blood sugar management.\",\n    \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\",\n    \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\",\n    \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\"\n  ],\n  \"suggestions\": [\n    \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\",\n    \"Incorporate regular physical activity, such as daily walks, into the routine.\",\n    \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\",\n    \"Monitor blood sugar closely during illness and seek medical attention if necessary.\",\n    \"Actively engage in diabetes support groups to learn from and share experiences with others.\"\n  ]\n}\n"),
    //                 );
    //             $insight = $this->generateReportAI($chatData, 3);
    //              #insert in database if success
    //             if(isset($insight['status']) && $insight['status'] == 200){

    //                 $newReport              = new JournalReport;
    //                 // $newReport->journal_id  = $insight->id;
    //                 $newReport->start_date  = $request->start_date;
    //                 $newReport->end_date    = $request->end_date;
    //                 $newReport->report      = json_encode($report['data']);
    //                 $newReport->type        = 3;
    //                 $newReport->save();
    //                 return response()->json($insight, 200);
    //             }
    //             else{

    //                 return response()->json($insight, 400);
    //             }
    
            
    
    //         }

    //       //  $instructions = "Hey Gemini, when responding to user queries, let's keep our language clear and relatable, avoiding complex terms or medical jargon. Ensure our responses resonate with the experiences and perspectives of the Black community. Let's maintain a focus on health-related issues and provide empathetic support. Encourage others to share insights, advice, and personal anecdotes to support the original poster.";

    //         // $instructionsToGemini = <<<INSTRUCTIONS
    //         // Hey Gemini, when assisting users, ensure responses align with the provided instructions. 
    //         // Always prioritize accuracy, privacy, and cultural sensitivity. Refer users to healthcare 
    //         // professionals for personalized advice and avoid speculative or unverified information. 
    //         // Let's create a supportive and informative environment for users seeking health-related guidance.
    //         // INSTRUCTIONS;
    //     }
    
    // }

    function convertIntoJson($text)
    {
        // $text="```json\n{\n  \"insights\": [\n    \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\",\n    \"Exercise has a noticeable positive impact on blood sugar management.\",\n    \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\",\n    \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\",\n    \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\"\n  ],\n  \"suggestions\": [\n    \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\",\n    \"Incorporate regular physical activity, such as daily walks, into the routine.\",\n    \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\",\n    \"Monitor blood sugar closely during illness and seek medical attention if necessary.\",\n    \"Actively engage in diabetes support groups to learn from and share experiences with others.\"\n  ]\n}\n```";
        $text = str_replace('```JSON', '', $text);
        $text = str_replace('```json', '', $text);
        $text = str_replace('```', '', $text);

        return $text;
    }


    public function insights(Request $request){
        
           
        $validate = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);
    
        if($validate->fails()){

            return $this->sendResponsewithoutData($validate->errors()->first(), 422);
        }
    
        $myId = Auth::id();
        $inbox = AiThread::where(function ($query) use ($myId) {
            $query->where('sender_id', $myId)
                ->orWhere('receiver_id', $myId);
        })->first();

        if (isset($inbox) && !empty($inbox)) {
            
            $inboxId = $inbox->id;
            // DB::enableQueryLog();
            $messages = AiMessage::with(['sender' => function ($query) {
                $query->select('id', 'name', 'user_name', 'profile');
            }])->where(function ($query) use ($myId) {
                $query->where('is_user1_trash', '!=', $myId)
                    ->orWhere('is_user2_trash', '!=', $myId);
            })->where('inbox_id', $inboxId)->whereBetween(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"), [$request->start_date, $request->end_date])->get();
            $chatData = [];

            foreach ($messages as $message) {

                $date=Carbon::parse($message->created_at)->format('Y-m-d H:i A');
                $media  =   (isset($message->media) && !empty($message->media)) ? $this->addBaseInImage($message->media) : null;
                $details= "Date:".$date;
                $details.= ", Sender: ".$message->participant;
                $details.= ", Message: ".$message->message;
                $details.= ", Media link: ".$media;
                array_push($chatData, ['text'=> $details]);
               
            }
            array_push($chatData, 
            array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= insights and top sugestions"),
            array("text" => "provide result in json format"),
            array("text" => "give the keys values in array format, even if only one key is available. and give minimum  five points in each key"),
            array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the conversation'"),
            array("text" => "format must be in this format => \n{\n  \"insights\": [\n    \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\",\n    \"Exercise has a noticeable positive impact on blood sugar management.\",\n    \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\",\n    \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\",\n    \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\"\n  ],\n  \"suggestions\": [\n    \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\",\n    \"Incorporate regular physical activity, such as daily walks, into the routine.\",\n    \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\",\n    \"Monitor blood sugar closely during illness and seek medical attention if necessary.\",\n    \"Actively engage in diabetes support groups to learn from and share experiences with others.\"\n  ]\n}\n"),
        );
            $insight = $this->generateReportAI($chatData, 3);
            if(isset($insight['status']) && $insight['status'] == 200){
                $newReport = new JournalReport;
                $newReport->start_date = $request->start_date;
                $newReport->end_date = $request->end_date;
                $newReport->report = json_encode($insight['data']);
                $newReport->type = 3;
                $newReport->save();
                return response()->json($insight, 200);
            }
            else{

                return response()->json($insight, 400);
            }
        }
    }
}
