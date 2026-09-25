@extends('admin.layouts.app')

@section('title', 'Create Timetable')

@section('page-title', 'Create Timetable')

@section('page-description', 'Create timetable for the selected Faculty, Major, Batch, Semester and TTID')

@section('content')

    @php
        /*
        |--------------------------------------------------------------------------
        | Timetable periods
        |--------------------------------------------------------------------------
        |
        | These IDs correspond to the existing tim.id values:
        |
        | 1 = Saturday 07:00 - 09:00
        | 2 = Saturday 10:00 - 12:00
        | 3 = Saturday 12:30 - 02:30
        | 4 = Saturday 03:00 - 05:00
        |
        */

        $periods = [
            1 => '07:00 AM-09:00 AM',
            2 => '10:00 AM-12:00 PM',
            3 => '12:30 PM-02:30 PM',
            4 => '03:00 PM-05:00 PM',
        ];

        $days = [
            0 => 'Saturday',
            1 => 'Sunday',
            2 => 'Monday',
            3 => 'Tuesday',
            4 => 'Wednesday',
            5 => 'Thursday',
        ];
    @endphp


    <div class="manage-page">

        {{-- ========================================================= --}}
        {{-- SUCCESS --}}
        {{-- ========================================================= --}}

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- ERROR --}}
        {{-- ========================================================= --}}

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- VALIDATION --}}
        {{-- ========================================================= --}}

        @if ($errors->any())

            <div class="alert alert-danger">

                <ul>

                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- ========================================================= --}}
        {{-- MAIN CARD --}}
        {{-- ========================================================= --}}

        <div class="manage-settings-grid">

            <div class="admin-card saved-settings-card">


                {{-- ================================================= --}}
                {{-- HEADER --}}
                {{-- ================================================= --}}

                <div class="admin-card-header">

                    <div>

                        <h3>
                            Create Timetable
                        </h3>

                        <p>
                            Select the academic configuration first.
                        </p>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- FORM --}}
                {{-- ================================================= --}}

                <form method="POST" action="{{ route('admin.timetable.store') }}" id="createTimetableForm">

                    @csrf


                    {{-- ================================================= --}}
                    {{-- CONFIGURATION --}}
                    {{-- ================================================= --}}

                    <div class="settings-section">

                        <h4>
                            Timetable Configuration
                        </h4>


                        <div class="form-grid">


                            {{-- ========================================= --}}
                            {{-- FACULTY --}}
                            {{-- ========================================= --}}

                            <div class="form-group">

                                <label for="faculty_code">
                                    Faculty
                                </label>

                                <select name="faculty_code" id="faculty_code" class="form-control" required>

                                    <option value="">
                                        Select Faculty
                                    </option>

                                    @foreach ($faculties as $faculty)
                                        <option value="{{ $faculty->faculty_code }}"
                                            {{ old('faculty_code') == $faculty->faculty_code ? 'selected' : '' }}>
                                            {{ $faculty->faculty_desc_e }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- ========================================= --}}
                            {{-- MAJOR --}}
                            {{-- ========================================= --}}

                            <div class="form-group">

                                <label for="major_code">
                                    Major
                                </label>

                                <select name="major_code" id="major_code" class="form-control" required>

                                    <option value="">
                                        Select Major
                                    </option>

                                    @foreach ($majors as $major)
                                        <option value="{{ $major->major_code }}"
                                            {{ old('major_code') == $major->major_code ? 'selected' : '' }}>
                                            {{ $major->major_desc_e }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- ========================================= --}}
                            {{-- BATCH --}}
                            {{-- ========================================= --}}

                            <div class="form-group">

                                <label for="batch">
                                    Batch
                                </label>

                                <select name="batch" id="batch" class="form-control" required>

                                    <option value="">
                                        Select Batch
                                    </option>

                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch->batch }}"
                                            {{ old('batch') == $batch->batch ? 'selected' : '' }}>
                                            {{ $batch->batch }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- ========================================= --}}
                            {{-- SEMESTER --}}
                            {{-- ========================================= --}}

                            <div class="form-group">

                                <label for="semester">
                                    Semester
                                </label>

                                <select name="semester" id="semester" class="form-control" required>

                                    <option value="">
                                        Select Semester
                                    </option>

                                    @for ($semester = 1; $semester <= 10; $semester++)
                                        <option value="{{ $semester }}"
                                            {{ old('semester') == $semester ? 'selected' : '' }}>
                                            {{ $semester }}
                                        </option>
                                    @endfor

                                </select>

                            </div>


                            {{-- ========================================= --}}
                            {{-- TTID --}}
                            {{-- ========================================= --}}

                            <div class="form-group">

                                <label for="ttid">
                                    TTID
                                </label>

                                <select name="ttid" id="ttid" class="form-control" required>

                                    <option value="">
                                        Select TTID
                                    </option>

                                    @foreach ([40, 41, 42, 43, 44, 45] as $ttid)
                                        <option value="{{ $ttid }}" {{ old('ttid') == $ttid ? 'selected' : '' }}>
                                            {{ $ttid }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- TIMETABLE --}}
                    {{-- ================================================= --}}

                    <div class="settings-section">

                        <h4>
                            Timetable
                        </h4>

                        <p class="text-muted">
                            Add courses to the appropriate day and period.
                        </p>


                        <div class="timetable-editor-wrapper">

                            <table class="timetable-editor">

                                {{-- ===================================== --}}
                                {{-- HEADER --}}
                                {{-- ===================================== --}}

                                <thead>

                                    <tr>

                                        <th class="timetable-day-header">
                                            Day
                                        </th>

                                        @foreach ($periods as $periodId => $periodName)
                                            <th data-period="{{ $periodId }}">
                                                {{ $periodName }}
                                            </th>
                                        @endforeach

                                    </tr>

                                </thead>


                                {{-- ===================================== --}}
                                {{-- BODY --}}
                                {{-- ===================================== --}}

                                <tbody>

                                    @foreach ($days as $dayId => $dayName)
                                        <tr>

                                            <td class="timetable-day">
                                                <strong>{{ $dayName }}</strong>
                                            </td>

                                            @foreach ($periods as $slot => $periodName)
                                                @php
                                                    $periodId = $dayId * 4 + $slot;
                                                @endphp

                                                <td>

                                                    <div class="timetable-cell" data-day="{{ $dayId }}"
                                                        data-period="{{ $periodId }}">

                                                        <button type="button" class="btn-secondary timetable-add-btn"
                                                            data-day="{{ $dayId }}"
                                                            data-period="{{ $periodId }}">
                                                            + Add
                                                        </button>

                                                        <div class="timetable-entries" data-day="{{ $dayId }}"
                                                            data-period="{{ $periodId }}"></div>

                                                    </div>

                                                </td>
                                            @endforeach

                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- ACTIONS --}}
                    {{-- ================================================= --}}

                    <div class="form-actions">

                        <button type="submit" class="btn-primary" id="saveTimetableBtn">
                            Create Timetable
                        </button>

                        <a href="#" class="btn-secondary">
                            Cancel
                        </a>

                    </div>


                </form>

            </div>

        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ============================================================= --}}

    @push('scripts')
        <script>
            window.adminTimetableCoursesUrl =
                @json(route('admin.timetable.courses'));

            window.timetableInstructors =
                @json($instructors);

            window.timetableClassrooms =
                @json($classrooms);

            window.adminMajorsUrl =
                @json(url('/admin/manage/majors'));
        </script>

        <script src="{{ asset('js/admin/timetable.js') }}"></script>

        <script src="{{ asset('js/admin/script.js') }}"></script>
    @endpush

@endsection
