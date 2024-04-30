<?php

namespace App\Http\Controllers\Api\Journals;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Http\Requests\JournalValidation;
use App\Http\Requests\Journal_entry\JournalEntry as JournalEntryValidation;
use App\Models\JournalEntry;
use App\Models\journalsFeeling;
use App\Models\journalSymptoms;
use Carbon\Carbon;
use App\Services\JournalService;
use App\Models\PhysicalSymptom;
use App\Models\Journal;
use App\Rules\FeelingTypeIsExist;
use App\Rules\SymptomIsExist;

class JournalController extends BaseController
{
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
            $userId         = Auth::id();
            $addJournal     = Journal::create([
                'title' => $request->title,
                'user_id' => $userId,
                'topic_id' => $request->topic,
                'writing_for' => $request->writing_for,
                'color' => $request->color,
                'entry_date' => Carbon::now(),
            ]);
            DB::commit();
            // Return journals with optional limit
            $limit      =   10;
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


    public function journalEntry(JournalEntryValidation $request)
    {
        DB::beginTransaction();
        try {
            $userId         = Auth::id();
            $journalId      = $request->journal_id;
            // Check if the journal with the specified ID exists
            $journalExists  = Journal::where('id', $journalId)->exists();
            if (!$journalExists) {
                DB::rollback();
                return $this->sendError(trans('message.journal_not_exist'), [], 403);
            }

            // Prepare data for creating a new journal entry
            $addJournal = [
                'journal_id' => $journalId,
                'user_id' => $userId,
                'content' => $request->content,
                'feeling_id' => $request->feeling,
                'pain' => $request->pain,
                'journal_on' => Carbon::now(),
            ];

            // Add optional fields if provided in the request
            if ($request->filled('link')) {
                $addJournal['link'] = $request->link;
            }

            if ($request->hasFile('media')) {
                $media = upload_file($request->file('media'), 'journals');
                $addJournal['media'] = $media;
            }

            if ($request->hasFile('audio')) {
                $audio = upload_file($request->file('audio'), 'journals/audio');
                $addJournal['audio'] = $audio;
            }

            // Create the new journal entry
            $newJournalEntry = JournalEntry::create($addJournal);
            DB::commit();

            // dd($newJournalEntry->id);
            // Handle feelings associated with the journal entry

            if ($request->filled('feeling_type')) {

                $feelings = $request->feeling_type;

                foreach ($feelings as $feeling) {

                    JournalsFeeling::updateOrCreate(
                        ['journal_entry_id' => $newJournalEntry->id, 'feeling_type' => $feeling],
                        ['is_active' => 1]
                    );
                    DB::commit();
                }
            }

            // Handle symptoms associated with the journal entry
            if ($request->filled('symptom')) {
                $symptoms = $request->symptom;
                foreach ($symptoms as $symptom) {
                    JournalSymptoms::updateOrCreate(
                        ['journal_entry_id' => $newJournalEntry->id, 'symptom_id' => $symptom],
                        ['is_active' => 1]
                    );
                    DB::commit();
                }
            }

            // Handle extra symptoms associated with the journal entry
            if ($request->filled('extra_symptom')) {
                $extraSymptoms = $request->extra_symptom;
                foreach ($extraSymptoms as $extraSymptom) {
                    $physicalSymptom = PhysicalSymptom::where(['symptom' => $extraSymptom, 'is_active' => 1, 'user_id' => null])->first();

                    if (!$physicalSymptom) {
                        $physicalSymptom = PhysicalSymptom::create([
                            'symptom' => $extraSymptom,
                            'is_active' => 1,
                            'user_id' => $userId,
                        ]);
                        DB::commit();
                    }

                    JournalSymptoms::updateOrCreate(
                        ['journal_entry_id' => $newJournalEntry->id, 'symptom_id' => $physicalSymptom->id],
                        ['is_active' => 1]
                    );
                    DB::commit();
                }
            }
            // Retrieve journals for the user after successful entry creation
            $limit = 10;
            return $this->journal->journalEntries($userId, $journalId, $limit, $request);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught in "journalEntry" method: ' . $e->getMessage());
            return $this->sendError('Failed to create journal entry.', [], 400);
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function addToFavorite(Request $request)
    {

        DB::beginTransaction();

        try {
            $validation =   Validator::make($request->all(), ['id' => 'required|integer','type'=>'required|between:1,2']);

            if ($validation->fails()) {

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            } else {

                $authId =   Auth::id();
                $type   =   $request->type;

                if($type==1){       // add to journal thread

                    $isExist                =   Journal::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->first();
                    if(isset($isExist) && !empty($isExist)){

                        if ($isExist->is_favorite == 0) {

                            $favorite       =   1;

                        } else {

                            $favorite       =   0;
                        }

                        $isExist->is_favorite       =   $favorite;
                        $isExist->save();
                        DB::commit();
                        return      $this->journal->journals($authId, $request['id'],$request);

                    }else{

                        return $this->sendResponsewithoutData(trans('message.journal_not_exist'), 422);

                    }
                }else{              // add to journal entry

                    $isExist                =    JournalEntry::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->first();

                    if(isset($isExist) && !empty($isExist)){

                        if ($isExist->is_favorite == 0) {

                            $favorite           =   1;

                        } else {

                            $favorite           =   0;
                        }

                        $isExist->is_favorite   =   $favorite;
                        $isExist->save();
                        DB::commit();
                        return      $this->journal->journalEntries($authId, $isExist['journal_id'],10,$request,$request['id']);
                        
                    }else{
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

            $validator      =   Validator::make($request->all(), ['type' => 'required|integer|between:1,2','id' => 'required|integer']);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            }
            $type       =   $request->type;
            $authId     =   Auth::id();
            if ($type == 1) {   // edit journal thread
                //check journal id
                //check jurnal is active and owner of the journal
                $journal        =   Journal::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->exists();
                if (empty($journal)) {

                    return $this->sendResponsewithoutData(trans('message.journal_not_exist'), 403);

                } else {

                    return      $this->journal->UpdateJournalThread($request,$authId);
                }
            } else {  // edit journal entry
               
                    $validator      =   Validator::make($request->all(), [
                                                'feeling'=>'nullable|integer|exists:feelings,id',
                                                'feeling_type' => ['nullable','array'],
                                                'pain' => 'nullable|integer|between:0,5',
                                                'symptom'=>['nullable','array'],
                                                'other_symptom'=>['nullable','array'],
                                                'content' => 'nullable|string|min:3',
                                                'link' => 'nullable|url',
                                                'media' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
                                                'audio' => 'nullable|file|mimes:mpeg,wav,mp3|max:9048']);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
            }
                $journalEntry        =    JournalEntry::where(['id' => $request->id, 'user_id' => $authId, 'is_active' => 1])->first();

                if (empty($journalEntry)) {

                    return $this->sendResponsewithoutData(trans('message.journal_not_exist'), 403);

                } 
                return      $this->journal->updateJournalEntry($request,$authId);
            }
        } catch (Exception $e) {

            DB::rollback();
            Log::error('Error caught: "update journal" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------ U P D A T E     J O U R N A L    T H R E A D     A N D     E N T R I E S ----------#

    #-------------------- G E N E R A L     I N S I G H T      -------------------------------#
    public function insights(Request $request){
        try {
            $validation         =   Validator::make($request->all(),['journal_id'=>'nullable|integer|exists:journals,id',
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d'],]);
            if($validation->fails()){
                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
            }
            $start_date                 =   $request->start_date;
            $end_date                   =   $request->end_date;
            $dates                      = getDatesBetween($start_date,$end_date);
            if(isset($dates[0]) && !empty($dates[0])){
                $insight              =   array();
                foreach ($dates as $date) {

                    $insights           =   JournalEntry::with(['feeling'=>function($query){

                        $query->select('id', 'name'); // Rename 'id' and 'name'

                    }])->select('id','feeling_id','pain')->where('is_active',1);

                    if(isset($request->journal_id) && !empty($request->journal_id)){
                        $insights       =   $insights->where('journal_id',$request->journal_id);
                    }
                    $insights                           =   $insights->whereDate('journal_on', '=', $date)->get();
                    if ($insights->isNotEmpty()) {
                        $insight[] = [
                            'date' => $date,
                            'count'=>count($insights),
                            'mood'=>$insights[0]['feeling_id'],
                            'insights' => $insights,
                        ];
                    }
                }
               return $this->sendResponse($insight, "hgk",200);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught in "UpdatejournalEntry" method: ' . $e->getMessage());
            return $this->sendError('Failed to create journal entry.', [], 400);
        }
    }
    #-------------------- G E N E R A L     I N S I G H T     -------------------------------#



}
