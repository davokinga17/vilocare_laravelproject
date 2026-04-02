@php
    $role = auth()->user()->role;
@endphp

<div class="bg-primary text-white p-3" style="width:250px; min-height:100vh;">
    <h4>ViLoCare</h4>
    <p class="small">HIV VL Management</p>

    <ul class="nav flex-column mt-4">

        {{-- Dashboard - accessible to all authenticated users --}}
        <li class="nav-item">
            <a href="/dashboard" class="nav-link text-white">🏠 Dashboard</a>
        </li>

        {{-- Patients --}}
        @if(in_array($role, ['Administrator', 'Clinician', 'Data Clerk']))
        <li class="nav-item">
            <a href="/patients" class="nav-link text-white">👥 Patients</a>
        </li>
        @endif

        {{-- Add Patient --}}
        @if(in_array($role, ['Administrator', 'Data Clerk']))
        <li class="nav-item">
            <a href="/patients/create" class="nav-link text-white">➕ Add Patient</a>
        </li>
        @endif

        {{-- Viral Load --}}
        @if(in_array($role, ['Administrator', 'Clinician', 'Lab Technician']))
        <li class="nav-item">
            <a href="/viral-load" class="nav-link text-white">🧪 Viral Load</a>
        </li>
        @endif

        {{-- EAC Sessions --}}
        @if(in_array($role, ['Administrator', 'Clinician']))
        <li class="nav-item">
            <a href="/eac" class="nav-link text-white">💬 EAC Sessions</a>
        </li>
        @endif

        {{-- Appointments for Admin and Data Clerk --}}
        @if(in_array($role, ['Administrator', 'Data Clerk']))
        <li class="nav-item">
            <a href="/appointments" class="nav-link text-white">📅 Appointments</a>
        </li>
        @endif

        {{-- Samples --}}
        <li class="nav-item">
            <a href="#" class="nav-link text-white">🔬 Samples</a>
        </li>

        {{-- Reports --}}
        <li class="nav-item">
            <a href="#" class="nav-link text-white">📈 Reports</a>
        </li>

    </ul>
</div>