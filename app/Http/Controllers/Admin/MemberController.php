<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;
use App\Models\GroupMember;
class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        
        $auth = Auth::user();
        $group_id=$request->group_id;
        $user_id=$request->user_id;
        return view('admin.posts.memberPosts',compact('auth','group_id','user_id'));

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
       
        $baseUrl = asset('storage') . '/';
        $dummy_img = asset('assets/img/user/community2.jpg');
         $members = GroupMember::select('users.id','users.is_active','users.user_name','users.profile','users.email','group_members.created_at','group_members.role')
         ->addSelect(DB::raw('(SELECT count(*) FROM posts WHERE user_id = group_members.user_id AND group_id=group_members.group_id ) as total_posts'))
         ->join('users','group_members.user_id','=','users.id')
         ->where(['group_members.group_id'=>$id])
         ->orderByDesc('group_members.id')
         ->get();
        return DataTables::of($members)
            ->addIndexColumn() // This will add DT_RowIndex
            ->editColumn('created_at', function ($member) {
                if (!empty($member->created_at)) {
                    return $member->created_at->format('d-m-Y'); // Format the date as desired
                } else {
                    return 'Not Available';
                }
            })
            ->addColumn('profile', function ($member) use ($baseUrl, $dummy_img) {
                if (!empty($member->profile)) {
                    return   '<object data="' . $baseUrl . $member->profile . '" width="50" height="50"></object>';
                } else {
                    return   '<object data="' . $dummy_img . '" width="50" height="50"></object>';
                }
            })
            ->addColumn('id', function ($member) {
                return '<button class="btn btn-lg btn-icon" onclick="showPosts(' . $member->id . ')" title="Make Active"><i class="fa-solid fa-eye"></i></button>';
            })
            ->editColumn('role', function ($member) {
                return '<span id="role_member_span_'.$member->id.'">'.$member->role.'</span>';
            })
            ->addColumn('assign',function($member){
                return '<div id="role_member_div_'.$member->id.'"><button class="btn btn-lg btn-icon" onclick="changeRole(' . $member->id . ', \'' . $member->role . '\')" title="Make Active"><i class="fa-solid fa-user-pen"></i></button></div>';
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
            ->rawColumns(['profile', 'id','assign','role','status','action'])
            ->make(true);

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
        $change_role=GroupMember::find($id);
        $change_role->role=$request->role;
        $change_role->save();
        echo json_encode(GroupMember::find($id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
