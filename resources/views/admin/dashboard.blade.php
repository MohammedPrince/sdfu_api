@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('page-description', 'Welcome to the Future University Administration Desk')

@section('content')

    @php
        $visitorCount = $visitorCount ?? [
            'today' => 0,
            'month' => 0,
            'total' => 0,
        ];

        $applicationStatus = $applicationStatus ?? [
            'success' => true,
            'settings' => null,
        ];

        $notificationCount = $notificationCount ?? 0;
        $recentNotifications = $recentNotifications ?? collect();
    @endphp

    {{-- Welcome --}}
    <div class="dashboard-welcome">
        <h2>
            Welcome back,
            {{ auth()->user()->name ?? 'Administrator' }}
        </h2>

        <p>
            Manage students, academic records and university services
            from your administration dashboard.
        </p>
    </div>


    {{-- Main Statistics --}}
    <div class="dashboard-cards">

        {{-- Students --}}
        <div class="dashboard-card">
            <div class="dashboard-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>

            <div>
                <span>Students</span>
                <strong>{{ number_format($studentCount) }}</strong>
            </div>
        </div>


        {{-- Courses --}}
        <div class="dashboard-card">
            <div class="dashboard-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>

            <div>
                <span>Courses</span>
                <strong>{{ number_format($courseCount ?? 0) }}</strong>
            </div>
        </div>


        {{-- Notifications --}}
        <div class="dashboard-card">
            <div class="dashboard-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M18 9A6 6 0 0 0 6 9c0 7-2.5 7-2.5 9h17C20.5 16 18 16 18 9Z"></path>
                    <path d="M10 21h4"></path>
                </svg>
            </div>

            <div>
                <span>Notifications</span>
                <strong>{{ number_format($notificationCount) }}</strong>
            </div>
        </div>


        {{-- System Status --}}
        <div class="dashboard-card">
            <div class="dashboard-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M8 12l2.5 2.5L16 9"></path>
                </svg>
            </div>

            <div>
                <span>System Status</span>

                @if ($applicationStatus['success'])
                    <strong class="dashboard-status-active">Active</strong>
                @else
                    <strong class="dashboard-status-inactive">Unavailable</strong>
                @endif
            </div>
        </div>

    </div>


    {{-- Dashboard Overview --}}
    <div class="dashboard-overview-grid">

        {{-- Visitor Statistics --}}
        <div class="dashboard-panel">

            <div class="dashboard-panel-header">
                <div>
                    <h3>Visitor Statistics</h3>
                    <p>Student Desk website visits</p>
                </div>

                <div class="dashboard-panel-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                        <circle cx="12" cy="12" r="2.5"></circle>
                    </svg>
                </div>
            </div>

            <div class="visitor-stat-grid">

                <div class="visitor-stat-item">
                    <span>Today</span>
                    <strong>{{ number_format($visitorCount['today']) }}</strong>
                </div>

                <div class="visitor-stat-item">
                    <span>This Month</span>
                    <strong>{{ number_format($visitorCount['month']) }}</strong>
                </div>

                <div class="visitor-stat-item">
                    <span>Total</span>
                    <strong>{{ number_format($visitorCount['total']) }}</strong>
                </div>

            </div>

        </div>


        {{-- Application Status --}}
        <div class="dashboard-panel">

            <div class="dashboard-panel-header">
                <div>
                    <h3>Application Status</h3>
                    <p>Current Student Desk services</p>
                </div>

                <div class="dashboard-panel-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M12 3v18"></path>
                        <path d="M3 12h18"></path>
                        <circle cx="12" cy="12" r="9"></circle>
                    </svg>
                </div>
            </div>

            @php
                $repo = new App\Repositories\AdminRepository();
                $overview = $repo->getApplicationOverview();
            @endphp

            <div class="application-status-list">

                <div class="application-status-row">
                    <span>Student Desk</span>
                    @php
                        $active = $overview['active'];
                        $inactive = $overview['inactive'];
                        $isAvailable = $active > $inactive;
                    @endphp
                    @if ($isAvailable)
                        <strong class="status-active">● Active</strong>
                    @else
                        <strong class="status-inactive">● Unavailable</strong>
                    @endif
                </div>

                <div class="application-status-row">
                    <span>Semester Results</span>
                    @php
                        $resultActive = $overview['result_active'];
                        $resultInactive = $overview['total'] - $resultActive;
                        $isAvailable = $resultActive > $resultInactive;
                    @endphp
                    @if ($isAvailable)
                        <strong class="status-active">● Active</strong>
                    @else
                        <strong class="status-inactive">● Unavailable</strong>
                    @endif
                </div>

                <div class="application-status-row">
                    <span>Timetable</span>
                    @php
                        $timetableActive = $overview['timetable_active'];
                        $timetableInactive = $overview['total'] - $timetableActive;
                        $isAvailable = $timetableActive > $timetableInactive;
                    @endphp
                    @if ($isAvailable)
                        <strong class="status-active">● Active</strong>
                    @else
                        <strong class="status-inactive">● Unavailable</strong>
                    @endif
                </div>

                <div class="application-status-row">
                    <span>Registration Fees</span>
                    @php
                        $feeActive = $overview['fee_active'];
                        $feeInactive = $overview['total'] - $feeActive;
                        $isAvailable = $feeActive > $feeInactive;
                    @endphp
                    @if ($isAvailable)
                        <strong class="status-active">● Active</strong>
                    @else
                        <strong class="status-inactive">● Unavailable</strong>
                    @endif
                </div>

            </div>

        </div>

    </div>


    {{-- Lower Dashboard --}}
    <div class="dashboard-lower-grid">

        {{-- Recent Notifications --}}
        <div class="dashboard-panel">

            <div class="dashboard-panel-header">
                <div>
                    <h3>Recent Notifications</h3>
                    <p>Latest messages sent to students</p>
                </div>

                <a href="{{ route('admin.notifications') }}" class="dashboard-panel-link">
                    View All
                </a>
            </div>

            @if ($recentNotifications->isNotEmpty())

                <div class="recent-notifications">

                    @foreach ($recentNotifications as $notification)
                        <div class="recent-notification-item">

                            <div class="recent-notification-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 9A6 6 0 0 0 6 9c0 7-2.5 7-2.5 9h17C20.5 16 18 16 18 9Z"></path>
                                    <path d="M10 21h4"></path>
                                </svg>
                            </div>

                            <div class="recent-notification-content">
                                <strong>{{ $notification->title }}</strong>
                                <p>{{ \Illuminate\Support\Str::limit($notification->body, 90) }}</p>
                                <small>{{ $notification->created_at?->diffForHumans() }}</small>
                            </div>

                        </div>
                    @endforeach

                </div>

                @if ($recentNotifications->hasPages())
                    @php
                        $notificationPaginator = $recentNotifications;
                        $startPage = max(1, $notificationPaginator->currentPage() - 1);
                        $endPage = min($notificationPaginator->lastPage(), $notificationPaginator->currentPage() + 1);
                    @endphp

                    <div class="pagination-wrapper notification-pagination">
                        <p class="notification-pagination-summary">
                            Showing {{ $notificationPaginator->firstItem() }} to {{ $notificationPaginator->lastItem() }} of
                            {{ $notificationPaginator->total() }} results
                        </p>

                        <nav aria-label="Recent notifications pagination">
                            <ul class="notification-pagination-list">
                                <li>
                                    @if ($notificationPaginator->onFirstPage())
                                        <span class="is-disabled" aria-disabled="true">Previous</span>
                                    @else
                                        <a href="{{ $notificationPaginator->previousPageUrl() }}" rel="prev">Previous</a>
                                    @endif
                                </li>

                                @foreach ($notificationPaginator->getUrlRange($startPage, $endPage) as $page => $url)
                                    <li>
                                        @if ($page === $notificationPaginator->currentPage())
                                            <span class="is-active" aria-current="page">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}">{{ $page }}</a>
                                        @endif
                                    </li>
                                @endforeach

                                <li>
                                    @if ($notificationPaginator->hasMorePages())
                                        <a href="{{ $notificationPaginator->nextPageUrl() }}" rel="next">Next</a>
                                    @else
                                        <span class="is-disabled" aria-disabled="true">Next</span>
                                    @endif
                                </li>
                            </ul>
                        </nav>
                    </div>
                @endif
            @else
                <div class="dashboard-empty-state">
                    <span>No notifications have been sent yet.</span>
                </div>

            @endif

        </div>


        {{-- Quick Actions --}}
        <div class="dashboard-panel">

            <div class="dashboard-panel-header">
                <div>
                    <h3>Quick Actions</h3>
                    <p>Frequently used administration tools</p>
                </div>
            </div>

            <div class="quick-actions">

                <a href="{{ route('admin.notifications') }}" class="quick-action">
                    <span class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>
                        </svg>
                    </span>

                    <span>
                        <strong>Send Notification</strong>
                        <small>Send a message to students</small>
                    </span>
                </a>


                <a href="{{ route('admin.manage') }}" class="quick-action">
                    <span class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path
                                d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 1.7-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2h-2.4v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-1.7-1.7.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H7v-2.4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1L10 5.6l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5v-.2h2.4v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 1.7 1.7-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.2v2.4h-.2a1.7 1.7 0 0 0-1.5 1Z">
                            </path>
                        </svg>
                    </span>

                    <span>
                        <strong>Manage Application</strong>
                        <small>Control application services</small>
                    </span>
                </a>


                <a href="{{ route('admin.notifications') }}" class="quick-action">
                    <span class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        </svg>
                    </span>

                    <span>
                        <strong>Student Notifications</strong>
                        <small>Manage student messages</small>
                    </span>
                </a>

            </div>

        </div>

    </div>

@endsection
