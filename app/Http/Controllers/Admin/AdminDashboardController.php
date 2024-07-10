<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use App\Models\Post;
use Carbon\Carbon;
use App\Models\User;
use App\Models\MyTeam;
use App\Models\UserMedicalCredentials;
use App\Models\UserParticipantCategory;
use App\Models\MyRoster;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $auth=Auth::user();

        if($request->ajax()){
            
            $data=User::where('is_active',1)->where('role', 1)
            ->orderBy('id', 'desc')->limit(20);
            return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('gender', function($row){
                if($row->gender == 1){
                    $gender = 'Male';
            }elseif($row->gender == 2){
                    $gender = 'FeMale';

            }else{
                    $gender = 'Other';
            }
                return $gender;
            })
           

            ->addColumn('registration_date', function($row){
                if(isset($row->created_at)){
                    $date=$row->created_at->format('d-m-Y');
                    return $date;
                }
            })
            ->addColumn('logo', function($row){
                if(isset($row->profile) && !empty($row->profile)){
                    if (Storage::disk('public')->exists($row->profile)) {
                        $img      = "storage/".$row->profile;
                        $imageUrl = asset($img);
                    }else{
                        $imageUrl = asset('assets/img/user/profile.png');
                    }
                }
                else{
                    $imageUrl = asset('assets/img/user/profile.png');
                }
                $logo = '<img style="height:45px; width:45px; border:1px solid black; border-radius:100%;" src="'.$imageUrl.'" />';
                return $logo;

            })
            ->addColumn('email_verify', function($row){
                
                if(isset($row->is_email_verified) && $row->is_email_verified == 1){
                    $status = '<span class="badge badge-success text-bg-info" >Verified</span>';
                }
                else{
                    $status = '<span class="badge badge-danger text-bg-warning">Not Verified</span>';
                }
                return $status;
            })
            
            ->rawColumns(['gender','logo','registration_date','email_verify'])
            ->make(true);

            
        }

        $patient_count= UserParticipantCategory::whereHas('user',function($query){
            $query->where('role',1);
        })
        ->where(['participant_id'=>1])
        ->count();

        $caretaker_count= UserParticipantCategory::whereHas('user',function($query){
            $query->where('role',1);
        })
        ->where(['participant_id'=>2])
        ->count();
          
        $health_provider_count= UserParticipantCategory::whereHas('user',function($query){
            $query->where('role',1);
        })
        ->where(['participant_id'=>3])
        ->count();

        $post_count=Post::count();

        $community_count=Group::count();

        return view('admin.index',compact( 'patient_count' , 'caretaker_count' , 'health_provider_count' , 'post_count' , 'community_count', 'auth'));
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
