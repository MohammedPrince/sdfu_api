<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Admin Dashboard') | Future University
    </title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- DataTables + Responsive (used by every admin table) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.dataTables.min.css">

    <link rel="stylesheet" href="{{ asset('css/admin-desk.css') }}">

    @stack('styles')

</head>


<body class="admin-page">

    <div class="admin-layout">

        {{-- Navigation --}}
        @include('admin.layouts.navigation')


        <main class="admin-main">

            {{-- Header --}}
            <header class="admin-header">

                <div>

                    <h1>
                        @yield('page-title', 'Dashboard')
                    </h1>

                    <p>
                        @yield('page-description', 'Future University Administration Desk')
                    </p>

                </div>


                <div class="admin-header-right">

                    <div class="admin-header-date">
                        {{ now()->format('d M Y') }}
                    </div>

                    <div class="admin-header-avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>

                </div>

            </header>


            {{-- Page Content --}}
            <section class="admin-content">

                @yield('content')

            </section>


            {{-- Footer --}}
            <footer class="admin-footer">

                <span>Student Desk Panel</span>

                <span>•</span>

                <span>
                    © {{ date('Y') }}
                    <a href="https://fu.edu.sd" target="_blank" rel="noopener"
                        style="text-decoration: none;color:#651522">
                        Future University
                    </a>
                </span>

                <span>•</span>

                <span>
                    <a href="https://fu.edu.sd/CESD" target="_blank" rel="noopener"
                        style="text-decoration: none; color:#651522">
                        CESD
                    </a>
                </span>

            </footer>

        </main>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js"></script>
    <script src="{{ asset('js/admin/admin-datatables.js') }}"></script>

    @stack('scripts')

</body>

</html>
