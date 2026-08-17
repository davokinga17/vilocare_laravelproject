<?php
namespace App\Http\Controllers;

use App\Services\SmsService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;


class AuthController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService
    ) {
    }

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
            'g-recaptcha-response' => $this->recaptchaIsEnabled() ? ['required', 'string'] : ['nullable', 'string'],
        ]);

        if ($this->recaptchaIsEnabled() && ! $this->verifyRecaptcha((string) $request->input('g-recaptcha-response'), (string) $request->ip())) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => ['Please confirm that you are not a robot and try again.'],
            ]);
        }

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

            return redirect($user->isReceptionist() ? route('payments.index') : $this->redirectTo);
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

        $prefersEmail = Str::contains($identifier, '@');

        if ($prefersEmail) {
            return $this->sendResetLinkByEmail($user);
        }

        return $this->sendResetLinkBySms($user);
    }

    private function sendResetLinkByEmail(User $user, bool $allowFallback = true)
    {
        if (blank($user->email)) {
            if ($allowFallback && filled($user->phone) && $this->smsIsConfigured()) {
                return $this->sendResetLinkBySms($user, 'This account has no email address on file, so password reset instructions were sent by SMS.');
            }

            return back()->withErrors([
                'identifier' => 'This account has no email address available for a reset link yet. Please contact a System Admin or Super User.',
            ]);
        }

        try {
            $status = Password::sendResetLink(['email' => $user->email]);

            return back()->with(
                $status === Password::RESET_LINK_SENT ? 'status' : 'error',
                __($status)
            );
        } catch (Throwable $exception) {
            report($exception);

            if ($allowFallback && filled($user->phone) && $this->smsIsConfigured()) {
                return $this->sendResetLinkBySms($user, 'Email delivery is unavailable right now, so password reset instructions were sent by SMS instead.');
            }

            return back()->withErrors([
                'identifier' => 'We could not send the reset email right now. If you are using Gmail SMTP, use a Gmail App Password or configure SMS reset credentials.',
            ]);
        }
    }

    private function sendResetLinkBySms(User $user, ?string $successMessage = null, bool $allowFallback = true)
    {
        if (blank($user->phone)) {
            return back()->withErrors([
                'identifier' => 'This account has no phone number available for SMS reset yet. Please contact a System Admin or Super User.',
            ]);
        }

        if (! $this->smsIsConfigured()) {
            if (filled($user->email)) {
                return $this->sendResetLinkByEmail($user, false);
            }

            return back()->withErrors([
                'identifier' => 'SMS reset is not configured yet. Add the Twilio credentials in the environment or use an account with a valid email address.',
            ]);
        }

        $plainToken = Str::random(64);

        DB::table('user_password_reset_tokens')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'token' => Hash::make($plainToken),
                'channel' => 'sms',
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', [
            'token' => $plainToken,
            'user' => $user->id,
        ]);

        $expire = (int) config('auth.passwords.users.expire', 10);
        $body = 'ViLoCare password reset: '.$resetUrl." This link expires in {$expire} minutes.";

        try {
            $this->dispatchSms($user->phone, $body);

            return back()->with(
                'status',
                $successMessage ?? 'If an account matches that phone number, password reset instructions have been sent by SMS.'
            );
        } catch (Throwable $exception) {
            report($exception);

            if ($allowFallback && filled($user->email)) {
                return $this->sendResetLinkByEmail($user, false);
            }

            return back()->withErrors([
                'identifier' => 'We could not send the reset SMS right now. Please try again later or contact a System Admin or Super User.',
            ]);
        }
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email'),
            'userId' => $request->integer('user') ?: null,
        ]);
    }

    public function missingResetToken()
    {
        return redirect()
            ->route('password.request')
            ->with('error', 'That reset link is incomplete or has expired. Please request a new password reset link.');
    }

    public function resetPassword(Request $request)
    {
        if ($request->filled('user_id')) {
            return $this->resetPasswordFromSms($request);
        }

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

    private function resetPasswordFromSms(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $resetRecord = DB::table('user_password_reset_tokens')
            ->where('user_id', $validated['user_id'])
            ->first();

        if (! $resetRecord) {
            throw ValidationException::withMessages([
                'token' => ['That SMS reset link is invalid or has already been used.'],
            ]);
        }

        $createdAt = Carbon::parse($resetRecord->created_at);

        if (now()->diffInMinutes($createdAt) > (int) config('auth.passwords.users.expire', 60)) {
            DB::table('user_password_reset_tokens')->where('user_id', $validated['user_id'])->delete();

            throw ValidationException::withMessages([
                'token' => ['That SMS reset link has expired. Please request a new password reset link.'],
            ]);
        }

        if (! Hash::check($validated['token'], $resetRecord->token)) {
            throw ValidationException::withMessages([
                'token' => ['That SMS reset link is invalid or has already been used.'],
            ]);
        }

        $user = User::findOrFail($validated['user_id']);

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('user_password_reset_tokens')->where('user_id', $validated['user_id'])->delete();

        return redirect()->route('login')->with('success', 'Your password has been reset. You can sign in now.');
    }

    private function smsIsConfigured(): bool
    {
        return $this->smsService->isConfigured();
    }

    private function dispatchSms(string $phoneNumber, string $message): void
    {
        $result = $this->smsService->send($phoneNumber, $message);

        if (($result['status'] ?? 'failed') !== 'sent') {
            throw ValidationException::withMessages([
                'identifier' => [$result['detail'] ?? 'SMS delivery failed. Please verify the SMS configuration and phone number format.'],
            ]);
        }
    }

    private function recaptchaIsEnabled(): bool
    {
        return ! app()->environment('local')
            && (bool) config('services.recaptcha.enabled')
            && filled(config('services.recaptcha.site_key'))
            && filled(config('services.recaptcha.secret_key'));
    }

    private function verifyRecaptcha(string $token, string $ipAddress): bool
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => (string) config('services.recaptcha.secret_key'),
            'response' => $token,
            'remoteip' => $ipAddress,
        ]);

        if ($response->failed()) {
            return false;
        }

        return (bool) data_get($response->json(), 'success', false);
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
