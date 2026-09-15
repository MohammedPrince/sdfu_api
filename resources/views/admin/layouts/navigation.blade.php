<nav class="admin-sidebar">

    <div class="admin-sidebar-brand">

        <a href="{{ url('/admin/dashboard') }}" class="admin-sidebar-logo-link">

            <img src="{{ asset('images/logo.png') }}" alt="Future University" class="admin-sidebar-logo">

        </a>

        <div class="admin-sidebar-brand-text">
            <span>Future University</span>
            <small>Administration Desk</small>
        </div>
    </div>

    <div class="admin-sidebar-divider"></div>

    <div class="admin-sidebar-menu">

        <div class="admin-menu-label">
            MAIN
        </div>

        <a href="{{ url('/admin/dashboard') }}"
            class="admin-nav-item {{ request()->is('admin/dashboard') ? 'active' : '' }}">
            <span class="admin-nav-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M3 10.5L12 3L21 10.5V20C21 20.55 20.55 21 20 21H4C3.45 21 3 20.55 3 20V10.5Z"
                        stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    <path d="M9 21V13H15V21" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                </svg>
            </span>

            <span class="admin-nav-text">
                Dashboard
            </span>
        </a>


        <a href="#" class="admin-nav-item">
            <span class="admin-nav-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M16 21V19C16 16.79 14.21 15 12 15H6C3.79 15 2 16.79 2 19V21" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" />
                    <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8" />
                    <path d="M22 21V19C22 17.16 20.75 15.61 19 15.13" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" />
                    <path d="M16 3.13C17.75 3.61 19 5.16 19 7C19 8.84 17.75 10.39 16 10.87" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </span>

            <span class="admin-nav-text">
                Students
            </span>
        </a>


        <a href="#" class="admin-nav-item">
            <span class="admin-nav-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="4" width="18" height="17" rx="2" stroke="currentColor"
                        stroke-width="1.8" />
                    <path d="M8 2V6M16 2V6M3 9H21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    <path d="M8 13H8.01M12 13H12.01M16 13H16.01M8 17H8.01M12 17H12.01" stroke="currentColor"
                        stroke-width="2.2" stroke-linecap="round" />
                </svg>
            </span>

            <span class="admin-nav-text">
                Academic
            </span>
        </a>


        <a href="#" class="admin-nav-item">
            <span class="admin-nav-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 19V5C4 3.9 4.9 3 6 3H20V17H6C4.9 17 4 17.9 4 19Z" stroke="currentColor"
                        stroke-width="1.8" stroke-linejoin="round" />
                    <path d="M6 17H20V21H6C4.9 21 4 20.1 4 19C4 17.9 4.9 17 6 17Z" stroke="currentColor"
                        stroke-width="1.8" stroke-linejoin="round" />
                </svg>
            </span>

            <span class="admin-nav-text">
                Courses
            </span>
        </a>


        <div class="admin-menu-label admin-menu-label-spaced">
            MANAGEMENT
        </div>


        <a href="#" class="admin-nav-item">
            <span class="admin-nav-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M3 3V21H21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    <path d="M7 16L11 12L14 15L20 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </span>

            <span class="admin-nav-text">
                Reports
            </span>
        </a>


        <a href="#" class="admin-nav-item">
            <span class="admin-nav-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path
                        d="M12 15.5C13.933 15.5 15.5 13.933 15.5 12C15.5 10.067 13.933 8.5 12 8.5C10.067 8.5 8.5 10.067 8.5 12C8.5 13.933 10.067 15.5 12 15.5Z"
                        stroke="currentColor" stroke-width="1.8" />
                    <path
                        d="M19.4 15C19.5 14.7 19.7 14.4 19.8 14.1L22 12L19.8 9.9C19.7 9.6 19.5 9.3 19.4 9L19.7 6L16.8 4.3L14.4 5.6C14.1 5.5 13.7 5.4 13.4 5.3L12 3L10.6 5.3C10.3 5.4 9.9 5.5 9.6 5.6L7.2 4.3L4.3 6L4.6 9C4.5 9.3 4.3 9.6 4.2 9.9L2 12L4.2 14.1C4.3 14.4 4.5 14.7 4.6 15L4.3 18L7.2 19.7L9.6 18.4C9.9 18.5 10.3 18.6 10.6 18.7L12 21L13.4 18.7C13.7 18.6 14.1 18.5 14.4 18.4L16.8 19.7L19.7 18L19.4 15Z"
                        stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
                </svg>
            </span>

            <span class="admin-nav-text">
                Settings
            </span>
        </a>

    </div>


    <div class="admin-sidebar-bottom">

        <div class="admin-sidebar-divider"></div>

        <div class="admin-sidebar-user">

            <div class="admin-user-avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>

            <div class="admin-user-info">

                <strong>
                    {{ auth()->user()->name ?? 'Administrator' }}
                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </div>


        <form method="POST" action="{{ url('/admin/logout') }}">
            @csrf

            <button type="submit" class="admin-logout-button">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M10 17L15 12L10 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round" />

                    <path d="M15 12H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />

                    <path d="M21 3V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>

                <span>Sign Out</span>
            </button>

        </form>

    </div>

</nav>
