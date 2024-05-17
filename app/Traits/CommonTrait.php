<?php

namespace App\Traits;

use App\Models\Journal;
use App\Models\JournalTopic;
use App\Models\PhysicalSymptom;
use Carbon\Carbon;
use Exception;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Resources\Parts\ImagePart;
use GeminiAPI\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait CommonTrait
{
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
}
