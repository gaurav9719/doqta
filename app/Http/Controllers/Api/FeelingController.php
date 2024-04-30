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

            $authId                  =   Auth::id();
            $data['color']           =   Color::where('is_active', 1)->get();
            $data['journal_topic']   =   JournalTopic::where('is_active', 1)->get();
            $data['symptom']         =   PhysicalSymptom::where('is_active',1)->orWhere('user_id',$authId)->get();
            $feelings                =   Feeling::with('feeling_type')->where('is_active', 1)->get();

            if(isset($feelings) && !empty($feelings)){

                $feelings->each(function($query){

                    if(isset($query->feeling) && !empty($query->feeling)){

                        $query->feeling =   asset('storage/'.$query->feeling);
                    }
                    if(isset($query->selected) && !empty($query->selected)){

                        $query->selected =   asset('storage/'.$query->selected);
                    }

                });
            }
            $data['feelings']           =   $feelings;
            return $this->sendResponse($data, "Journal inputs", 200);
            
        } catch (Exception $e) {
            Log::error('Error caught: "feeling" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
