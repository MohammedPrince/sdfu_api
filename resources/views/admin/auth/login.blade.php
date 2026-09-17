<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | Future University</title>

    <link rel="stylesheet" href="{{ asset('css/admin-desk.css') }}">
</head>

<body class="admin-login-page">

    <main class="admin-login-wrapper">

        <section class="admin-login-card">

            <div class="admin-login-brand">

                <div class="admin-logo-wrapper">
                    <img src="{{ asset('images/logo.png') }}" alt="Future University" class="admin-login-logo">
                </div>

                <h1>Future University</h1>

                <p>Student Desk App Panel</p>

            </div>


            <div class="admin-login-content">

                <h2>Admin Desk</h2>

                <p class="admin-login-subtitle">
                    Sign in to access the administration panel
                </p>

                <div class="admin-login-divider">
                    <span class="admin-divider-line"></span>
                    <span class="admin-divider-diamond"></span>
                    <span class="admin-divider-line"></span>
                </div>

                @if (session('success'))
                    <div class="admin-alert admin-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="admin-alert admin-alert-error">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.submit') }}">

                    @csrf

                    <div class="admin-form-group">

                        <label for="username">
                            Username
                        </label>

                        <div class="admin-input-wrapper">

                            <span class="admin-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>

                            <input type="text" id="username" name="username" value="{{ old('username') }}"
                                placeholder="Enter your username" autocomplete="username" required>

                        </div>

                    </div>

                    <div class="admin-form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="admin-input-wrapper">

                            <span class="admin-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2">
                                    </rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>

                            <input type="password" id="password" name="password" placeholder="Enter your password"
                                autocomplete="current-password" required>

                        </div>

                    </div>



                    <button type="submit" class="admin-login-button">
                        <span>Sign In</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>

                </form>

            </div>


            <div class="admin-login-footer">

                <div class="admin-login-footer-brand">
                    <span class="admin-footer-line"></span>
                    <span class="admin-footer-name">CESD</span>
                    <span class="admin-footer-line"></span>
                </div>


            </div>

        </section>

    </main>

</body>

</html>
