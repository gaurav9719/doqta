<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class BaseController extends Controller
{
    //

    public function sendResponse($result, $message,$code)
    {
    	$response = [
            'status' => $code,
            'message' => $message,
            'data'    => $result,
            
        ];

        return response()->json($response, $code);
    }


    public function sendResponsewithoutData($message,$code)
    {
    	$response = [
            'status' => $code,
            'message' => $message,
        ];

        return response()->json($response, $code);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 400)
    {
    	$response = [
            'status' => $code,
            'message' => $error,
        ];

        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    
        function getErrorAsString($messagearr)
        {
            $message = '';
            if (is_string($messagearr)) {
                return $messagearr;
            }
            $totalmsg = $messagearr->count();
            foreach ($messagearr->all() as $key => $value) {
                $message .= $key < $totalmsg - 1 ? $value . '/' : $value;
            }
            return $message;
        }
        


    function validationErrorsToString($errArray) {
        $valArr = array();
        foreach ($errArray->toArray() as $key => $value) { 
          //  $errStr = $key.' '.$value[0];
            $errStr = $value[0];
            array_push($valArr, $errStr);
        }
        if(!empty($valArr)){
            $errStrFinal = implode(',', $valArr);
        }
        return $errStrFinal;
    }

    public function createToken($user_id){
        $userDetail               =               User::where('id', $user_id)->first();
        $userDetail['token']      =               $userDetail->createToken(env('PASSPORT_SECURITY_TOKEN'))->accessToken;
        // $userDetail->country_name =               Countries::find($userDetail->country_id)['name'];
        // $userDetail->state_name   =               States::find($userDetail->state_id)['name'];    
        // $userDetail->city_name    =               Cities::find($userDetail->city_id)['name'];    
        return  $userDetail;
    }
}
