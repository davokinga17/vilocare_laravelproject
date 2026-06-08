<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


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
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $user->forceFill([
                'last_login_at' => now(),
            ])->save();

            if ($user->must_change_password) {
                return redirect()->route('password.change.edit');
            }

            return redirect($this->redirectTo);
        }

        return back()
            ->withInput($request->only('username', 'remember'))
            ->with('error', 'Invalid credentials');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $identifier = trim($validated['identifier']);
        $user = Str::contains($identifier, '@')
            ? User::where('email', $identifier)->first()
            : User::where('phone', $identifier)->first();

        if (! $user) {
            return back()->with('status', 'If an account matches that email or phone number, password reset instructions will be sent.');
        }

        if (blank($user->email)) {
            return back()->withErrors([
                'identifier' => 'This account has no email address available for a reset link yet. Please contact a System Admin or Super User.',
            ]);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        return back()->with(
            $status === Password::RESET_LINK_SENT ? 'status' : 'error',
            __($status)
        );
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return redirect()->route('login')->with('success', 'Your password has been reset. You can sign in now.');
    }

    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8', 'different:current_password'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()->route('dashboard')->with('success', 'Password updated successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
