<header>

    <a href="{{ url('/') }}" class="brand">

        <div class="brand-icon">
            <img src="{{ asset('images/logo.png') }}" alt="Future University">
        </div>

        <div>

            <div class="brand-title">
                The Future University
            </div>

            <div class="brand-subtitle">
                Student Desk Application
            </div>

        </div>

    </a>

    <nav class="header-nav">

        @if (request()->is('privacy-policy*'))
            <a href="{{ url('/') }}" class="nav-link">
                Home
            </a>
        @else
            <a href="#services" class="nav-link active">
                Application Services
            </a>
        @endif


        <a href="{{ url('/privacy-policy') }}" class="nav-link {{ request()->is('privacy-policy*') ? 'active' : '' }}">
            Privacy & Policy
        </a>

    </nav>

</header>
