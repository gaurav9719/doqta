<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminBasicController extends Controller
{
    function viewCommunity(Request $request)
    {
        $auth = Auth::user();

        if ($request->ajax()) {
            $data = Group::select('groups.*')
                ->addSelect(DB::raw("(Select count(id) from group_members where group_id=groups.id AND role='admin' ) as total_admin"))
                ->orderBy('id', 'desc')
                ->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('created_by', function ($row) {
                    $user = User::find($row->created_by);
                    $created_by = $user->name ? $user->name : "No Name";
                    return $created_by;
                })

                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('d-m-Y');
                })
                ->addColumn('logo', function ($row) {
                    if (isset($row->cover_photo) && !empty($row->cover_photo)) {
                        if (Storage::disk('public')->exists($row->cover_photo)) {
                            $img      = "storage/" . $row->cover_photo;
                            $imageUrl = asset($img);
                        } else {
                            $imageUrl = asset('assets/img/user/community2.jpg');
                        }
                    } else {
                        $imageUrl = asset('assets/img/user/community2.jpg');
                    }
                    $logo = '<span id="image_' . $row->id . '"><img style="height:45px; width:45px;    border-radius:100%;" src="' . $imageUrl . '" /> </span>';
                    return $logo;
                })
                ->addColumn('status', function ($row) {
                    $statusBadge = $row->is_active == 1
                        ? '<div class="status_' . $row->id . '"><span class="badge badge-success" style="color:white;background-color:green;">Active</span></div>'
                        : '<div class="status_' . $row->id . '"><span class="badge badge-danger" style="color:white;background-color:red;">Inactive</span></div>';
                    return $statusBadge;
                })
                ->addColumn('edit', function ($row) {
                    $actionButton = $row->is_active
                        ? '<div class="status_btn' . $row->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatus(' . $row->id . ',' . $row->is_active . ')" title="Make Inactive"><i class="material-icons">block</i></button></div>'
                        : '<div class="status_btn' . $row->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatus(' . $row->id . ',' . $row->is_active . ')" title="Make Active"><i class="material-icons">check_circle</i></button></div>';
                    return $actionButton;
                })

                ->addColumn('id', function ($row) {
                    return '<button class="btn btn-lg btn-icon" onclick="showMember(' . $row->id . ')"  title="Make Active"><i class="fa-solid fa-user-group"></i></button>';
                })
                ->addColumn('edit_community', function ($row) {
                    return '<button class="btn btn-lg btn-icon" onclick="editCommunity(' . $row->id . ')"  title="Make Active"><i class="fa-solid fa-pen-to-square"></i></button>';
                })
                ->editColumn('name', function ($row) {
                    return '<span id="name_' . $row->id . '">' . $row->name . '</span>';
                })
                ->editColumn('details', function ($row) {
                    return '<button class="btn btn-lg btn-icon" onclick="show_community(' . $row->id . ')"  title="Make Active"><i class="fa-solid fa-eye"></i></button>';
                })
                ->rawColumns(['logo', 'status', 'edit', 'created_by', 'created_at', 'id', 'edit_community', 'name','details'])
                ->make(true);
        }
        return view('admin/community', compact('auth'));
    }

    function updateCommunity($id)
    {
        $community = Group::when(isset($id))->findOrFail($id);
        if (isset($community)) {
            if ($community->is_active == 1) {
                $community->is_active = 0;
                $community->save();
            } else {
                $community->is_active = 1;
                $community->save();
            }
            return Group::when(isset($id))->findOrFail($id);
        }
    }
}
