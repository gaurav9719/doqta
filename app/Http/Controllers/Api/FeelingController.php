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
class FeelingController extends BaseController
{
    //

    public function feeling(Request $request){

        try {
            if ($request->has('type') && !empty($request->type)) {

                if ($request->type == 1) {

                    $data = Color::where('is_active', 1)->get();
                    $message =trans("message.color"); 

                } elseif ($request->type == 2) {

                    $data = JournalTopic::where('is_active', 1)->get();
                    $message =trans("message.journal_topic"); 
                }
            } else {

                $data = Feeling::with('feeling_type')->where('is_active', 1)->get();
                $message =trans("message.feelings"); 

            }
    
            return $this->sendResponse($data, $message, 200);
        } catch (Exception $e) {
            Log::error('Error caught: "feeling" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
