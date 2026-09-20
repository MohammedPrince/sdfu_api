@extends('admin.layouts.app')

@section('title', 'Reports')

@section('page-title', 'Reports')

@section('page-description')
    Future University Student Desk reports and system statistics
@endsection


@section('content')

    <div class="reports-page">

        {{-- ========================================================= --}}
        {{-- Filters --}}
        {{-- ========================================================= --}}

        <div class="admin-card reports-filter-card">

            <div class="admin-card-header">

                <div>
                    <h3>Report Filters</h3>

                    <p>
                        Filter student and application statistics
                        by academic information and date.
                    </p>
                </div>

            </div>


            <form method="GET" action="{{ route('admin.reports') }}" class="reports-filter-form">

                <div class="form-group">

                    <label for="faculty_code">
                        Faculty
                    </label>

                    <select name="faculty_code" id="faculty_code" class="form-control">

                        <option value="">
                            All Faculties
                        </option>

                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->faculty_code }}" @selected(($filters['faculty_code'] ?? '') === $faculty->faculty_code)>

                                {{ $faculty->faculty_desc_e }}

                            </option>
                        @endforeach

                    </select>

                </div>


                <div class="form-group">

                    <label for="major_code">
                        Major
                    </label>

                    <select name="major_code" id="major_code" class="form-control">

                        <option value="">
                            All Majors
                        </option>

                        @foreach ($majors as $major)
                            <option value="{{ $major->major_code }}" @selected(($filters['major_code'] ?? '') === $major->major_code)>

                                {{ $major->major_desc_e }}

                            </option>
                        @endforeach

                    </select>

                </div>


                <div class="form-group">

                    <label for="batch">
                        Batch
                    </label>

                    <select name="batch" id="batch" class="form-control">

                        <option value="">
                            All Batches
                        </option>

                        @foreach ($batches as $batch)
                            <option value="{{ $batch->DISCIT ?? ($batch->batch ?? '') }}" @selected(($filters['batch'] ?? '') === ($batch->DISCIT ?? ($batch->batch ?? '')))>

                                {{ $batch->DISCIT ?? ($batch->batch ?? '') }}

                            </option>
                        @endforeach

                    </select>

                </div>


                <div class="form-group">

                    <label for="semester">
                        Semester
                    </label>

                    <select name="semester" id="semester" class="form-control">

                        <option value="">
                            All Semesters
                        </option>

                        @for ($semester = 1; $semester <= 10; $semester++)
                            <option value="{{ $semester }}" @selected((string) ($filters['semester'] ?? '') === (string) $semester)>

                                {{ $semester }}

                            </option>
                        @endfor

                    </select>

                </div>


                <div class="form-group">

                    <label for="date_from">
                        From
                    </label>

                    <input type="date" name="date_from" id="date_from" class="form-control"
                        value="{{ $filters['date_from'] ?? '' }}">

                </div>


                <div class="form-group">

                    <label for="date_to">
                        To
                    </label>

                    <input type="date" name="date_to" id="date_to" class="form-control"
                        value="{{ $filters['date_to'] ?? '' }}">

                </div>


                <div class="reports-filter-actions">

                    <button type="submit" class="btn-secondary">

                        Generate Report

                    </button>


                    <a href="{{ route('admin.reports') }}" class="btn-secondary">

                        Clear

                    </a>

                    <button type="button" onclick="window.print()" class="btn-secondary">

                        Print

                    </button>

                </div>

            </form>

        </div>


        {{-- ========================================================= --}}
        {{-- Main Statistics --}}
        {{-- ========================================================= --}}

        <div class="report-stat-grid">

            <div class="report-stat-card">

                <span>Students</span>

                <strong>
                    {{ number_format($report['students']['total']) }}
                </strong>

                <small>
                    Registered students
                </small>

            </div>


            <div class="report-stat-card">

                <span>Active</span>

                <strong>
                    {{ number_format($report['students']['active']) }}
                </strong>

                <small>
                    Application access
                </small>

            </div>


            <div class="report-stat-card">

                <span>Disabled</span>

                <strong>
                    {{ number_format($report['students']['disabled']) }}
                </strong>

                <small>
                    Application unavailable
                </small>

            </div>


            <div class="report-stat-card">

                <span>Devices</span>

                <strong>
                    {{ number_format($report['devices']['total']) }}
                </strong>

                <small>
                    Registered devices
                </small>

            </div>


            <div class="report-stat-card">

                <span>Notifications</span>

                <strong>
                    {{ number_format($report['notifications']['total']) }}
                </strong>

                <small>
                    Selected period
                </small>

            </div>


            <div class="report-stat-card">

                <span>Visitors</span>

                <strong>
                    {{ number_format($report['visitors']['month']) }}
                </strong>

                <small>
                    This month
                </small>

            </div>

        </div>

        {{-- ========================================================= --}}
        {{-- Application Status --}}
        {{-- ========================================================= --}}

        <div class="admin-card report-card">

            <div class="admin-card-header">

                <div>

                    <h3>Application Status</h3>

                    <p>
                        Student Desk availability by academic configuration.
                    </p>

                </div>

            </div>


            <div class="application-report-grid">

                <div>
                    <span>Total Configurations</span>
                    <strong>
                        {{ number_format($report['application']['total']) }}
                    </strong>
                </div>

                <div>
                    <span>Active</span>
                    <strong class="report-success">
                        {{ number_format($report['application']['active']) }}
                    </strong>
                </div>

                <div>
                    <span>Inactive</span>
                    <strong class="report-danger">
                        {{ number_format($report['application']['inactive']) }}
                    </strong>
                </div>

                <div>
                    <span>Fee Active</span>
                    <strong>
                        {{ number_format($report['application']['fee_active']) }}
                    </strong>
                </div>

                <div>
                    <span>Result Active</span>
                    <strong>
                        {{ number_format($report['application']['result_active']) }}
                    </strong>
                </div>

                <div>
                    <span>Timetable Active</span>
                    <strong>
                        {{ number_format($report['application']['timetable_active']) }}
                    </strong>
                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- Devices + Notifications --}}
        {{-- ========================================================= --}}

        <div class="reports-two-column">

            <div class="admin-card report-card">

                <div class="admin-card-header">

                    <div>

                        <h3>Device Statistics</h3>

                        <p>
                            Registered Student Desk devices.
                        </p>

                    </div>

                </div>


                <div class="device-summary">

                    <div>
                        <span>Total Devices</span>
                        <strong>
                            {{ number_format($report['devices']['total']) }}
                        </strong>
                    </div>

                    <div>
                        <span>Active Devices</span>
                        <strong class="report-success">
                            {{ number_format($report['devices']['active']) }}
                        </strong>
                    </div>

                </div>


                <div class="report-list">

                    @foreach ($report['device_types'] as $device)
                        <div class="report-list-row">

                            <strong>
                                {{ ucfirst($device->device_type) }}
                            </strong>

                            <span>
                                {{ number_format($device->total) }}
                            </span>

                        </div>
                    @endforeach

                </div>

            </div>


            <div class="admin-card report-card">

                <div class="admin-card-header">

                    <div>

                        <h3>Notifications</h3>

                        <p>
                            Notification activity for the selected period.
                        </p>

                    </div>

                </div>


                <div class="device-summary">

                    <div>
                        <span>Total</span>
                        <strong>
                            {{ number_format($report['notifications']['total']) }}
                        </strong>
                    </div>

                    <div>
                        <span>Read</span>
                        <strong class="report-success">
                            {{ number_format($report['notifications']['read']) }}
                        </strong>
                    </div>

                    <div>
                        <span>Unread</span>
                        <strong>
                            {{ number_format($report['notifications']['unread']) }}
                        </strong>
                    </div>

                </div>


                <div class="report-list">

                    @foreach ($report['notification_types'] as $notification)
                        <div class="report-list-row">

                            <strong>
                                {{ ucfirst($notification->type) }}
                            </strong>

                            <span>
                                {{ number_format($notification->total) }}
                            </span>

                        </div>
                    @endforeach

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- Visitors --}}
        {{-- ========================================================= --}}

        <div class="admin-card report-card">

            <div class="admin-card-header">

                <div>

                    <h3>Visitor Statistics</h3>

                    <p>
                        Student Desk website visitor activity.
                    </p>

                </div>

            </div>


            <div class="visitor-report-grid">

                <div>
                    <span>Today</span>
                    <strong>
                        {{ number_format($report['visitors']['today']) }}
                    </strong>
                </div>

                <div>
                    <span>This Month</span>
                    <strong>
                        {{ number_format($report['visitors']['month']) }}
                    </strong>
                </div>

                <div>
                    <span>Total</span>
                    <strong>
                        {{ number_format($report['visitors']['total']) }}
                    </strong>
                </div>

            </div>


            <div class="visitor-bars">

                @php
                    $maxVisitors = max($report['visitor_activity']->max('total') ?? 0, 1);
                @endphp

                @foreach ($report['visitor_activity'] as $visitor)
                    <div class="visitor-bar-item">

                        <div class="visitor-bar-value">
                            {{ number_format($visitor['total']) }}
                        </div>

                        <div class="visitor-bar-track">

                            <div class="visitor-bar-fill" style="height: {{ ($visitor['total'] / $maxVisitors) * 100 }}%;">
                            </div>

                        </div>

                        <span>
                            {{ $visitor['date'] }}
                        </span>

                    </div>
                @endforeach

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- Recent Activity --}}
        {{-- ========================================================= --}}

        <div class="admin-card report-card">

            <div class="admin-card-header">

                <div>

                    <h3>Recent Student Activity</h3>

                    <p>
                        Recently active Student Desk devices.
                    </p>

                </div>

            </div>


            <div class="table-responsive">

                <table class="settings-table reports-table">

                    <thead>

                        <tr>
                            <th>Student</th>
                            <th>Index</th>
                            <th>Device</th>
                            <th>Type</th>
                            <th>Last Activity</th>
                        </tr>

                    </thead>


                    <tbody>

                        @forelse($report['recent_activity'] as $activity)
                            <tr>

                                <td>
                                    <strong>
                                        {{ $activity->user->name ?? 'Unknown' }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $activity->user->stud_index ?? '-' }}
                                </td>

                                <td>
                                    {{ $activity->device_name ?? '-' }}
                                </td>

                                <td>
                                    {{ ucfirst($activity->device_type ?? '-') }}
                                </td>

                                <td>

                                    @if ($activity->last_seen_at)
                                        {{ \Carbon\Carbon::parse($activity->last_seen_at)->format('d M Y h:i A') }}
                                    @else
                                        Never
                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="report-empty">

                                    No recent activity available.

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

                @if ($report['recent_activity']->hasPages())
                    <div class="pagination-wrapper">
                        {{ $report['recent_activity']->onEachSide(1)->links() }}
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
