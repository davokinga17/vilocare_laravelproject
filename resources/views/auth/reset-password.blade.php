<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Password - ViLoCare</title>
    <link href="{{ asset('css/login.css') }}" rel="stylesheet" />
</head>
<body class="login-page">
    <div class="background-orb orb-one"></div>
    <div class="background-orb orb-two"></div>

    <main class="login-shell">
        <section class="brand-panel">
            <img class="brand-logo" src="{{ asset('images/vilocarelogo.png') }}" alt="ViLoCare Logo" />
            <h1 class="brand-title">Choose a New Password</h1>
            <p class="brand-copy">Your new password will be hashed in ViLoCare and will not be visible to administrators or super users.</p>
        </section>

        <section class="form-panel">
            <h2 class="form-title">Reset password</h2>
            <p class="form-subtitle">Create a strong password you only know.</p>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $email) }}" required />
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">New password</label>
                    <input id="password" type="password" name="password" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required />
                </div>
                <button type="submit" class="btn login-submit-btn w-100">Reset password</button>
            </form>
        </section>
    </main>
</body>
</html>
