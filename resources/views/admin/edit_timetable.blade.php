@extends('admin.layouts.app')

@section('title', 'Edit Timetable')
@section('page-title', 'Edit Timetable')
@section('page-description', 'Update the selected timetable')

@section('content')

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


    <div class="manage-page">

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

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


        <div class="admin-card saved-settings-card">

            <div class="admin-card-header">

                <div>

                    <h3>
                        Edit Timetable
                    </h3>

                    <p>
                        Update the selected timetable.
                    </p>

                </div>

            </div>


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


                <div class="settings-section">

                    <h4>
                        Timetable Configuration
                    </h4>


                    <div class="form-grid">

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

                <div class="settings-section">

                    <h4>
                        Timetable
                    </h4>

                    <p class="text-muted">
                        Add, remove or modify timetable entries.
                    </p>


                    <div class="timetable-editor-wrapper">

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
                                                        data-day="{{ $dayId }}" data-period="{{ $periodId }}">
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


                <div class="form-actions">

                    <button type="submit" class="btn-primary">
                        Update Timetable
                    </button>


                    <a href="{{ route('admin.timetable.display') }}" class="btn-secondary">
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>

    @push('scripts')
        <script>
            window.adminTimetableCoursesUrl =
                @json(route('admin.timetable.courses'));

            window.timetableInstructors =
                @json($instructors);

            window.timetableClassrooms =
                @json($classrooms);

            window.existingTimetableEntries =
                @json($existingEntries);

            window.editTimetable =
                true;

            window.adminMajorsUrl =
                @json(url('/admin/manage/majors'));
        </script>

        <script src="{{ asset('js/admin/timetable.js') }}"></script>

        <script src="{{ asset('js/admin/script.js') }}"></script>
    @endpush

@endsection
