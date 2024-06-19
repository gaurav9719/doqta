<?php

namespace App\Http\Controllers\Api\Discover;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AddCommunity;
use App\Http\Requests\EditCommunity;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\GroupMemberRequest;
use Carbon\Carbon;
use App\Services\Discover\DicoverService;
use Exception;
use App\Models\User;

class DiscoverController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    protected $discover;

    public function __construct(DicoverService $discover)
    {
        $this->discover     =       $discover;
      
    }
    //
    public function index(Request $request)
    {
        $authId         =   Auth::id();

        return $this->discover->discover($request,$authId,3);
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

    public function topHealthProvider(Request $request){
        $authId     =   Auth::id();
        
        return $this->discover->topCommunityThisWeek($request,$authId,3,1);

    }

    #------------------------ D I S C O V E R        P A R T S   ----------------------# 
    public function discoverPart(Request $request){

        try {
            
            $authId         =   Auth::id();

            $validation     =   Validator::make($request->all(),['type'=>'required|integer|in:0,3,4',
                                'inner_type' => [
                                    'required_if:type,0,3,4', // inner_type is required if type is 0, 3, or 4
                                    'integer',
                                    // inner_type must be one of 0, 3, or 4 if provided
                                ],]);
            if($validation->fails()){

                return $this->sendResponsewithoutData($validation->errors()->first(), 422);

            }else{

                $limit      =   $request->limit??10;

                if($request->type==0){      // All type

                    if($request->inner_type==1){ #----------support_shared_interests ---------------#

                        return $this->discover->supportShareInterest($request,$authId,$limit);

                       


                    }elseif ($request->inner_type==2) { #----------top_communities_this_week ---------------#
                       
                        return $this->discover->topCommunityThisWeek($request,$authId,$limit);

                        
                    }elseif ($request->inner_type==3) { #----------articles ---------------#
                        
                        return $this->discover->topArticles($request,$authId,$limit);


                    }
                    elseif ($request->inner_type==4) {  #----------top_videos ---------------#
                        
                        return $this->discover->topVideos($request,$authId,$limit);

                    }
                }
                // if($request->type==1){  #-----------Discover posts ---------------#

                
                    
                // }

                // if($request->type==2){      #-----------Discover communities ---------------#


                
                // }

                if($request->type==3){       #-----------Discover people ---------------#

                
                    if($request->inner_type==1){ #----------support_shared_interests ---------------#

                        // return $this->discover->supportUsers($request,$authId,$limit);

                        return supportUserS($request, $authId, $limit);

                    }elseif ($request->inner_type==2) { #----------top_communities_this_week ---------------#
                       
                        // return $this->discover->topHealthProvider($request,$authId,$limit);
                        return topHealthProvider($request, $authId, $limit);

                        
                    }elseif ($request->inner_type==3) { #----------care_takers ---------------#
                        
                        return supportUserS($request, $authId, $limit, "",1);

                        // return $this->discover->careTakerBySearch($request,$authId,$limit);

                    }
                }

                if($request->type==4){      #-----------Discover media ---------------#


                    if($request->inner_type==1){ #--------- T O P   A R T I C L E S ---------------#

                         return $this->discover->topArticles($request,$authId,$limit);

                    }elseif ($request->inner_type==2) { #---------- T O P   V I D E O ---------------#
                       
                        return $this->discover->topVideos($request,$authId,$limit);
                    }
                }
            }
        }catch(Exception $e){

            DB::rollback();
            Log::error('Error caught: "discoverPart" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #------------------------ D I S C O V E R        P A R T S   ----------------------# 

}
