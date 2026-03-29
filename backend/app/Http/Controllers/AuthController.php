<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(){
        return view('auth.login');
    }

    public function prosesLogin(Request $request){
        $admin = Admin::where('email', $request->email)->first();

        if (!$admin){
            return back()->with('error', 'Email tidak di temukan');
        }

        if(!Hash::check($request->password,$admin->password)){
            return back()->with('error', 'Password salah');
        }

        session([
            'admin_id' => $admin->id,
            'admin_nama' => $admin->nama
        ]);
        return redirect()->route('admin.dashboard');
    }

    public function logout(){
    session()->flush();
    return redirect()->route('login')->with('success', 'Berhasil logout!');
}
}
