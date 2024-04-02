<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PointSystem as pointSystems;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Exception;
use Illuminate\Support\Facades\Log;

class PointSystem extends BaseController
{
    //

    protected $authId;
    public function __construct()
    {

        $this->authId = Auth::id();
    }

    #-----------------------------  P O I N T     S Y S T E M  ------------------------------#
    public function pointSystem(Request $request)
    {
        try {
            $points = pointSystems::where("user_role", Auth::user()->current_role_id)->get();

            return $this->sendResponse($points, trans("message.pointSystem"), 200);

        } catch (Exception $e) {

            Log::error('Error caught: "pointSystam/pointSystam" ' . $e->getMessage());
            
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #----------------------------- ********* E N D *********  ------------------------------#





}
