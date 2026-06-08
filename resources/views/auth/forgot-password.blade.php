<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Forgot Password - ViLoCare</title>
    <link href="{{ asset('css/login.css') }}" rel="stylesheet" />
</head>
<body class="login-page">
    <div class="background-orb orb-one"></div>
    <div class="background-orb orb-two"></div>

    <main class="login-shell">
        <section class="brand-panel">
            <img class="brand-logo" src="{{ asset('images/vilocarelogo.png') }}" alt="ViLoCare Logo" />
            <h1 class="brand-title">Recover Account Access Securely</h1>
            <p class="brand-copy">Enter your email address or phone number. ViLoCare will use the account on file to start password recovery.</p>
        </section>

        <section class="form-panel">
            <h2 class="form-title">Forgot password</h2>
            <p class="form-subtitle">We will send a reset link to the email saved on the account.</p>

            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label for="identifier" class="form-label">Email or phone number</label>
                    <input id="identifier" type="text" name="identifier" class="form-control" value="{{ old('identifier') }}" placeholder="user@example.com or +211..." required />
                </div>
                <button type="submit" class="btn login-submit-btn w-100">Send reset link</button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="forgot-link">Back to sign in</a>
            </div>
        </section>
    </main>
</body>
</html>
