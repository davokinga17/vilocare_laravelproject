@php
    $selectedRole = old('role', $user->role ?? '');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Full Name</label>
        <input id="name" type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>

    <div class="col-md-6">
        <label for="username" class="form-label">Username</label>
        <input id="username" type="text" name="username" class="form-control" value="{{ old('username', $user->username ?? '') }}" required>
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email Address</label>
        <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" placeholder="user@example.com">
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">Phone Number</label>
        <input id="phone" type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? $user->contact ?? '') }}" placeholder="+211...">
    </div>

    <div class="col-md-6">
        <label for="role" class="form-label">Role</label>
        <select id="role" name="role" class="form-select" required>
            <option value="">Select role</option>
            @foreach($roles as $role)
                <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
            @endforeach
        </select>
    </div>
</div>

@if($passwordRequired)
    <div class="alert alert-info mt-4 mb-0">
        ViLoCare will generate a secure temporary password automatically. The user will be forced to change it at first login.
    </div>
@endif

<div class="mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</div>
