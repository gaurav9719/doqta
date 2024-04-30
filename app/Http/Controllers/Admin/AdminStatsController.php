<?php

namespace App\Http\Controllers\Admin;

use App\Models\Stat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Yajra\DataTables\DataTables;

class AdminStatsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if($request->ajax()){
            $stats=Stat::orderBy('id', 'desc');
            $datatable= DataTables::of($stats)
            ->addIndexColumn()
            ->addColumn('status', function($row){
                if(isset($row->is_active) && $row->is_active == 1){
                    $status = '<span class="badge badge-success" style="color: #fff!important; background-color: RGBA(25, 135, 84)!important;">Active</span>';
                }
                else{
                    $status = '<span class="badge badge-danger" style="color: #fff!important; background-color: RGBA(220, 53, 69)!important;">Inactive</span>';
                }
                return $status;
            })
            ->addColumn('actions', function($row){
                $status = '<button class="btn btn-lg btn-icon" onclick="editStats('.$row->id.')" title="Edit Stats"><i class="material-icons">edit</i></button>';
                $status = $status .'<button class="btn btn-lg btn-icon" onclick="deleteStats('.$row->id.')"  title="Delete Stats"><i class="material-icons">delete</i></button>';
                
                return $status;
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
            return $datatable;
        }
        $auth= Auth::user();
        return view('admin.stats', compact('auth'));
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
        
        $validate=Validator::make($request->all(), [
            'question' => 'required|string',
            'min_value' => 'required|integer',
            'max_value' => 'required|integer',
            'is_active' => 'boolean',
        ]);
        if($validate->fails()){
            return response()->json(['status'=>400, 'message'=> $validate->errors()->first()]);
        }

        if(isset($request->status) && $request->status == true){
            $status=1;
        }
        else{
            $status=0;
        }
        
        $check=new Stat;
        $check->question = $request->question;
        $check->min_value = $request->min_value;
        $check->max_value = $request->max_value;
        $check->is_active = $status;
        $check->save();
        
        return response()->json(['status'=>200, 'message'=> "Stats inserted successfully"]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $stat=Stat::findOrFail($id);
        return $stat;
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
        $validate=Validator::make($request->all(), [
            'id'        => 'required|exists:stats,id',
            'question'  => 'required|string',
            'min_value' => 'required|integer',
            'max_value' => 'required|integer',
            'is_active' => 'boolean',
        ]);
        if($validate->fails()){
            return response()->json(['status'=>400, 'message'=> $validate->errors()->first()]);
        }

        if(isset($request->status) && $request->status == true){
            $status=1;
        }
        else{
            $status=0;
        }

        $stat= Stat::find($request->id);
        $stat->question = $request->question;
        $stat->min_value = $request->min_value;
        $stat->max_value = $request->max_value;
        $stat->is_active = $status;
        $stat->save();

        return response()->json(['status'=>200, 'message'=> "Stats updated successfully"]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Stat::findOrFail($id)->delete();
        return response()->json(['status'=>200, 'message'=> "Stats daleted successfully"]);
    }
}
