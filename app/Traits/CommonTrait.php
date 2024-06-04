<?php

namespace App\Traits;

use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\User;
use App\Models\Inbox;
use App\Models\Journal;
use App\Models\Message;
use App\Models\JournalTopic;
use App\Models\PhysicalSymptom;
use App\Models\SharePost;
use App\Models\UserFollower;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use GeminiAPI\Laravel\Facades\Gemini;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\Parts\ImagePart;
use App\Traits\postCommentLikeCount;

trait CommonTrait
{
    protected $count = 0;



    use postCommentLikeCount;
    public function createSymtomByTopic($journalId, $topic)
    {
        try {
            //code...
            $journal                            =   Journal::where('id', $journalId)->exists();
            log::info("joural:" . $journal);
            if ($journal) {
                log::info("jouralok:");

                $getTopic                          =   JournalTopic::where('id', $topic)->first();

                if (isset($getTopic) && !empty($getTopic)) {
                    log::info("topic:");
                    $topicName                  =   $getTopic->name;
                    // DB::enableQueryLog();
                    $isExistopic                =   JournalTopic::where('name', $topicName)->where('id', '!=', $topic)->whereNull('parent_id')->first();
                    log::info(DB::getQueryLog());
                    if (isset($isExistopic) && !empty($isExistopic)) {
                        log::info("exist topic:" . $isExistopic);
                        $getTopic->parent_id       = $isExistopic->id;
                        $getTopic->save();
                    } else {
                        log::info("ai call topic:");
                        $result  = Gemini::generateText("Provide the list of different types of pain or symptoms associated with this health " . $topicName . " topic  with the 'symptoms' key. Only include the names of the symptoms with related this topic, with a maximum of 20 with each symptoms name upto 20 characters only in JSON object output without extra spaces and quotes around the keys and without any text outside the symptoms key:");
                        log::info("ai call result:" . $result);

                        if (strpos($result, '"""') !== false) {

                            $jsonString          =   str_replace('"""', '', $result); // Removes all spaces

                        } else {

                            $jsonString         =   $result;
                        }

                        $jsonData               =   json_decode($jsonString, true);
                        if (empty($jsonData)) {

                            $this->createSymtomByTopic($journalId, $topic);
                        }

                        foreach ($jsonData['symptoms'] as $value) {

                            $addPhysical                =   new PhysicalSymptom();
                            $addPhysical->symptom       =   $value;
                            $addPhysical->topic_id      =   $topic;
                            $addPhysical->type          =   1;
                            $addPhysical->save();
                        }
                    }
                }
            }
        } catch (Exception $e) {

            log::error($e->getMessage());
        }
    }
    #---------------- C R E A T E       B Y     D E F A U L T       J O U R N A L S ------------------------#

    public function createByDefaultJournal($userId)
    {
        $journal                     =       JournalTopic::where('name', "Health and Wellness")->first();
        if (isset($journal) && !empty($journal)) {

            $topicId                 =      $journal->id;
        } else {

            $journal                =       new JournalTopic();
            $journal->name          =       "Health and Wellness";
            $journal->type          =       1;
            $journal->icon          =       "interest/health.png";
            $journal->save();
            $topicId                =       $journal->id;
            //add and get id and call AI
        }
        $addJournal                 =      Journal::create([
            'title' => "General Health",
            'user_id' => $userId,
            'topic_id' => $topicId,
            'writing_for' => "own",
            'color' => 1,
            'entry_date' => Carbon::now(),
        ]);
    }

    #-----------------  S H A R E        P O S T        I N      C H A T    ------------------#
    public function sharePostInChat($request, $myId, $recievers)
    {

        DB::beginTransaction();
        try {

            $userIDs                            =               explode(',', $recievers);

            if (isset($userIDs) && !empty($userIDs)) {

                $message_type                   =               5;
                $postData                       =               Post::where(['id' => $request->post_id, 'is_active' => 1])->first();
                $postTitle                      =               $postData->title;
                foreach ($userIDs as $reciever) {

                    $message                    =               Inbox::where(function ($query) use ($myId, $reciever) {

                        $query->where(function ($subQuery) use ($myId, $reciever) {

                            $subQuery->where('sender_id', $myId)
                                ->where('receiver_id', $reciever);
                        })->orWhere(function ($subQuery) use ($myId, $reciever) {

                            $subQuery->where('receiver_id', $myId)
                                ->where('sender_id', $reciever);
                        });
                    })->first();

                    if (empty($message)) {
                        // create new thread
                        $message                       =               new Inbox();
                        $message->sender_id            =               $myId;
                        $message->receiver_id          =               $reciever;
                        $message->save();
                    }

                    $inboxId                            =               $message->id;
                    #----------- A D D      D A T A         T O         M E S S A G E       T A B L E -----------#
                    $sendMessage                        =                new Message();
                    $sendMessage->inbox_id              =                $inboxId;
                    $sendMessage->sender_id             =                $myId;
                    $sendMessage->message               =                $postTitle;
                    $sendMessage->message_type          =                $message_type;
                    $sendMessage->post_id               =                $postData->id;
                    $sendMessage->save();
                    $lastMessageId                      =               $sendMessage->id;
                    Inbox::where('id', $inboxId)->update(['message_id' => $lastMessageId]);
                    #----- share Post with user -----#
                    $isCreated                           =              SharePost::updateOrCreate(['user_id' => $myId, 'send_to' => $reciever, 'post_id' => $postData->id], ['message_id' => $lastMessageId]);
                    if ($isCreated->wasRecentlyCreated) {
                        // The record was just created
                        increment('posts', ['id' => $postData->id], 'share_count', 1);
                    }
                    #----- share Post with user -----#
                    #send notification
                    $receiver                           =               User::find($reciever);
                    $sender                             =               User::find($myId);
                    $message                            =               "New message from " . $sender->name;
                    $data                               =               ["message" => $message, 'notification_type' => trans('notification_message.send_message_type')];
                    sendPushNotificationNew($sender, $receiver, $data);
                    DB::commit();
                }
                return $this->sendResponsewithoutData(trans('message.shared_successfully'), 200);
            } else {

                return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 403);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "sharePostWithMessage" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }
    #------------------- S H A R E      P O S T         I N     C H A T     ------------------#


    #------------------ S H A R E       P R O F I L E   I N     C H A T     ------------------#
    public function shareUserInChat($request, $myId, $recievers)
    {

        DB::beginTransaction();

        try {

            $userIDs                            =               explode(',', $recievers);

            if (isset($userIDs) && !empty($userIDs)) {

                $message_type                   =               5;
                $userData                       =               User::where(['id' => $request->user_id, 'is_active' => 1])->first();
                $userName                       =               "shared profile " . $userData->user_name;

                foreach ($userIDs as $reciever) {

                    $message                    =               Inbox::where(function ($query) use ($myId, $reciever) {

                        $query->where(function ($subQuery) use ($myId, $reciever) {

                            $subQuery->where('sender_id', $myId)
                                ->where('receiver_id', $reciever);
                        })->orWhere(function ($subQuery) use ($myId, $reciever) {

                            $subQuery->where('receiver_id', $myId)
                                ->where('sender_id', $reciever);
                        });
                    })->first();

                    if (empty($message)) {
                        // create new thread
                        $message                       =               new Inbox();
                        $message->sender_id            =               $myId;
                        $message->receiver_id          =               $reciever;
                        $message->save();
                    }
                    $inboxId                            =               $message->id;
                    #----------- A D D      D A T A         T O         M E S S A G E       T A B L E -----------#
                    $sendMessage                        =                new Message();
                    $sendMessage->inbox_id              =                $inboxId;
                    $sendMessage->sender_id             =                $myId;
                    $sendMessage->message               =                $userName;
                    $sendMessage->message_type          =                $message_type;
                    $sendMessage->user_id               =                $userData->id;
                    $sendMessage->save();
                    $lastMessageId                      =               $sendMessage->id;
                    Inbox::where('id', $inboxId)->update(['message_id' => $lastMessageId]);
                    #----- share Post with user -----#
                    #send notification
                    $receiver                           =               User::find($reciever);
                    $sender                             =               User::find($myId);
                    $message                            =               "New message from " . $sender->name;
                    $data                               =               ["message" => $message, 'notification_type' => trans('notification_message.send_message_type')];
                    sendPushNotificationNew($sender, $receiver, $data);
                    DB::commit();
                }
                return $this->sendResponsewithoutData(trans('message.shared_successfully'), 200);
            } else {

                return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 403);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "shareUserWithMessage" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }
    #------------------ S H A R E       P R O F I L E   I N     C H A T     ------------------#

    public function shareInChat($request, $myId, $recievers)
    {
        DB::beginTransaction();
        try {
            $userIDs = explode(',', $recievers);

            if (isset($userIDs) && !empty($userIDs)) {
                $messageContent = '';
                $relatedDataId = null;
                $type = $request->type;

                if ($type == 1) {
                    // Sharing a post
                    $message_type = 5;
                    $postData = Post::where(['id' => $request->post_id, 'is_active' => 1])->first();
                    $messageContent = $postData->title;
                    $relatedDataId = $postData->id;

                } elseif ($type == 2) {

                    $message_type = 6;
                    // Sharing a user profile
                    $userData = User::where(['id' => $request->user_id, 'is_active' => 1])->first();
                    $messageContent = "shared profile @". $userData->user_name;
                    $relatedDataId = $userData->id;
                }

                foreach ($userIDs as $reciever) {
                    $message = Inbox::where(function ($query) use ($myId, $reciever) {
                        $query->where(function ($subQuery) use ($myId, $reciever) {
                            $subQuery->where('sender_id', $myId)->where('receiver_id', $reciever);
                        })->orWhere(function ($subQuery) use ($myId, $reciever) {
                            $subQuery->where('receiver_id', $myId)->where('sender_id', $reciever);
                        });
                    })->first();

                    if (empty($message)) {
                        // Create new thread
                        $message = new Inbox();
                        $message->sender_id = $myId;
                        $message->receiver_id = $reciever;
                        $message->save();
                    }

                    $inboxId = $message->id;

                    // Add data to message table
                    $sendMessage = new Message();
                    $sendMessage->inbox_id = $inboxId;
                    $sendMessage->sender_id = $myId;
                    $sendMessage->message = $messageContent;
                    $sendMessage->message_type = $message_type;
                    if ($type == 1) {
                        $sendMessage->post_id = $relatedDataId;
                    } elseif ($type == 2) {
                        $sendMessage->user_id = $relatedDataId;
                    }
                    $sendMessage->save();

                    $lastMessageId = $sendMessage->id;
                    Inbox::where('id', $inboxId)->update(['message_id' => $lastMessageId]);

                    // Share post with user (if type is 1)
                    if ($type == 1) {

                        $isCreated = SharePost::updateOrCreate(
                            ['user_id' => $myId, 'send_to' => $reciever, 'post_id' => $relatedDataId],
                            ['message_id' => $lastMessageId]
                        );

                        if ($isCreated->wasRecentlyCreated) {

                            increment('posts', ['id' => $relatedDataId], 'share_count', 1);

                        }
                    }

                    // Send notification
                    $receiver = User::find($reciever);
                    $sender = User::find($myId);
                    $notificationMessage = "New message from " . $sender->name;
                    $data = ["message" => $notificationMessage, 'notification_type' => trans('notification_message.send_message_type')];
                    sendPushNotificationNew($sender, $receiver, $data);
                }

                DB::commit();
                return $this->sendResponsewithoutData(trans('message.shared_successfully'), 200);
            } else {

                return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 403);
                
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "shareInChat" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
    }












    public function getLastMessage($message_id, $myId)
    {

        $result             =             Message::with(['sender' => function ($query) {

            $query->select('id', 'name', 'profile');
        }, 'reply_to.sender' => function ($query) {

            $query->select('id', 'name', 'profile');
        }, 'post' => function ($query) {

            $query->select('id', 'title', 'parent_id', 'title', 'content', 'media_type', 'media_url');
        }])->where(function ($query) use ($myId) {

            $query->where('is_user1_trash', '!=', $myId)
                ->orWhere('is_user2_trash', '!=', $myId);
        })->where('id', $message_id)->first();

        if (isset($result) && !empty($result)) {

            // $result->time_ago         =              $result->created_at->diffForHumans();
            $result->time_ago                      =    time_elapsed_string($result->created_at);

            if (isset($result->sender) && !empty($result->sender)) {

                if (isset($result->sender->profile) && !empty($result->sender->profile)) {

                    $result->sender->profile        =   $this->addBaseInImage($result->sender->profile);
                }
            }

            if (isset($result->post) && !empty($result->post)) {

                if (isset($result->post->media_url) && !empty($result->post->media_url)) {

                    $result->post->media_url        =   $this->addBaseInImage($result->post->media_url);
                }
            }


            if (isset($result->media) && !empty($result->media)) {

                $result->media                      =   $this->addBaseInImage($result->media);
            }

            if (isset($result->media_thumbnail) && !empty($result->media_thumbnail)) {

                $result->media_thumbnail            =   $this->addBaseInImage($result->media_thumbnail);
            }

            if (isset($result->reply_to) && !empty($result->reply_to)) {

                if (isset($result->reply_to->media) && !empty($result->reply_to->media)) {

                    $result->reply_to->media        =    $this->addBaseInImage($result->reply_to->media);
                }

                if (isset($result->reply_to->media_thumbnail) && !empty($result->reply_to->media_thumbnail)) {

                    $result->reply_to->media_thumbnail        =   $this->addBaseInImage($result->reply_to->media_thumbnail);
                }

                if (isset($result->reply_to->sender) && !empty($result->reply_to->sender)) {

                    if (isset($result->reply_to->sender->profile) && !empty($result->reply_to->sender->profile)) {

                        $result->reply_to->sender->profile        =   $this->addBaseInImage($result->reply_to->sender->profile);
                    }
                }
            }
        }

        return $result;
    }


    public function isSupportSupporting($loginUser, $otherUserId)
    {

        $isSupporting               =   UserFollower::where(['user_id' => $otherUserId, 'follower_user_id' => $loginUser])->exists();
    }


    function generateReportAItraint($data, $type)
    {

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

                if ($type == 1 || $type == 3) {
                    if (isset($finalResponse['insights']) && isset($finalResponse['suggestions']) && count($finalResponse['insights']) > 0 && count($finalResponse['suggestions']) > 0) {
                        // return ($finalResponse['insights']);

                        return $finalResponse;
                    } else {

                        if (self::$count < 3) {
                            self::$count++;
                            $this->generateReportAItraint($data, $type);
                        }
                    }
                    return null;
                } elseif ($type == 2) {
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
            } catch (Exception $e) {
                Log::error('Error while creating journal report: ' . $e->getMessage());
                return [
                    'status' => 400,
                    "message" => "Exception Error",
                    'data' => $e->getLine()
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



    function generateReportAIChatTrait($data, $count = 1)
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
        // return $data;
        $guidelines = [
            [
                "text" => "System: You are now an insights generator that analyzes conversations between Doqta users and the Doqta AI medical companion chatbot. Your role is to identify key observations that can help users and their doctors better understand the user's health journey based on their chatbot interactions. When generating insights from these conversations, follow these guidelines:"
            ],
            [
                "text" => "Health Focus: Only provide insights from conversations pertaining to medical conditions, symptoms, treatments, advice, etc. Ignore any non-health related dialogue."
            ],
            [
                "text" => "User-Centric: Insights should be tailored to each individual user's unique experiences, concerns, and health needs expressed in their conversations."
            ],
            [
                "text" => "Simple Language: Explain insights using clear, easy-to-understand terminology. Avoid complex medical jargon unless defining a term. Maximize clarity and relatability."
            ],
            [
                "text" => "Cultural Competence: Incorporate culturally relevant contexts and phrasing that resonates with the Black community's healthcare experiences where applicable."
            ],
            [
                "text" => "Identify Patterns: Analyze conversations over time to detect patterns, progressions, regressions, themes, knowledge gaps, etc. related to symptoms, treatments, understanding, etc."
            ],
            [
                "text" => "Note Advice Relevance: Highlight areas where the chatbot's advice aligns with or contradicts the user's experiences, beliefs, or ability to follow recommendations."
            ],
            [
                "text" => "Surface Key Concerns: Bring attention to the user's primary health worries, challenges, or unresolved issues that may need further discussion with their doctor."
            ],
            [
                "text" => "Suggest Next Steps: Provide constructive recommendations for the user to explore with their doctor, such as additional clarification, tests, treatment options, lifestyle adjustments, etc."
            ],
            [
                "text" => "Empathetic Tone: Write with empathy, emotional intelligence, and a caring, supportive voice appropriate for a health companion."
            ],
            [
                "text" => "Be concise and write insights as bullets that are no longer than one sentence each."
            ]
        ];
        // Convert the PHP array to JSON
        $data = array(
            "system_instruction" => array("parts" => $guidelines),
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

            return [
                'status' => 400,
                "message" => "Exception Error",
                'data' => $error
            ];

            // echo "cURL Error: " . $error;
        } else {
            // Close cURL session
            curl_close($curl);

            $response = json_decode($response, true);
            try {

                if (isset($response['candidates']) && isset($response['candidates'][0]) && isset($response['candidates'][0]['content']) && isset($response['candidates'][0]['content']['parts']) && isset($response['candidates'][0]['content']['parts'][0]) && isset($response['candidates'][0]['content']['parts'][0]['text'])) {

                    $result = $response['candidates'][0]['content']['parts'][0]['text'];

                    $finalResponse = $this->convertIntoJson($result);
                    $finalResponse = json_decode($finalResponse, true);
                    // return $finalResponse;

                    if (isset($finalResponse['insights']) && isset($finalResponse['suggestions']) && count($finalResponse['insights']) > 0 && count($finalResponse['suggestions']) > 0) {
                        // $finalResponse['function_called_count'] = $count;
                        $finalResult = [
                            'status' => 200,
                            "message" => "Insights & Suggestions generated successfully",
                            'data' => $finalResponse
                        ];
                        return $finalResult;
                    } else {

                        return $this->generateReportAIChatTrait($data, $count + 1);
                    }
                } else {

                    return $this->generateReportAIChatTrait($data, $count + 1);
                }
            } catch (Exception $e) {
                Log::error('Error while creating journal report: ' . $e->getMessage());
                return [
                    'status' => 400,
                    "message" => "Exception Error",
                    'data' => $e->getMessage()
                ];
            }
        }
        // Close cURL session
    }
}
