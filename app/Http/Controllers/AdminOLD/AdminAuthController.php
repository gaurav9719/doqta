<?php

namespace App\Http\Controllers\Admin;

use Validator;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.login');
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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password, 'role' => 3])){
            return redirect('admin/dashboard');
        }
        else{
            return redirect()->back()->with('message', 'Incorrect credentials')->withInput();
        }

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
    public function destroy()
    {
        Auth::logout();
        return redirect('admin/login');
    }

    function profile(){
        $auth= Auth::user();
        return view('admin.profile', compact('auth'));
    }

    function profileUpdate(Request $request){
        if($request->type && $request->type == 1){
            $request->validate([
                'name' => 'required|string',
                'phone' => 'required|numeric',
            ]);
            $id= Auth::id();
            $user=User::find($id);
            $user->name = $request->name;
            $user->phone_no =  $request->phone;
            $user->save();
            return redirect()->back()->with('success', 'Profile updated successfully');
        }
        elseif($request->type && $request->type == 2){
            $request->validate([
                'old_password' => 'required|string',
                'password' => 'required|confirmed|min:6'
            ]);

            $id= Auth::id();
            $user=User::find($id);
            if(Hash::check($request->old_password, $user->password)){
                $user->password= Hash::make($request->password);
                $user->save();
                return redirect()->back()->with('success', 'Password changed successfully');
            }
            else{
                return redirect()->back()->with('fail', 'Incorrect old password');
            }
        }
        else{
            return redirect()->back()->with('fail', 'Invailed input');
        }
    }
}
