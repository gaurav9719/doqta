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
    use postCommentLikeCount;
    public function createSymtomByTopic($journalId, $topic)
    {
        try {
            //code...
            $journal                            =   Journal::where('id', $journalId)->exists();
            log::info("joural:".$journal);
            if ($journal) {
                log::info("jouralok:");

                $getTopic                          =   JournalTopic::where('id', $topic)->first();

                if (isset($getTopic) && !empty($getTopic)) {
                    log::info("topic:");
                    $topicName                  =   $getTopic->name;
                    // DB::enableQueryLog();
                    $isExistopic                =   JournalTopic::where('name', $topicName)->where('id','!=',$topic)->whereNull('parent_id')->first();
                    log::info(DB::getQueryLog());
                    if (isset($isExistopic) && !empty($isExistopic)) {
                        log::info("exist topic:".$isExistopic);
                        $getTopic->parent_id       = $isExistopic->id;
                        $getTopic->save();

                    } else {
                        log::info("ai call topic:");
                        $result  = Gemini::generateText("Provide the list of different types of pain or symptoms associated with this health " . $topicName . " topic  with the 'symptoms' key. Only include the names of the symptoms with related this topic, with a maximum of 20 with each symptoms name upto 20 characters only in JSON object output without extra spaces and quotes around the keys and without any text outside the symptoms key:");
                        log::info("ai call result:".$result);

                        if (strpos($result, '"""') !== false) {

                            $jsonString          =   str_replace('"""', '', $result); // Removes all spaces

                        } else {

                            $jsonString         =   $result;
                        }

                        $jsonData               =   json_decode($jsonString, true);
                        if(empty($jsonData)){

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


    public function sharePostInChat($request,$myId,$recievers){

        DB::beginTransaction();
        try {
            
            $userIDs                            =               explode(',',$recievers);
          
            if(isset($userIDs) && !empty($userIDs)){
                
                $message_type                   =               5;
                $postData                       =               Post::where(['id'=>$request->post_id,'is_active'=>1])->first();
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
                    $isCreated                           =              SharePost::updateOrCreate(['user_id'=>$myId,'send_to'=>$reciever,'post_id'=>$postData->id],['message_id'=>$lastMessageId]);
                    if ($isCreated->wasRecentlyCreated) {
                        // The record was just created
                        increment('posts',['id'=>$postData->id],'share_count',1);
                    }
                    #----- share Post with user -----#
                    #send notification
                    $receiver                           =               User::find($reciever);
                    $sender                             =               User::find($myId);
                    $message                            =               "New message from " . $sender->name;
                    $data                               =               ["message"=> $message,'notification_type'=>trans('notification_message.send_message_type')];
                    sendPushNotificationNew($sender, $receiver,$data);
                    DB::commit();
                }
                return $this->sendResponsewithoutData(trans('message.shared_successfully'), 200);
            }else{

                return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 403);

            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "sharePostWithMessage" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 422);
        }
        
    }

    public function getLastMessage($message_id, $myId)
    {

        $result             =             Message::with(['sender' => function ($query) {

            $query->select('id', 'name', 'profile');
        }, 'reply_to.sender' => function ($query) {

            $query->select('id', 'name', 'profile');

        },'post'=>function($query){

            $query->select('id','title','parent_id','title','content','media_type','media_url');

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


    public function isSupportSupporting($loginUser,$otherUserId){

        $isSupporting               =   UserFollower::where(['user_id'=>$otherUserId , 'follower_user_id'=>$loginUser])->exists();
        


    }











}
