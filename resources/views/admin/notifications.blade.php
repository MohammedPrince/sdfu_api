@extends('admin.layouts.app')

@section('title', 'Push Notifications')

@section('page-title', 'Push Notifications')

@section('page-description', 'Send push notifications to all students in an academic group or to one student.')

@section('content')

    <div class="manage-page">

        {{-- Success Message --}}
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error Message --}}
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
        {{-- NOTIFICATION CARDS --}}
        {{-- ========================================================= --}}

        <div class="notification-grid">

            {{-- ===================================================== --}}
            {{-- LEFT: PUSH TO ALL STUDENTS --}}
            {{-- ===================================================== --}}

            <div class="admin-card">

                <div class="admin-card-header">
                    <div>
                        <h3>Push to All Students</h3>

                        <p>
                            Send a notification to all students matching the selected
                            faculty, major, batch and semester.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.notifications.push') }}">
                    @csrf

                    <div class="settings-section">

                        <h4>Academic Group</h4>

                        <div class="form-grid">

                            {{-- Faculty --}}
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


                            {{-- Major --}}
                            <div class="form-group">
                                <label for="major_code">
                                    Major
                                </label>

                                <select name="major_code" id="major_code" class="form-control" required disabled>
                                    <option value="">
                                        Select Major
                                    </option>
                                </select>
                            </div>


                            {{-- Batch --}}
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


                            {{-- Semester --}}
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

                        </div>

                    </div>


                    {{-- Notification --}}
                    <div class="settings-section">

                        <h4>Notification</h4>

                        <div class="form-grid">

                            <div class="form-group">
                                <label for="title">
                                    Title
                                </label>

                                <input type="text" name="title" id="title" class="form-control"
                                    value="{{ old('title') }}" maxlength="255" required placeholder="Notification title">
                            </div>


                            <div class="form-group">
                                <label for="body">
                                    Body
                                </label>

                                <textarea name="body" id="body" class="form-control" rows="5" maxlength="2000" required
                                    placeholder="Write notification message...">{{ old('body') }}</textarea>
                            </div>

                        </div>

                    </div>


                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Push to All Students
                        </button>
                    </div>

                </form>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT: PUSH TO ONE STUDENT --}}
            {{-- ===================================================== --}}

            <div class="admin-card">

                <div class="admin-card-header">

                    <div>
                        <h3>Push to One Student</h3>

                        <p>
                            Send a notification directly to one student using the
                            student index number.
                        </p>
                    </div>

                </div>


                <form method="POST" action="{{ route('admin.notifications.push.one') }}">

                    @csrf


                    <div class="settings-section">

                        <h4>Student</h4>

                        <div class="form-grid">

                            <div class="form-group">

                                <label for="student_index">
                                    Student Index
                                </label>

                                <input type="text" name="student_index" id="student_index" class="form-control"
                                    value="{{ old('student_index') }}" maxlength="100" required
                                    placeholder="Enter student index">

                            </div>

                        </div>

                    </div>


                    {{-- Notification --}}
                    <div class="settings-section">

                        <h4>Notification</h4>

                        <div class="form-grid">

                            <div class="form-group">

                                <label for="one_title">
                                    Title
                                </label>

                                <input type="text" name="title" id="one_title" class="form-control"
                                    value="{{ old('title') }}" maxlength="255" required placeholder="Notification title">

                            </div>


                            <div class="form-group">

                                <label for="one_body">
                                    Body
                                </label>

                                <textarea name="body" id="one_body" class="form-control" rows="5" maxlength="2000" required
                                    placeholder="Write notification message...">{{ old('body') }}</textarea>

                            </div>

                        </div>

                    </div>


                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            Push Notification
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
    {{-- ========================================================= --}}
    {{-- FACULTY -> MAJOR --}}
    {{-- ========================================================= --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const facultySelect = document.getElementById('faculty_code');
            const majorSelect = document.getElementById('major_code');

            facultySelect.addEventListener('change', function() {

                const facultyCode = this.value;

                majorSelect.innerHTML =
                    '<option value="">Select Major</option>';

                majorSelect.disabled = true;

                if (!facultyCode) {
                    return;
                }

                fetch(
                        `{{ url('/admin/manage/majors') }}/${encodeURIComponent(facultyCode)}`
                    )
                    .then(response => {

                        if (!response.ok) {
                            throw new Error('Failed to load majors.');
                        }

                        return response.json();

                    })
                    .then(majors => {

                        majors.forEach(major => {

                            const option =
                                document.createElement('option');

                            option.value = major.major_code;

                            option.textContent = major.major_desc_e;

                            majorSelect.appendChild(option);

                        });

                        majorSelect.disabled = false;

                    })
                    .catch(error => {

                        console.error(error);

                        majorSelect.innerHTML =
                            '<option value="">Unable to load majors</option>';

                        majorSelect.disabled = true;

                    });

            });

        });
    </script>

@endsection
