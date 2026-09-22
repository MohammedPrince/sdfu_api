@extends('admin.layouts.app')

@section('title', 'Manage Timetable')

@section('page-title', 'Manage Timetable')

@section('page-description', 'Fetch timetable data from local server and configure server connection')

@section('content')

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
        {{-- TIMETABLE FETCH CONFIGURATION --}}
        {{-- ========================================================= --}}

        <div class="manage-settings-grid">

            {{-- ===================================================== --}}
            {{-- LEFT: FETCH TIMETABLE SETTINGS --}}
            {{-- ===================================================== --}}

            <div class="admin-card">

                <div class="admin-card-header">
                    <div>
                        <h3>Fetch Timetable Data</h3>

                        <p>
                            Select Faculty, Major, Batch, Semester and TTID to fetch timetable data from local server.
                        </p>
                    </div>
                </div>


                <form method="POST" action="{{ route('admin.manage.timetable.fetch') }}">

                    @csrf

                    {{-- Academic Selection --}}
                    <div class="settings-section">

                        <h4>Timetable Configuration</h4>

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

                            {{-- TTID --}}
                            <div class="form-group">

                                <label for="ttid">
                                    TTID
                                </label>

                                <select name="ttid" id="ttid" class="form-control" required>
                                    <option value="">
                                        Select TTID
                                    </option>
                                    <!-- TTID options will be populated via JavaScript or can be hardcoded common values -->
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                    <option value="40">40</option>
                                    <option value="50">50</option>
                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            Fetch Timetable
                        </button>

                    </div>

                </form>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT: SERVER CONFIGURATION --}}
            {{-- ===================================================== --}}

            <div class="admin-card server-config-card">

                <div class="admin-card-header">

                    <div>

                        <h3>Server Configuration</h3>

                        <p>
                            Configure the local server IP address for API connections.
                        </p>

                    </div>

                </div>


                @if ($savedSettings->isEmpty())
                    <div class="empty-state">

                        <p>
                            No server configuration found. Please save your server settings below.
                        </p>

                    </div>
                @else
                    <?php
                    $serverConfig = null;
                    foreach ($savedSettings as $setting) {
                        if ($setting['faculty_code'] === 'SERVER_CONFIG' && $setting['major_code'] === 'TIMETABLE_API') {
                            $serverConfig = $setting;
                            break;
                        }
                    }
                    ?>
                    <div class="server-config-info">
                        <h4>Current Server Settings</h4>
                        <div class="config-item">
                            <span class="config-label">Server IP:</span>
                            <span
                                class="config-value">{{ isset($serverConfig) && isset($serverConfig['server_ip']) ? $serverConfig['server_ip'] : 'Not configured' }}</span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">API Endpoint:</span>
                            <span
                                class="config-value">{{ isset($serverConfig) && isset($serverConfig['server_ip']) ? 'http://' . $serverConfig['server_ip'] . '/index.php' : 'Not configured' }}</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.manage.timetable.server.config') }}">

                    @csrf
                    <?php
                    $serverConfig = null;
                    foreach ($savedSettings as $setting) {
                        if ($setting['faculty_code'] === 'SERVER_CONFIG' && $setting['major_code'] === 'TIMETABLE_API') {
                            $serverConfig = $setting;
                            break;
                        }
                    }
                    ?>

                    <div class="form-group">

                        <label for="server_ip">
                            Local Server IP Address
                        </label>

                        <input type="text" name="server_ip" id="server_ip" class="form-control"
                            placeholder="Enter server IP (e.g., 192.168.1.100)"
                            value="{{ old('server_ip', isset($serverConfig) && isset($serverConfig['server_ip']) ? $serverConfig['server_ip'] : '') }}">

                        <small class="form-text text-muted">
                            Enter the IP address of your local server where the index.php API is hosted.
                        </small>

                    </div>

                    <div class="form-group">

                        <label for="api_active">
                            Enable Timetable API
                        </label>

                        <label class="switch">

                            <input type="hidden" name="api_active" value="0">

                            <input type="checkbox" name="api_active" value="1"
                                {{ old('api_active', isset($serverConfig) && isset($serverConfig['api_active']) ? $serverConfig['api_active'] : 1) ? 'checked' : '' }}>

                            <span class="slider"></span>

                        </label>

                    </div>

                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            Save Server Configuration
                        </button>

                    </div>

                </form>

            </div>

        </div>

        {{-- ========================================================= --}}
        {{-- TIMETABLE RESULTS DISPLAY --}}
        {{-- ========================================================= --}}

        @if (session('timetable_data'))
            <div class="admin-card timetable-results-card">

                <div class="admin-card-header">
                    <div>
                        <h3>Timetable Data</h3>

                        <p>
                            Retrieved timetable data from local server.
                        </p>
                    </div>
                </div>

                <div class="timetable-container">
                    {{ session('timetable_data') }}
                </div>

            </div>
        @endif

    </div>

    @push('scripts')
        <script>
            window.adminMajorsUrl = @json(url('/admin/manage/majors'));

            // AJAX call to update majors when faculty changes
            document.getElementById('faculty_code')?.addEventListener('change', function(e) {
                const facultyCode = e.target.value;
                const majorSelect = document.getElementById('major_code');

                if (!facultyCode) {
                    majorSelect.innerHTML = '<option value="">Select Major</option>';
                    return;
                }

                // Show loading state
                majorSelect.innerHTML = '<option value="">Loading majors...</option>';
                majorSelect.disabled = true;

                fetch(window.adminMajorsUrl + '?faculty_code=' + encodeURIComponent(facultyCode))
                    .then(response => response.json())
                    .then(data => {
                        majorSelect.innerHTML = '<option value="">Select Major</option>';
                        data.forEach(major => {
                            const option = document.createElement('option');
                            option.value = major.major_code;
                            option.textContent = major.major_desc_e;
                            majorSelect.appendChild(option);
                        });
                        majorSelect.disabled = false;
                    })
                    .catch(() => {
                        majorSelect.innerHTML = '<option value="">Select Major</option>';
                        majorSelect.disabled = false;
                    });
            });
        </script>

        <script src="{{ asset('js/admin/script.js') }}"></script>
    @endpush
@endsection
