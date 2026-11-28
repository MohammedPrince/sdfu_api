@php
    $groupNames = $templateGroups->keys();
    $templateTypes = \App\Models\CampaignTemplate::TYPES;
@endphp

{{-- =========================================================
     TEMPLATE SETS
     ========================================================= --}}

<div class="admin-card saved-settings-card">

    <div class="admin-card-header">
        <div>
            <h3>Campaign Templates</h3>
            <p>
                Messages are grouped into sets and sent in order (#1, #2, ...). If a campaign sends more
                times than its set has messages, the set repeats. Placeholders:
                <code>{number}</code>, <code>{total}</code>, <code>{remaining}</code>.
            </p>
        </div>

        <div class="notifications-count">
            <strong>{{ $groupNames->count() }}</strong>
            <span>{{ $groupNames->count() === 1 ? 'Set' : 'Sets' }}</span>
        </div>
    </div>

    <div class="campaign-templates">

        @foreach ($templateGroups as $group => $templates)
            <details class="campaign-template-group" {{ $loop->first ? 'open' : '' }}>
                <summary>
                    <strong>{{ $group }}</strong>
                    <span class="notification-muted">{{ $templates->count() }} messages</span>
                </summary>

                @foreach ($templates as $template)
                    <form method="POST" action="{{ route('admin.notifications.templates.update', $template) }}"
                        class="campaign-template-row">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="template_group" value="{{ $template->template_group }}">

                        <div class="form-group campaign-template-seq">
                            <label>#</label>
                            <input type="number" name="sequence" class="form-control" min="1" max="520"
                                value="{{ $template->sequence }}" required>
                        </div>

                        <div class="form-group campaign-template-type">
                            <label>Type</label>
                            <select name="type" class="form-control" required>
                                @foreach ($templateTypes as $value => $label)
                                    <option value="{{ $value }}" {{ $template->type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group campaign-template-title">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" maxlength="255"
                                value="{{ $template->title }}" required>
                        </div>

                        <div class="form-group campaign-template-body">
                            <label>Body</label>
                            <textarea name="body" class="form-control" rows="2" maxlength="2000" required>{{ $template->body }}</textarea>
                        </div>

                        <div class="campaign-template-actions">
                            <button type="submit" class="btn-primary btn-sm">Save</button>
                            <button type="submit" class="btn-danger btn-sm"
                                form="delete-template-{{ $template->id }}">Delete</button>
                        </div>
                    </form>

                    <form method="POST" id="delete-template-{{ $template->id }}"
                        action="{{ route('admin.notifications.templates.destroy', $template) }}"
                        onsubmit="return confirm('Delete message #{{ $template->sequence }} from this set?')">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            </details>
        @endforeach


        {{-- Add a template (to an existing set or a new one) --}}

        <form method="POST" action="{{ route('admin.notifications.templates.store') }}"
            class="campaign-template-row campaign-template-new">
            @csrf

            <div class="form-group campaign-template-group-name">
                <label>Template Set</label>
                <input type="text" name="template_group" class="form-control" maxlength="100" required
                    list="campaign-template-groups" placeholder="Existing set or new name (e.g. Weekly Reminders)">
                <datalist id="campaign-template-groups">
                    @foreach ($groupNames as $group)
                        <option value="{{ $group }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="form-group campaign-template-seq">
                <label>#</label>
                <input type="number" name="sequence" class="form-control" min="1" max="520" value="1" required>
            </div>

            <div class="form-group campaign-template-type">
                <label>Type</label>
                <select name="type" class="form-control" required>
                    @foreach ($templateTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group campaign-template-title">
                <label>Title</label>
                <input type="text" name="title" class="form-control" maxlength="255" required
                    placeholder="Message title">
            </div>

            <div class="form-group campaign-template-body">
                <label>Body</label>
                <textarea name="body" class="form-control" rows="2" maxlength="2000" required
                    placeholder="Message text, e.g. Reminder {number} of {total}..."></textarea>
            </div>

            <div class="campaign-template-actions">
                <button type="submit" class="btn-success btn-sm">Add Template</button>
            </div>
        </form>

    </div>

</div>
