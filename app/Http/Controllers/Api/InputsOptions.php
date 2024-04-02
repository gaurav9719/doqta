<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\UserRegister;
use App\Http\Requests\LoginUser;
use App\Models\User;
use App\Models\UserDevice;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\RegisterUserService;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use App\Http\Requests\verifyEmail;
use App\Services\VerifyEmail as verifyEmailService;
use Faker\Provider\Base;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Validator;
use App\Models\ParticipantCategory;
use App\Models\Interest;
use App\Models\DocumentTypes;
use App\Models\MedicalCredential;
use App\Models\Specialty;
class InputsOptions extends BaseController
{
    #--------------  U S E R    I N P U T S       S E L E C T I O N  ------------------#

    public function inputSelection(Request $request){
        try {
            $validation     =   Validator::make($request->all(),['type'=>'required|integer']);

            if($validation->fails()){

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }else{

                $type               =   $request->type;
                if($type==3){
                    $category       =      ParticipantCategory::where('is_active',1)->get();
                    return $this->sendResponse($category, trans("message.bring_you_here"), 200);
                }elseif ($type==4) {        //interest
                    
                    $interest       =       Interest::where('is_active',1)->get();
                    return $this->sendResponse($interest, trans("message.bring_you_here"), 200);

                }elseif ($type==6) {       // identity document
                    
                    $documentType       =   DocumentTypes::where('is_active',1)->get();
                    return $this->sendResponse($documentType, trans("message.identity_document_list"), 200);

                }elseif ($type==7) {        // medical conditions

                    $medical['medical_credentials']  =   MedicalCredential::where('is_active',1)->get();
                    $medical['specialty']  =   Specialty::where('is_active',1)->get();
                    return $this->sendResponse($medical, trans("message.medical_credentials_list"), 200);

                }else{

                    return $this->sendError(trans('message.invalid_get_credentials'), [], 400);
                }
            }
        } catch (Exception $e) {

            Log::error('Error caught: "inputSelection" ' . $e->getMessage());
            return $this->sendError(trans('message.something_went_wrong'), [], 400);
        }
    }
    #------------------------------------     E N D ----------------------------------------#

}
