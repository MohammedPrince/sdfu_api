@extends('admin.layouts.app')

@section('title', 'User Permissions')

@section('page-title', 'User Permissions')

@section('page-description', 'Configure menu access for administrative users')

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
        {{-- USER SELECTION + PERMISSIONS --}}
        {{-- ========================================================= --}}

        <div class="manage-settings-grid">

            {{-- ===================================================== --}}
            {{-- LEFT: MANAGE USER PERMISSIONS --}}
            {{-- ===================================================== --}}

            <div class="admin-card">

                <div class="admin-card-header">
                    <div>
                        <h3>Manage User Permissions</h3>

                        <p>
                            Select an administrative user and configure which
                            sections of the administration panel they can access.
                        </p>
                    </div>
                </div>


                <form method="POST"
                    action="{{ $selectedUser ? route('admin.permissions.update', $selectedUser->id) : route('admin.permissions') }}">

                    @csrf

                    {{-- User Selection --}}
                    <div class="settings-section">

                        <h4>Administrative User</h4>

                        <div class="form-grid">

                            {{-- Username --}}
                            <div class="form-group">

                                <label for="user_id">
                                    Username
                                </label>

                                <select name="user_id" id="user_id" class="form-control"
                                    onchange="if (this.value) window.location='{{ route('admin.permissions') }}?user_id=' + this.value;"
                                    required>

                                    <option value="">
                                        Select User
                                    </option>

                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}"
                                            {{ $selectedUser && $selectedUser->id == $user->id ? 'selected' : '' }}>
                                            {{ $user->username }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>

                    </div>


                    {{-- Menu Permissions --}}
                    @if ($selectedUser)

                        <div class="settings-section">

                            <h4>Application Menu Access</h4>

                            <div class="status-list">

                                @foreach ($menus as $menuKey => $menu)
                                    <div class="status-item">

                                        <div class="status-info">

                                            <strong>
                                                {{ $menu['label'] }}
                                            </strong>

                                            <span>
                                                {{ $menu['section'] ?? 'ADMINISTRATION' }}
                                            </span>

                                        </div>

                                        <label class="switch">

                                            <input type="hidden" name="permissions[]" value="">

                                            <input type="checkbox" name="permissions[]" value="{{ $menuKey }}"
                                                {{ in_array($menuKey, $permissions ?? []) ? 'checked' : '' }}>

                                            <span class="slider"></span>

                                        </label>

                                    </div>
                                @endforeach

                            </div>

                        </div>


                        <div class="form-actions">

                            <button type="submit" class="btn-primary">
                                Save Permissions
                            </button>

                        </div>
                    @else
                        <div class="empty-state">

                            <p>
                                Select a user to manage their permissions.
                            </p>

                        </div>

                    @endif

                </form>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT: CURRENT USERS --}}
            {{-- ===================================================== --}}

            <div class="admin-card saved-settings-card">

                <div class="admin-card-header">

                    <div>

                        <h3>Administrative Users</h3>

                        <p>
                            Users with role ID and their current access status.
                        </p>

                    </div>

                </div>


                @if ($users->isEmpty())

                    <div class="empty-state">

                        <p>
                            No administrative users have been created yet.
                        </p>

                    </div>
                @else
                    <div class="table-responsive">

                        <table class="settings-table">

                            <thead>

                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Permissions</th>
                                    <th>Status</th>
                                    <th>Edit</th>
                                </tr>

                            </thead>


                            <tbody>

                                @foreach ($users as $user)
                                    <tr>

                                        {{-- Username --}}
                                        <td>
                                            {{ $user->username }}
                                        </td>


                                        {{-- Email --}}
                                        <td>
                                            {{ $user->email ?? '-' }}
                                        </td>


                                        {{-- Permissions --}}
                                        <td>

                                            @php
                                                $userPermissions = $user->adminPermissions
                                                    ->where('can_view', true)
                                                    ->count();
                                            @endphp

                                            <span class="status-badge active">
                                                {{ $userPermissions }}
                                                {{ $userPermissions == 1 ? 'Menu' : 'Menus' }}
                                            </span>

                                        </td>


                                        {{-- Status --}}
                                        <td>

                                            @if ($user->is_active)
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

                                            <a href="{{ route('admin.permissions', [
                                                'user_id' => $user->id,
                                            ]) }}"
                                                class="btn-edit btn-sm">

                                                <button type="button" class="btn-primary btn-view-timetable">
                                                    Edit
                                                </button>

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

@endsection
