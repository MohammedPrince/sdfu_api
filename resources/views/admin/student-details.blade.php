@extends('admin.layouts.app')

@section('title', 'Student Details')

@section('page-title', 'Student Details')

@section('content')

    <div class="student-details-page">

        {{-- Header --}}
        <div class="student-details-header">

            <div>

                <a href="{{ route('admin.students') }}" class="back-link">
                    ← Back to Students
                </a>

                <div class="student-profile-heading">

                    <div class="large-student-avatar">
                        {{ strtoupper(substr($student->name ?? 'S', 0, 1)) }}
                    </div>

                    <div>

                        <h1>
                            {{ $student->name }}
                        </h1>

                        <p>
                            {{ $student->stud_index }}
                        </p>

                    </div>

                </div>

            </div>


            <div>

                @if ($student->is_active)
                    <span class="status-badge status-active large">
                        Active Account
                    </span>
                @else
                    <span class="status-badge status-inactive large">
                        Inactive Account
                    </span>
                @endif

            </div>

        </div>


        {{-- Overview --}}
        <div class="student-overview-grid">

            <div class="student-stat-card">

                <span>Registered Devices</span>

                <strong>
                    {{ $student->devices_count }}
                </strong>

            </div>


            <div class="student-stat-card">

                <span>Notifications</span>

                <strong>
                    {{ $student->notification_count }}
                </strong>

            </div>


            <div class="student-stat-card">

                <span>Unread Notifications</span>

                <strong>
                    {{ $student->unread_notification_count }}
                </strong>

            </div>


            <div class="student-stat-card">

                <span>Account</span>

                <strong>
                    {{ $student->is_active ? 'Active' : 'Inactive' }}
                </strong>

            </div>

        </div>


        <div class="student-details-grid">

            {{-- Personal Information --}}
            <div class="admin-card">

                <div class="detail-card-header">
                    <h2>Personal Information</h2>
                </div>

                <div class="details-list">

                    <div>
                        <span>Student Index</span>
                        <strong>{{ $student->stud_index }}</strong>
                    </div>

                    <div>
                        <span>Full Name</span>
                        <strong>{{ $student->name }}</strong>
                    </div>

                    <div>
                        <span>Email</span>
                        <strong>{{ $student->email ?: '—' }}</strong>
                    </div>

                    <div>
                        <span>Phone</span>
                        <strong>{{ $student->phone ?: '—' }}</strong>
                    </div>

                    <div>
                        <span>Gender</span>
                        <strong>{{ $student->gender ?: '—' }}</strong>
                    </div>

                </div>

            </div>


            {{-- Academic Information --}}
            <div class="admin-card">

                <div class="detail-card-header">
                    <h2>Academic Information</h2>
                </div>

                <div class="details-list">

                    <div>
                        <span>Faculty</span>
                        <strong>
                            {{ $student->faculty_name ?: $student->faculty_code }}
                        </strong>
                    </div>

                    <div>
                        <span>Major</span>
                        <strong>
                            {{ $student->major_name ?: $student->major_code }}
                        </strong>
                    </div>

                    <div>
                        <span>Batch</span>
                        <strong>{{ $student->batch }}</strong>
                    </div>

                    <div>
                        <span>Semester</span>
                        <strong>{{ $student->semester }}</strong>
                    </div>

                </div>

            </div>


            {{-- Account Management --}}
            <div class="admin-card">

                <div class="detail-card-header">

                    <div>
                        <h2>Account Management</h2>
                        <p>Control student access to Student Desk.</p>
                    </div>

                </div>


                <form method="POST"
                    action="{{ route('admin.students.status', [
                        'studentId' => base64_encode($student->id),
                    ]) }}">

                    @csrf
                    @method('PATCH')

                    <div class="account-status-control">

                        <div>
                            <strong>Account Status</strong>

                            <span>
                                {{ $student->account_active ? 'Student can access the application.' : 'Student account is currently disabled.' }}
                            </span>
                        </div>

                        <label class="switch">

                            <input type="checkbox" name="is_active" value="1" @checked($student->account_active)
                                onchange="this.form.submit()">

                            <span class="slider"></span>

                        </label>

                    </div>

                </form>

            </div>


            {{-- Devices --}}
            <div class="admin-card student-devices-card">

                <div class="detail-card-header">

                    <div>
                        <h2>Registered Devices</h2>
                        <p>FCM devices registered by this student.</p>
                    </div>

                </div>


                @if ($student->devices->isNotEmpty())

                    <div class="device-list">

                        @foreach ($student->devices as $device)
                            <div class="device-item">

                                <div class="device-icon">

                                    @if ($device->device_type === 'ios')
                                        
                                    @else
                                        ▣
                                    @endif

                                </div>


                                <div class="device-info">

                                    <strong>
                                        {{ $device->device_name ?: ucfirst($device->device_type ?: 'Device') }}
                                    </strong>

                                    <span>
                                        {{ $device->device_type ?: 'Unknown device' }}
                                        @if ($device->app_version)
                                            · App {{ $device->app_version }}
                                        @endif
                                    </span>

                                    @if ($device->last_seen_at)
                                        <small>
                                            Last seen
                                            {{ $device->last_seen_at->format('d M Y, h:i A') }}
                                        </small>
                                    @else
                                        <small>
                                            No activity recorded
                                        </small>
                                    @endif

                                </div>


                                <div>

                                    @if ($device->is_active)
                                        <span class="status-badge status-active">
                                            Active
                                        </span>
                                    @else
                                        <span class="status-badge status-inactive">
                                            Inactive
                                        </span>
                                    @endif

                                </div>

                            </div>
                        @endforeach

                    </div>
                @else
                    <div class="empty-device-state">
                        No registered devices for this student.
                    </div>

                @endif

            </div>

        </div>

    </div>

@endsection
