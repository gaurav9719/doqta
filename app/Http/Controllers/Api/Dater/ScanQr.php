<?php

namespace App\Http\Controllers\Api\Dater;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Api\BaseController;
use App\Models\MyRoster;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPortfolio;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\GetUserService;
use App\Services\UserProfileUpdate;
use Illuminate\Support\Facades\Storage;
use App\Models\PointHistory;
use App\Models\PartnerMatch;
use App\Models\PointSystem;

class ScanQr extends BaseController
{
    //

    #----------  S C A N      Q R     C O D E       O N    D A T E  --------------#
    public function scanQrCode(Request $request){

        $validation =       Validator::make($request->all(),['qr_text'=>'required']);
        if ($validation->fails()) {

            return $this->sendResponsewithoutData($validation->errors()->first(), 422);

        } else {

            $authUserId         =   Auth::id();
            $qrText             =   $request->qr_text;
            $urlParts           =   parse_url($qrText);
            parse_str($urlParts['query'], $queryParams);

            if(isset($queryParams['u']) && !empty($queryParams['u'])){

                $originalId     =   substr($queryParams['u'], 4);
                //check match point is already given or not
                $isUserExist    =   User::where('id',$originalId)->exists();
                
                if($isUserExist){
                    //check if point already given or not
                    //check if match is exist or not
                    $isMatchExist   =   PartnerMatch::where(function($query) use ($authUserId, $originalId) {
                        $query->where('user1_id', $authUserId)
                              ->where('user2_id', $originalId);
                    })->orWhere(function($query) use ($authUserId, $originalId) {
                        $query->where('user1_id', $originalId)
                              ->where('user2_id', $originalId);
                    })->where('is_active',1)->first();

                    if(isset($isMatchExist) && !empty($isMatchExist)){

                        $matchPoint                 =       PointSystem::where('slug','go-on-a-date-with-a-match')->first();

                        if(isset($matchPoint) && !empty($matchPoint)){
                              //check duplicate points
                            $userPoint              =       PointHistory::where(function($query) use($originalId,$authUserId,$matchPoint){
                                $query->where('user_id', $authUserId)
                                    ->where('reference_user_id', $originalId)
                                    ->where('point_id',$matchPoint->id)
                                    ->where('role_id',2);
                            })->exists();

                            if(!$userPoint){                        // add point to 

                                PointHistory::create(['user_id'=>$authUserId,'reference_user_id'=>$originalId,'point_id'=>$matchPoint->id,'role_id'=>2,'points'=>$matchPoint->points]);

                                $recruiter      =   MyRoster::select('recruiter_id')->where(['match_id'=>$matchPoint->id,'user_id'=>$authUserId,'is_active'=>1])->first();

                                if(isset($recruiter) && !empty($recruiter)){

                                    $datePoint                 =       PointSystem::where('slug','dater-goes-on-a-date-with-match')->first();
                                    PointHistory::create(['user_id'=>$recruiter->recruiter_id,'reference_user_id'=>$authUserId,'point_id'=>$datePoint->id,'role_id'=>3,'points'=>$datePoint->points]);
                                }
                            }else{
                                return $this->sendResponsewithoutData(trans('message.you_have_received_already'), 400);
                            }
                        }
                    }else{

                        return $this->sendResponsewithoutData(trans('message.invalid_qr'), 400);
                    }
                }else{
                    return $this->sendResponsewithoutData(trans('message.invalid_qr'), 400);
                }
            }else{
                return $this->sendResponsewithoutData(trans('message.invalid_qr'), 400);
            }
        }
    }
    #------------------------------- E N D  --------------------------------------#
}
