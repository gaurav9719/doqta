<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Domain;
use App\Models\Employee;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Models\CorporativePlanUser;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminPartnersDomainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $auth= Auth::user();
        if($request->ajax()){
            $domain = Domain::orderBy('id', 'desc')->get(); 
            $data = DataTables::of($domain)
            ->addIndexColumn()
            ->addColumn('created_at', function($row){
                return $row->created_at->format('d-m-Y');
            })
            ->addColumn('status', function($row){
                if(isset($row->is_active) && $row->is_active == 1){
                    $status = '<span class="badge badge-success text-bg-success" >Whitelisted</span>';
                }
                else{
                    $status = '<span class="badge badge-danger text-bg-danger">Blacklisted</span>';
                }
                return $status;
            })
            ->addColumn('edit', function($row){
                $status = '<button class="btn btn-lg btn-icon" onclick="viewEmployees('.$row->id.')" title="View Employees"><i class="material-icons">groups</i></button>';
                // $status = '<button class="btn btn-lg btn-icon" onclick="editDomain('.$row->id.')" title="Edit Domain"><i class="material-icons">edit</i></button>';
                // $status = $status. '<button class="btn btn-lg btn-icon" onclick="deleteDomain('.$row->id.')" title="Delete Domain"><i class="material-icons">delete</i></button>';
                if(isset($row->is_active) && $row->is_active == 1){
                    $status =$status. '<button class="btn btn-lg btn-icon" onclick="changeStatus('.$row->id.')" title="Blacklist domain"><i class="material-icons">block</i></button>';
                }
                else{
                    $status = $status.'<button class="btn btn-lg btn-icon" onclick="changeStatus('.$row->id.')" title="Whitelist domain"><i class="material-icons">check_circle</i></button>';
                }
                return $status;
            })
            ->rawColumns(['created_at', 'edit','status'])
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
        $validate=Validator::make($request->all(), [
            'domain'  => 'required|string|unique:domains,name',
        ]);
        if($validate->fails()){
            return response()->json(['status'=>400, 'message'=> $validate->errors()->first()]);
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
        if(isset($request->type) && $request->type == 1){
            $domain=Domain::findOrFail($id);
            return $domain;
        }
        elseif(isset($request->type) && $request->type == 2){
            $domain=Domain::findOrFail($id);
            if(isset($domain)){

                $employees= CorporativePlanUser::where('domain_id', $domain->id)->where('is_verified', 1)->get();
                if(count($employees) > 0){
                    for($i=0; $i < count($employees); $i++){
                        $name = User::findOrFail($employees[$i]['user_id'])->name;
                        $employees[$i]['employee_name']= $name ? $name : "No Name";
                    }
                    return response()->json([
                        'status' => 200, 
                        'data' => $employees
                    ]);
                }
                else{
                    return response()->json([
                        'status' => 400, 
                        'message' => "Data not available"
                    ]);
                }
                
            }
            else{
                return response()->json([
                    'status' => 400, 
                    'message' => "Invailed input"
                ]);
            }
        }
        else{
            return response()->json(['status'=>400, 'message'=> "Invailed input"]);
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
        $validate=Validator::make($request->all(), [
            'id'        => 'required|exists:domains,id',
            'domain'  => 'required|string|unique:domains,name',
        ]);
        if($validate->fails()){
            return response()->json(['status'=>400, 'message'=> $validate->errors()->first()]);
        }

        $domain= Domain::find($request->id);
        $domain->name = $request->domain;
        $domain->save();

        return response()->json(['status'=>200, 'message'=> "Domain updated successfully"]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request ,string $id)
    {
        if(isset($request->type) && $request->type == 1){
            $domain = Domain::findOrFail($id);
            if(isset($domain)){
                if($domain->is_active == 1){
                    $domain->is_active = 0;
                    $domain->save();
                    return response()->json(['status'=>200, 'message'=> "Domain balcklisted successfully"]);
                }
                else{
                    $domain->is_active = 1;
                    $domain->save();
                    return response()->json(['status'=>200, 'message'=> "Domain whitelisted successfully"]);
                }
            }
            else{
                return response()->json(['status'=>400, 'message'=> "Invailed input"]);
            }
        }
        elseif(isset($request->type) && $request->type == 2){
             $employee= CorporativePlanUser::findOrFail($id);
             if(isset($employee)){
                if($employee->is_active == 1){
                    $employee->is_active = 0;
                    $employee->save();
                    return response()->json(['status'=>200, 'message'=> "Employee deactivated successfully", 'id' =>$employee->domain_id]);
                }
                else{
                    $employee->is_active = 1;
                    $employee->save();
                    return response()->json(['status'=>200, 'message'=> "Employee activated successfully", 'id' =>$employee->domain_id]);
                }
             } 
        }
        else{
            return response()->json(['status'=>400, 'message'=> "Invailed input"]);
        }
        
    }
}
