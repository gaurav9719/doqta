<?php

namespace App\Services;
use App\Models\JournalEntry;
use App\Models\journalsFeeling;
use App\Models\Feeling;
use App\Models\PhysicalSymptom;
use App\Models\FeelingType;
use App\Models\journalSymptoms;
use App\Http\Controllers\Api\BaseController;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\JournalTopic;
use Carbon\Carbon;

/**
 * Class JournalService.
 */
class JournalService extends BaseController
{

    public function getJournal($userid,$id="",$limit="",$request="",$message=""){
        try {

            if(isset($id) && !empty($id)){

                $journal = JournalEntry::with(['feeling'=>function ($query){
                  
                    $query->select('id','feeling','name');
                },'feeling_types' => function($query) {

                        $query->select('id','journal_id' ,'feeling_type_id');
                    },
                    'symptom' => function($query) {

                        $query->select('id','journal_id' ,'symptom_id');
                    }
                ])
                ->where(['id' => $id, 'user_id' => $userid])
                ->first();
        
                if (!empty($journal)) {

                    foreach ($journal->feeling_types as $feeling) {

                        $feelingName=   FeelingType::select('name')->where('id',$feeling->feeling_type_id)->first();
                        
                        $feeling->name = $feelingName->name??'';
                    }
    
                    foreach ($journal->symptom as $symptom) {

                        $symptomName  =   PhysicalSymptom::select('symptom')->where('id',$symptom->symptom_id)->first();
                      
                        $symptom->name = $symptomName->symptom ?? '';
                    }

                    if(isset($journal->media) && !empty($journal->media)){

                        if (Storage::disk('public')->exists($journal->media)) {

                            $journal->media     =    asset('storage/'.$journal->media);

                        }else{

                            $journal->media     =    null;

                        }
                    }
                    if(isset($journal->audio) && !empty($journal->audio)){
                        if (Storage::disk('public')->exists($journal->audio)) {
                            $journal->audio     =    asset('storage/'.$journal->audio);
                        }else{
                            $journal->audio     =    null;
                        }
                    }
                }
            }else{          #----------  GET ALL USER  JOURNALS -----------#
    


                // $journal = JournalEntry::with(['feeling' => function ($query) {
                //     $query->select('id', 'feeling', 'name');

                // }, 'feeling_types' => function ($query) {

                //     $query->select('id', 'journal_id', 'feeling_type_id');

                // }, 'symptom' => function ($query) {

                //     $query->select('id', 'journal_id', 'symptom_id');
                // }])
                // ->when(isset($request['filter']) && !empty($request['filter']), function ($q) use ($request) {
                //     $q->where('title', 'like', '%' . $request['filter'] . '%')
                //         ->orWhereHas('topic', function ($q) use ($request) {
                //             $q->where('name', 'like', '%' . $request['filter'] . '%');
                //         });
                // })
                // ->where('user_id', $userid)
                // ->orderByDesc('id')
                // ->simplePaginate($limit);
        
                // if (!empty($journal)) {

                //     $journal->each(function ($getJournal) {
                        
                //         $topicName      =   JournalTopic::select('name')->where('id',$getJournal->topic_id)->first();

                //         $getJournal->topic_name =($topicName['name'])??"";

                //         foreach ($getJournal->feeling_types as $feeling) {

                //             $feelingName=   FeelingType::select('name')->where('id',$feeling->feeling_type_id)->first();
                            
                //             $feeling->name = $feelingName->name??'';
                //         }
        
                //         foreach ($getJournal->symptom as $symptom) {
    
                //             $symptomName  =   PhysicalSymptom::select('symptom')->where('id',$symptom->symptom_id)->first();
                          
                //             $symptom->name = $symptomName->symptom ?? '';
                //         }
    
                //         if(isset($getJournal->media) && !empty($getJournal->media)){
    
                //             if (Storage::disk('public')->exists($getJournal->media)) {
    
                //                 $getJournal->media     =    asset('storage/'.$getJournal->media);
                //             }else{
                //                 $getJournal->media     =    null;    
                //             }
                //         }
                //         if(isset($getJournal->audio) && !empty($getJournal->audio)){
                //             if (Storage::disk('public')->exists($getJournal->audio)) {
                //                 $getJournal->audio     =    asset('storage/'.$getJournal->audio);
                //             }else{
                //                 $getJournal->audio     =    null;
                //             }
                //         }
                //     });
                // }

                // new code
                $journal = JournalEntry::with([
                    'feeling:id,feeling,name',
                    'feeling_types:id,journal_id,feeling_type_id',
                    'symptom:id,journal_id,symptom_id',
                    'topic:id,name' // Assuming 'topic' is the relationship name
                ])
                ->when(isset($request['filter']) && !empty($request['filter']), function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request['filter'] . '%')
                        ->orWhereHas('topic', function ($q) use ($request) {
                            $q->where('name', 'like', '%' . $request['filter'] . '%');
                        });
                })
                ->where('user_id', $userid)
                ->orderByDesc('id')
                ->simplePaginate($limit);
                
                // Eager load necessary relationships to avoid N+1 problem
                // $journal->load(['feeling_types.feelingType', 'symptom.symptom']);
              
                // Iterate over each journal entry
                $journal->each(function ($getJournal) {
                    // Accessing 'name' attribute of 'topic' relationship directly without additional query
                
                    // Accessing 'feeling_types' and 'symptom' relationships directly without additional queries
                    $getJournal->feeling_types->each(function ($feeling) {

                        $feeling->name = $feeling->feelingType->name ?? '';
                    });

                    $getJournal->entry_time =   Carbon::parse($getJournal->created_at)->diffForHumans();
                
                    $getJournal->symptom->each(function ($symptom) {

                        $symptom->name = $symptom->symptom->symptom ?? '';
                    });
                
                    // Handling media and audio attributes
                    $getJournal->media = $getJournal->media ? asset('storage/'.$getJournal->media) : null;
                    $getJournal->audio = $getJournal->audio ? asset('storage/'.$getJournal->audio) : null;
                });
                

            }

            return $this->sendResponse($journal, ($message)?$message:trans("message.add_journals"), 200);
    
            // $requests->each(function ($groupRequest) {
        
            //     if(isset($groupRequest->myGroup) && !empty($groupRequest->myGroup)){
    
            //         $groupRequest->myGroup->cover_photo    =   asset('storage/'.$groupRequest->myGroup->cover_photo);
            //     }
    
            //     if(isset($groupRequest->requested_user) && !empty($groupRequest->requested_user)){
    
            //         if(isset($groupRequest->requested_user->profile) && !empty($groupRequest->requested_user->profile)){
    
            //             $groupRequest->requested_user->profile    =   asset('storage/'.$groupRequest->myGroup->profile);
            //         }
            //     }
            // });
            
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Error caught: "getJournalservice" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
