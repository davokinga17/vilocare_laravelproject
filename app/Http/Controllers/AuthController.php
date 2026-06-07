<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class AuthController extends Controller
{

    protected $redirectTo = '/dashboard';

    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
            'role' => ['required', Rule::in(['Administrator', 'Clinician', 'Lab Technician', 'Data Clerk'])],
        ]);

        $roleAliases = [
            'Data Clerk' => ['Data Clerk', 'Data Officer'],
        ];

        $roles = $roleAliases[$credentials['role']] ?? [$credentials['role']];

        $user = User::where('username', $credentials['username'])
                    ->whereIn('role', $roles)
                    ->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect($this->redirectTo);
        }

        return back()
            ->withInput($request->only('username', 'role', 'remember'))
            ->with('error', 'Invalid credentials');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
