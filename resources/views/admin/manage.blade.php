@extends('admin.layouts.app')

@section('title', 'Manage Application')

@section('page-title', 'Manage Application')

@section('page-description', 'Configure application access by faculty, major, batch and semester')

@section('content')

    @php
        $editingAppVersion = $editAppVersion ?? null;
        $isEditingAppVersion = $editingAppVersion !== null;
    @endphp

    <div class="manage-page">

        {{-- Success --}}
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation Errors --}}
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
        {{-- APP VERSIONS --}}
        {{-- ========================================================= --}}

        <div class="notification-grid">

            <div class="admin-card">

                <div class="admin-card-header">

                    <div>

                        <h3>App Versions</h3>

                        <p>
                            Configure minimum supported versions and update links
                            for Android and iOS applications.
                        </p>

                    </div>

                </div>


                <form method="POST"
                    action="{{ $isEditingAppVersion
                        ? route('admin.manage.app-version.update', $editAppVersion->id)
                        : route('admin.manage.app-version.store') }}">
                    @csrf

                    @if ($isEditingAppVersion)
                        @method('PUT')
                    @endif

                    <div class="settings-section">

                        <h4>Version Configuration</h4>


                        <div class="form-grid">

                            {{-- Platform --}}
                            <div class="form-group">

                                <label for="version_platform">
                                    Platform
                                </label>

                                <select name="platform" id="version_platform" class="form-control" required>

                                    <option value="">
                                        Select Platform
                                    </option>

                                    <option value="ios"
                                        {{ old('platform', $editAppVersion->platform ?? '') === 'ios' ? 'selected' : '' }}>
                                        iOS
                                    </option>

                                    <option value="android"
                                        {{ old('platform', $editAppVersion->platform ?? '') === 'android' ? 'selected' : '' }}>
                                        Android
                                    </option>

                                </select>

                            </div>


                            {{-- Minimum Version --}}
                            <div class="form-group">

                                <label for="minimum_version">
                                    Minimum Version
                                </label>

                                <input type="text" name="minimum_version" id="minimum_version" class="form-control"
                                    placeholder="e.g. 1.6.2"
                                    value="{{ old('minimum_version', $editAppVersion->minimum_version ?? '') }}" required>

                            </div>


                            {{-- App URL --}}
                            <div class="form-group" style="grid-column: 1 / -1;">

                                <label for="app_url">
                                    App URL
                                </label>

                                <input type="url" name="app_url" id="app_url" class="form-control"
                                    placeholder="https://apps.apple.com/..."
                                    value="{{ old('app_url', $editAppVersion->app_url ?? '') }}" required>

                            </div>

                        </div>

                    </div>


                    {{-- Force Update --}}
                    <div class="settings-section">

                        <h4>Update Policy</h4>

                        <div class="status-list">

                            <div class="status-item">

                                <div class="status-info">

                                    <strong>
                                        Force Update
                                    </strong>

                                    <span>
                                        Prevent students from continuing until
                                        they update to the required version.
                                    </span>

                                </div>

                                <label class="switch">

                                    <input type="hidden" name="force_update" value="0">

                                    <input type="checkbox" name="force_update" value="1"
                                        {{ old('force_update', isset($editAppVersion) ? $editAppVersion->force_update : 0) ? 'checked' : '' }}>

                                    <span class="slider"></span>

                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            {{ $isEditingAppVersion ? 'Update App Version' : 'Save App Version' }}
                        </button>

                        @if ($isEditingAppVersion)
                            <a href="{{ route('admin.manage') }}" class="btn-secondary">
                                Cancel
                            </a>
                        @endif

                    </div>

                </form>

            </div>


            {{-- Existing Versions --}}
            <div class="admin-card">

                <div class="admin-card-header">

                    <div>

                        <h3>Configured Versions</h3>

                        <p>
                            Current minimum versions for each mobile platform.
                        </p>

                    </div>

                </div>


                @if ($appVersions->isEmpty())

                    <div class="empty-state">

                        <p>
                            No application versions have been configured.
                        </p>

                    </div>
                @else
                    <div class="table-responsive">

                        <table class="settings-table">

                            <thead>

                                <tr>

                                    <th>
                                        Platform
                                    </th>

                                    <th>
                                        Minimum Version
                                    </th>

                                    <th>
                                        Force Update
                                    </th>

                                    <th>
                                        URL
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @foreach ($appVersions as $version)
                                    <tr>

                                        <td>

                                            <strong>
                                                {{ strtoupper($version->platform) }}
                                            </strong>

                                        </td>


                                        <td>

                                            <span class="status-badge active">
                                                {{ $version->minimum_version }}
                                            </span>

                                        </td>


                                        <td>

                                            @if ($version->force_update)
                                                <span class="status-badge active">
                                                    Required
                                                </span>
                                            @else
                                                <span class="status-badge inactive">
                                                    Optional
                                                </span>
                                            @endif

                                        </td>


                                        <td>

                                            <a href="{{ $version->app_url }}" target="_blank" rel="noopener">
                                                <button class="btn-primary btn-sm">Open Store</button>
                                            </a>

                                        </td>


                                        <td class="actions-cell">

                                            <a href="{{ route('admin.manage', ['edit_version' => $version->id]) }}">
                                                <button class="btn-secondary btn-sm">Edit</button>
                                            </a>

                                            <form method="POST"
                                                action="{{ route('admin.manage.app-version.delete', $version->id) }}"
                                                style="display:inline-block;"
                                                onsubmit="return confirm('Delete this app version configuration?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn-danger btn-sm">
                                                    Delete
                                                </button>
                                            </form>

                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @endif

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- SETTINGS + SAVED SETTINGS --}}
        {{-- ========================================================= --}}

        <div class="admin-card-grid" style="padding-top: 24px;">

            {{-- ===================================================== --}}
            {{-- LEFT: MANAGE APPLICATION SETTINGS --}}
            {{-- ===================================================== --}}

            <div class="admin-card">

                <div class="admin-card-header">
                    <div>
                        <h3>Manage Application Settings</h3>

                        <p>
                            Select Faculty, Major, Batch and Semester to control which
                            application features are available to students.
                        </p>
                    </div>
                </div>


                <form method="POST" action="{{ route('admin.manage.update') }}">

                    @csrf

                    <input type="hidden" name="id" value="{{ $editSetting->id ?? '' }}">


                    {{-- Academic Selection --}}
                    <div class="settings-section">

                        <h4>App Settings</h4>

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
                                            {{ old('faculty_code', $editSetting->faculty_code ?? '') == $faculty->faculty_code ? 'selected' : '' }}>
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

                                <select name="batch" id="batch" class="form-control" required>
                                    <option value="">
                                        Select Batch
                                    </option>

                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch->batch }}"
                                            {{ old('batch', $editSetting->batch ?? '') == $batch->batch ? 'selected' : '' }}>
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
                                            {{ old('semester', $editSetting->semester ?? '') == $semester ? 'selected' : '' }}>
                                            {{ $semester }}
                                        </option>
                                    @endfor

                                </select>

                            </div>

                        </div>

                    </div>


                    {{-- Application Status --}}
                    <div class="settings-section">

                        <h4>Application Access</h4>

                        <div class="status-list">


                            {{-- API --}}
                            <div class="status-item">

                                <div class="status-info">
                                    <strong>Application</strong>

                                    <span>
                                        Allow students to access the application.
                                    </span>
                                </div>

                                <label class="switch">

                                    <input type="hidden" name="api_active" value="0">

                                    <input type="checkbox" name="api_active" value="1"
                                        {{ old('api_active', $editSetting->api_active ?? 1) ? 'checked' : '' }}>

                                    <span class="slider"></span>

                                </label>

                            </div>


                            {{-- Fee --}}
                            <div class="status-item">

                                <div class="status-info">
                                    <strong>Fees</strong>

                                    <span>
                                        Allow students to view current semester fees.
                                    </span>
                                </div>

                                <label class="switch">

                                    <input type="hidden" name="fee_active" value="0">

                                    <input type="checkbox" name="fee_active" value="1"
                                        {{ old('fee_active', $editSetting->fee_active ?? 1) ? 'checked' : '' }}>

                                    <span class="slider"></span>

                                </label>

                            </div>


                            {{-- Result --}}
                            <div class="status-item">

                                <div class="status-info">
                                    <strong>Result</strong>

                                    <span>
                                        Allow students to view their semester result.
                                    </span>
                                </div>

                                <label class="switch">

                                    <input type="hidden" name="result_active" value="0">

                                    <input type="checkbox" name="result_active" value="1"
                                        {{ old('result_active', $editSetting->result_active ?? 1) ? 'checked' : '' }}>

                                    <span class="slider"></span>

                                </label>

                            </div>


                            {{-- Timetable --}}
                            <div class="status-item">

                                <div class="status-info">
                                    <strong>Timetable</strong>

                                    <span>
                                        Allow students to view their timetable.
                                    </span>
                                </div>

                                <label class="switch">

                                    <input type="hidden" name="timetable_active" value="0">

                                    <input type="checkbox" name="timetable_active" value="1"
                                        {{ old('timetable_active', $editSetting->timetable_active ?? 0) ? 'checked' : '' }}>

                                    <span class="slider"></span>

                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            Save Settings
                        </button>

                    </div>

                </form>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT: SAVED SETTINGS --}}
            {{-- ===================================================== --}}

            <div class="admin-card saved-settings-card">

                <div class="admin-card-header">

                    <div>

                        <h3>Saved Settings</h3>

                        <p>
                            Application access settings currently configured for students.
                        </p>

                    </div>

                </div>


                @if ($savedSettings->isEmpty())

                    <div class="empty-state">

                        <p>
                            No application settings have been saved yet.
                        </p>

                    </div>
                @else
                    <div class="table-responsive">

                        <table class="settings-table">

                            <thead>

                                <tr>
                                    <th>Faculty</th>
                                    <th>Major</th>
                                    <th>Batch</th>
                                    <th>Semester</th>
                                    <th>Application</th>
                                    <th>Fees</th>
                                    <th>Result</th>
                                    <th>Timetable</th>
                                    <th>Edit</th>
                                </tr>

                            </thead>


                            <tbody>

                                @foreach ($savedSettings as $setting)
                                    <tr>

                                        <td>
                                            {{ $setting['faculty_desc_e'] }}
                                        </td>

                                        <td>
                                            {{ $setting['major_desc_e'] }}
                                        </td>

                                        <td>
                                            {{ $setting['batch'] }}
                                        </td>

                                        <td>
                                            {{ $setting['semester'] }}
                                        </td>


                                        {{-- Application --}}
                                        <td>

                                            @if ($setting['api_active'])
                                                <span class="status-badge active">
                                                    Active
                                                </span>
                                            @else
                                                <span class="status-badge inactive">
                                                    Inactive
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Fees --}}
                                        <td>

                                            @if ($setting['fee_active'])
                                                <span class="status-badge active">
                                                    Active
                                                </span>
                                            @else
                                                <span class="status-badge inactive">
                                                    Inactive
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Result --}}
                                        <td>

                                            @if ($setting['result_active'])
                                                <span class="status-badge active">
                                                    Active
                                                </span>
                                            @else
                                                <span class="status-badge inactive">
                                                    Inactive
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Timetable --}}
                                        <td>

                                            @if ($setting['timetable_active'])
                                                <span class="status-badge active">
                                                    Active
                                                </span>
                                            @else
                                                <span class="status-badge inactive">
                                                    Inactive
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Edit --}}
                                        <td class="actions-cell">

                                            <a href="{{ route('admin.manage', ['edit' => $setting['id']]) }}"
                                                class="btn-edit btn-sm">
                                                <button class="btn-primary btn-view-timetable">Edit</button>
                                            </a>

                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

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
