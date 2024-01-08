<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
class AdminLoginController extends Controller
{
    public function login(Request $request)
    {
        
        $request->validate(
            [
                'email' => 'required|exists:admins,email',
                'password' => 'required',

            ]
        );

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {            
            return redirect()->intended('dashboard');
        }

        return redirect()->back()->withErrors(['email' => 'Invalid email or password.']);
    }
    public function getLogout()
    {
        return view('user.logout');
    }
    public function logout()
    {
        Session::flush();
        Auth::logout();
        return redirect()->route('login');
    }
}
