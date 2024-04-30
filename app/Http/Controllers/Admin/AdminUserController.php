<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\PartnerMatch;

use App\Models\UserFollower;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $auth=Auth::user();
        // $last_month=Carbon::today()->subDays(30);
        // $user_count=User::whereDate('created_at', '>=', $last_month)->count();
        
        if($request->ajax()){
            
            $data=User::orderBy('id', 'desc')->where('role', '!=' , 3);
            return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('gender1', function($row){
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
                $logo = '<img style="height:45px; width:45px;    border-radius:100%;" src="'.$imageUrl.'" />';
                return $logo;
                
                
            })
            ->addColumn('status', function($row){
                if(isset($row->is_active) && $row->is_active == 1){
                    $status = '<span class="badge badge-success text-bg-success" >Active</span>';
                }
                else{
                    $status = '<span class="badge badge-danger text-bg-danger">Inactive</span>';
                }
                return $status;
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
            ->addColumn('edit', function($row){
                $status = '<button class="btn btn-lg btn-icon" onclick="viewProfile('.$row->id.')" title="View profile"><i class="material-icons">person</i></button>';
                // $status = $status .'<button class="btn btn-lg btn-icon" onclick="viewFollower('.$row->id.',2)" title="View follower"><i class="material-icons">groups_2</i></button>';
                // $status = $status .'<button class="btn btn-lg btn-icon" onclick="viewFollowing('.$row->id.',3)" title="View following"><i class="material-icons">diversity_3</i></button>';
                if(isset($row->is_active) && $row->is_active == 1){
                    $status = $status .'<button class="btn btn-lg btn-icon" onclick="changeStatus('.$row->id.')" title="Block User"><i class="material-icons">block</i></button>';
                }
                else{
                    $status = $status .'<button class="btn btn-lg btn-icon" onclick="changeStatus('.$row->id.')" title="Make Active"><i class="material-icons">check_circle</i></button>';
                }
                return $status;
            })
            ->rawColumns(['gender', 'status', 'email_verify' , 'logo', 'edit'])
            ->make(true);

            
        }

        
        return view('admin.users', compact('auth'));
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
    public function show(Request $request, string $id)
    {
        $user=User::when(isset($id))->findOrFail($id);

        if(isset($user)){
            #user profile
            if(isset($request->type) && $request->type == 1){
                
                if(isset($user->profile_pic) && !empty($user->profile_pic)){
                    if (Storage::disk('public')->exists($user->profile_pic)) {
                        $img      = "storage/".$user->profile_pic;
                        $imageUrl = asset($img);
                    }else{
                        $imageUrl = asset('assets/img/user/profile.png');
                    }
                }
                else{
                    $imageUrl = asset('assets/img/user/profile.png');
                }

                if($user->gender == 1){
                    $gender = 'Male';
                }elseif($user->gender == 2){
                        $gender = 'FeMale';

                }else{
                        $gender = 'Other';
                }
                

                $user['gender']=$gender;
                $user['profile']=$imageUrl;
                $user['registration_date']=$user->created_at->format('d-m-Y');
                return response()->json(['status'=>200, 'user'=>$user]);
                    
            }
            #get follower 
            // elseif(isset($request->type) && $request->type == 2){
            //     $user=User::findOrFail($id);
            //     if(isset($user)){
            //         $matches=UserFollower::where('user_id', $user->id)->distinct()->orderBy('id', 'desc')->with('follower')->get();
            //         if(count($matches) > 0){
                        
            //             for($i = 0; $i < count($matches); $i++){
            //                 $follower[]=$matches[$i]['follower'];
            //             }
            //             return response()->json(['status'=>200, 'data'=>$follower]);
            //         }
            //         else{
            //             return response()->json(['status'=>400]);
            //         }
            //     }
            // }
            // #get following
            // elseif(isset($request->type) && $request->type == 3){
            //     $user=User::findOrFail($id);
            //     if(isset($user)){
            //         $matches=UserFollower::where('follower_user_id', $user->id)->orderBy('id', 'desc')->get();
            //         if(count($matches) > 0){
            //             return response()->json(['status'=>200, 'data'=>$matches]);
            //         }
            //         else{
            //             return response()->json(['status'=>400]);
            //         }
            //     }
            // }
            else{
                return "not receive";
            }
        }

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
        $user=User::find($id);
        // $user->is_active
        // return $user;
        // $user=User::where('id', $id)->update(['is_active'=> 0]);
        // return "success";
        if(isset($user)){
            if(isset($user) && isset($user->is_active) && $user->is_active == 1){
                $user->is_active = '0';
                $user->save();
                return response()->json(['status'=>200, 'message' => 'User profile deactivated successfully']);
            }
            else{
                $user->is_active = '1';
                $user->save();
                return response()->json(['status'=>200, 'message' => 'User profile activated successfully']);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    function documentVerification(){
        Paginator::useBootstrap();
        $auth= Auth::user();
        $users=User::orderBy('id', 'desc')->paginate(10);

        return view('admin/document-verification', compact('users', 'auth'));
    }
}
