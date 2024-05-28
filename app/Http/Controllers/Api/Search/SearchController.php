<?php

namespace App\Http\Controllers\Api\Search;

use App\Http\Controllers\Controller;
use App\Models\UsersInterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Services\Like\likesService;
use App\Traits\IsCommunityJoined;
use App\Models\ReportToComment;

class SearchController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $authId         =   Auth::id();
        $userInterest   =   UsersInterest::with('interest',function($query){

            $query->select('id','name');

        })->where('user_id',$authId)->get();
        if(isset($userInterest[0]) && !empty($userInterest[0])){
            
            $interest       =   [];

            foreach($userInterest as $interest){

                if(isset($interest['interest']) && !empty($interest['interest']['name']))
                {

                    $interest[]  = $interest['interest']['name'];

                }
            }

            if(isset())



        }else{





        }





    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
