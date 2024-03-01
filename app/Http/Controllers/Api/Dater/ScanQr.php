<?php

namespace App\Http\Controllers\Api\Dater;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPortfolio;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\GetUserService;
use App\Services\UserProfileUpdate;
use Illuminate\Support\Facades\Storage;

class ScanQr extends BaseController
{
    //

    #----------  S C A N      Q R     C O D E       O N    D A T E  --------------#
    public function scanQrCode(Request $request){

        $validation =       Validator::make($request->all(),['qr_text'=>'required']);
        if ($validation->fails()) {

            return $this->sendResponsewithoutData($validation->errors()->first(), 422);

        } else {
            $qrText          =   $request->qr_text;
            $urlParts        =   parse_url($qrText);
            parse_str($urlParts['query'], $queryParams);
            if(isset($queryParams['u']) && !empty($queryParams['u'])){

                $originalId   = substr($queryParams['u'], 4);

                //check match point is already given or not

                



            }else{
                return $this->sendResponsewithoutData(trans('message.invalid_qr'), 400);
            }
        }
    }
    #------------------------------- E N D  --------------------------------------#

}
