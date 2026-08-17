@extends('layouts.app')

@section('page_title', 'Change Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="mb-2">Change Temporary Password</h2>
                <p class="text-muted mb-4">For security, you must replace the system-generated password before continuing.</p>

                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.change.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password</label>
                        <input id="current_password" type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <input id="password" type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
