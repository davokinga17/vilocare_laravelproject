@php
    $role = auth()->user()->role;
@endphp

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-head">
        <div class="sidebar-logo-wrap">
            <span class="sidebar-logo">
                <img src="{{ asset('images/vilocarelogo.png') }}" alt="ViLoCare Logo" />
            </span>
            <div class="sidebar-brand">
                <p class="sidebar-brand-title">ViLoCare</p>
                <p class="sidebar-brand-sub">HIV Viral Load Platform</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <p class="sidebar-section">Overview</p>

        <a href="/dashboard" class="sidebar-link {{ request()->is('dashboard') ? 'is-active' : '' }}">
            <span class="sidebar-icon">DB</span>
            Dashboard
        </a>

        <p class="sidebar-section">Clinical</p>

        @if(in_array($role, ['Administrator', 'Clinician', 'Data Clerk', 'Data Officer']))
            <a href="/patients" class="sidebar-link {{ request()->is('patients') || request()->is('patients/*') ? 'is-active' : '' }}">
                <span class="sidebar-icon">PT</span>
                Patients
            </a>
        @endif

        @if(in_array($role, ['Administrator', 'Data Clerk', 'Data Officer']))
            <a href="/patients/create" class="sidebar-link {{ request()->is('patients/create') ? 'is-active' : '' }}">
                <span class="sidebar-icon">AP</span>
                Add Patient
            </a>
        @endif

        @if(in_array($role, ['Administrator', 'Clinician', 'Lab Technician']))
            <a href="/viral-load" class="sidebar-link {{ request()->is('viral-load') || request()->is('viral-load/*') ? 'is-active' : '' }}">
                <span class="sidebar-icon">VL</span>
                Viral Load
            </a>
        @endif

        @if(in_array($role, ['Administrator', 'Clinician']))
            <a href="/eac" class="sidebar-link {{ request()->is('eac') || request()->is('eac/*') ? 'is-active' : '' }}">
                <span class="sidebar-icon">EA</span>
                EAC Sessions
            </a>
        @endif

        @if(in_array($role, ['Administrator', 'Data Clerk', 'Data Officer']))
            <a href="/appointments" class="sidebar-link {{ request()->is('appointments') || request()->is('appointments/*') ? 'is-active' : '' }}">
                <span class="sidebar-icon">AM</span>
                Appointments
            </a>
        @endif

        <p class="sidebar-section">Insights</p>

        <a href="/reports" class="sidebar-link {{ request()->is('reports') || request()->is('reports/*') ? 'is-active' : '' }}">
            <span class="sidebar-icon">RP</span>
            Reports
        </a>
    </nav>

    <div class="sidebar-footer">
        Signed in as {{ $role }}
    </div>
</aside>
