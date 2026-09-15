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

                Welcome to the Student Desk Application. Access your
                academic information, semester results, registration
                fees, timetable and other student services through
                one simple and secure platform.

            </p>

            <div class="hero-actions">
                <a href="#services"class="secondary-button">Explore Services</a>
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

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >

                            <path
                                d="M18 8A6 6 0 0 0 6 8c0 7-3 7-3 9h18c0-2-3-2-3-9"
                            />

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

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >

                                <path d="M4 19V5" />

                                <path
                                    d="M4 5h13a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3"
                                />

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

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >

                                <rect
                                    x="3"
                                    y="5"
                                    width="18"
                                    height="15"
                                    rx="2"
                                />

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

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />

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

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >

                                <rect
                                    x="3"
                                    y="5"
                                    width="18"
                                    height="14"
                                    rx="2"
                                />

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
                            Student Desk
                        </div>

                    </div>

                    <div class="status-value">
                        ● Available
                    </div>

                </div>

            </div>

        </div>

    </section>


    {{-- Services --}}
    <section
        class="features"
        id="services"
    >

        <div class="section-heading">

            <h2>
                Student Services
            </h2>

            <p>
                Access the information you need from your student desk App.
            </p>

        </div>


        <div class="feature-grid">


            {{-- Semester Results --}}
            <div class="feature">

                <div class="feature-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >

                        <path d="M4 19V5" />

                        <path
                            d="M4 5h13a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3"
                        />

                        <path d="M7 19h13" />

                    </svg>

                </div>

                <h3>
                    Semester Results
                </h3>

                <p>
                    View your semester grades, GPA, CGPA and academic status.
                </p>

            </div>


            {{-- Timetable --}}
            <div class="feature">

                <div class="feature-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >

                        <rect
                            x="3"
                            y="5"
                            width="18"
                            height="15"
                            rx="2"
                        />

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

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >

                        <path d="M4 7h16" />

                        <path d="M4 12h16" />

                        <path d="M4 17h16" />

                        <circle
                            cx="8"
                            cy="7"
                            r="1"
                        />

                        <circle
                            cx="8"
                            cy="12"
                            r="1"
                        />

                        <circle
                            cx="8"
                            cy="17"
                            r="1"
                        />

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

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >

                        <rect
                            x="3"
                            y="5"
                            width="18"
                            height="14"
                            rx="2"
                        />

                        <path d="M7 9h10" />

                        <path d="M7 13h4" />

                        <path d="M15 13h2" />

                    </svg>

                </div>

                <h3>
                    Registration Fees
                </h3>

                <p>
                    Check your registration fee details and payment status.
                </p>

            </div>

        </div>

    </section>

</main>

@endsection