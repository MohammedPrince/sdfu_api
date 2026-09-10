<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Student Desk Panel</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --laravel-red: #FF2D20;
            --laravel-red-dark: #e6251a;
            --bg: #f9fafb;
            --text: #18181b;
            --muted: #71717a;
            --border: #e4e4e7;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .page {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Laravel-style background decoration */
        .background-shape {
            position: absolute;
            width: 650px;
            height: 650px;
            top: -320px;
            left: -260px;
            background: rgba(255, 45, 32, 0.07);
            border-radius: 50%;
            pointer-events: none;
        }

        .background-shape::after {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            top: 180px;
            left: 180px;
            border: 2px solid rgba(255, 45, 32, 0.08);
            border-radius: 50%;
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1180px;
            margin: auto;
            padding: 0 24px;
        }

        /* Header */
        header {
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            text-decoration: none;
            color: var(--text);
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: rgba(255, 45, 32, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--laravel-red);
        }

        .brand-icon svg {
            width: 26px;
            height: 26px;
        }

        .brand-title {
            font-size: 19px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .brand-subtitle {
            font-size: 12px;
            color: var(--muted);
            margin-top: 1px;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link {
            text-decoration: none;
            color: #52525b;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: .2s;
        }

        .nav-link:hover {
            color: var(--laravel-red);
            background: rgba(255, 45, 32, .06);
        }

        .login-button {
            text-decoration: none;
            background: var(--laravel-red);
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(255, 45, 32, .18);
            transition: .2s;
        }

        .login-button:hover {
            background: var(--laravel-red-dark);
            transform: translateY(-1px);
        }

        /* Hero */
        .hero {
            min-height: 530px;
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            align-items: center;
            gap: 70px;
            padding: 65px 0 75px;
        }

        .hero-content {
            max-width: 620px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255, 45, 32, .08);
            color: var(--laravel-red);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 22px;
        }

        .badge-dot {
            width: 7px;
            height: 7px;
            background: var(--laravel-red);
            border-radius: 50%;
        }

        h1 {
            margin: 0;
            font-size: clamp(42px, 5vw, 64px);
            line-height: 1.04;
            letter-spacing: -2.5px;
            font-weight: 700;
            color: #18181b;
        }

        h1 span {
            color: var(--laravel-red);
        }

        .hero-text {
            margin: 24px 0 0;
            max-width: 560px;
            font-size: 17px;
            line-height: 1.8;
            color: #71717a;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 30px;
        }

        .primary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            text-decoration: none;
            background: var(--laravel-red);
            color: white;
            padding: 13px 21px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(255, 45, 32, .2);
            transition: .2s;
        }

        .primary-button:hover {
            background: var(--laravel-red-dark);
            transform: translateY(-2px);
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #3f3f46;
            border: 1px solid var(--border);
            background: white;
            padding: 12px 19px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .secondary-button:hover {
            border-color: rgba(255, 45, 32, .3);
            color: var(--laravel-red);
        }

        /* Student panel preview */
        .panel-wrapper {
            position: relative;
        }

        .panel-glow {
            position: absolute;
            width: 300px;
            height: 300px;
            right: 10px;
            top: 30px;
            background: rgba(255, 45, 32, .10);
            filter: blur(70px);
            border-radius: 50%;
        }

        .panel {
            position: relative;
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 18px;
            padding: 18px;
            box-shadow:
                0 25px 60px rgba(0, 0, 0, .08),
                0 4px 15px rgba(0, 0, 0, .03);
        }

        .panel-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 17px;
            border-bottom: 1px solid #f0f0f2;
        }

        .panel-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 39px;
            height: 39px;
            border-radius: 50%;
            background: rgba(255, 45, 32, .1);
            color: var(--laravel-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .user-name {
            font-size: 14px;
            font-weight: 700;
        }

        .user-label {
            font-size: 11px;
            color: #a1a1aa;
            margin-top: 2px;
        }

        .notification {
            width: 35px;
            height: 35px;
            border: 1px solid #eeeef0;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #71717a;
        }

        .notification svg {
            width: 17px;
        }

        .panel-title {
            font-size: 13px;
            color: #71717a;
            margin: 20px 0 10px;
        }

        .panel-student {
            font-size: 23px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .panel-id {
            font-size: 12px;
            color: #a1a1aa;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 11px;
            margin-top: 20px;
        }

        .mini-card {
            border: 1px solid #eeeeef;
            border-radius: 11px;
            padding: 14px;
            background: #fff;
        }

        .mini-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: rgba(255, 45, 32, .08);
            color: var(--laravel-red);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .mini-icon svg {
            width: 18px;
            height: 18px;
        }

        .mini-title {
            font-size: 12px;
            color: #71717a;
        }

        .mini-value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 4px;
        }

        .status-card {
            margin-top: 11px;
            padding: 13px 14px;
            border-radius: 11px;
            background: rgba(255, 45, 32, .05);
            border: 1px solid rgba(255, 45, 32, .09);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .status-title {
            font-size: 12px;
            color: #71717a;
        }

        .status-value {
            color: #16a34a;
            font-size: 12px;
            font-weight: 700;
        }

        /* Features */
        .features {
            padding: 20px 0 70px;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 28px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: 26px;
            letter-spacing: -.7px;
        }

        .section-heading p {
            margin: 8px auto 0;
            color: #71717a;
            font-size: 14px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .feature {
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            padding: 20px;
            transition: .2s;
        }

        .feature:hover {
            transform: translateY(-3px);
            border-color: rgba(255, 45, 32, .22);
            box-shadow: 0 12px 30px rgba(0, 0, 0, .05);
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: rgba(255, 45, 32, .08);
            color: var(--laravel-red);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .feature-icon svg {
            width: 21px;
            height: 21px;
        }

        .feature h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
        }

        .feature p {
            margin: 7px 0 0;
            font-size: 12px;
            line-height: 1.6;
            color: #71717a;
        }

        footer {
            border-top: 1px solid #e4e4e7;
            padding: 25px 0;
            text-align: center;
            color: #a1a1aa;
            font-size: 12px;
        }

        footer span {
            color: var(--laravel-red);
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
                gap: 45px;
                padding-top: 45px;
            }

            .hero-content {
                max-width: 700px;
                margin: auto;
                text-align: center;
            }

            .hero-actions {
                justify-content: center;
            }

            .panel-wrapper {
                max-width: 550px;
                width: 100%;
                margin: auto;
            }

            .feature-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 0 17px;
            }

            header {
                height: 75px;
            }

            .header-nav .nav-link {
                display: none;
            }

            .brand-title {
                font-size: 16px;
            }

            .brand-subtitle {
                font-size: 10px;
            }

            h1 {
                font-size: 40px;
                letter-spacing: -1.5px;
            }

            .hero {
                padding: 40px 0 55px;
            }

            .hero-text {
                font-size: 15px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .primary-button,
            .secondary-button {
                width: 100%;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .cards {
                gap: 8px;
            }
        }
    </style>
</head>

<body>

    <div class="page">

        <div class="background-shape"></div>

        <div class="container">

            {{-- Header --}}
            <header>

                <a href="{{ url('/') }}" class="brand">

                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 3L20 7.5V16.5L12 21L4 16.5V7.5L12 3Z" stroke="currentColor" stroke-width="1.8"
                                stroke-linejoin="round" />
                            <path d="M8 10L12 12L16 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M12 12V17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </div>

                    <div>
                        <div class="brand-title">Student Desk Panel</div>
                        <div class="brand-subtitle">Student Academic Portal</div>
                    </div>

                </a>

                <nav class="header-nav">

                    <a href="#services" class="nav-link">
                        Services
                    </a>

                    @if (Route::has('login'))
                        @auth

                            <a href="{{ url('/dashboard') }}" class="login-button">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="login-button">
                                Student Login
                            </a>

                        @endauth
                    @endif

                </nav>

            </header>


            {{-- Hero --}}
            <main>

                <section class="hero">

                    <div class="hero-content">

                        <div class="badge">
                            <span class="badge-dot"></span>
                            Student Services Portal
                        </div>

                        <h1>
                            Everything you need,
                            <span>in one place.</span>
                        </h1>

                        <p class="hero-text">
                            Welcome to the Student Desk Panel. Access your
                            academic information, semester results, registration
                            fees, timetable and other student services through
                            one simple and secure platform.
                        </p>

                        <div class="hero-actions">

                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="primary-button">

                                    Student Login

                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M5 12h14" />
                                        <path d="M13 6l6 6-6 6" />
                                    </svg>

                                </a>
                            @endif

                            <a href="#services" class="secondary-button">
                                Explore Services
                            </a>

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

                                <div class="mini-card">

                                    <div class="mini-icon">

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8">
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


                                <div class="mini-card">

                                    <div class="mini-icon">

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8">
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


                                <div class="mini-card">

                                    <div class="mini-icon">

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8">
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


                                <div class="mini-card">

                                    <div class="mini-icon">

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8">
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
                                        Student Portal
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
                <section class="features" id="services">

                    <div class="section-heading">

                        <h2>
                            Student Services
                        </h2>

                        <p>
                            Access the information you need from your student panel.
                        </p>

                    </div>


                    <div class="feature-grid">

                        <div class="feature">

                            <div class="feature-icon">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M4 19V5" />
                                    <path d="M4 5h13a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3" />
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
                                Registration Fees
                            </h3>

                            <p>
                                Check your registration fee details and payment status.
                            </p>

                        </div>

                    </div>

                </section>

            </main>


            {{-- Footer --}}
            <footer>

                Student Desk Panel
                <span>•</span>
                Student Academic Portal

            </footer>

        </div>

    </div>

</body>

</html>
