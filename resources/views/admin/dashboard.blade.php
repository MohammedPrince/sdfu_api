@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('page-description', 'Welcome to the Future University Administration Desk')


@section('content')

    <div class="dashboard-welcome">

        <h2>
            Welcome back,
            {{ auth()->user()->name ?? 'Administrator' }}
        </h2>

        <p>
            Manage students, academic records and university services
            from your administration dashboard.
        </p>

    </div>

    <div class="dashboard-cards">

        <div class="dashboard-card">

            <div class="dashboard-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>

            <div>
                <span>Students</span>
                <strong> {{ number_format($studentCount) }}</strong>
            </div>

        </div>


        <div class="dashboard-card">

            <div class="dashboard-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>

            <div>
                <span>Courses</span>
                <strong>0</strong>
            </div>

        </div>


        <div class="dashboard-card">

            <div class="dashboard-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M3 3v18h18"></path>
                    <path d="M7 16l4-4 3 3 5-6"></path>
                </svg>
            </div>

            <div>
                <span>Reports</span>
                <strong>0</strong>
            </div>

        </div>


        <div class="dashboard-card">

            <div class="dashboard-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M8 12l2.5 2.5L16 9"></path>
                </svg>
            </div>

            <div>
                <span>System Status</span>
                <strong>Active</strong>
            </div>

        </div>

    </div>

@endsection
