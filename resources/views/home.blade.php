@extends('layouts.app')

@section('title', 'Student Desk Application')

@section('content')

    <main>

        {{-- Hero --}}
        <section class="hero">

            <div class="hero-content">

                <div class="badge">

                    <span class="badge-dot"></span>

                    Student Desk Application

                </div>

                <h1>

                    Everything you need,

                    <span>in one place.</span>

                </h1>

                <p class="hero-text">

                    Access your academic information, semester results,
                    registration fees, timetable and other student services
                    through one simple and secure platform.

                </p>

                <div class="hero-actions">
                    <a href="#services" class="secondary-button">Explore services</a>
                </div>

            </div>


            {{-- Dashboard Preview --}}
            <div class="panel-wrapper">

                <div class="panel-glow"></div>

                <div class="panel">

                    <div class="panel-top">

                        <div class="panel-user">

                            <div class="avatar">
                                S
                            </div>

                            <div>

                                <div class="user-name">
                                    Student Account
                                </div>

                                <div class="user-label">
                                    Student Desk
                                </div>

                            </div>

                        </div>

                        <div class="notification">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 7-3 9h18c0-2-3-2-3-9" />

                                <path d="M10 21h4" />

                            </svg>

                        </div>

                    </div>


                    <div class="panel-title">
                        Welcome back
                    </div>

                    <div class="panel-student">
                        Student Dashboard
                    </div>

                    <div class="panel-id">
                        Access your academic information and services
                    </div>


                    <div class="cards">

                        {{-- Semester Result --}}
                        <div class="mini-card">

                            <div class="mini-icon">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                                    <path d="M4 19V5" />

                                    <path d="M4 5h13a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3" />

                                    <path d="M7 19h13" />

                                </svg>

                            </div>

                            <div class="mini-title">
                                Semester Result
                            </div>

                            <div class="mini-value">
                                View Results
                            </div>

                        </div>


                        {{-- Timetable --}}
                        <div class="mini-card">

                            <div class="mini-icon">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                                    <rect x="3" y="5" width="18" height="15" rx="2" />

                                    <path d="M7 3v4" />

                                    <path d="M17 3v4" />

                                    <path d="M3 10h18" />

                                </svg>

                            </div>

                            <div class="mini-title">
                                Timetable
                            </div>

                            <div class="mini-value">
                                View Schedule
                            </div>

                        </div>


                        {{-- Registration --}}
                        <div class="mini-card">

                            <div class="mini-icon">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                                    <circle cx="12" cy="12" r="9" />

                                    <path d="M12 7v5l3 2" />

                                </svg>

                            </div>

                            <div class="mini-title">
                                Registration
                            </div>

                            <div class="mini-value">
                                Academic Services
                            </div>

                        </div>


                        {{-- Fees --}}
                        <div class="mini-card">

                            <div class="mini-icon">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                                    <rect x="3" y="5" width="18" height="14" rx="2" />

                                    <path d="M7 9h10" />

                                    <path d="M7 13h4" />

                                    <path d="M15 13h2" />

                                </svg>

                            </div>

                            <div class="mini-title">
                                Fees
                            </div>

                            <div class="mini-value">
                                Fee Details
                            </div>

                        </div>

                    </div>

                    <div class="status-card">

                        <div>

                            <div class="status-title">
                                <b>Student Desk Application</b>
                            </div>

                        </div>

                        @if ($applicationStatus['success'])
                            <div class="status-value status-available">
                                ● Available
                            </div>
                        @else
                            <div class="status-value status-unavailable">
                                ● Unavailable
                            </div>
                        @endif
                    </div>

                </div>

            </div>

        </section>

        {{-- App Download --}}
        <section class="app-download">

            <div class="section-heading">

                <h2>
                    Take it with you
                </h2>

                <p>
                    Get the Student Desk app on your phone.
                </p>

            </div>

            <div class="store-grid">

                {{-- iOS --}}
                <a href="#" class="store-card">

                    <div class="store-icon">

                        <svg viewBox="0 0 24 24" fill="currentColor">

                            <path
                                d="M16.365 1.43c0 1.14-.462 2.096-1.386 2.926-.94.83-1.976 1.29-3.106 1.23-.06-.09-.09-.27-.09-.54 0-1.14.494-2.13 1.482-2.97.494-.42 1.096-.75 1.806-1.05.71-.3 1.368-.48 1.976-.54.03.15.045.42.045.81zm4.17 15.99c-.6 1.29-.916 1.86-1.71 3.03-1.11 1.62-2.673 3.63-4.61 3.66-1.72.03-2.166-1.11-4.5-1.11-2.334 0-2.826 1.08-4.52 1.14-1.87.06-3.293-1.74-4.41-3.36-2.402-3.48-2.68-7.56-1.184-9.75.847-1.245 2.203-1.98 3.485-1.98 1.53 0 2.77.12 3.775.75.81-.48 1.988-.78 3.18-.78 1.077 0 2.24.24 3.164.72-1.926 1.05-3.243 2.94-3.243 5.235 0 3.63 3.24 4.86 3.573 4.95z" />

                        </svg>

                    </div>

                    <div>
                        <div class="store-eyebrow">Download on the</div>
                        <div class="store-title">App Store</div>
                    </div>

                </a>


                {{-- Android --}}
                <a href="#" class="store-card">

                    <div class="store-icon">

                        <svg viewBox="0 0 24 24" fill="currentColor">

                            <path
                                d="M6.5 3.5 8.6 7a12.9 12.9 0 0 1 6.8 0l2.1-3.5.9.5-2 3.4A8.6 8.6 0 0 1 21 15H3a8.6 8.6 0 0 1 4.6-7.6l-2-3.4.9-.5ZM8 11.2a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm8 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2ZM3 16.5h18V19a2 2 0 0 1-2 2h-1v1.2a1.3 1.3 0 0 1-2.6 0V21H8.6v1.2a1.3 1.3 0 0 1-2.6 0V21H5a2 2 0 0 1-2-2v-2.5Z" />

                        </svg>

                    </div>

                    <div>
                        <div class="store-eyebrow">Get it on</div>
                        <div class="store-title">Google Play</div>
                    </div>

                </a>

            </div>

        </section>

        {{-- Services --}}
        <section class="features" id="services">

            <div class="section-heading">

                <h2>
                    Student services
                </h2>

                <p>
                    Everything on your student desk, in one place.
                </p>

            </div>


            <div class="feature-grid">


                {{-- Semester Results --}}
                <div class="feature">

                    <div class="feature-icon">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                            <path d="M4 19V5" />

                            <path d="M4 5h13a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3" />

                            <path d="M7 19h13" />

                        </svg>

                    </div>

                    <h3>
                        Semester results
                    </h3>

                    <p>
                        View your semester grades, GPA, CGPA and academic status.
                    </p>

                </div>


                {{-- Timetable --}}
                <div class="feature">

                    <div class="feature-icon">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                            <rect x="3" y="5" width="18" height="15" rx="2" />

                            <path d="M7 3v4" />

                            <path d="M17 3v4" />

                            <path d="M3 10h18" />

                        </svg>

                    </div>

                    <h3>
                        Timetable
                    </h3>

                    <p>
                        Check your current semester timetable and class schedule.
                    </p>

                </div>


                {{-- Registration --}}
                <div class="feature">

                    <div class="feature-icon">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                            <path d="M4 7h16" />

                            <path d="M4 12h16" />

                            <path d="M4 17h16" />

                            <circle cx="8" cy="7" r="1" />

                            <circle cx="8" cy="12" r="1" />

                            <circle cx="8" cy="17" r="1" />

                        </svg>

                    </div>

                    <h3>
                        Registration
                    </h3>

                    <p>
                        Access your registration information and academic services.
                    </p>

                </div>


                {{-- Registration Fees --}}
                <div class="feature">

                    <div class="feature-icon">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                            <rect x="3" y="5" width="18" height="14" rx="2" />

                            <path d="M7 9h10" />

                            <path d="M7 13h4" />

                            <path d="M15 13h2" />

                        </svg>

                    </div>

                    <h3>
                        Registration fees
                    </h3>

                    <p>
                        Check your registration fee details and payment status.
                    </p>

                </div>

            </div>

            <div class="visitor-counter">

                <div class="visitor-stat">
                    <div class="visitor-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor"
                                stroke-width="1.8" stroke-linejoin="round" />
                            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </div>

                    <div>
                        <span class="visitor-label">Visitors today</span>
                        <strong>{{ number_format($visitorCount['today']) }}</strong>
                    </div>
                </div>


                <div class="visitor-stat">
                    <div class="visitor-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 19V5M4 19H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <path d="M7 15L10 11L13 13L19 7" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <div>
                        <span class="visitor-label">Total visits</span>
                        <strong>{{ number_format($visitorCount['total']) }}</strong>
                    </div>
                </div>

            </div>

        </section>



    </main>

@endsection
