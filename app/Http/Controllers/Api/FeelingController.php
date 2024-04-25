<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\BaseController;
use App\Models\Feeling;
use Illuminate\Support\Facades\Log;
use App\Models\Color;
use App\Models\JournalTopic;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Validated;
use App\Models\PhysicalSymptom;

class FeelingController extends BaseController
{
    //

    public function feeling(Request $request){

        try {

            $authId         =   Auth::id();

            $validator      =  Validator::make($request->all(),['type'=>"nullable|integer|between:1,3"],['type.*'=>"Invalid type"]);

            if($validator->fails()){

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);

            }else{


                if ($request->has('type') && !empty($request->type)) {

                    if ($request->type == 1) {

                        $data       =   Color::where('is_active', 1)->get();
                        
                        $message    =   trans("message.color"); 

                    } 
                    if ($request->type == 2) {

                        $data       =   JournalTopic::where('is_active', 1)->get();
                        $message    =   trans("message.journal_topic"); 
                    }

                    if($request->type==3){

                        $data       =   PhysicalSymptom::where('is_active',1)->orWhere('user_id',$authId)->get();
                        $message    =   trans("message.physical_symptom"); 
                    }
                } else {

                    $data           =   Feeling::with('feeling_type')->where('is_active', 1)->get();

                    if(isset($data) && !empty($data)){

                        $data->each(function($query){

                            if(isset($query->feeling) && !empty($query->feeling)){

                                $query->feeling =   asset('storage/'.$query->feeling);
                            }
                            if(isset($query->selected) && !empty($query->selected)){

                                $query->selected =   asset('storage/'.$query->selected);
                            }

                        });
                    }
                    $message        =    trans("message.feelings"); 
                }
        
                return $this->sendResponse($data, $message, 200);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "feeling" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
