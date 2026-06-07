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
        <label for="role" class="form-label">Role</label>
        <select id="role" name="role" class="form-select" required>
            <option value="">Select role</option>
            @foreach($roles as $role)
                <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="contact" class="form-label">Contact</label>
        <input id="contact" type="text" name="contact" class="form-control" value="{{ old('contact', $user->contact ?? '') }}">
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password" name="password" class="form-control" @required($passwordRequired)>
        @unless($passwordRequired)
            <div class="form-text">Leave blank to keep the current password.</div>
        @endunless
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" @required($passwordRequired)>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</div>
