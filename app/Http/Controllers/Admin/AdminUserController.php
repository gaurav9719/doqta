<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\PartnerMatch;
use App\Models\ParticipantCategory;
use App\Models\UserParticipantCategory;
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
        $auth = Auth::user();
        
        if ($request->ajax()) {

            $data = User::orderBy('id', 'desc')->where('role', 1);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('gender1', function ($row) {
                    if ($row->gender == 1) {
                        $gender = 'Male';
                    } elseif ($row->gender == 2) {
                        $gender = 'FeMale';
                    } else {
                        $gender = 'Other';
                    }
                    return $gender;
                })

                ->addColumn('registration_date', function ($row) {
                    if (isset($row->created_at)) {
                        $date = $row->created_at->format('d-m-Y');
                        return $date;
                    }
                })
                ->addColumn('logo', function ($row) {
                    if (isset($row->profile) && !empty($row->profile)) {
                        if (Storage::disk('public')->exists($row->profile)) {
                            $img      = "storage/" . $row->profile;
                            $imageUrl = asset($img);
                        } else {
                            $imageUrl = asset('assets/img/user/profile.png');
                        }
                    } else {
                        $imageUrl = asset('assets/img/user/profile.png');
                    }
                    $logo = '<img style="height:45px; width:45px;    border-radius:100%;" src="' . $imageUrl . '" />';
                    return $logo;
                })

                ->addColumn('email_verify', function ($row) {

                    if (isset($row->is_email_verified) && $row->is_email_verified == 1) {
                        $status = '<span class="badge badge-success text-bg-info" >Verified</span>';
                    } else {
                        $status = '<span class="badge badge-danger text-bg-warning">Not Verified</span>';
                    }
                    return $status;
                })
                ->addColumn('details', function ($row) {
                    return  '<button class="btn btn-lg btn-icon" onclick="viewProfile(' . $row->id . ')" title="View profile"><i class="material-icons">person</i></button>';
                })
                ->addColumn('status', function ($row) {
                    $statusBadge = $row->is_active == 1
                        ? '<div class="status_' . $row->id . '"><span class="badge badge-success" style="color:white;background-color:green;">Active</span></div>'
                        : '<div class="status_' . $row->id . '"><span class="badge badge-danger" style="color:white;background-color:red;">Inactive</span></div>';
                    return $statusBadge;
                })
                ->addColumn('edit', function ($row) {
                    $actionButton = $row->is_active
                        ? '<div class="status_btn' . $row->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatus(' . $row->id . ','.$row->is_active .')" title="Make Inactive"><i class="material-icons">block</i></button></div>'
                        : '<div class="status_btn' . $row->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatus(' . $row->id . ','.$row->is_active .')" title="Make Active"><i class="material-icons">check_circle</i></button></div>';
                    return $actionButton;
                })
                ->rawColumns(['gender', 'status', 'email_verify', 'logo', 'edit', 'details'])
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
        $user = User::when(isset($id))->findOrFail($id);

        if (isset($user)) {
            #user profile
            if (isset($request->type) && $request->type == 1) {

                if (isset($user->profile) && !empty($user->profile)) {
                    if (Storage::disk('public')->exists($user->profile)) {
                        $img      = "storage/" . $user->profile;
                        $imageUrl = asset($img);
                    } else {
                        $imageUrl = asset('assets/img/user/profile.png');
                    }
                } else {
                    $imageUrl = asset('assets/img/user/profile.png');
                }
                
                if ($user->gender == 1) {
                    $gender = 'Male';
                } elseif ($user->gender == 2) {
                    $gender = 'FeMale';
                } else {
                    $gender = 'Other';
                }

                $user['gender'] = $gender;

                $user['profile'] = $imageUrl;

                $user['registration_date'] = $user->created_at->format('d-m-Y');

                $user['roles']=ParticipantCategory::select('name')->whereHas('user_participant_category',function($query) use ($id){
                    $query->where('user_id',$id);
                })->get();

                return response()->json(['status' => 200, 'user' => $user]);
            }
          
            else {
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
        $user = User::find($id);
        if (isset($user)) {
            if (isset($user) && isset($user->is_active) && $user->is_active == 1) {
                $user->is_active = '0';
                $user->save();
            } else {
                $user->is_active = '1';
                $user->save();
            }
          return User::find($id);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    function documentVerification()
    {
        Paginator::useBootstrap();
        $auth = Auth::user();
        $users = User::orderBy('id', 'desc')->with('user_documents', 'user_medical_certificate')->paginate(10);
        return view('admin/document-verification', compact('users', 'auth'));
    }
}
