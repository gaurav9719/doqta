<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Domain;
use App\Models\Employee;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Models\CorporativePlanUser;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminPartnersDomainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $auth = Auth::user();
        if ($request->ajax()) {
            
            $domain = Domain::select('domains.*')
            ->selectRaw("(SELECT COUNT(id) FROM users WHERE email LIKE CONCAT('%', domains.name)) AS total_users")
            ->orderBy('id', 'desc')
            ->get();
        
            
            $data = DataTables::of($domain)
                ->addIndexColumn()
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('d-m-Y');
                })
                ->addColumn('status', function ($row) {
                    $statusBadge = $row->is_active == 1
                        ? '<div class="status_domain' . $row->id . '"><span class="badge badge-success" style="color:white;background-color:green;">Whitelisted</span></div>'
                        : '<div class="status_domain' . $row->id . '"><span class="badge badge-danger" style="color:white;background-color:red;">Blacklisted</span></div>';
                    return $statusBadge;
                })
                ->addColumn('action', function ($row) {
                    $actionButton = $row->is_active
                        ? '<div class="status_btn_domain' . $row->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatusDomain(' . $row->id . ',' . $row->is_active . ')" title="Make Inactive"><i class="material-icons">block</i></button></div>'
                        : '<div class="status_btn_domain' . $row->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatusDomain(' . $row->id . ',' . $row->is_active . ')" title="Make Active"><i class="material-icons">check_circle</i></button></div>';
                    return $actionButton;
                })                            
                ->addColumn('users', function ($row) {
                     return '<button class="btn btn-lg btn-icon" onclick="viewEmployees(' . $row->id . ')" title="View Employees"><i class="material-icons">groups</i></button>';
                })
                ->rawColumns(['created_at', 'status','action','users'])
                ->make(true);
            return $data;
        }
        return view('admin.partner-domain', compact('auth'));
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
        $validate = Validator::make($request->all(), [
            'domain'  => 'required|string|unique:domains,name',
        ]);
        if ($validate->fails()) {
            return response()->json(['status' => 400, 'message' => $validate->errors()->first()]);
        }

        $domain = new Domain;
        $domain->name = $request->domain;
        $domain->save();

        return response()->json([
            'status' => 200,
            'message' => "Domain added successfully"
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if (isset($request->type) && $request->type == 1) {
            $domain = Domain::findOrFail($id);
            return $domain;
        } elseif (isset($request->type) && $request->type == 2) {
            $domain = Domain::findOrFail($id);
            if (isset($domain)) {

                $employees = CorporativePlanUser::where('domain_id', $domain->id)->where('is_verified', 1)->get();
                if (count($employees) > 0) {
                    for ($i = 0; $i < count($employees); $i++) {
                        $name = User::findOrFail($employees[$i]['user_id'])->name;
                        $employees[$i]['employee_name'] = $name ? $name : "No Name";
                    }
                    return response()->json([
                        'status' => 200,
                        'data' => $employees
                    ]);
                } else {
                    return response()->json([
                        'status' => 400,
                        'message' => "Data not available"
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => "Invailed input"
                ]);
            }
        } else {
            return response()->json(['status' => 400, 'message' => "Invailed input"]);
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

        $domain_status_change=Domain::find($id);
        $domain_status_change->is_active=($domain_status_change->is_active==1)?0:1;
        $domain_status_change->save();
        return Domain::find($id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if (isset($request->type) && $request->type == 1) {
            $domain = Domain::findOrFail($id);
            if (isset($domain)) {
                if ($domain->is_active == 1) {
                    $domain->is_active = 0;
                    $domain->save();
                    return response()->json(['status' => 200, 'message' => "Domain balcklisted successfully"]);
                } else {
                    $domain->is_active = 1;
                    $domain->save();
                    return response()->json(['status' => 200, 'message' => "Domain whitelisted successfully"]);
                }
            } else {
                return response()->json(['status' => 400, 'message' => "Invailed input"]);
            }
        } elseif (isset($request->type) && $request->type == 2) {
            $employee = CorporativePlanUser::findOrFail($id);
            if (isset($employee)) {
                if ($employee->is_active == 1) {
                    $employee->is_active = 0;
                    $employee->save();
                    return response()->json(['status' => 200, 'message' => "Employee deactivated successfully", 'id' => $employee->domain_id]);
                } else {
                    $employee->is_active = 1;
                    $employee->save();
                    return response()->json(['status' => 200, 'message' => "Employee activated successfully", 'id' => $employee->domain_id]);
                }
            }
        } else {
            return response()->json(['status' => 400, 'message' => "Invailed input"]);
        }
    }

    public function domain_users(Request $request)
    {
        $baseUrl = asset('storage') . '/';

        $dummy_img = asset('assets/img/user/community2.jpg');
        
        $domain = Domain::findOrFail($request->domain_id);

        $members = User::select('users.*')
        ->where('email','like','%'.$domain->name)->get();
        return DataTables::of($members)
            ->addIndexColumn() // This will add DT_RowIndex
            ->addColumn('profile', function ($member) use ($baseUrl, $dummy_img) {
                if (!empty($member->profile)) {
                    return   '<object data="' . $baseUrl . $member->profile . '" width="50" height="50"></object>';
                } else {
                    return   '<object data="' . $dummy_img . '" width="50" height="50"></object>';
                }
            })
            ->addColumn('status', function ($member) {
                $statusBadge = $member->is_active == 1
                    ? '<div class="status_users' . $member->id . '"><span class="badge badge-success" style="color:white;background-color:green;">Active</span></div>'
                    : '<div class="status_users' . $member->id . '"><span class="badge badge-danger" style="color:white;background-color:red;">Inactive</span></div>';
                return $statusBadge;
            })
            ->addColumn('action', function ($member) {
                $actionButton = $member->is_active
                    ? '<div class="status_btn_users' . $member->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatusUser(' . $member->id . ',' . $member->is_active . ')" title="Make Inactive"><i class="material-icons">block</i></button></div>'
                    : '<div class="status_btn_users' . $member->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatusUser(' . $member->id . ',' . $member->is_active . ')" title="Make Active"><i class="material-icons">check_circle</i></button></div>';
                return $actionButton;
            })
            ->rawColumns(['profile', 'id', 'assign', 'role', 'status', 'action'])
            ->make(true);
    }
}
