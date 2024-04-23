<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UserPreferenceValidation;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\UserPreference;
use Illuminate\Support\Facades\DB;
use App\Services\GetUserService;
use App\Services\UserProfileUpdate;
use Illuminate\Support\Facades\Validator;
use App\Models\UserRole;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\BaseController;
use App\Models\UserStat;
use App\Models\Job_status;
use App\Models\MyTeam;
use App\Models\MyTeamMember;
use Carbon\Carbon;
use App\Rules\NoSpecialCharacters;
use App\Models\User;
use App\Rules\AdultValidation;
use App\Models\Gender;
use App\Models\Pronouns;
use App\Models\Ethnicity;
use App\Rules\ExistsInParticipate;
use App\Models\UserParticipantCategory;
use App\Rules\ExistsInInterest;
use App\Models\UsersInterest;
use App\Models\UserDocuments;
use App\Models\Specialty;
use App\Models\UserMedicalCredentials;
class SignStepsController extends BaseController
{
    //
    protected $getUser ,$authId;
    #--------------  S I G N U P        P R O C E S S  ------------------------#

    public function __construct(GetUserService $getUser)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });
        $this->getUser = $getUser;
    }
    public function completeSignUpSteps(Request $request){

        $auth           =   Auth::user();
        $step           =   $request->step;

        $validator = Validator::make($request->all(), ['step' => 'required', 'between:1,7']);
        // Add custom rule for no special characters        
        if ($validator->fails()) {
            
            return $this->sendResponsewithoutData($validator->errors()->first(), 422);

        } else {

            $userStep   =   User::select('complete_step','is_email_verified')->where('id',$auth->id)->first();
          
            if($userStep->is_email_verified!=1){

                return $this->sendResponsewithoutData(trans("message.Please_verify_account"), 403);

            }
            if($step==1){

                return $this->step1($request,$auth);

            }elseif ($step>1) {
               
                $previousCompleted      =   $this->checkPrevious($step,$auth->id);

                if(!$previousCompleted){

                    return $this->checkSteps($request,$auth->id);

                }else{

                    return $previousCompleted;
                }
            }
        }
    }
    #--------------------------------    E N D   ------------------------------#

    #---------------*************** S T E P  1 ****************----------------#
    public function step1($request,$auth){ //complete username and name 
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'user_name' =>'required|min:3|regex:/^[a-zA-Z0-9]+$/|unique:users,user_name,'.$auth->id,
                'name' => ['nullable', 'string', 'regex:/^[a-zA-Z\s]+$/']],
            ['user_name.regex'=>"Use only letter and number",'user_name.min'=>'user name must be 3 character long']);
            // Add custom rule for no special characters
            $validator->addRules(['user_name' => new NoSpecialCharacters]);
            
            if ($validator->fails()) {
                
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {
                $userStep1                  =   User::find($auth->id);
                // $userStep1              =   new User();
                $userStep1->user_name       =   filter_text($request->user_name);

                if(isset($request->name) && !empty($request->name)){

                    $userStep1->name        =   filter_text($request->name);
                }
                $userStep1->complete_step   =  1;
                $userStep1->save();   
                DB::commit();
                $userData   =   $this->getUser->getUser($auth->id);
                return $this->sendResponse($userData, trans("message.steps_completed"), 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "step1" ' . $e->getMessage());
            return $this->sendError("Failed to update", [], 400);
        }
    }
    #---------------*************** S T E P  1 ****************----------------#

    #---------------*************** S T E P  2 ****************----------------#

    public function step2($request,$auth_id){ //complete username and name 
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'dob' => ['required', 'date', 'date_format:m/d/Y', new AdultValidation],
                'gender' => ['required', 'integer', 'exists:genders,id'],'pronoun'=>['required', 'integer', 'exists:pronouns,id','ethnicity'=>'required','integer','exists:ethnicities,id']],
            ['pronouns.exists'=>"Invalid pronoun",'ethnicity.exists'=>'Invalid ethnicity']);
            
            if ($validator->fails()) {
                
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {
               
                //check if step1 is compeleted
                $userStep2                  =   User::find($auth_id);
                $userStep2->dob             =   Carbon::createFromFormat('m/d/Y', $request->dob)->format('Y-m-d'); 
                $userStep2->gender          =   $request->gender;
                $userStep2->pronoun         =   $request->pronoun;
                $userStep2->ethnicity       =   $request->ethnicity;
                $userStep2->complete_step   =   2;

                $userStep2->save();   
                DB::commit();
                $userData                  =   $this->getUser->getUser($auth_id);
                // dd($userData);
                return $this->sendResponse($userData, trans("message.steps_completed"), 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "step2" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------*************** S T E P  2****************----------------#




    #---------------*************** S T E P  3 ****************----------------#
    public function step3($request,$auth_id){               //complete username and name 
        DB::beginTransaction();
        try {
            
            $validator = Validator::make($request->all(), [
                'reasons' => ['required','array', new ExistsInParticipate],
                'reasons.*' => ['required', 'integer']],
            ['reasons.array'=>"Invalid data type"]);
            
            if ($validator->fails()) {
                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {

                $resons                     =  $request['reasons'];
                foreach ($resons as $resons) {
                    UserParticipantCategory::updateOrCreate(
                        ['user_id' => $auth_id, 'participant_id' => $resons],
                        ['is_active' => 1]
                    );
                }
                //check if step1 is compeleted
                $userStep3                  =   User::find($auth_id);
                $userStep3->complete_step   =   3;
                $userStep3->save();   
                DB::commit();
                $userData                  =   $this->getUser->getUser($auth_id);
                return $this->sendResponse($userData, trans("message.steps_completed"), 200);
            }
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Error caught: "step3" ' . $e->getMessage());
            return $this->sendError("Failed to update", [], 400);
        }
    }


    #---------------*************** S T E P  4 ****************----------------#
    public function step4($request,$auth_id){               //complete interest 

        DB::beginTransaction();
        try {
            
            $validator = Validator::make($request->all(), [
                'interest' => ['required','array', new ExistsInInterest],
                'interest.*' => ['required','integer']],
            ['reasons.array'=>"Invalid data type"]);
        
            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {

                $interests                     =  $request['interest'];
                foreach ($interests as $interest_id) {
                    UsersInterest::updateOrCreate(
                        ['user_id' => $auth_id, 'interest_id' => $interest_id],
                        ['is_active' => 1]
                    );
                }
                //check if step1 is compeleted
                $userStep3                  =   User::find($auth_id);
                $userStep3->complete_step   =   4;
                $userStep3->save();   
                DB::commit();
                $userData                  =   $this->getUser->getUser($auth_id);
                return $this->sendResponse($userData, trans("message.steps_completed"), 200);
            }
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Error caught: "step4" ' . $e->getMessage());
            return $this->sendError("Failed to update", [], 400);
        }
    }
    #---------------*************** S T E P  4 ****************----------------#



    #---------------*************** S T E P  5 ****************----------------#
    public function step5($request,$auth_id){               //********GUIDELINES*********\\    

        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'guidelines' => ['required','in:1']]);
                
            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {

                $userStep3                  =   User::find($auth_id);
                $userStep3->complete_step   =   5;
                $userStep3->guideliness     =   1;
                $userStep3->save();   
                DB::commit();
                $userData                  =   $this->getUser->getUser($auth_id);
                return $this->sendResponse($userData, trans("message.steps_completed"), 200);
            }
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Error caught: "step5" ' . $e->getMessage());
            return $this->sendError("Failed to update", [], 400);
        }
    }
    #---------------*************** S T E P  5 ****************----------------#



    #---------------*************** S T E P  6 ****************----------------#
    public function step6($request,$auth_id){               //**** IDENTIFY     PROOF ******/ 

        DB::beginTransaction();
        try {

            $validator = Validator::make($request->all(), [
                'identity_proof' => 'required|file|mimes:jpeg,png,pdf|max:2048', 
                'identity_type' => 'required|integer|exists:document_types,id', 
            ]);
            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {

                if ($request->hasFile('identity_proof')) {

                    $identity_proof                 =       $request->file('identity_proof');
                    $useridentity                   =       upload_file($identity_proof, 'useridentity');
                    $userDocument                   =       new UserDocuments();
                    $userDocument->user_id          =       $auth_id;
                    $userDocument->document_type    =       $request['identity_type'];
                    $userDocument->document         =       $useridentity;
                    $userDocument->save();


                    $userStep6                      =   User::find($auth_id);
                    $userStep6->complete_step       =   6;
                    $userStep6->save();   
                    DB::commit();
                    $userData                       =   $this->getUser->getUser($auth_id);
                    return $this->sendResponse($userData, trans("message.steps_completed"), 200);
                    
                    } else {

                        return $this->sendResponsewithoutData("Invalid portfolio or position", 400);
                    }
                }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error caught: "step6" ' . $e->getMessage());
            return $this->sendError("Failed to update", [], 400);
        }
    }
    #---------------*************** S T E P  6 ****************----------------#



        #---------------*************** S T E P  7 ****************----------------#


  
    public function step7($request,$auth_id){               //*********complete verify credentials *********
    
        DB::beginTransaction();
        try {

            $validator = Validator::make($request->all(), [
                'degree_type'=>'required|integer|exists:medical_credentials,id',
                'medicial_document' => 'required|file|mimes:jpeg,png,pdf|max:2048', 
            ]);

            if ($validator->fails()) {

                return $this->sendResponsewithoutData($validator->errors()->first(), 422);
    
            } else {

                $specialty          =   $request->specialty;

                if (is_numeric($specialty)) {

                    $isExist        =   Specialty::where('id', $specialty)->exists();

                    return $this->sendResponsewithoutData(trans('message.invalid_specialty'), 422);

                } else {
                    // Check if the specialty name exists
                    $existingSpecialty      =       Specialty::where('name', $specialty)->whereNull('user_id')->first();

                    if (empty($existingSpecialty)) {

                        $addSpecialty       =       removeSpecialCharsAndFormat($specialty);
                        // If the specialty name doesn't exist, attempt to add it as a new specialty
                        $newSpecialty       =       Specialty::create(['name' => $addSpecialty,'user_id'=>$auth_id]);

                        $specialty          =       $newSpecialty->id;
                    }
                }

                if ($request->hasFile('medicial_document')) {

                    $identity_proof                     =       $request->file('medicial_document');
                    $userMedicialDoc                    =       upload_file($identity_proof, 'medicial_document');
                    $userDocument                       =       new UserMedicalCredentials();
                    $userDocument->user_id              =       $auth_id;
                    $userDocument->medicial_degree_type =       $request['degree_type'];
                    $userDocument->medicial_document    =       $userMedicialDoc;
                    $userDocument->specialty            =       $specialty;

                    
                    $userDocument->save();
                    $userStep7                      =   User::find($auth_id);
                    $userStep7->complete_step       =   7;
                    $userStep7->save();   
                    DB::commit();
                    $userData                           =       $this->getUser->getUser($auth_id);
                    return $this->sendResponse($userData, trans("message.steps_completed"), 200);
                }
            }
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Error caught: "step7" ' . $e->getMessage());
            return $this->sendError("Failed to update", [], 400);
        }
    }
    #----------------  S T E P     5 --------------#









    #------------------- E N D  --------------------#

    #------------   C H E C K       S T E P         W I T H   C A S E -------------------#

    public function checkSteps($request,$userid)
    {
        $step     = $request->step;
       
        switch ($step) {
            case '2':
                return $this->step2($request,$userid);
                break;
            case '3':
                return $this->step3($request,$userid);

                break;

            case '4':
                return $this->step4($request,$userid);
                break;
            case '5':
                return $this->step5($request,$userid);
                break;
            case '6':
                return $this->step6($request,$userid);
                break;
            case '7':
                return $this->step7($request,$userid);
                break;
            default:
                return $this->sendResponsewithoutData(trans("message.complete_previous_step"), 400);
                break;
        }

    }

    public function checkPrevious($step,$userid){

        $checkPreviousStep      =   User::select('id','complete_step')->where('id',$userid)->first();

        if($checkPreviousStep['complete_step']>=$step ){

            //step already completed
            return $this->sendResponsewithoutData(trans("message.step_already_completed"), 400);


        }elseif ($checkPreviousStep['complete_step'] < ($step-1)) {
            
            return $this->sendResponsewithoutData(trans("message.complete_previous_step"), 400);

        }else{

            return false;
        }

    }
}
