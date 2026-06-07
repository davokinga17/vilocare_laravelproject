@extends('layouts.app')

@section('page_title', 'Edit User')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="mb-1">Edit User</h2>
        <p class="text-muted mb-0">Update account details and access role.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf

            @include('admin.users.form', [
                'user' => $user,
                'roles' => $roles,
                'submitLabel' => 'Update User',
                'passwordRequired' => false,
            ])
        </form>
    </div>
</div>

@endsection
