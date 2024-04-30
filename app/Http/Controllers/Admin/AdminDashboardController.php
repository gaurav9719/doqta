<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use App\Models\Post;
use Carbon\Carbon;
use App\Models\User;
use App\Models\MyTeam;
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
        $last_month=Carbon::today()->subDays(30);

        $user_count=User::whereDate('created_at', '>=', $last_month)->count();
        $community_count=Group::whereDate('created_at', '>=', $last_month)->count();
        $post_count=Post::whereDate('created_at', '>=', $last_month)->count();
        // $auth_user=Auth::user();
        // return $match_count;
        $data1=array(
            'user'=>$user_count,
            'community'=>$community_count,
            'post'=>$post_count,
        );
        // return $data1;
        
        if($request->ajax()){
            
            $data=User::where('is_active',1)->where('role', '!=' , 3)
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
                $date=$row->created_at->format('d-m-Y');
                return $date;
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

        
        return view('admin.index', compact('data1', 'auth'));
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
