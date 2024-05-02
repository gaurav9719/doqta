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
use App\Models\Journal;
use GuzzleHttp\Psr7\Request;

/**
 * Class JournalService.
 */
class JournalService extends BaseController
{


    public function journals($authId,$limit,$request){

        try {
            // Default limit if not provided
            $limit        = $limit ?? 10; 
            // Eager load relationships with selected fields
            $userJournals = Journal::with(['color:id,hex_code,opacity', 'topic:id,name'])
                ->where('user_id', $authId)
                ->where('is_active', 1)
                ->when($request->filled('search'), function ($query) use ($request) {
                    // Apply search filter if 'search' parameter is provided
                    $searchTerm = '%' . $request->input('search') . '%';

                    $query->where('title', 'like', $searchTerm)

                    ->orWhereHas('topic', function ($topicQuery) use ($searchTerm) {

                        $topicQuery->where('name', 'like', $searchTerm);
                        
                    });
                })
                ->orderByRaw('FIELD(is_favorite,1) DESC')
                ->orderByDesc('id')
                ->simplePaginate($limit);

            return $this->sendResponse($userJournals, trans("message.journals"), 200);

        } catch (Exception $e) {

            Log::error('Error caught: "getJournalservice" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);

        }
    }

    


    public function journalEntries($authId,$journalId,$limit,$request,$id=""){

        try {
            // Default limit if not provided
          
            $limit          =   $limit ?? 10; 
           
            $journalEntry   =   JournalEntry::with(['feeling_types'=>function($q){

                $q->select('id','journal_entry_id','feeling_type');
            },
            'feeling_types.feeling_type'=>function($q){

                $q->select('id','name');
            }
            ,'feeling'=>function($q){
                $q->select('id','name');

            },'symptom'=>function($q){

                $q->select('id','symptom_id','journal_entry_id');
            },
            'symptom.journalSymtom'=>function($q){
                $q->select('id','symptom');
            }]);
            
            if(isset($id) && !empty($id)){

                $journalEntry = $journalEntry->where(['id'=>$id,'is_active'=>1,'user_id'=>$authId]);

            }else{

                $journalEntry = $journalEntry->where(['journal_id'=>$journalId,'is_active'=>1,'user_id'=>$authId]);

            }
            $journalEntry = $journalEntry->orderByRaw('FIELD(is_favorite,1) DESC')

            ->orderByDesc('id')
            ->simplePaginate($limit);
            // dd(DB::getQueryLog());
            $journalEntry->each(function($journal){
                if($journal->media){

                    $journal->media     =   asset('storage/'.$journal->media);    
                }

                if($journal->audio){

                    $journal->audio     =   asset('storage/'.$journal->audio);    
                }
            });
            return $this->sendResponse($journalEntry, trans("message.journals"), 200);

        } catch (Exception $e) {

            Log::error('Error caught: "getJournalservice" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);

        }
    }

















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

                    // $getJournal->entry_time =   Carbon::parse($getJournal->created_at)->diffForHumans();
                    $getJournal->entry_time =   time_elapsed_string($getJournal->created_at);
                

                    
                    $getJournal->symptom->each(function ($symptom) {

                        $symptom->name = $symptom->symptom->symptom ?? '';
                    });
                
                    // Handling media and audio attributes
                    $getJournal->media = $getJournal->media ? asset('storage/'.$getJournal->media) : null;
                    $getJournal->audio = $getJournal->audio ? asset('storage/'.$getJournal->audio) : null;
                });
                

            }

            return $this->sendResponse($journal, ($message)?$message:trans("message.journals"), 200);
    
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


    #------------------       E D I T    J O U R N A L          --------------#
    public function UpdateJournalThread($request,$authId){

        DB::beginTransaction();
        try {

            $journal        =   Journal::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->first();
            if ($request->filled('title')) {

                $journal->title = $request->title;
            }
            // Always set 'user_id' as it's required
            $journal->user_id   = $authId;
            // Check and set 'topic_id' if provided
            if ($request->filled('topic')) {

                $journal->topic_id = $request->topic;
            }

            // Check and set 'writing_for' if provided
            if ($request->filled('writing_for')) {
                $journal->writing_for = $request->writing_for;
            }

            // Check and set 'color' if provided
            if ($request->filled('color')) {
                $journal->color = $request->color;
            }
            // Save the journal entry
            $journal->save();
            DB::commit();
            // Return journals with optional limit
            $limit      =   10;
            return $this->journals($authId, $limit, $request);

        } catch (Exception $e) {
           
            DB::rollBack();
            Log::error('Error caught: "UpdateJournalThread" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------------       E D I T    J O U R N A L          --------------#


    #----------------------- U P D A T E         J O U R N A L       E N T R Y ---------------#

    public function updateJournalEntry($request,$authId){

        DB::beginTransaction();

        try {
          
            $journalEntry               =    JournalEntry::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->first();
    
            if ($request->filled('content')) {
                $journalEntry->content = $request->content;
            }
    
            // Check and set 'feeling_id' if provided
            if ($request->filled('feeling')) {

                $journalEntry->feeling_id = $request->feeling;
            }
    
            // Check and set 'pain' if provided
            if ($request->filled('pain')) {

                $journalEntry->pain = $request->pain;
            }
           
            // Add optional fields if provided in the request
            if ($request->filled('link')) {
                $journalEntry['link'] = $request->link;
            }

            if ($request->hasFile('media')) {

                $media                  = upload_file($request->file('media'), 'journals');
                $journalEntry['media']  = $media;

            }

            if ($request->hasFile('audio')) {
                $audio = upload_file($request->file('audio'), 'journals/audio');
                $journalEntry['audio'] = $audio;
            }

            $journalEntry->save();

            if (isset($request->feeling_type[0]) && !empty($request->feeling_type[0])) {

                $feelings = $request->feeling_type;

                foreach ($feelings as $feeling) {

                    JournalsFeeling::updateOrCreate(

                        ['journal_entry_id' => $request->id, 'feeling_type' => $feeling],
                        ['is_active' => 1]

                    );
                }
                JournalsFeeling::where('journal_entry_id', $request->id)->whereNotIn('feeling_type', $feelings)->delete();
                // DB::commit();
            }

            // Handle symptoms associated with the journal entry
            if (isset($request->symptom[0]) && !empty($request->symptom[0])) {
                $symptoms = $request->symptom;
                foreach ($symptoms as $symptom) {
                    JournalSymptoms::updateOrCreate(
                        ['journal_entry_id' => $request->id, 'symptom_id' => $symptom],
                        ['is_active' => 1]
                    );
                }
                JournalSymptoms::where('journal_entry_id', $request->id)->whereNotIn('symptom_id', $symptoms)->delete();
                // DB::commit();
            }

            // Handle extra symptoms associated with the journal entry
            if (isset($request->extra_symptom[0]) && !empty($request->extra_symptom[0])) {

                $extraSymptoms = $request->extra_symptom;

                foreach ($extraSymptoms as $extraSymptom) {

                    $physicalSymptom = PhysicalSymptom::where(['symptom' => $extraSymptom, 'is_active' => 1, 'user_id' => null])->first();

                    if (!$physicalSymptom) {
                        $physicalSymptom = PhysicalSymptom::create([
                            'symptom' => $extraSymptom,
                            'is_active' => 1,
                            'user_id' => $authId,
                        ]);
                        // DB::commit();
                    }

                    JournalSymptoms::updateOrCreate(
                        ['journal_entry_id' => $request->id, 'symptom_id' => $physicalSymptom->id],
                        ['is_active' => 1]
                    );
                   
                }
            }
            DB::commit();
            // Retrieve journals for the user after successful entry creation
            $limit = 10;
            return $this->journalEntries($authId, $journalEntry->journal_id, $limit, $request,$request->id);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught in "UpdatejournalEntry" method: ' . $e->getMessage());
            return $this->sendError('Failed to create journal entry.', [], 400);
        }
    }
    #--------------------------------   E N D    ---------------------------------------------#



    




}
