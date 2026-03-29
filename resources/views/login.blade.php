<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ViLoCare - HIV Patient Viral Load Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f8fafc; min-height: 100vh; }
    .login-container { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); height: 100vh; display: flex; align-items: center; justify-content: center; }
    .login-card { background: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); width: 100%; max-width: 420px; padding: 2rem; }
    .login-logo { text-align: center; margin-bottom: 2rem; }
    .login-logo img { height: 80px; }
    .btn-primary { background-color: #2563eb; border-color: #2563eb; }
    .btn-primary:hover { background-color: #1d4ed8; border-color: #1d4ed8; }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-logo">
        <img src="vilocarelogo.png" alt="ViLoCare Logo" class="img-fluid" style="max-height: 80px;" />
        <p class="text-muted mb-3" style="font-size: 1rem;">HIV Patient Viral Load Management System</p>
      </div>
      
      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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
        <div class="mb-3">
          <label for="role" class="form-label">Select your role</label>
          <select class="form-select" name="role" id="role" required>
            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select your role</option>
            <option value="Clinician" {{ old('role') == 'Clinician' ? 'selected' : '' }}>Clinician</option>
            <option value="Lab Technician" {{ old('role') == 'Lab Technician' ? 'selected' : '' }}>Lab Technician</option>
            <option value="Data Officer" {{ old('role') == 'Data Officer' ? 'selected' : '' }}>Data Officer</option>
            <option value="Administrator" {{ old('role') == 'Administrator' ? 'selected' : '' }}>Administrator</option>
          </select>
        </div>
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
          <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign in</button>
        <div class="text-center mt-3">
          <a href="#" class="text-decoration-none">Forgot password?</a>
        </div>
      </form>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>