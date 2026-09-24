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
                                    value="{{ old('student_index') }}" maxlength="100" required
                                    placeholder="Enter student index">

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
                        {{ number_format($notifications->total()) }}
                    </strong>

                    <span>
                        {{ $notifications->total() === 1 ? 'Notification' : 'Notifications' }}
                    </span>

                </div>

            </div>


            {{-- =====================================================
                 TABLE
                 ===================================================== --}}

            <div class="table-responsive notification-history-wrapper">

                <table class="settings-table notification-history-table">

                    <thead>

                        <tr>

                            <th>
                                Student
                            </th>

                            <th>
                                Student Index
                            </th>

                            <th>
                                Title
                            </th>

                            <th>
                                Message
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Sent At
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($notifications as $notification)
                            <tr>

                                {{-- Student --}}

                                <td>

                                    @if ($notification->user)
                                        <div class="notification-student-info">


                                            <div>

                                                <strong>
                                                    {{ $notification->user->name ?? 'Student' }}
                                                </strong>

                                            </div>

                                        </div>
                                    @else
                                        <span class="notification-deleted-user">
                                            Student account removed
                                        </span>
                                    @endif

                                </td>


                                {{-- Student Index --}}

                                <td>

                                    @if ($notification->user)
                                        <span class="notification-student-index">
                                            {{ $notification->user->stud_index }}
                                        </span>
                                    @else
                                        <span class="notification-muted">
                                            —
                                        </span>
                                    @endif

                                </td>


                                {{-- Title --}}

                                <td>

                                    <strong class="notification-title">
                                        {{ $notification->title }}
                                    </strong>

                                </td>


                                {{-- Body --}}

                                <td>

                                    <span class="notification-body">
                                        {{ $notification->body }}
                                    </span>

                                </td>


                                {{-- Type --}}

                                <td>

                                    <span class="notification-type">
                                        {{ ucfirst($notification->type ?? 'general') }}
                                    </span>

                                </td>


                                {{-- Status --}}

                                <td>

                                    @if ($notification->read_at)
                                        <span class="notification-status read">
                                            Read
                                        </span>
                                    @else
                                        <span class="notification-status unread">
                                            Unread
                                        </span>
                                    @endif

                                </td>


                                {{-- Sent At --}}

                                <td>

                                    <div class="notification-date">

                                        <strong>
                                            {{ $notification->created_at->format('d M Y') }}
                                        </strong>

                                        <span>
                                            {{ $notification->created_at->format('h:i A') }}
                                        </span>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7" class="notifications-empty">

                                    No pushed notifications yet.

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- =====================================================
                 PAGINATION
                 ===================================================== --}}

            @if ($notifications->hasPages())
                @php
                    $activityPaginator = $notifications;
                    $startPage = max(1, $activityPaginator->currentPage() - 1);
                    $endPage = min($activityPaginator->lastPage(), $activityPaginator->currentPage() + 1);
                @endphp

                <div class="pagination-wrapper report-pagination">
                    <p class="report-pagination-summary">
                        Showing {{ $activityPaginator->firstItem() }} to {{ $activityPaginator->lastItem() }} of
                        {{ $activityPaginator->total() }} results
                    </p>

                    <nav aria-label="Recent student activity pagination">
                        <ul class="report-pagination-list">
                            <li>
                                @if ($activityPaginator->onFirstPage())
                                    <span class="is-disabled" aria-disabled="true">Previous</span>
                                @else
                                    <a href="{{ $activityPaginator->previousPageUrl() }}" rel="prev">Previous</a>
                                @endif
                            </li>

                            @foreach ($activityPaginator->getUrlRange($startPage, $endPage) as $page => $url)
                                <li>
                                    @if ($page === $activityPaginator->currentPage())
                                        <span class="is-active" aria-current="page">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}">{{ $page }}</a>
                                    @endif
                                </li>
                            @endforeach

                            <li>
                                @if ($activityPaginator->hasMorePages())
                                    <a href="{{ $activityPaginator->nextPageUrl() }}" rel="next">Next</a>
                                @else
                                    <span class="is-disabled" aria-disabled="true">Next</span>
                                @endif
                            </li>
                        </ul>
                    </nav>
                </div>
            @endif

        </div>

    </div>


    {{-- =========================================================
         SCRIPTS
         ========================================================= --}}

    @push('scripts')
        <script>
            window.adminMajorsUrl = @json(url('/admin/manage/majors'));
        </script>

        <script src="{{ asset('js/admin/script.js') }}"></script>
    @endpush

@endsection
