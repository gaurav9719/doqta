<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\User;
use Hash;


class AuthController extends Controller
{
    //


    // public function index()
    // {

    //     if()
    //     return view('Admin.admin_login');

    // }

    function index(Request $request)
    {

        if ($request->isMethod('post')) {

            if (isset($request->admin_submit))
            {
                $request->validate([
                    'email' =>  'required',
                    'password'  =>  'required'
                ]);

                $credentials = $request->only('email', 'password');

                if(Auth::attempt(array('email' => $request->email, 'password' => $request->password))){
                    session()->flash('success', 'Login Successfully');
                    return redirect()->route('admin.dashboard')->with('success', 'Login Successfully');

                }else{
                    return redirect()->back()->with('error', 'Invalid Credentials');
                }
            }
        }else {
            // Display the form for GET request
            return view('Admin.admin_login');
        }
    }

    public function logout()
    {
        Auth::logout();

        // Redirect to a specific route after logout
        return redirect('admin/login')->with('status', 'You have been logged out!');
    }














}
