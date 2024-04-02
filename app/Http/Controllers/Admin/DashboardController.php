<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\User;
use Hash;
use App\Models\Business_campaign;

class DashboardController extends Controller
{
    //


    public function index(){

        $data['influencers']    =   User::where(['is_active'=>1,'user_role'=>1])->count();
        $data['business']       =   User::where(['is_active'=>1,'user_role'=>2])->count();
        $data['campaigns']      =   Business_campaign::where(['is_active'=>1,])->count();
        return view('Admin.pages.dashboard')->with($data);

    }





}
