@extends('layouts.app')

@section('page_title', 'My Profile')

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center p-4">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-light border" style="width: 132px; height: 132px; overflow: hidden;">
                    @if($user->profilePhotoUrl())
                        <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span class="fw-bold fs-2 text-secondary">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>
                <h2 class="h4 mb-1">{{ $user->name }}</h2>
                <p class="text-muted mb-2">{{ $user->role }}</p>
                <p class="text-muted small mb-0">Keep your contact details and profile photo current so account recovery and team identification stay accurate.</p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-4">
                    <h2 class="h4 mb-1">Update Profile Information</h2>
                    <p class="text-muted mb-0">Edit your personal details and upload a profile picture.</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <input id="name" type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input id="username" type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" placeholder="user@example.com">
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input id="phone" type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+211...">
                        </div>

                        <div class="col-12">
                            <label for="profile_photo" class="form-label">Profile Picture</label>
                            <input id="profile_photo" type="file" name="profile_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <div class="form-text">Accepted formats: JPG, PNG, or WEBP. Max size: 2MB.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="{{ $user->role }}" disabled>
                            <div class="form-text">Role changes remain controlled through user management.</div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                        <a href="{{ route('password.change.edit') }}" class="btn btn-outline-secondary">Change Password</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
