@php
    $groupNames = $templateGroups->keys();
    $weekdays = \App\Models\ScheduledCampaign::WEEKDAYS;
    $templateTypes = \App\Models\CampaignTemplate::TYPES;
@endphp

{{-- =========================================================
     SCHEDULED CAMPAIGNS (daily / weekly)
     ========================================================= --}}

<div class="notification-grid campaigns-section">

    {{-- =====================================================
         CREATE CAMPAIGN
         ===================================================== --}}

    <div class="admin-card">

        <div class="admin-card-header">
            <div>
                <h3>Scheduled Campaign</h3>
                <p>
                    Send the messages of a template set one by one, daily or weekly,
                    at a fixed time (Khartoum time). Example: Google Play testers, daily at 13:30 for 14 days.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.notifications.campaigns.store') }}" id="campaign-form">
            @csrf

            {{-- Campaign --}}

            <div class="settings-section">
                <h4>Campaign</h4>

                <div class="form-grid">

                    <div class="form-group">
                        <label for="campaign_name">Campaign Name</label>
                        <input type="text" name="name" id="campaign_name" class="form-control"
                            value="{{ old('name', 'Google Play Closed Testing') }}" maxlength="255" required>
                    </div>

                    <div class="form-group">
                        <label for="campaign_template_group">Template Set</label>
                        <select name="template_group" id="campaign_template_group" class="form-control" required>
                            @forelse ($groupNames as $group)
                                <option value="{{ $group }}" {{ old('template_group') === $group ? 'selected' : '' }}>
                                    {{ $group }} ({{ $templateGroups[$group]->count() }} messages)
                                </option>
                            @empty
                                <option value="">No template sets yet: add templates below</option>
                            @endforelse
                        </select>
                    </div>

                </div>
            </div>

            {{-- Audience --}}

            <div class="settings-section">
                <h4>Audience</h4>

                <div class="form-grid">

                    <div class="form-group">
                        <label for="campaign_audience">Send To</label>
                        <select name="audience" id="campaign_audience" class="form-control" required>
                            <option value="indexes" {{ old('audience', 'indexes') === 'indexes' ? 'selected' : '' }}>
                                Specific students (e.g. Google testers)
                            </option>
                            <option value="all" {{ old('audience') === 'all' ? 'selected' : '' }}>
                                All students
                            </option>
                            <option value="group" {{ old('audience') === 'group' ? 'selected' : '' }}>
                                Academic group
                            </option>
                        </select>
                    </div>

                    <div class="form-group" data-audience="indexes">
                        <label for="campaign_indexes">Student Indexes</label>
                        <textarea name="student_indexes" id="campaign_indexes" class="form-control" rows="3"
                            maxlength="10000"
                            placeholder="Student indexes separated by commas or new lines">{{ old('student_indexes') }}</textarea>
                    </div>

                    <div class="form-group" data-audience="group">
                        <label for="campaign_faculty_code">Faculty</label>
                        <select name="faculty_code" id="campaign_faculty_code" class="form-control">
                            <option value="">Select Faculty</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->faculty_code }}"
                                    {{ old('faculty_code') == $faculty->faculty_code ? 'selected' : '' }}>
                                    {{ $faculty->faculty_desc_e }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" data-audience="group">
                        <label for="campaign_major_code">Major (optional)</label>
                        <select name="major_code" id="campaign_major_code" class="form-control" disabled>
                            <option value="">All Majors</option>
                        </select>
                    </div>

                    <div class="form-group" data-audience="group">
                        <label for="campaign_batch">Batch (optional)</label>
                        <select name="batch" id="campaign_batch" class="form-control">
                            <option value="">All Batches</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->batch }}" {{ old('batch') == $batch->batch ? 'selected' : '' }}>
                                    {{ $batch->batch }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" data-audience="group">
                        <label for="campaign_semester">Semester (optional)</label>
                        <select name="semester" id="campaign_semester" class="form-control">
                            <option value="">All Semesters</option>
                            @for ($semester = 1; $semester <= 10; $semester++)
                                <option value="{{ $semester }}" {{ old('semester') == $semester ? 'selected' : '' }}>
                                    {{ $semester }}
                                </option>
                            @endfor
                        </select>
                    </div>

                </div>
            </div>

            {{-- Schedule --}}

            <div class="settings-section">
                <h4>Schedule</h4>

                <div class="form-grid">

                    <div class="form-group">
                        <label for="campaign_frequency">Frequency</label>
                        <select name="frequency" id="campaign_frequency" class="form-control" required>
                            <option value="daily" {{ old('frequency', 'daily') === 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ old('frequency') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        </select>
                    </div>

                    <div class="form-group" data-frequency="weekly">
                        <label for="campaign_weekday">Day of Week</label>
                        <select name="weekday" id="campaign_weekday" class="form-control">
                            @foreach ($weekdays as $value => $label)
                                <option value="{{ $value }}" {{ (string) old('weekday', '0') === (string) $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="campaign_start_date">Start Date</label>
                        <input type="date" name="start_date" id="campaign_start_date" class="form-control"
                            value="{{ old('start_date', now('Africa/Khartoum')->toDateString()) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="campaign_send_time">Send Time</label>
                        <input type="time" name="send_time" id="campaign_send_time" class="form-control"
                            value="{{ old('send_time', '13:30') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="campaign_total_sends">Number of Sends</label>
                        <input type="number" name="total_sends" id="campaign_total_sends" class="form-control"
                            value="{{ old('total_sends', 14) }}" min="1" max="520" required>
                    </div>

                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary" {{ $groupNames->isEmpty() ? 'disabled' : '' }}>
                    Schedule Campaign
                </button>
            </div>
        </form>

    </div>


    {{-- =====================================================
         CAMPAIGNS LIST
         ===================================================== --}}

    <div class="admin-card">

        <div class="admin-card-header">
            <div>
                <h3>Campaigns</h3>
                <p>"Send Now" sends today's message early; it will not be sent again at the scheduled time today.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="settings-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Audience</th>
                        <th>Progress</th>
                        <th>Next Send</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($campaigns as $campaign)
                        @php $nextSend = $campaign->nextSendAt(); @endphp

                        <tr>
                            <td>
                                <strong>{{ $campaign->name }}</strong>
                                <div class="notification-muted">
                                    {{ $campaign->template_group }} · {{ $campaign->scheduleLabel() }}
                                </div>
                            </td>

                            <td @if ($campaign->audience === 'indexes') title="{{ implode(', ', $campaign->student_indexes ?? []) }}" @endif>
                                {{ $campaign->audienceLabel() }}
                            </td>

                            <td>
                                {{ $campaign->sent_count }} / {{ $campaign->total_sends }}
                                @if ($campaign->last_result)
                                    <div class="notification-muted">
                                        Last: {{ $campaign->last_result['students'] ?? 0 }} students,
                                        {{ $campaign->last_result['with_device'] ?? 0 }} with device
                                        @if (!empty($campaign->last_result['not_found']))
                                            , {{ $campaign->last_result['not_found'] }} not found
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td>{{ $nextSend ? $nextSend->format('D d M Y h:i A') : '—' }}</td>

                            <td>
                                <span class="status-badge {{ $campaign->status === 'active' ? 'active' : 'inactive' }}">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </td>

                            <td>
                              <div class="campaign-actions">
                                @if ($campaign->status === 'active')
                                    <form method="POST" action="{{ route('admin.notifications.campaigns.send', $campaign) }}"
                                        onsubmit="return confirm('Send today\'s message to this audience now?')">
                                        @csrf
                                        <button type="submit" class="btn-success btn-sm">Send Now</button>
                                    </form>
                                @endif

                                @if ($campaign->status !== 'completed')
                                    <form method="POST" action="{{ route('admin.notifications.campaigns.toggle', $campaign) }}">
                                        @csrf
                                        <button type="submit" class="btn-light btn-sm">
                                            {{ $campaign->status === 'active' ? 'Pause' : 'Resume' }}
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.notifications.campaigns.destroy', $campaign) }}"
                                    onsubmit="return confirm('Delete this campaign?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm">Delete</button>
                                </form>
                              </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="notifications-empty">No scheduled campaigns yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('campaign-form');
            if (!form) return;

            const audience = document.getElementById('campaign_audience');
            const frequency = document.getElementById('campaign_frequency');
            const faculty = document.getElementById('campaign_faculty_code');
            const major = document.getElementById('campaign_major_code');
            const indexes = document.getElementById('campaign_indexes');

            // Show only the fields for the selected audience / frequency
            function toggleFields() {
                form.querySelectorAll('[data-audience]').forEach(el => {
                    el.style.display = el.dataset.audience === audience.value ? '' : 'none';
                });
                form.querySelectorAll('[data-frequency]').forEach(el => {
                    el.style.display = el.dataset.frequency === frequency.value ? '' : 'none';
                });
                indexes.required = audience.value === 'indexes';
                faculty.required = audience.value === 'group';
            }

            audience.addEventListener('change', toggleFields);
            frequency.addEventListener('change', toggleFields);
            toggleFields();

            // Load majors for the selected faculty
            faculty.addEventListener('change', function () {
                major.innerHTML = '<option value="">All Majors</option>';
                major.disabled = true;

                if (!this.value || !window.adminMajorsUrl) return;

                fetch(`${window.adminMajorsUrl}/${encodeURIComponent(this.value)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(response => response.ok ? response.json() : Promise.reject(response.status))
                    .then(majors => {
                        (Array.isArray(majors) ? majors : []).forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.major_code;
                            option.textContent = item.major_desc_e;
                            major.appendChild(option);
                        });
                        major.disabled = false;
                    })
                    .catch(error => console.error('Campaign majors loading error:', error));
            });
        });
    </script>
@endpush
