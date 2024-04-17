<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Http\Requests\JournalValidation;
use App\Models\JournalEntry;
use App\Models\journalsFeeling;
use App\Models\journalSymptoms;
use Carbon\Carbon;
use App\Services\JournalService;
class JournalEntries extends BaseController
{

    protected $journal ,$authId;
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


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $userId     =   Auth::id();
        $limit      =   10;
        if(isset($request->limit) && !empty($request->limit)){

            $limit      =   $request->limit;
        }
        return $this->journal->getJournal($userId,"",$limit,$request);

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
    public function store(JournalValidation $request)
    {
        DB::beginTransaction();
        try {
            $userId                 =   Auth::id();
            $addJournal             =   new  JournalEntry();
            $addJournal->title      =   $request->title;
            $addJournal->topic_id   =   $request->topic;
            $addJournal->writing_for=   $request->writing_for;
            $addJournal->color_id      =   $request->color;
            $addJournal->pain       =   $request->pain;
            $addJournal->content    =   $request->content;
            $addJournal->user_id    =   $userId;
            $addJournal->feeling_id =   $request->feeling;

            
            $addJournal->entry_date =   Carbon::now();
            
            if(isset($request->link) && !empty($request->link)){

                $addJournal->link   =   $request->link;
            }

            if(isset($request->media) && !empty($request->media)){

                $media              =       upload_file($request->media, 'journals');

                $addJournal->media  =   $media;
            }

            if(isset($request->audio) && !empty($request->audio)){

                $audio              =       upload_file($request->audio, 'journals/audio');

                $addJournal->audio  =       $audio;
            }

            if($addJournal->save()){
                DB::commit();
                $journalId          =       $addJournal->id;
                $feelings           =       $request->feeling_type;
                $symptom            =       $request->symptom;

                for ($i=0; $i <count($feelings) ; $i++) { 
                
                    journalsFeeling::updateOrCreate(
                        ['journal_id' => $journalId, 'feeling_type_id' => $feelings[$i]],
                        ['is_active' => 1]
                    );
                    DB::commit();

                }
                // symptons
                for ($i=0; $i <count($symptom) ; $i++) { 
                
                    journalSymptoms::updateOrCreate(
                        ['journal_id' => $journalId, 'symptom_id' => $symptom[$i]],
                        ['is_active' => 1]
                    );
                    DB::commit();
                }

                
                return $this->journal->getJournal($userId,$journalId);
            }
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "addJournal" ' . $e->getMessage());
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

    public function addToFavorite(Request $request){
        DB::beginTransaction();
        try {
            $userId     =   Auth::id();
            $validation =   Validator::make($request->all(),['journal_id'=>'required|integer|exists:journal_entries,id']);

            if($validation->fails()){
    
                return $this->sendResponsewithoutData($validation->errors()->first(), 422);
    
            }else{

                $isExist      =   JournalEntry::where(['id'=>$request['journal_id'],'user_id'=>$userId,'is_active'=>1])->first();

                if(isset($isExist) && !empty($isExist)){

                    if($isExist->is_favorite==0){
                        $favorite       =   1;
                    }else{
                        $favorite       =   0;
                    }
                    $isExist->is_favorite       =   $favorite;  
                    $isExist->save();
                    DB::commit();
                    return      $this->journal->getJournal($userId,$request['journal_id'],"","",trans('message.updated_success_common'));
                    
                }else{
                    return $this->sendResponsewithoutData(trans('message.something_went_wrong'), 400);
                }
            }
        } catch (Exception $e) {
            
            DB::rollback();
            Log::error('Error caught: "addToFavorite" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);

        }
       
    }


}
