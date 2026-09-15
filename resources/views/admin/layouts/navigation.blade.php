<nav class="admin-sidebar">

    <div class="admin-sidebar-brand">

        <a href="{{ url('/admin/dashboard') }}" class="admin-sidebar-logo-link">

            <img src="{{ asset('images/logo.png') }}" alt="Future University" class="admin-sidebar-logo">

        </a>

        <div class="admin-sidebar-brand-text">
            <span>Future University</span>
            <small>Student Desk Panel</small>
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


        <a href="{{ url('/admin/manage') }}"
            class="admin-nav-item
            {{ request()->is('admin/manage') ? 'active' : '' }}">
            <span class="admin-nav-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 15.5A3.5 3.5 0 1 0 12 8.5A3.5 3.5 0 0 0 12 15.5Z" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />

                    <path
                        d="M19.4 15A1.7 1.7 0 0 0 19.74 16.88L19.8 16.94L18.94 18.44L18.86 18.39A1.7 1.7 0 0 0 17.02 18.42L16.96 18.46A1.7 1.7 0 0 0 16.13 20.08V20.2H14.4L14.38 20.08A1.7 1.7 0 0 0 13.16 18.67H13.08A1.7 1.7 0 0 0 11.45 19.65L11.39 19.76L9.66 19.13L9.7 19.02A1.7 1.7 0 0 0 9.12 17.3L9.06 17.25A1.7 1.7 0 0 0 7.25 17.34L7.15 17.4L6.15 15.83L6.25 15.76A1.7 1.7 0 0 0 6.54 13.89V13.82A1.7 1.7 0 0 0 5.1 12.55H5V10.82H5.12A1.7 1.7 0 0 0 6.54 9.55V9.48A1.7 1.7 0 0 0 6.25 7.61L6.15 7.54L7.15 5.97L7.25 6.03A1.7 1.7 0 0 0 9.06 6.12L9.12 6.07A1.7 1.7 0 0 0 9.7 4.35L9.66 4.24L11.39 3.61L11.45 3.72A1.7 1.7 0 0 0 13.08 4.7H13.16A1.7 1.7 0 0 0 14.38 3.29L14.4 3.17H16.13V3.29A1.7 1.7 0 0 0 16.96 4.91L17.02 4.95A1.7 1.7 0 0 0 18.86 4.98L18.94 4.93L19.8 6.43L19.74 6.49A1.7 1.7 0 0 0 19.4 8.37V8.44A1.7 1.7 0 0 0 20.84 9.71H21V11.44H20.88A1.7 1.7 0 0 0 19.4 12.71V15Z"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>

            <span class="admin-nav-text">
                Manage Application
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
