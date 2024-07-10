<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminBasicController extends Controller
{
    function viewCommunity(Request $request){
        $auth= Auth::user();
        

        if($request->ajax()){
            $data= Group::orderBy('id', 'desc')->get();
            return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('created_by', function($row){
                $user= User::find($row->created_by);
                $created_by =$user->name ? $user->name : "No Name";
                return $created_by;
            })
            ->addColumn('created_at', function($row){
                return $row->created_at->format('d-m-Y');
            })
            ->addColumn('logo', function($row){
                if(isset($row->cover_photo) && !empty($row->cover_photo)){
                    if(Storage::disk('public')->exists($row->cover_photo)){
                        $img      = "storage/".$row->cover_photo;
                        $imageUrl = asset($img);
                    }
                    else{
                        $imageUrl = asset('assets/img/user/community2.jpg');
                    }
                }
                else{
                    $imageUrl = asset('assets/img/user/community2.jpg');
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
            ->addColumn('edit', function($row){
                if(isset($row->is_active) && $row->is_active == 1){
                    $status = '<button class="btn btn-lg btn-icon" onclick="changeStatus('.$row->id.')" title="Block Community"><i class="material-icons">block</i></button>';
                }
                else{
                    $status = '<button class="btn btn-lg btn-icon" onclick="changeStatus('.$row->id.')" title="Make Active"><i class="material-icons">check_circle</i></button>';
                }
                return $status;
            })
            ->rawColumns(['logo','status', 'edit', 'created_by', 'created_at'])
            ->make(true);
        }
        return view('admin/community', compact('auth'));
    }

    function updateCommunity($id){
        $community = Group::when(isset($id))->findOrFail($id);
        if(isset($community)){
            if($community->is_active == 1){
                $community->is_active = 0;
                $community->save();
                return response()->json([
                    'status' => 200,
                    'message' => "Community deactivated Successfuly"
                ]);
            }
            else{
                $community->is_active = 1;
                $community->save();
                return response()->json([
                    'status' => 200,
                    'message' => "Community activated Successfully"
                ]);
            } 
        }
    }

}
