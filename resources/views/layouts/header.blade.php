<header class="site-header">

    <div class="brand">
        <img src="{{ asset('images/logo.png') }}"
             alt="Future University"
             class="brand-logo">

        <div class="brand-text">
            <div class="brand-title">
                The Future University
            </div>

            <div class="brand-subtitle">
                Student Desk Application
            </div>
        </div>
    </div>

    {{-- Mobile menu button --}}
    <button
        type="button"
        class="mobile-menu-button"
        id="mobileMenuButton"
        aria-label="Toggle navigation"
        aria-expanded="false"
        aria-controls="headerNav"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav class="header-nav" id="headerNav">

        <a href="{{ url('/') }}"
           class="nav-link {{ request()->is('/') ? 'active' : '' }}">
            Home
        </a>

        <a href="{{ url('/#services') }}"
           class="nav-link">
            Application Services
        </a>

        <a href="{{ url('/privacy-policy') }}"
           class="nav-link {{ request()->is('privacy-policy*') ? 'active' : '' }}">
            Privacy & Policy
        </a>

    </nav>

</header>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const button = document.getElementById('mobileMenuButton');
    const nav = document.getElementById('headerNav');

    if (!button || !nav) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle mobile menu
    |--------------------------------------------------------------------------
    */
    button.addEventListener('click', function (event) {

        event.stopPropagation();

        const isOpen = nav.classList.toggle('open');

        button.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );

    });

    /*
    |--------------------------------------------------------------------------
    | Close menu after clicking a navigation link
    |--------------------------------------------------------------------------
    */
    nav.querySelectorAll('.nav-link').forEach(function (link) {

        link.addEventListener('click', function () {

            nav.classList.remove('open');

            button.setAttribute(
                'aria-expanded',
                'false'
            );

        });

    });

    /*
    |--------------------------------------------------------------------------
    | Close menu when clicking outside
    |--------------------------------------------------------------------------
    */
    document.addEventListener('click', function (event) {

        if (
            !nav.contains(event.target) &&
            !button.contains(event.target)
        ) {

            nav.classList.remove('open');

            button.setAttribute(
                'aria-expanded',
                'false'
            );

        }

    });

});
</script>