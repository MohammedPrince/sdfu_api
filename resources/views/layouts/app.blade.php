<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Student Desk Panel')
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">

    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    {{-- Student Desk CSS --}}
    <link rel="stylesheet" href="{{ asset('css/student-desk.css') }}">

    @stack('styles')

</head>

<body>

    <div class="page">

        {{-- Background decoration --}}
        <div class="background-shape"></div>

        <div class="container">

            {{-- Header --}}
            @include('layouts.header')

            {{-- Page Content --}}
            @yield('content')

            {{-- Footer --}}
            @include('layouts.footer')

        </div>

    </div>

    @stack('scripts')

</body>

</html>
