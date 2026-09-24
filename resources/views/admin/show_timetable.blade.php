@extends('admin.layouts.app')

@section('title', 'Show Timetable')

@section('page-title', 'Show Timetable')

@section('page-description', 'View timetable from the selected Faculty, Major, Batch, Semester and TTID')

@section('content')



    <div class="manage-page">

        {{-- ========================================================= --}}
        {{-- SUCCESS MESSAGE --}}
        {{-- ========================================================= --}}

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- ERROR MESSAGE --}}
        {{-- ========================================================= --}}

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- VALIDATION ERRORS --}}
        {{-- ========================================================= --}}

        @if ($errors->any())
            <div class="alert alert-danger">

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- TIMETABLE CONFIGURATION --}}
        {{-- ========================================================= --}}

        <div class="manage-settings-grid">

            <div class="admin-card saved-settings-card">

                <div class="admin-card-header">

                    <div>

                        <h3>Show Timetable</h3>

                        <p>
                            Select Faculty, Major, Batch, Semester and TTID
                            to display the synchronized timetable.
                        </p>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- FORM --}}
                {{-- ================================================= --}}

                <form method="POST" action="{{ route('admin.timetable.show') }}">

                    @csrf


                    <div class="settings-section">

                        <h4>Show Timetable</h4>


                        <div class="form-grid">


                            {{-- ===================================== --}}
                            {{-- Faculty --}}
                            {{-- ===================================== --}}

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


                            {{-- ===================================== --}}
                            {{-- Major --}}
                            {{-- ===================================== --}}

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


                            {{-- ===================================== --}}
                            {{-- Batch --}}
                            {{-- ===================================== --}}

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


                            {{-- ===================================== --}}
                            {{-- Semester --}}
                            {{-- ===================================== --}}

                            <div class="form-group">

                                <label for="semester">
                                    Semester
                                </label>

                                <select name="semester" id="semester" class="form-control" required>

                                    <option value="">
                                        Select Semester
                                    </option>


                                    @for ($semester = 1; $semester <= 12; $semester++)
                                        <option value="{{ $semester }}"
                                            {{ old('semester') == $semester ? 'selected' : '' }}>
                                            {{ $semester }}
                                        </option>
                                    @endfor

                                </select>

                            </div>


                            {{-- ===================================== --}}
                            {{-- TTID --}}
                            {{-- ===================================== --}}

                            <div class="form-group">

                                <label for="ttid">
                                    TTID
                                </label>

                                <select name="ttid" id="ttid" class="form-control" required>

                                    <option value="">
                                        Select TTID
                                    </option>

                                    <option value="30" {{ old('ttid') == 30 ? 'selected' : '' }}>
                                        30
                                    </option>

                                    <option value="31" {{ old('ttid') == 31 ? 'selected' : '' }}>
                                        31
                                    </option>

                                    <option value="32" {{ old('ttid') == 32 ? 'selected' : '' }}>
                                        32
                                    </option>

                                    <option value="33" {{ old('ttid') == 33 ? 'selected' : '' }}>
                                        33
                                    </option>

                                    <option value="34" {{ old('ttid') == 34 ? 'selected' : '' }}>
                                        34
                                    </option>

                                    <option value="35" {{ old('ttid') == 35 ? 'selected' : '' }}>
                                        35
                                    </option>

                                    <option value="40" {{ old('ttid') == 40 ? 'selected' : '' }}>
                                        40
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- ACTION --}}
                    {{-- ================================================= --}}

                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            Show Timetable
                        </button>

                    </div>

                </form>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- TIMETABLE RESULTS --}}
        {{-- ========================================================= --}}

        @if (!empty($timetableData))

            <div class="admin-card timetable-results-card">

                {{-- HEADER --}}
                <div class="admin-card-header">

                    <div>

                        <h3>
                            Timetable
                        </h3>

                        <p>
                            Timetable for the selected academic configuration.
                        </p>

                    </div>

                </div>


                {{-- SELECTED CONFIGURATION --}}
                <div class="settings-section">

                    <h4>
                        Selected Configuration
                    </h4>

                    <div class="form-grid">

                        <div class="config-item">

                            <span class="config-label">
                                Faculty
                            </span>

                            <span class="config-value">
                                {{ $timetableSelection['faculty_code'] ?? '' }}
                            </span>

                        </div>


                        <div class="config-item">

                            <span class="config-label">
                                Major
                            </span>

                            <span class="config-value">
                                {{ $timetableSelection['major_code'] ?? '' }}
                            </span>

                        </div>


                        <div class="config-item">

                            <span class="config-label">
                                Batch
                            </span>

                            <span class="config-value">
                                {{ $timetableSelection['batch'] ?? '' }}
                            </span>

                        </div>


                        <div class="config-item">

                            <span class="config-label">
                                Semester
                            </span>

                            <span class="config-value">
                                {{ $timetableSelection['semester'] ?? '' }}
                            </span>

                        </div>


                        <div class="config-item">

                            <span class="config-label">
                                TTID
                            </span>

                            <span class="config-value">
                                {{ $timetableSelection['ttid'] ?? '' }}
                            </span>

                        </div>

                    </div>

                </div>


                {{-- TIMETABLE --}}
                <div class="timetable-container">

                    @if (!empty($timetableHtml))
                        {!! $timetableHtml !!}
                    @else
                        <div class="alert alert-info">
                            No timetable display data available.
                        </div>
                    @endif

                </div>

            </div>

        @endif

    </div>


    {{-- ============================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ============================================================= --}}

    @push('scripts')
        <script>
            window.adminMajorsUrl = @json(url('/admin/manage/majors'));
        </script>

        <script src="{{ asset('js/admin/script.js') }}"></script>
    @endpush

@endsection
