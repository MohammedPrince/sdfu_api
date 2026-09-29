<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>
        {{ $facultyName }} - {{ $majorName }} - {{ $batch }}
    </title>

    <link rel="stylesheet" type="text/css" href="{{ asset('css/print.css') }}">

</head>

<body>

    <div class="print-page">

        {{-- =========================================================
         ACTIONS
    ========================================================== --}}

        <div class="print-actions">

            <a href="{{ url()->previous() }}" class="back-button">

                ← Back

            </a>

            <button type="button" class="print-button" onclick="window.printTimetable()">

                Print Timetable

            </button>


        </div>


        {{-- =========================================================
         HEADER
    ========================================================== --}}

        <div class="print-header">

            <h1>
                {{ $facultyName }}
            </h1>

            <div class="subtitle">

                {{ $majorName }}, {{ $batch }}, Semester: {{ $semester }}

            </div>

            {{-- @if (!empty($semester))
                <div class="meta">
                    Semester {{ $semester }}
                </div>
            @endif --}}

            <div class="print-line"></div>

        </div>


        {{-- =========================================================
         TIMETABLE
    ========================================================== --}}

        @php

            $days = [
                0 => 'Saturday',
                1 => 'Sunday',
                2 => 'Monday',
                3 => 'Tuesday',
                4 => 'Wednesday',
                5 => 'Thursday',
            ];

            $periods = [
                1 => '07:00 AM - 09:00 AM',
                2 => '10:00 AM - 12:00 PM',
                3 => '12:30 PM - 02:30 PM',
                4 => '03:00 PM - 05:00 PM',
            ];

        @endphp


        <table class="print-timetable">

            <thead>

                <tr>

                    <th class="day-column">
                        Day
                    </th>

                    @foreach ($periods as $period)
                        <th>
                            {{ $period }}
                        </th>
                    @endforeach

                </tr>

            </thead>


            <tbody>

                @foreach ($days as $dayId => $dayName)
                    <tr>

                        <td class="day-cell">

                            {{ $dayName }}

                        </td>


                        @foreach ($periods as $slot => $periodName)
                            <td class="time-cell">

                                @php
                                    $entries = $timetable[$dayId][$slot] ?? [];
                                @endphp


                                @forelse ($entries as $entry)
                                    <div class="timetable-entry">

                                        <div class="course">

                                            {{ $entry['course_code'] }}

                                            @if (!empty($entry['course_name']))
                                                - {{ $entry['course_name'] }}
                                            @endif

                                        </div>


                                        @if (!empty($entry['instructor']))
                                            <div class="instructor">
                                                <strong>Instructor:</strong>
                                                {{ $entry['instructor'] }}
                                            </div>
                                        @endif


                                        @if (!empty($entry['room']))
                                            <div class="room">
                                                <strong>Room:</strong>
                                                {{ $entry['room'] }}
                                            </div>
                                        @endif


                                        @if (!empty($entry['group']))
                                            <div class="group">
                                                <strong>Group:</strong>
                                                {{ $entry['group'] }}
                                            </div>
                                        @endif


                                        <div class="entry-type">

                                            {{ $entry['type'] }}

                                        </div>

                                    </div>

                                @empty

                                    <div class="empty-cell"></div>
                                @endforelse

                            </td>
                        @endforeach

                    </tr>
                @endforeach

            </tbody>

        </table>


        <div class="print-footer">

            Developed and Designed By Center of E-learning and Software Development. https://fu.edu.sd/CESD

        </div>

    </div>


    <script>
        function printTimetable() {
            window.focus();

            setTimeout(function() {
                window.print();
            }, 500);
        }

        window.addEventListener('load', function() {

            // Do not automatically open print dialog.
            // User clicks the Print button.

        });
    </script>

</body>

</html>
