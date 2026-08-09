<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ViLoCare - HIV Patient Viral Load Management System</title>
  <link href="{{ asset('css/login.css') }}" rel="stylesheet" />
  @if(config('services.recaptcha.enabled') && config('services.recaptcha.site_key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  @endif
</head>
<body class="login-page">
  <div class="background-orb orb-one"></div>
  <div class="background-orb orb-two"></div>

  <main class="login-shell">
    <section class="brand-panel">
      <img class="brand-logo" src="{{ asset('images/vilocarelogo.png') }}" alt="ViLoCare Logo" />
      <h1 class="brand-title">Smarter Viral Load Care Starts Here</h1>
      <p class="brand-copy">Securely access ViLoCare to manage patient viral load results, coordinate follow-up, and improve care outcomes.</p>
      <div class="badge-row">
        <span class="mini-badge">Secure Access</span>
        <span class="mini-badge">Care Coordination</span>
        <span class="mini-badge">Data Accuracy</span>
      </div>
    </section>

    <section class="form-panel">
      <h2 class="form-title">Welcome back</h2>
      <p class="form-subtitle">Sign in to your account to continue.</p>

      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger">
          @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}" autocomplete="off">
        @csrf

        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" name="username" class="form-control" id="username" placeholder="Enter your username" value="{{ old('username') }}" required />
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password" required />
        </div>
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
          <label class="form-check-label" for="remember">Remember me</label>
        </div>
        @if(config('services.recaptcha.enabled') && config('services.recaptcha.site_key'))
          <div class="mb-3">
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
          </div>
        @endif
        <button type="submit" class="btn login-submit-btn w-100">Sign in</button>
        <div class="text-center mt-3">
          <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
        </div>
      </form>
    </section>
  </main>

</body>
</html>
