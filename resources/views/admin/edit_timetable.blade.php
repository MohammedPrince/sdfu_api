@php

    $isViewMode = $isViewMode ?? false;
    $isEditMode = $isEditMode ?? !$isViewMode;

    $facultyName = $facultyCode ?? '';
    $majorName = $majorCode ?? '';

    foreach ($faculties ?? [] as $faculty) {
        if (!is_object($faculty)) {
            continue;
        }

        if ((string) ($faculty->faculty_code ?? '') === (string) ($facultyCode ?? '')) {
            $facultyName = $faculty->faculty_desc_e ?? $facultyCode;
            break;
        }
    }

    foreach ($majors ?? [] as $major) {
        if (!is_object($major)) {
            continue;
        }

        if ((string) ($major->major_code ?? '') === (string) ($majorCode ?? '')) {
            $majorName = $major->major_desc_e ?? $majorCode;
            break;
        }
    }
@endphp


@extends('admin.layouts.app')

@section('title', $isViewMode ? 'View Timetable' : 'Edit Timetable')

@section('page-title', $isViewMode ? 'View Timetable' : 'Edit Timetable')

@section('page-description', $isViewMode ? 'View the selected timetable' : 'Update the selected timetable')

@section('content')

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/timetable.css') }}">
    @endpush

    @php

        $periods = [
            1 => '07:00 AM - 09:00 AM',
            2 => '10:00 AM - 12:00 PM',
            3 => '12:30 PM - 02:30 PM',
            4 => '03:00 PM - 05:00 PM',
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



    <div class="manage-page timetable-page">

        {{-- ========================================================= --}}
        {{-- ALERTS --}}
        {{-- ========================================================= --}}

        @if (session('success'))
            <div class="alert alert-success no-print">
                {{ session('success') }}
            </div>
        @endif


        @if (session('error'))
            <div class="alert alert-danger no-print">
                {{ session('error') }}
            </div>
        @endif


        @if ($errors->any())

            <div class="alert alert-danger no-print">

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

        <div class="admin-card saved-settings-card timetable-page-card">


            {{-- ===================================================== --}}
            {{-- PAGE HEADER --}}
            {{-- ===================================================== --}}

            <div class="admin-card-header timetable-page-header">

                <div>

                    <h3>
                        {{ $isViewMode ? 'View Timetable' : 'Edit Timetable' }}
                    </h3>

                    <p>
                        @if ($isViewMode)
                            <b>{{ $facultyName }}, {{ $majorName }}, {{ $batchValue }}, Semester:
                                {{ $semester }}</b>

                            <div class="form-actions timetable-actions" style="direction: rtl;  ">

                                <a href="{{ route('admin.timetable.print', [
                                    'faculty_code' => $facultyCode,
                                    'major_code' => $majorCode,
                                    'batch' => $batchValue,
                                    'ttid' => $ttid,
                                ]) }}"
                                    class="btn-danger timetable-print-button">
                                    Print Timetable
                                </a>

                                <a href="{{ route('admin.timetable.edit', [
                                    'faculty_code' => $facultyCode,
                                    'major_code' => $majorCode,
                                    'batch' => $batchValue,
                                    'ttid' => $ttid,
                                    'type' => 'edit',
                                ]) }}"
                                    class="btn-secondary">
                                    Edit
                                </a>

                                <a href="{{ route('admin.timetable.display') }}" class="btn-primary">
                                    Back
                                </a>
                            </div>
                        @else
                            Update the selected timetable.
                        @endif
                    </p>

                </div>




            </div>


            {{-- ===================================================== --}}
            {{-- EDIT FORM START --}}
            {{-- ===================================================== --}}

            @if ($isEditMode)
                <form method="POST"
                    action="{{ route('admin.timetable.update', [
                        'faculty_code' => $facultyCode,
                        'major_code' => $majorCode,
                        'batch' => $batchValue,
                        'ttid' => $ttid,
                    ]) }}"
                    id="editTimetableForm">

                    @csrf

                    @method('PUT')
            @endif


            {{-- ===================================================== --}}
            {{-- EDIT CONFIGURATION --}}
            {{-- ===================================================== --}}

            @if ($isEditMode)

                <div class="settings-section">

                    <h4>
                        Timetable Configuration
                    </h4>


                    <div class="form-grid">

                        {{-- Faculty --}}

                        <div class="form-group">

                            <label>
                                Faculty
                            </label>

                            <select name="faculty_code" id="faculty_code" class="form-control" required>

                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->faculty_code }}"
                                        {{ (string) $faculty->faculty_code === (string) $facultyCode ? 'selected' : '' }}>

                                        {{ $faculty->faculty_desc_e }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Major --}}

                        <div class="form-group">

                            <label>
                                Major
                            </label>

                            <select name="major_code" id="major_code" class="form-control" required>

                                @foreach ($majors as $major)
                                    <option value="{{ $major->major_code }}"
                                        {{ (string) $major->major_code === (string) $majorCode ? 'selected' : '' }}>

                                        {{ $major->major_desc_e }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Batch --}}

                        <div class="form-group">

                            <label>
                                Batch
                            </label>

                            <select name="batch" id="batch" class="form-control" required>

                                @foreach ($batches as $batch)
                                    <option value="{{ $batch->batch }}"
                                        {{ (string) $batch->batch === (string) $batchValue ? 'selected' : '' }}>

                                        {{ $batch->batch }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Semester --}}

                        <div class="form-group">

                            <label>
                                Semester
                            </label>

                            <select name="semester" id="semester" class="form-control" required>

                                @for ($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ (int) $semester === $i ? 'selected' : '' }}>

                                        {{ $i }}

                                    </option>
                                @endfor

                            </select>

                        </div>


                        {{-- TTID --}}

                        <div class="form-group">

                            <label>
                                TTID
                            </label>

                            <select name="ttid" id="ttid" class="form-control" required>

                                @foreach ([40, 41, 42, 43, 44, 45] as $id)
                                    <option value="{{ $id }}" {{ (int) $ttid === $id ? 'selected' : '' }}>

                                        {{ $id }}

                                    </option>
                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>
            @else
                {{-- ================================================= --}}
                {{-- VIEW MODE CONFIGURATION --}}
                {{-- ================================================= --}}

                <input type="hidden" id="faculty_code" value="{{ $facultyCode }}">

                <input type="hidden" id="major_code" value="{{ $majorCode }}">

                <input type="hidden" id="batch" value="{{ $batchValue }}">

                <input type="hidden" id="semester" value="{{ $semester }}">

                {{-- Hidden TTID for JS --}}

                <input type="hidden" id="ttid" value="{{ $ttid }}">

            @endif

            {{-- ===================================================== --}}
            {{-- TIMETABLE --}}
            {{-- ===================================================== --}}

            <div class="settings-section timetable-section">

                <div class="timetable-section-header">

                    <div>

                        @if ($isEditMode)
                            <h4>
                                Timetable
                            </h4>
                            <p class="text-muted">
                                Add, remove or modify timetable entries.
                            </p>
                        @else
                        @endif

                    </div>

                </div>

                <div
                    class="timetable-editor-wrapper
                    {{ $isViewMode ? 'timetable-view-mode' : 'timetable-edit-mode' }}">


                    <table class="timetable-editor">

                        <thead>

                            <tr>

                                <th class="timetable-day-header">
                                    Day
                                </th>

                                @foreach ($periods as $slot => $periodName)
                                    <th>
                                        {{ $periodName }}
                                    </th>
                                @endforeach

                            </tr>

                        </thead>


                        <tbody>

                            @foreach ($days as $dayId => $dayName)
                                <tr>

                                    <td class="timetable-day">

                                        <strong>
                                            {{ $dayName }}
                                        </strong>

                                    </td>


                                    @foreach ($periods as $slot => $periodName)
                                        @php
                                            $periodId = $dayId * 4 + $slot;
                                        @endphp


                                        <td>

                                            <div class="timetable-cell" data-day="{{ $dayId }}"
                                                data-period="{{ $periodId }}">


                                                {{-- Add button ONLY in edit mode --}}

                                                @if ($isEditMode)
                                                    <button type="button" class="btn-secondary timetable-add-btn"
                                                        data-day="{{ $dayId }}" data-period="{{ $periodId }}">

                                                        + Add

                                                    </button>
                                                @endif


                                                <div class="timetable-entries" data-day="{{ $dayId }}"
                                                    data-period="{{ $periodId }}">
                                                </div>

                                            </div>

                                        </td>
                                    @endforeach

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- ACTIONS --}}
            {{-- ===================================================== --}}

            @if ($isEditMode)
                <div class="form-actions no-print">

                    <button type="submit" class="btn-primary">

                        Update Timetable

                    </button>


                    <a href="{{ route('admin.timetable.display') }}" class="btn-secondary">

                        Cancel

                    </a>

                </div>

                </form>
            @endif

        </div>
    </div>


    {{-- ============================================================= --}}
    {{-- JAVASCRIPT VARIABLES --}}
    {{-- ============================================================= --}}

    @push('scripts')
        <script>
            window.adminTimetableCoursesUrl =
                @json(route('admin.timetable.courses'));


            window.timetableInstructors =
                @json($instructors ?? []);


            window.timetableClassrooms =
                @json($classrooms ?? []);


            window.existingTimetableEntries =
                @json($existingEntries ?? []);


            window.editTimetable =
                @json($isEditMode);


            window.viewTimetable =
                @json($isViewMode);


            window.adminMajorsUrl =
                @json(url('/admin/manage/majors'));
        </script>


        <script src="{{ asset('js/admin/timetable.js') }}"></script>

        <script src="{{ asset('js/admin/script.js') }}"></script>
    @endpush

@endsection
