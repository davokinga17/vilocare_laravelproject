<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{

    protected $redirectTo = '/dashboard';

    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'role' => 'required'
        ]);

        $user = User::where('username', $request->username)
                    ->where('role', $request->role)
                    ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);
            return redirect($this->redirectTo);
        }

        return back()->with('error', 'Invalid credentials');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}