<?php

namespace App\Http\Controllers\Api\Journals;

use Exception;
use Carbon\Carbon;
use App\Models\Journal;
use App\Models\UserQuota;
use App\Traits\CommonTrait;
use App\Models\JournalEntry;
use App\Models\JournalTopic;
use Illuminate\Http\Request;
use App\Models\JournalReport;
use App\Rules\SymptomIsExist;
use App\Models\journalsFeeling;
use App\Models\journalSymptoms;
use App\Models\PhysicalSymptom;
use App\Services\JournalService;
use App\Rules\FeelingTypeIsExist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\JournalValidation;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use Egulias\EmailValidator\Result\Reason\EmptyReason;
use App\Http\Requests\Journal_entry\JournalEntry as JournalEntryValidation;

class JournalController extends BaseController
{
    use CommonTrait;
    /**
     * Display a listing of the resource.
     */
    protected $journal, $authId;
    #--------------  S I G N U P        P R O C E S S  ------------------------#
    public function __construct(JournalService $journal)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });
        $this->journal = $journal;
    }

    public function index(Request $request)
    {
        try {

            $limit = $request->filled('limit') ? (int) $request->input('limit') : 10;
            $userId = Auth::id();
            return $this->journal->journals($userId, $limit, $request);
        } catch (Exception $e) {

            Log::error('Error caught in "journals" method: ' . $e->getMessage());
            return $this->sendError('Failed to retrieve journals.', [], 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(JournalValidation $request)
    {
        DB::beginTransaction();

        try {

            $userId = Auth::id();

            if (empty($request->topic) && empty($request->other_topic)) {

                return $this->sendError("topic required", [], 400);
            }
            $topic = "";

            if (isset($request->topic) && !empty($request->topic)) {

                $topic = $request->topic;
            }

            $action = 0;
            if (isset($request->other_topic) && !empty($request->other_topic)) {

                $topicString = $request->other_topic;

                $isExist = JournalTopic::where('name', $topicString)->where(function ($query) use ($userId) {

                    $query->whereNull('user_id')->orWhere('user_id', $userId);
                })->first();
                if (empty($isExist)) {

                    $addTopic = new JournalTopic();
                    $addTopic->name = $topicString;
                    $addTopic->icon = 'interest/other.png';
                    $addTopic->user_id = $userId;
                    $addTopic->type = 3; //user_defined
                    $addTopic->save();
                    $topic = $addTopic->id;
                    $action = 1;
                } else {

                    $topic = $isExist->id;
                }
                //we need to add topic id
            }
            $addJournal = Journal::create([
                'title' => $request->title,
                'user_id' => $userId,
                'topic_id' => $topic,
                'writing_for' => $request->writing_for,
                'color' => $request->color,
                'entry_date' => Carbon::now(),
            ]);
            $insertedId = $addJournal->id;
            //call to AI
            if ($action == 1) {

                $this->createSymtomByTopic($insertedId, $topic);
            }


            DB::commit();
            // Return journals with optional limit
            $limit = 10;
            return $this->journal->journals($userId, $limit, $request);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "addJournal" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }


    // public function journalEntry(JournalEntryValidation $request)
    // {
    //     DB::beginTransaction();

    //     try {

    //         $userId         = Auth::id();
    //         $isExit         =   Journal::find($request->journal_id)->exists();
    //         if(!$isExit){
    //             return $this->sendError(trans('message.journal_not_exist'), [], 403);
    //         }

    //         $addJournal         =   ['journal_id'=>$request->journal_id,
    //                                 'user_id'=>$userId,
    //                                 'content'=>$request->content,
    //                                 'feeling_id'=>$request->feeling,
    //                                 'pain'=>$request->pain,
    //                                 'journal_on' => Carbon::now()];
    //         if(isset($request->link) && !empty($request->link)){

    //             $addJournal['link']   =   $request->link;
    //         }
    //         if(isset($request->media) && !empty($request->media)){
    //             $media                =       upload_file($request->media, 'journals');
    //             $addJournal['media']  =   $media;
    //         }
    //         if(isset($request->audio) && !empty($request->audio)){
    //             $audio                =       upload_file($request->audio, 'journals/audio');
    //             $addJournal['audio']  =       $audio;
    //         }

    //         $addJournal               =       JournalEntry::create($addJournal);
    //         DB::commit();
    //         $journalId                =       $addJournal->id;
    //         $feelings                 =       $request->feeling_type;
    //         for ($i=0; $i <count($feelings) ; $i++) { 

    //             journalsFeeling::updateOrCreate(
    //                 ['journal_entry_id' => $journalId, 'feeling_type_id' => $feelings[$i]],
    //                 ['is_active' => 1]
    //             );
    //             DB::commit();
    //         }
    //         if (isset($request->symptom) && !empty($request->symptom)) {
    //             $symptom                 =     $request->symptom;
    //                 // symptons
    //             for ($i=0; $i <count($symptom) ; $i++) { 
    //                 journalSymptoms::updateOrCreate(
    //                     ['journal_entry_id' => $journalId, 'symptom_id' => $symptom[$i]],
    //                     ['is_active' => 1]
    //                 );
    //                 DB::commit();
    //             }
    //         }
    //             if (isset($request->extra_symptom) && !empty($request->extra_symptom)) {

    //                 $extra_symptom                 =     $request->extra_symptom;
    //                     // symptons
    //                 for ($i=0; $i <count($extra_symptom) ; $i++) {

    //                     $physicalSym       =   PhysicalSymptom::where(['symptom'=>$extra_symptom[$i],'is_active'])->whereNull('user_id')->first();

    //                     if(isset($physicalSym) && !empty($physicalSym)){

    //                         $physicalExtra   =  $physicalSym->id;

    //                     }else{
    //                         $addPhysicalSympton               =      new PhysicalSymptom();
    //                         $addPhysicalSympton->symptom      =      $extra_symptom[$i];
    //                         $addPhysicalSympton->is_active    =      1;
    //                         $addPhysicalSympton->user_id      =      $userId;
    //                         $addPhysicalSympton->save();
    //                         $physicalExtra                    =      $physicalSym->id;
    //                     }
    //                     journalSymptoms::updateOrCreate(
    //                         ['journal_entry_id' => $journalId, 'symptom_id' => $physicalExtra],
    //                         ['is_active' => 1]
    //                     );
    //                     DB::commit();
    //                 }
    //             }
    //         $limit                                           =   10;
    //         return $this->journal->journals($userId, $limit, $request);
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         Log::error('Error caught: "addJournal" ' . $e->getMessage());
    //         return $this->sendError($e->getMessage(), [], 400);
    //     }
    // }


    // public function journalEntry(JournalEntryValidation $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $userId         = Auth::id();
    //         $journalId      = $request->journal_id;
    //         // Check if the journal with the specified ID exists

    //         $journalExists  = Journal::find($journalId);

    //         if (!isset($journalExists)) {

    //             DB::rollback();

    //             return $this->sendError(trans('message.journal_not_exist'), [], 403);
    //         }
    //         if($journalExists->user_id != $userId){

    //             return $this->sendError('The selected journal id is invalid.', [], 400);
    //         }
    //         // Prepare data for creating a new journal entry
    //         $addJournal = [
    //             'journal_id' => $journalId,
    //             'user_id' => $userId,
    //             'content' => $request->content,
    //             'feeling_id' => $request->feeling,
    //             'pain' => $request->pain,
    //             'journal_on' => Carbon::now(),
    //         ];

    //         // Add optional fields if provided in the request
    //         if ($request->filled('link')) {
    //             $addJournal['link'] = $request->link;
    //         }

    //         if ($request->hasFile('media')) {
    //             $media = upload_file($request->file('media'), 'journals');
    //             $addJournal['media'] = $media;
    //         }

    //         if ($request->hasFile('audio')) {
    //             $audio = upload_file($request->file('audio'), 'journals/audio');
    //             $addJournal['audio'] = $audio;
    //         }

    //         // Create the new journal entry
    //         $newJournalEntry = JournalEntry::create($addJournal);
    //         DB::commit();

    //         // dd($newJournalEntry->id);
    //         // Handle feelings associated with the journal entry

    //         if ($request->filled('feeling_type')) {

    //             $feelings = $request->feeling_type;

    //             foreach ($feelings as $feeling) {

    //                 JournalsFeeling::updateOrCreate(
    //                     ['journal_entry_id' => $newJournalEntry->id, 'feeling_type' => $feeling],
    //                     ['is_active' => 1]
    //                 );
    //                 DB::commit();
    //             }
    //         }

    //         // Handle symptoms associated with the journal entry
    //         if ($request->filled('symptom')) {
    //             $symptoms       =       $request->symptom;
    //             foreach ($symptoms as $symptom) {
    //                 JournalSymptoms::updateOrCreate(
    //                     ['journal_entry_id' => $newJournalEntry->id, 'symptom_id' => $symptom],
    //                     ['is_active' => 1]
    //                 );
    //                 DB::commit();
    //             }
    //         }

    //         // Handle extra symptoms associated with the journal entry
    //         if ($request->filled('extra_symptom')) {
    //             $extraSymptoms = $request->extra_symptom;
    //             foreach ($extraSymptoms as $extraSymptom) {

    //                 $topic_id            =       $journalExists->topic_id;
    //                 $journalTopic        =       JournalTopic::where('id',$topic_id)->first();
    //                 $sysmtomId           =      "";
    //                 if ($journalTopic->type == 3) { // user defined

    //                     $physicalSymptom = PhysicalSymptom::where('symptom', $extraSymptom)
    //                         ->where(function($query) use ($journalTopic, $userId) {
    //                             // Check for journalTopic id or parent_id with user_id being either null or matching the current user
    //                             $query->where('id', $journalTopic->id)
    //                                   ->orWhere(function($query) use ($journalTopic, $userId) {
    //                                       if (!empty($journalTopic->parent_id)) {
    //                                           $query->where('id', $journalTopic->parent_id)
    //                                                 ->where(function($query) use ($userId) {
    //                                                     $query->whereNull('user_id')
    //                                                           ->orWhere('user_id', $userId);
    //                                                 });
    //                                       }
    //                                   });
    //                         })
    //                         ->first();

    //                     if(isset($physicalSymptom) && !empty($physicalSymptom)){ // exist

    //                         $sysmtomId  = $physicalSymptom->id;

    //                     }else{  // empty
    //                         $physicalSymptom = PhysicalSymptom::create([
    //                             'symptom' => $extraSymptom,
    //                             'is_active' => 1,
    //                             'user_id' => $userId,
    //                             'topic_id' => $journalTopic->id,
    //                         ]);
    //                         DB::commit();
    //                         $sysmtomId  =$physicalSymptom->id;
    //                     }
    //                 }elseif ($journalTopic->type==1) {      // bydefault

    //                     $physicalSymptom =       PhysicalSymptom::where(['symptom' => $extraSymptom,'topic_id'=>$journalTopic->id,'is_active' => 1, 'user_id' => null])->first();

    //                     if (!$physicalSymptom) {
    //                         $physicalSymptom = PhysicalSymptom::create([
    //                             'symptom' => $extraSymptom,
    //                             'is_active' => 1,
    //                             'user_id' => $userId,
    //                         ]);
    //                         DB::commit();
    //                         $sysmtomId  =$physicalSymptom->id;
    //                     }else{
    //                         $sysmtomId  =$physicalSymptom->id;
    //                     }
    //                 }
    //                 JournalSymptoms::updateOrCreate(
    //                     ['journal_entry_id' => $newJournalEntry->id, 'symptom_id' => $sysmtomId],
    //                     ['is_active' => 1]
    //                 );
    //                 DB::commit();
    //             }
    //         }
    //         // Retrieve journals for the user after successful entry creation
    //         $limit = 10;
    //         return $this->journal->journalEntries($userId, $journalId, $limit, $request);
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         Log::error('Error caught in "journalEntry" method: ' . $e->getMessage());
    //         return $this->sendError('Failed to create journal entry.', [], 400);
    //     }
    // }

    #----------  N E W      F U N C T I O N --------------_____# MAY17,2024
    public function journalEntry(JournalEntryValidation $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {

            $symptom        = isset($request['symptom']) && is_array($request['symptom']) && !empty($request['symptom']);
            $otherSymptom   = isset($request['extra_symptom']) && is_array($request['extra_symptom']) && !empty($request['extra_symptom']);

            if (!$symptom && !$otherSymptom) {

                return $this->sendError('Either symptom or other_symptom must be present', [], 400);

            }

            #---------- validation symptom  with AI--------------#
            // if(isset($request->extra_symptom)){
            //     $symptomValidity = $this->validateSymptoms($request->extra_symptom);

            //     if(isset($symptomValidity['status']) && isset($symptomValidity['data']) && $symptomValidity['status'] == 200){

            //         if($symptomValidity['data'] == 0){

            //             return $this->sendError('Incorrect symptom', [], 400);
            //         }
            //     }
            // }

            #---------- validation symptom  with AI--------------#

            $userId         = Auth::id();
            $journalId      = $request->journal_id;
            $journalExists  = Journal::find($journalId);
            if (!$journalExists) {

                return $this->sendError(trans('message.journal_not_exist'), [], 403);
            }
            if ($journalExists->user_id != $userId) {

                return $this->sendError('The selected journal id is invalid.', [], 400);
            }

            $addJournal = [
                'journal_id' => $journalId,
                'user_id' => $userId,
                'content' => $request->content,
                'feeling_id' => $request->feeling,
                'pain' => $request->pain,
                'journal_on' => Carbon::now(),
            ];

            if ($request->filled('link')) {
                $addJournal['link'] = $request->link;
            }

            if ($request->hasFile('media')) {
                $addJournal['media'] = upload_file($request->file('media'), 'journals');
            }

            if ($request->hasFile('audio')) {
                $addJournal['audio'] = upload_file($request->file('audio'), 'journals/audio');
            }

            $newJournalEntry = JournalEntry::create($addJournal);

            if ($request->filled('feeling_type')) {

                $feelings = $request->feeling_type;
                foreach ($feelings as $feeling) {
                    JournalsFeeling::updateOrCreate(
                        ['journal_entry_id' => $newJournalEntry->id, 'feeling_type' => $feeling],
                        ['is_active' => 1]
                    );
                }
            }

            if ($request->filled('symptom')) {
                $symptoms = $request->symptom;
                foreach ($symptoms as $symptom) {
                    JournalSymptoms::updateOrCreate(
                        ['journal_entry_id' => $newJournalEntry->id, 'symptom_id' => $symptom],
                        ['is_active' => 1]
                    );
                }
            }

            if ($request->filled('extra_symptom')) {

                $this->handleExtraSymptoms($journalExists, $request->extra_symptom, $newJournalEntry->id, $userId);
            }

             #--------------  RECORD USER QUOTA PER DAY-------------#
             if(isset($newJournalEntry->id) && !empty($newJournalEntry->id)){
               
                $quotaUpdated               = UserQuota::updateQuota($userId, 'journal_entry');
                
            }
            #--------------  RECORD USER QUOTA PER DAY-------------#
            DB::commit();
            $limit = 10;
            return $this->journal->journalEntries($userId, $journalId, $limit, $request);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught in "journalEntry" method: ' . $e->getMessage());
            return $this->sendError('Failed to create journal entry.', [], 400);
        }
    }

    private function handleExtraSymptoms($journalExists, $extraSymptoms, $journalEntryId, $userId)
    {
        $topicId = $journalExists->topic_id;
        $journalTopic = JournalTopic::find($topicId);

        foreach ($extraSymptoms as $extraSymptom) {
            $physicalSymptom = null;
            if ($journalTopic->type == 3) { // user defined
                $physicalSymptom = PhysicalSymptom::where('symptom', $extraSymptom)
                    ->where(function ($query) use ($journalTopic, $userId) {
                        $query->where('id', $journalTopic->id)
                            ->orWhere(function ($query) use ($journalTopic, $userId) {
                                if (!empty ($journalTopic->parent_id)) {
                                    $query->where('id', $journalTopic->parent_id)
                                        ->where(function ($query) use ($userId) {
                                            $query->whereNull('user_id')
                                                ->orWhere('user_id', $userId);
                                        });
                                }
                            });
                    })
                    ->first();
            } elseif ($journalTopic->type == 1) { // by default
                $physicalSymptom = PhysicalSymptom::where([
                    'symptom' => $extraSymptom,
                    'topic_id' => $journalTopic->id,
                    'is_active' => 1,
                    'user_id' => null
                ])->first();
            }

            if (!$physicalSymptom) {
                $physicalSymptom = PhysicalSymptom::updateOrCreate([
                    'symptom' => $extraSymptom,
                    'is_active' => 1,
                    'user_id' => $userId,
                    'topic_id' => $journalTopic->id,
                ]);
            }
            JournalSymptoms::updateOrCreate(
                ['journal_entry_id' => $journalEntryId, 'symptom_id' => $physicalSymptom->id],
                ['is_active' => 1]
            );
        }
    }
    #------------------------------------------------------------------------#













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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        //
        if (empty($id)) {

            return $this->sendResponsewithoutData("Invalid id", 422);
        }
        DB::beginTransaction();

        try {
            $authId = Auth::id();
            $validation = Validator::make($request->all(), ['type' => 'required|between:1,2']);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            } else {

                if ($request->type == 1) {      // journal thread

                    //check is exist and ownner of the journal 
                    $isExist = Journal::where(['id' => $id, 'user_id' => $authId, 'is_active' => 1])->first();

                    if (isset($isExist) && !empty($isExist)) {

                        $isExist->delete();

                        DB::commit();
                    } else {

                        return $this->sendResponsewithoutData("Invalid journal", 422);
                    }
                } else {

                    $isExist = JournalEntry::where(['id' => $id, 'user_id' => $authId, 'is_active' => 1])->first();
                    if (isset($isExist) && !empty($isExist)) {

                        $isExist->delete();

                        DB::commit();
                    } else {

                        return $this->sendResponsewithoutData("Invalid journal", 422);
                    }
                }
                return $this->sendResponsewithoutData(trans('message.deleted_successfully'), 200);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "delete journal" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    public function addToFavorite(Request $request)
    {

        DB::beginTransaction();

        try {
            $validation = Validator::make($request->all(), ['id' => 'required|integer', 'type' => 'required|between:1,2']);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            } else {

                $authId = Auth::id();
                $type = $request->type;

                if ($type == 1) {       // add to journal thread

                    $isExist = Journal::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->first();
                    if (isset($isExist) && !empty($isExist)) {

                        if ($isExist->is_favorite == 0) {

                            $favorite = 1;
                            $message = "Added to favorite.";
                        } else {

                            $favorite = 0;
                            $message = "Removed from favorite.";
                        }

                        $isExist->is_favorite = $favorite;
                        $isExist->save();
                        DB::commit();
                        return $this->sendResponsewithoutData($message, 200);
                        // return      $this->journal->journals($authId, $request['id'],$request);

                    } else {

                        return $this->sendResponsewithoutData(trans('message.journal_not_exist'), 422);
                    }
                } else {              // add to journal entry

                    $isExist = JournalEntry::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->first();

                    if (isset($isExist) && !empty($isExist)) {

                        if ($isExist->is_favorite == 0) {

                            $favorite = 1;
                            $message = "Added to favorite.";
                        } else {

                            $favorite = 0;
                            $message = "Removed from favorite.";
                        }

                        $isExist->is_favorite = $favorite;
                        $isExist->save();
                        DB::commit();
                        return $this->sendResponsewithoutData($message, 200);
                        // return      $this->journal->journalEntries($authId, $isExist['journal_id'],10,$request,$request['id']);

                    } else {
                        return $this->sendResponsewithoutData(trans('message.journal_not_exist'), 422);
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "addToFavorite" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    #------------ U P D A T E     J O U R N A L    T H R E A D     A N D     E N T R I E S ----------#
    public function updateJournal(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), ['type' => 'required|integer|between:1,2', 'id' => 'required|integer']);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            }
            $type = $request->type;
            $authId = Auth::id();
            if ($type == 1) {   // edit journal thread
                //check journal id
                //check jurnal is active and owner of the journal
                $journal = Journal::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->exists();

                if (empty($journal)) {

                    return $this->sendResponsewithoutData(trans('message.journal_not_exist'), 403);
                } else {

                    return $this->journal->UpdateJournalThread($request, $authId);
                }
            } else {  // edit journal entry

                $validator = Validator::make($request->all(), [
                    'feeling' => 'nullable|integer|exists:feelings,id',
                    'feeling_type' => ['nullable', 'array'],
                    'pain' => 'nullable|integer|between:0,5',
                    'symptom' => ['nullable', 'array'],
                    'other_symptom' => ['nullable', 'array'],
                    'content' => 'nullable|string|min:3',
                    'link' => 'nullable|url',
                    'media' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
                    'audio' => 'nullable|file|mimes:mpeg,wav,mp3|max:9048'
                ]);

                if ($validator->fails()) {

                    return $this->sendResponsewithoutData($validator->errors()->first(), 422);
                }
                $journalEntry = JournalEntry::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->first();

                if (empty($journalEntry)) {

                    return $this->sendResponsewithoutData(trans('message.journal_not_exist'), 403);
                }
                return $this->journal->updateJournalEntry($request, $authId);
            }
        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "update journal" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------ U P D A T E     J O U R N A L    T H R E A D     A N D     E N T R I E S ----------#

    #-------------------- G E N E R A L     I N S I G H T      -------------------------------#
    public function insights(Request $request)
    {
        try {
            
            $painScale = ["No Pain","Mild Pain", "Discomforting Pain", "Moderate Pain","Severe Pain","Very Severe Pain"];
             
            $validation = Validator::make($request->all(), [
                'journal_id' => 'nullable|integer|exists:journals,id',
                'start_date' => ['required', 'date', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date', 'date_format:Y-m-d'],
            ]);
            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            }
            $user_id    =   Auth::id();
            $journal    =   Journal::find($request->journal_id);
        
            if($journal->user_id != $user_id){

                return $this->sendResponsewithoutData("Invailed journal", 403);
            }
            $start_date         = $request->start_date;
            $end_date           = $request->end_date;
            $dates              = getDatesBetween($start_date, $end_date);
            $insight = array();

            if (isset($dates[0]) && !empty($dates[0])) {

                foreach ($dates as $date) {

                    $insights = JournalEntry::with([

                        'feeling' => function ($query) {

                            $query->select('id', 'name'); // Rename 'id' and 'name'
    
                        }
                    ])->select('id', 'feeling_id', 'pain')->where('is_active', 1);

                    if (isset($request->journal_id) && !empty($request->journal_id)) {

                        $insights = $insights->where('journal_id', $request->journal_id);
                    }
                    $insights = $insights->whereDate('journal_on', '=', $date)->get();

                    if ($insights->isNotEmpty()) {

                        $moodAvg = JournalEntry::where('is_active', 1);

                        if (isset($request->journal_id) && !empty($request->journal_id)) {
                            $moodAvg->where('journal_id', $request->journal_id);
                        }

                        $moodAvg = $moodAvg->whereDate('journal_on', '=', $date)
                            ->selectRaw('AVG(feeling_id) AS avg_mood')
                            ->first();
                        $avg    =   ceil((isset($moodAvg) && !empty($moodAvg))?$moodAvg['avg_mood']:0);    
                        
                        $insight[] = [
                            'date' => $date,
                            'count' => count($insights),
                            'avg_mood'=>$avg,
                            'mood' => $insights[0]['feeling_id'],
                            'mood_pain' => $insights,
                        ];
                    }
                }
            }
            #---------  C R E A T E         I N S I G H T S -------------------#
            $journalInsights        =   [];
            if(isset($insight) && !empty($insight)){

                $request_ids    =       JournalEntry::where('journal_id', $journal->id)->whereBetween('journal_on', [$start_date, $end_date])->pluck('id')->toArray();
                
                $reports        =       JournalReport::where('journal_id', $journal->id)->where('type', $request->type)->where(['start_date'=>$start_date,'end_date'=>$end_date])->first();

                $first_entry_id =       JournalEntry::where(['journal_id'=>$journal->id,'is_active'=>1])->whereBetween('journal_on', [$start_date, $end_date])->min('id');

                $last_entry_id  =        JournalEntry::where(['journal_id'=>$journal->id,'is_active'=>1])->whereBetween('journal_on', [$start_date, $end_date])->max('id');

                if(isset($reports) && !empty($reports)){

                    if(!empty($first_entry_id) && !empty($first_entry_id)){

                        if($reports->start==$first_entry_id && $reports->end==$last_entry_id){

                            $journalInsights       = json_decode($reports->report);
                          
                        }
                    }
                }else{  #-------- generate new insights ----------------#

                    $data           =       array(['text'=>"Journal Name : $journal->title"],['text' => "Disease: ".$journal->topic->name], ['text' => 'Journal Entries']);
                    #preparing journal entries as input in array
                    $entries        =       JournalEntry::where('journal_id', $journal->id)->whereBetween('journal_on', [$request->start_date, $request->end_date])->with(['feeling','feeling_types.feeling_type_details', 'symptom.journalSymtom'])->get();
    
                    foreach($entries as $entry){
                        #date
                        $date               =       Carbon::parse($entry->journal_on)->format('Y-m-d H:i A');
                        array_push($data, ['text'=> "Date: $date"]);
                        #mood
                        $details= "Mood: ". $entry->feeling->name;
                        $felling_types= $entry->feeling_types->pluck('feeling_type_details.name')->implode(", ");
                        $details = $details.". Feelings: $felling_types";
            
                        #symptoms
                        $symptoms= $entry->symptom->pluck('journalSymtom.symptom')->implode(", ");
                        $details = $details.". Symptoms: $symptoms";
                        $pain= $painScale[$entry->pain];
                        $details = $details.". Pain: $pain";
                        #description
                        $details = $details.". Description: $entry->content";
                        array_push($data, ['text'=> $details]);
                    }
                    #Insides & Suggestion
                    array_push($data, 
                            array("text" => "-------------------------------------------------------------------------------------------------------------------------------summarize this content in only these keys= insights and sugestions"),
                            array("text" => "provide result in json format"),
                            array("text" => "give the keys values in array format, even if only one key is available. and give minimum  five points in each key"),
                            array("text" => "don't give any key null or black, suppose if pain not mention above, give in the response like: 'No pain metion in the journal entries'"),
                            array("text" => "format must be in this format => \n{\n  \"insights\": [\n    \"High blood sugar can occur even when following a meal plan, requiring investigation and adjustments.\",\n    \"Exercise has a noticeable positive impact on blood sugar management.\",\n    \"Resisting unhealthy food choices during social events is crucial for maintaining stable blood sugar levels.\",\n    \"Illness can disrupt blood sugar control, highlighting the need for close monitoring and medical advice when sick.\",\n    \"Connecting with others through support groups provides motivation and valuable insights for diabetes management.\"\n  ],\n  \"suggestions\": [\n    \"Consult healthcare professionals when blood sugar fluctuations occur despite following a plan.\",\n    \"Incorporate regular physical activity, such as daily walks, into the routine.\",\n    \"Explore healthy dessert alternatives to satisfy cravings while managing blood sugar.\",\n    \"Monitor blood sugar closely during illness and seek medical attention if necessary.\",\n    \"Actively engage in diabetes support groups to learn from and share experiences with others.\"\n  ]\n}\n"),
                        );
                    $journalInsights = $this->generateReportAItraint($data, 1);
                    
                    if(isset($journalInsights) && !empty($journalInsights)){
                        $newReport              = new JournalReport;
                        $newReport->journal_id  = $journal->id;
                        $newReport->start_date  = $request->start_date;
                        $newReport->end_date    = $request->end_date;
                        $newReport->start_id    = $first_entry_id;
                        $newReport->end_id      = $last_entry_id;
                        
                        $newReport->report      = json_encode($journalInsights);
                        $newReport->type        = $request->type;
                        $newReport->save();
                    }
                }
            }
            return response()->json([
                'status' => 200,
                'message' => trans('message.insight'),
                'data' => $insight,
                'insights' => $journalInsights
            ], 200);
        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught in "insights" method: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------- G E N E R A L     I N S I G H T     -------------------------------#

    function getJournalEntries(Request $request)
    {

        $limit = $request->filled('limit') ? (int) $request->input('limit') : 10;
        $userId = Auth::id();

        $validate = Validator::make($request->all(), [
            'journal_id' => 'required|integer|exists:journals,id',
            'limit' => 'nullable|integer',
        ]);

        if ($validate->fails()) {
            return $this->sendError($validate->errors()->first(), [], 400);
        }

        $check = Journal::find($request->journal_id);

        if ($check->user_id == $userId) {

            $response = $this->journal->journalEntries($userId, $request->journal_id, $limit, $request);

            return $response;
        } else {

            return $this->sendError('The selected journal id is invalid.', [], 400);
        }
    }


    #------------------ G E T        J O U R N A L      S Y M P T O M S -------------------#
    public function symtoms(Request $request)
    {
        try {

            $validate = Validator::make($request->all(), [
                'topic_id' => 'required|integer|exists:journal_topics,id',
            ]);
            if ($validate->fails()) {
                return $this->sendError($validate->errors()->first(), [], 400);

            } else {
                $authId     =   Auth::id();
                $topicId    =   $request->topic_id;
                //DB::enableQueryLog();
                 $topic      =   JournalTopic::where('id', $topicId)->first();

                // $where      =   "(topic_id=" . $topicId." or type=2)";

                // $where      =   "((topic_id=" . $topicId." and user_id='".$authId."') or (topic_id=" . $topicId." and user_id IS NULL)) or type=2)";

                // if (isset($topic->parent_id) && !empty($topic->parent_id)) {

                //     $where .= " or (topic_id='" . $topic->parent_id . "' and user_id IS NULL)";
                // }

                // $symptoms = PhysicalSymptom::select('id', 'symptom', 'type', 'is_active')->whereRaw($where)->orderBy('type')->orderBy('symptom')->get();

                $query = PhysicalSymptom::select('id', 'symptom', 'type', 'is_active')
                ->where(function ($query) use ($topicId, $authId) {
                    $query->where('topic_id', $topicId)
                        ->where(function ($query) use ($authId) {
                            $query->where('user_id', $authId)
                                ->orWhereNull('user_id');
                        })
                        ->orWhere('type', 2);
                });

            if (isset($topic->parent_id) && !empty($topic->parent_id)) {
                $query->orWhere(function ($query) use ($topic) {
                    $query->where('topic_id', $topic->parent_id)
                        ->whereNull('user_id');
                });
            }

            $symptoms = $query->orderBy('type')->orderBy('symptom')->get();


              //  dd(DB::getQueryLog());
                
                return $this->sendResponse($symptoms, trans('message.physical_symptom'), 200);
            }
        } catch (Exception $e) {

            Log::error('Error caught: "update journal" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #-------------------------- *********** E N D  *************---------------------------#


    private function categorizePain($painScore)
    {
        if ($painScore == 0) {
            return 'No Pain';
        } elseif ($painScore >= 1 && $painScore <= 3) {
            return 'Moderate Pain';
        } elseif ($painScore >= 4 && $painScore <= 5) {
            return 'Severe Pain';
        } else {
            return 'No Pain mentioned in the journal entries';
        }
    }

    #----------   G E T         J O U R N A L       B Y     D A T E    ----------------__#
    public function getJournalBydate(Request $request){

        try {

            $validate = Validator::make($request->all(), [

                'journal_id' => 'nullable|integer|exists:journals,id',
                'date' => ['required', 'date', 'date_format:Y-m-d'],
                [
                    'date_field.required' => 'The date field is required.',
                    'date_field.date' => 'The date field must be a valid date.',
                    'date_field.date_format' => 'The date field must be in the format YYYY-MM-DD.',
                ]
            ]);
            if ($validate->fails()) {

                return $this->sendError($validate->errors()->first(), [], 400);

            } else {

                $authId     =   Auth::id();

                return $this->journal->journalEntryByDate($authId,$request);
            }
              
        } catch (Exception $e) {

            Log::error('Error caught: "update journal" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }


    }
    #----------   G E T         J O U R N A L       B Y     D A T E    ----------------__#



     #======================= SYMPTOMS VALIDATION USING AI ========================#
     function validateSymptoms($symptom, $count = 1)
     {
         if ($count > 3) {
             return null;
         }
         // Define your API key
         $API_KEY = "AIzaSyCN9891vVrDvLHsQvZU9M2mv-9W85dOX8g";
         $symptoms = implode(', ', $symptom);
         // Define the URL
         $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key=" . $API_KEY;
         $data =[
             array("text" => "Medical Symptoms: $symptoms"),
             array("text" => "validate these symptoms, Is it right or wrong."),
             array("text" => "give the response in json format in response key, response shold only true or false"),
             array("text" => "do not give true if symptoms spelling is incorrect. only give true in case of currect symptom with currect spelling of the symptoms"),
             array("text" => "format must be in this format => \n  {\"response\": \"true\"}\n"),
         ];
 
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
             $response = [
                 'status' => 400,
                 "message" => "Curl Error",
                 "data" => $error,
             ];
             return $response;
 
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
                         if (isset($finalResponse['response'])) {
 
                             if($finalResponse['response'] == "true")
                             {
 
                                 return ['status' => 200, 'data' => 1];
                             }
                             else
                             {
                                 return ['status' => 200, 'data' => 0];
                             }
                         } else {
                             return $this->validateSymptoms($data, $count + 1);
                         }
                 } else {
                     return $this->validateSymptoms($data, $count + 1);
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
 
     }

}
