@extends('admin.layouts.app')

@section('title', 'Students')

@section('page-title', 'Students')

@section('page-description')
    Manage Future University Student Desk accounts and registered devices
@endsection

@section('content')

    <div class="manage-page">

        <div class="manage-settings-grid">

            {{-- =====================================================
             LEFT: STUDENT SEARCH / FILTER
             ===================================================== --}}
            <div class="admin-card">

                <div class="admin-card-header">

                    <div>
                        <h3>Student Search</h3>

                        <p>
                            Search and filter students by academic information
                            and account status.
                        </p>
                    </div>

                </div>


                <form method="GET" action="{{ route('admin.students') }}">

                    {{-- Search --}}
                    <div class="settings-section">

                        <h4>Search Student</h4>

                        <div class="form-grid">

                            <div class="form-group form-group-full">

                                <label for="search">
                                    Student Index / Name
                                </label>

                                <input type="text" name="search" id="search" class="form-control"
                                    value="{{ $filters['search'] ?? '' }}" placeholder="Enter student index or name">

                            </div>

                        </div>

                    </div>


                    {{-- Academic Filters --}}
                    <div class="settings-section">

                        <h4>Academic Information</h4>

                        <div class="form-grid">

                            {{-- Faculty --}}
                            <div class="form-group">

                                <label for="faculty_code">
                                    Faculty
                                </label>

                                <select name="faculty_code" id="faculty_code" class="form-control">

                                    <option value="">
                                        All Faculties
                                    </option>

                                    @foreach ($faculties as $faculty)
                                        <option value="{{ $faculty->faculty_code }}" @selected(($filters['faculty_code'] ?? '') == $faculty->faculty_code)>
                                            {{ $faculty->faculty_desc_e }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- Major --}}
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
                                            {{ old('major_code', $editSetting->major_code ?? '') == $major->major_code ? 'selected' : '' }}>
                                            {{ $major->major_desc_e }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- Batch --}}
                            <div class="form-group">

                                <label for="batch">
                                    Batch
                                </label>

                                <select name="batch" id="batch" class="form-control">

                                    <option value="">
                                        All Batches
                                    </option>

                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch }}" @selected(($filters['batch'] ?? '') == $batch)>
                                            {{ $batch }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- Semester --}}
                            <div class="form-group">

                                <label for="semester">
                                    Semester
                                </label>

                                <select name="semester" id="semester" class="form-control">

                                    <option value="">
                                        All Semesters
                                    </option>

                                    @for ($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" @selected(($filters['semester'] ?? '') == $i)>
                                            {{ $i }}
                                        </option>
                                    @endfor

                                </select>

                            </div>

                        </div>

                    </div>


                    {{-- Account --}}
                    <div class="settings-section">

                        <h4>Account</h4>

                        <div class="status-list">

                            <div class="status-item">

                                <div class="status-info">

                                    <strong>
                                        Account Status
                                    </strong>

                                    <span>
                                        Show students according to their
                                        Student Desk account status.
                                    </span>

                                </div>

                                <select name="status" class="form-control student-status-select">

                                    <option value="">
                                        All
                                    </option>

                                    <option value="1" @selected(($filters['status'] ?? '') === '1')>
                                        Active
                                    </option>

                                    <option value="0" @selected(($filters['status'] ?? '') === '0')>
                                        Inactive
                                    </option>

                                </select>


                                <button type="submit" class="btn-primary">
                                    Search
                                </button>

                                <a href="{{ route('admin.students') }}" class="btn-secondary">
                                    Clear
                                </a>



                            </div>

                        </div>

                    </div>



                </form>

            </div>


            {{-- =====================================================
             RIGHT: STUDENT LIST
             ===================================================== --}}
            <div class="admin-card saved-settings-card">

                <div class="admin-card-header">

                    <div>

                        <h3>Student Accounts</h3>

                        <p>
                            Students registered in the Future University
                            Student Desk application.
                        </p>

                    </div>

                    <div class="students-count">

                        <strong>
                            {{ number_format($students->total()) }}
                        </strong>

                        <span>
                            Students
                        </span>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="settings-table students-table">

                        <thead>

                            <tr>

                                <th>Student</th>

                                <th>Faculty</th>

                                <th>Major</th>

                                <th>Batch</th>

                                <th>Semester</th>

                                <th>Account</th>

                                <th>Devices</th>

                                <th>Last Activity</th>

                                <th>View</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($students as $student)
                                <tr>

                                    {{-- Student --}}
                                    <td>

                                        <div class="student-table-info">


                                            <div>

                                                <strong>
                                                    {{ $student->name }}
                                                </strong>

                                                <span>
                                                    {{ $student->stud_index }}
                                                </span>

                                                @if ($student->email)
                                                    <small>
                                                        {{ $student->email }}
                                                    </small>
                                                @endif

                                            </div>

                                        </div>

                                    </td>


                                    {{-- Faculty --}}
                                    <td>

                                        <span class="student-academic-name">
                                            {{ $student->faculty_desc_e ?? $student->faculty_code }}
                                        </span>

                                    </td>


                                    {{-- Major --}}
                                    <td>

                                        <span class="student-academic-name">
                                            {{ $student->major_desc_e ?? $student->major_code }}
                                        </span>

                                    </td>


                                    {{-- Batch --}}
                                    <td>
                                        {{ $student->batch }}
                                    </td>


                                    {{-- Semester --}}
                                    <td>
                                        {{ $student->semester }}
                                    </td>


                                    {{-- Account --}}
                                    <td>

                                        @if ($student->account_active)
                                            <span class="status-badge active">
                                                Active
                                            </span>
                                        @else
                                            <span class="status-badge inactive">
                                                Disabled
                                            </span>
                                        @endif

                                    </td>


                                    {{-- Devices --}}
                                    <td>

                                        <span class="student-device-count">

                                            {{ $student->devices_count }}

                                        </span>

                                    </td>


                                    {{-- Last FCM Activity --}}
                                    <td>

                                        @if ($student->devices_max_last_seen_at)
                                            @php
                                                $lastSeen = \Carbon\Carbon::parse($student->devices_max_last_seen_at);
                                            @endphp

                                            <div class="student-last-activity">

                                                <strong>
                                                    {{ $lastSeen->format('d M Y') }}
                                                </strong>

                                                <span>
                                                    {{ $lastSeen->format('h:i A') }}
                                                </span>

                                            </div>
                                        @else
                                            <span class="student-never">
                                                Never
                                            </span>
                                        @endif

                                    </td>


                                    {{-- View --}}
                                    <td>

                                        <a href="{{ route('admin.students.show', base64_encode($student->id)) }}"
                                            class="btn-edit btn-sm">
                                            View
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="9" class="students-empty">
                                        No students found.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- Pagination --}}
                @if ($students->hasPages())
                    <div class="students-pagination">

                        {{ $students->links() }}

                    </div>
                @endif

            </div>

        </div>

    </div>

    @push('scripts')
        <script>
            window.adminMajorsUrl = @json(url('/admin/manage/majors'));
        </script>

        <script src="{{ asset('js/admin/script.js') }}"></script>
    @endpush
@endsection
