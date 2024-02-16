<?php

namespace App\Http\Controllers\Api\Dater;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Validator;
class PortfolioController extends BaseController
{
    //
    #----------- U P L O A D    P O R T F O L I O S --------------#
    public function index(Request $request){

        Validator::make($request->all(), ['portfolio'=>'required']);
        if ($validator->fails()) {  

            return response()->json([
                'success'   => 422,
                'message'   => $validator->errors()->first(),
            ],422);

        }else{


    }
    #----------- U P L O A D    P O R T F O L I O S --------------#


}
