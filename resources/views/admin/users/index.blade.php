@extends('layouts.app')

@section('page_title', 'Users')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="mb-1">User Accounts</h2>
        <p class="text-muted mb-0">Create role-based accounts with secure temporary passwords and first-login password rotation.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
</div>

@if(session('generated_password'))
    <div class="alert alert-warning">
        Temporary password for the newly created account: <strong>{{ session('generated_password') }}</strong>. Share it securely now because it will not be shown again.
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Created By</th>
                        <th>Password Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->role }}</td>
                            <td>{{ $user->email ?: 'N/A' }}</td>
                            <td>{{ $user->phone ?: 'N/A' }}</td>
                            <td>{{ $user->creator?->username ?: 'System' }}</td>
                            <td>
                                @if($user->must_change_password)
                                    <span class="badge text-bg-warning">Temporary password</span>
                                @else
                                    <span class="badge text-bg-success">Updated by user</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $users->links() }}
</div>

@endsection
