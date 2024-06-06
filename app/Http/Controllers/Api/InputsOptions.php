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
use App\Models\Ethnicity;
use App\Models\Gender;
use App\Models\Pronouns;
class InputsOptions extends BaseController
{
    #--------------  U S E R    I N P U T S       S E L E C T I O N  ------------------#

    public function inputSelection(Request $request){
        try {
            $validation     =   Validator::make($request->all(),['type'=>'required|integer']);

            if($validation->fails()){

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }else{

                $type                   =  $request->type;
                $authId                 =  Auth::id();
                // dd($authId);
                if($type==2){ 

                    $data['ethnicity']  =   Ethnicity::where('is_active',1)->get();
                    $data['gender']     =   Gender::where('is_active',1)->get();
                    $data['pronouns']   =   Pronouns::where('is_active',1)->get();
                    return $this->sendResponse($data, "step 2", 200);
                }

                elseif($type==3){

                    $category       =      ParticipantCategory::where('is_active',1)->get();

                    if(isset($category) && !empty($category)){

                        $category->each(function($query){

                            if(isset($query->image) && !empty($query->image)){

                                $query->image   =       asset('storage/'.$query->image);   
                            }
                        });
                    }

                    return $this->sendResponse($category, trans("message.bring_you_here"), 200);

                }elseif ($type==4) {        //interest
                    
                    $interest                   =       Interest::where('is_active',1)->get();

                    if(isset($interest) && !empty($interest)){

                        $interest->each(function($query){

                            if(isset($query->icon) && !empty($query->icon)){

                                $query->icon   = asset('storage/'.$query->icon);   
                            }
                        });
                    }

                    return $this->sendResponse($interest, trans("message.interest"), 200);

                }elseif ($type==6) {       // identity document
                    
                    $documentType       =   DocumentTypes::where('is_active',1)->get();
                    return $this->sendResponse($documentType, trans("message.identity_document_list"), 200);


                }elseif ($type==7) {        // medical conditions

                    $medical['medical_credentials']  =   MedicalCredential::where('is_active',1)->where(function($query) use($authId){

                        $query->where('type','<>',3);

                        $query->orWhere(function($q)use($authId){

                            $q->where('user_id',$authId);
                        });

                    })->get();


                    $medical['specialty']   =   Specialty::where('is_active',1)->where(function($query) use($authId){

                        $query->where('type','<>',3);

                        $query->orWhere(function($q)use($authId){

                            $q->where('user_id',$authId);
                        });

                    })->get();



                    // $medical['specialty']            =   Specialty::where('is_active',1)->whereNull('user_id')->get();
                    return $this->sendResponse($medical, trans("message.medical_credentials_list"), 200);

                }else{

                    return $this->sendError(trans('message.invalid_get_credentials'), [], 400);
                }
            }
        } catch (Exception $e) {

            Log::error('Error caught: "completeSignUpSteps" ' . $e->getMessage());
            return $this->sendError(trans('message.something_went_wrong'), [], 400);
        }
    }
    #------------------------------------     E N D ----------------------------------------#

}
