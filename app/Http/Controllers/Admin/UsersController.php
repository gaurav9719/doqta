<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business_campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\User;
use Carbon\Carbon;
use Hash;
use DataTables;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use App\Models\UserBusiness;
use App\Models\BusinessPosition;
use App\Models\Country;
use App\Models\Category;
use App\Models\Post_type;

class UsersController extends Controller
{
    //

    public function influencer(Request $request){

        if ($request->ajax()) {

        
                $data = User::select('id',DB::raw("CONCAT(first_name, ' ', last_name) as full_name"),'email','bio','phone_no','profile_pic','is_active','created_at')->where(['user_role'=>1])->get();

           
            return Datatables::of($data)->addIndexColumn()

            ->addColumn('is_active', function ($status) {

                if ($status->is_active == 1) {

                    $Activestatus = '<span id="x-status'.$status->id . '"><a  data-src="' . Crypt::encryptString($status->id). '" xyz="0" data-div="x-status'.$status->id .'" onclick="chageUserStatus(this)"  class="btn btn-success enable" href ="javascript:void(0)">Active</a></span>';

                } else {

                    $Activestatus = '<span id="x-status'.$status->id . '"><a  data-src="' . Crypt::encryptString($status->id) . '" xyz="1" data-div="x-status'.$status->id .'" onclick="chageUserStatus(this)"  class="btn btn-danger enable" href="javascript:void(0)">Inactive</a></span>';
                }
                return $Activestatus;

            })

            ->addColumn('created_at', function ($created_at) {


                return Carbon::parse($created_at->created_at)->format('Y-m-d H:i:s');

               
            })

            ->addColumn('profile_pic', function ($profile) {

                if(!empty($profile->profile_pic)){

                    $profile        =   $profile->profile_pic;

                    if (File::exists(asset($profile->profile_pic))) {

                        $imageUrl = asset($profile);
                      
                    }else{

                        $imageUrl        =   asset('dummy_image/dummy_user.png');
                    }

                }else{

                    $imageUrl            =   asset('dummy_image/dummy_user.png');

                }
                return '<img src="'. $imageUrl.'" border="0" width="40" class="img-rounded" align="center" />';
            })


            // ->addColumn('action', function ($row) {

            //     // $actionBtn = '<div class="table-data-feature"> <a  href="'. route('blog.view', Crypt::encryptString($row->id)) . '" class="item" data-toggle="tooltip" data-placement="top" title="View blog"> <i class="zmdi zmdi-eye"></i></a>


            //     $actionBtn = '<div class="table-data-feature "> <a  href="'. route('blog.view',['slug' => Crypt::encryptString($row->id)]).'" class="item" data-toggle="tooltip" data-placement="top" title="View blog"> <i class="zmdi zmdi-eye"></i></a>

            //     <a  href="'.route('blog.view',['slug'=>Crypt::encryptString($row->id),'type'=>1]).'" class="item" data-toggle="tooltip" data-placement="top" title="Edit Blog"> <i class="zmdi zmdi-edit"></i></a>



            //     <a href="javascript:void(0)" class="item" data-toggle="tooltip" data-placement="top" title="Delete Blog"  data-src="'.$row->id.'"   onclick="deleteBlog('.$row->id.')" data_src="Crypt::encryptString($row->id)"><i class="zmdi zmdi-delete"></i></a></div>';
            //     return $actionBtn;
            // })
            ->rawColumns(['created_at', 'is_active','profile_pic'])
                ->make(true);
        }
        
        return view('Admin.pages.Influencers');

    }
    
    public function activeInactive(Request $request){

    
        try {

            //code...
        
            if($request->ajax()){
                $userId         =   Crypt::decryptString($request->uid);

               
                $user           =   User::find($userId);
                $user->is_active=   $request->status;
                if($user->save()){  //updated
                    
                    if($request->status==1){

                        $Activestatus = '<a  data-src="' . Crypt::encryptString($userId). '" xyz="0" data-div="x-status'.$userId.'" onclick="chageUserStatus(this)"  usd ="'.$userId.'" class="btn btn-success enable" href ="javascript:void(0)">Active</a>';

                        $message      = "Successfully Activated";
                    }else{
                        $Activestatus = '<a  data-src="' . Crypt::encryptString($userId) . '" xyz="1" data-div="x-status'.$userId.'" onclick="chageUserStatus(this)"  class="btn btn-danger enable" href="javascript:void(0)">Inactive</a>';
                        $message      = "Successfully Inactived";
                    }
                    return response()->json(['status'=>200,'html'=>$Activestatus,'message'=>$message ]);

                }else{  // not updated

                    return response()->json(['status'=>400,'html'=>""]);
                }
            }
        } catch (Exception $e) {
            return response()->json(['status'=>400,'html'=>$e->getMessage()]);

        }
    }


    public function business(Request $request){

        if ($request->ajax()) {

           

            $data = User::select('id',DB::raw("CONCAT(first_name, ' ', last_name) as full_name"),'email','bio','phone_no','profile_pic','is_active','created_at')->where(['user_role'=>2])->get();



            return Datatables::of($data)->addIndexColumn()

            ->addColumn('is_active', function ($status) {

                if ($status->is_active == 1) {

                    $Activestatus = '<span id="x-status'.$status->id . '"><a  data-src="' . Crypt::encryptString($status->id). '" xyz="0" data-div="x-status'.$status->id .'" onclick="chageUserStatus(this)"  class="btn btn-success enable" href ="javascript:void(0)">Active</a></span>';

                } else {

                    $Activestatus = '<span id="x-status'.$status->id . '"><a  data-src="' . Crypt::encryptString($status->id) . '" xyz="1" data-div="x-status'.$status->id .'" onclick="chageUserStatus(this)"  class="btn btn-danger enable" href="javascript:void(0)">Inactive</a></span>';
                }
                return $Activestatus;

            })

            ->addColumn('created_at', function ($created_at) {


                return Carbon::parse($created_at->created_at)->format('Y-m-d H:i:s');

               
            })

            ->addColumn('profile_pic', function ($profile) {

                if(!empty($profile->profile_pic)){

                    $profile        =   $profile->profile_pic;

                    if (File::exists(asset($profile->profile_pic))) {

                        $imageUrl = asset($profile);
                      
                    }else{

                        $imageUrl        =   asset('dummy_image/dummy_user.png');
                    }

                }else{

                    $imageUrl            =   asset('dummy_image/dummy_user.png');

                }
                return '<img src="'. $imageUrl.'" border="0" width="40" class="img-rounded" align="center" />';
            })


            // ->addColumn('action', function ($row) {

            //     // $actionBtn = '<div class="table-data-feature"> <a  href="'. route('blog.view', Crypt::encryptString($row->id)) . '" class="item" data-toggle="tooltip" data-placement="top" title="View blog"> <i class="zmdi zmdi-eye"></i></a>


            //     $actionBtn = '<div class="table-data-feature "> <a  href="'. route('blog.view',['slug' => Crypt::encryptString($row->id)]).'" class="item" data-toggle="tooltip" data-placement="top" title="View blog"> <i class="zmdi zmdi-eye"></i></a>

            //     <a  href="'.route('blog.view',['slug'=>Crypt::encryptString($row->id),'type'=>1]).'" class="item" data-toggle="tooltip" data-placement="top" title="Edit Blog"> <i class="zmdi zmdi-edit"></i></a>



            //     <a href="javascript:void(0)" class="item" data-toggle="tooltip" data-placement="top" title="Delete Blog"  data-src="'.$row->id.'"   onclick="deleteBlog('.$row->id.')" data_src="Crypt::encryptString($row->id)"><i class="zmdi zmdi-delete"></i></a></div>';
            //     return $actionBtn;
            // })
            ->rawColumns(['created_at', 'is_active','profile_pic'])
                ->make(true);
        }
        
        return view('Admin.pages.Business');

    }




    public function Userbusiness(Request $request){

        if ($request->ajax()) {

            $businessId     =   $request->id;

            $data = UserBusiness::select('id','business_name','position','about','phone_no','business_profile','is_active','created_at')->where(['user_id'=>$businessId])->get();



            return Datatables::of($data)->addIndexColumn()

            ->addColumn('is_active', function ($status) {

                if ($status->is_active == 1) {

                    $Activestatus = '<span id="bx-status'.$status->id . '"><a  data-src="' . Crypt::encryptString($status->id). '" xyz="0" data-div="bx-status'.$status->id .'" onclick="chagebusinessStatus(this)"  class="btn btn-success enable" href ="javascript:void(0)">Active</a></span>';

                } else {

                    $Activestatus = '<span id="bx-status'.$status->id . '"><a  data-src="' . Crypt::encryptString($status->id) . '" xyz="1" data-div="bx-status'.$status->id .'" onclick="chagebusinessStatus(this)"  class="btn btn-danger enable" href="javascript:void(0)">Inactive</a></span>';
                }
                return $Activestatus;

            })

            ->addColumn('created_at', function ($created_at) {


                return Carbon::parse($created_at->created_at)->format('Y-m-d H:i:s');

               
            })

            ->addColumn('position', function ($position) {

                $position       =   BusinessPosition::select('name')->where(['id'=>$position->position])->first();
                return ($position->name)?$position->name:null;
              

               
            })


            ->addColumn('profile_pic', function ($profile) {

                if(!empty($profile->business_profile)){

                    $profile        =   $profile->business_profile;

                    if (File::exists(asset($profile->business_profile))) {

                        $imageUrl = asset($profile);
                      
                    }else{

                        $imageUrl        =   asset('dummy_image/dummy_user.png');
                    }

                }else{

                    $imageUrl            =   asset('dummy_image/dummy_user.png');

                }
                return '<img src="'. $imageUrl.'" border="0" width="40" class="img-rounded" align="center" />';
            })


            ->addColumn('view_campaign', function ($row) {
                $encryptedId = Crypt::encrypt($row->id);

                // $actionBtn = '<div class="table-data-feature"> <a  href="'. route('blog.view', Crypt::encryptString($row->id)) . '" class="item" data-toggle="tooltip" data-placement="top" title="View blog"> <i class="zmdi zmdi-eye"></i></a>

                return '<button type="button" class="btn btn-primary" onclick="businessCamp(\'' . $encryptedId . '\')">View Campaign</button>';

            })
            ->rawColumns(['created_at', 'is_active','profile_pic','position','view_campaign'])
                ->make(true);
        }
        
        return view('Admin.pages.Business');

    }



    #----------- GET BUSINESS CAMPAIGNS-----------------#
    public function getBusinessCampagins(Request $request){

        if($request->ajax()){

            if(isset($request->id)){

                $businessId     =   Crypt::decrypt($request->id);

                $campagins      =   Business_campaign::with(['campaign_owner'=>function ($query) {

                                    $query->select('id','first_name','last_name','profile_pic','gender');

                                    },'business_detail'=>function($business){

                                        $business->select('id','business_name','business_profile','website_url','position','about');

                                    }])->where(['business_id'=>$businessId])->get();



                if(isset($campagins)  && !empty($campagins)){

                    for ($i=0; $i < count($campagins) ; $i++) { 

                        $category                       =   Category::select('name')->where(['id' => $campagins[$i]['category_id']])->first();

                        $campagins[$i]['category_name']      =     (isset($campagins[$i]) && !empty($category)) ? $category['name'] : "";

                        if(isset($campagins[$i]['post_type']) && !empty($campagins[$i]['post_type'])){

                            $postType                               =   Post_type::find($campagins[$i]['post_type']);
    
                            if($postType){
    
                                $campagins[$i]['post_type_name']     =   ($postType->name)?$postType->name:null;
                                $campagins[$i]['post_type_image']    =   (isset($postType->image) && !empty($postType->image))?$postType->image:null;
                            }else{
    
                                $campagins[$i]['post_type_name']     =   null;
                                $campagins[$i]['post_type_image']    =   null;
                            }
    
                        }else{
                            
                            $campagins[$i]['post_type_name']     =   null;
                            $campagins[$i]['post_type_image']    =   null;
                        }
                    }
                }
                   
    
                    $campaginsModal =  view('Admin.modal_view.UserBusinessCampaign',compact('campagins'))->render();
    
                    return response()->json(['status' => 200,'html' => $campaginsModal,'message' => 'Campaigns']);
    
            }

                        
        }


            // $campaign['post_type_name']     =       isset($campaign['post_type']) && !empty($campaign['post_type']) ? Post_type::find($campaign['post_type'])->name : null;
    
    } 
    #----------- GET BUSINESS CAMPAIGNS-----------------# 






}
