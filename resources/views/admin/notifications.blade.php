@extends('admin.layouts.app')

@section('title', 'Push Notifications')

@section('page-title', 'Push Notifications')

@section('page-description', 'Send push notifications to all students in an academic group or to one student.')

@section('content')

    <div class="manage-page">

        {{-- =========================================================
             SUCCESS MESSAGE
             ========================================================= --}}

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif


        {{-- =========================================================
             ERROR MESSAGE
             ========================================================= --}}

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


        {{-- =========================================================
             NOTIFICATION CARDS
             ========================================================= --}}

        <div class="notification-grid">


            {{-- =====================================================
                 LEFT: PUSH TO ALL STUDENTS
                 ===================================================== --}}

            <div class="admin-card">

                <div class="admin-card-header">

                    <div>

                        <h3>
                            Push to All Students
                        </h3>

                        <p>
                            Send a notification to all students matching the selected
                            faculty, major, batch and semester.
                        </p>

                    </div>

                </div>


                <form method="POST" action="{{ route('admin.notifications.push') }}">

                    @csrf


                    {{-- =================================================
                         ACADEMIC GROUP
                         ================================================= --}}

                    <div class="settings-section">

                        <h4>
                            Academic Group
                        </h4>


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


                                    @for ($semester = 1; $semester <= 10; $semester++)
                                        <option value="{{ $semester }}"
                                            {{ old('semester') == $semester ? 'selected' : '' }}>

                                            {{ $semester }}

                                        </option>
                                    @endfor

                                </select>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         NOTIFICATION
                         ================================================= --}}

                    <div class="settings-section">

                        <h4>
                            Notification
                        </h4>


                        <div class="form-grid">


                            <div class="form-group">

                                <label for="student_index">
                                    Notification Type
                                </label>

                                <select name="notification_type" id="notification_type" class="form-control" required>

                                    <option value="">Select Type</option>
                                    <option value="general">Announcement</option>
                                    <option value="timetable">Timetable</option>
                                    <option value="result">Result</option>
                                    <option value="fees">Fees</option>

                                </select>

                            </div>


                            {{-- Title --}}

                            <div class="form-group">

                                <label for="title">
                                    Title
                                </label>


                                <input type="text" name="title" id="title" class="form-control"
                                    value="{{ old('title') }}" maxlength="255" required placeholder="Notification title">

                            </div>


                            {{-- Body --}}

                            <div class="form-group">

                                <label for="body">
                                    Body
                                </label>

                                <textarea name="body" id="body" class="form-control" rows="5" maxlength="2000" required
                                    placeholder="Write notification message...">{{ old('body') }}</textarea>

                            </div>

                        </div>

                    </div>


                    {{-- Submit --}}

                    <div class="form-actions">

                        <button type="submit" class="btn-primary">

                            Push to All Students

                        </button>

                    </div>

                </form>

            </div>


            {{-- =====================================================
                 RIGHT: PUSH TO ONE STUDENT
                 ===================================================== --}}

            <div class="admin-card">

                <div class="admin-card-header">

                    <div>

                        <h3>
                            Push to One Student
                        </h3>

                        <p>
                            Send a notification directly to one student using the
                            student index number.
                        </p>

                    </div>

                </div>


                <form method="POST" action="{{ route('admin.notifications.push.one') }}">

                    @csrf


                    {{-- =================================================
                         STUDENT
                         ================================================= --}}

                    <div class="settings-section">

                        <h4>
                            Student
                        </h4>


                        <div class="form-grid">

                            <div class="form-group">

                                <label for="student_index">
                                    Student Index
                                </label>


                                <input type="text" name="student_index" id="student_index" class="form-control"
                                    value="{{ old('student_index') }}" maxlength="2000" required
                                    placeholder="Enter student indexes separated by commas">

                            </div>

                            <div class="form-group">

                                <label for="student_index">
                                    Notification Type
                                </label>

                                <select name="notification_type" id="notification_type" class="form-control" required>

                                    <option value="">Select Type</option>
                                    <option value="General">Announcement</option>
                                    <option value="timetable">Timetable</option>
                                    <option value="result">Result</option>
                                    <option value="fees">Fees</option>

                                </select>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         NOTIFICATION
                         ================================================= --}}

                    <div class="settings-section">

                        <h4>
                            Notification
                        </h4>


                        <div class="form-grid">


                            {{-- Title --}}

                            <div class="form-group">

                                <label for="one_title">
                                    Title
                                </label>


                                <input type="text" name="title" id="one_title" class="form-control"
                                    value="{{ old('title') }}" maxlength="255" required
                                    placeholder="Notification title">

                            </div>


                            {{-- Body --}}

                            <div class="form-group">

                                <label for="one_body">
                                    Body
                                </label>


                                <textarea name="body" id="one_body" class="form-control" rows="5" maxlength="2000" required
                                    placeholder="Write notification message...">{{ old('body') }}</textarea>

                            </div>

                        </div>

                    </div>


                    {{-- Submit --}}

                    <div class="form-actions">

                        <button type="submit" class="btn-primary">

                            Push Notification

                        </button>

                    </div>

                </form>

            </div>

        </div>


        @include('admin.partials.scheduled-campaigns')


        {{-- =========================================================
             PUSHED NOTIFICATIONS
             ========================================================= --}}

        <div class="admin-card saved-settings-card">


            {{-- =====================================================
                 HEADER
                 ===================================================== --}}

            <div class="admin-card-header">

                <div>

                    <h3>
                        Pushed Notifications
                    </h3>

                    <p>
                        Notification history sent to students through the
                        Student Desk application.
                    </p>

                </div>


                <div class="notifications-count">

                    <strong>
                        {{ number_format($notificationsTotal) }}
                    </strong>

                    <span>
                        {{ $notificationsTotal === 1 ? 'Notification' : 'Notifications' }}
                    </span>

                </div>

            </div>


            {{-- =====================================================
                 TABLE (DataTables, server-side, searchable by index)
                 ===================================================== --}}

            <div class="notification-history-wrapper">

                <table id="pushedNotificationsTable" data-datatable="custom"
                    class="settings-table notification-history-table display nowrap" style="width:100%">

                    <thead>
                        <tr>
                            <th data-priority="3">Student</th>
                            <th data-priority="1">Student Index</th>
                            <th data-priority="2">Title</th>
                            <th data-priority="6">Message</th>
                            <th data-priority="7">Type</th>
                            <th data-priority="5">Status</th>
                            <th data-priority="4">Sent At</th>
                        </tr>
                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>


        {{-- =========================================================
             CAMPAIGN TEMPLATES
             ========================================================= --}}

        @include('admin.partials.campaign-templates')

    </div>


    {{-- =========================================================
         SCRIPTS
         ========================================================= --}}

    @push('scripts')
        <script>
            window.adminMajorsUrl = @json(url('/admin/manage/majors'));
        </script>

        <script src="{{ asset('js/admin/script.js') }}"></script>

        {{-- DataTables is loaded in the admin layout; this table is server-side --}}

        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const tableElement = document.getElementById('pushedNotificationsTable');

                if (!tableElement || typeof DataTable === 'undefined') {
                    return;
                }

                // Show load errors (e.g. expired session) in the console instead of an alert
                DataTable.ext.errMode = 'none';

                const table = new DataTable(tableElement, {
                    serverSide: true,
                    processing: true,
                    responsive: true,
                    autoWidth: false,
                    searchDelay: 400,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[6, 'desc']],

                    ajax: {
                        url: @json(route('admin.notifications.data')),
                    },

                    columns: [
                        { data: 'student', orderable: false },
                        { data: 'student_index', orderable: false },
                        { data: 'title', orderable: false },
                        { data: 'message', orderable: false },
                        { data: 'type', orderable: false },
                        { data: 'status', orderable: false },
                        { data: 'sent_at', orderable: true },
                    ],

                    layout: {
                        topStart: 'search',
                        topEnd: 'pageLength',
                        bottomStart: 'info',
                        bottomEnd: 'paging',
                    },

                    language: {
                        search: '',
                        searchPlaceholder: 'Search by student index...',
                        lengthMenu: '_MENU_ per page',
                        info: 'Showing _START_ to _END_ of _TOTAL_ results',
                        infoEmpty: 'No results',
                        infoFiltered: '(filtered from _MAX_)',
                        emptyTable: 'No pushed notifications yet.',
                        zeroRecords: 'No notifications found for this student index.',
                        processing: 'Loading...',
                    },
                });

                table.on('error.dt', function (event, settings, techNote, message) {
                    console.error('Pushed notifications table error:', message);
                });

                // Numeric keyboard on phones for the index search box
                const searchInput = document.querySelector('#pushedNotificationsTable_wrapper .dt-search input');

                if (searchInput) {
                    searchInput.setAttribute('inputmode', 'numeric');
                }
            });
        </script>
    @endpush

@endsection
