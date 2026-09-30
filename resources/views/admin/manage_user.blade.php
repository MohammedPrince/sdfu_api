@extends('admin.layouts.app')

@section('title', 'Manage Users')
@section('page-title', 'Manage Users')
@section('page-description',
    'Create administrator accounts and control which administration pages each user can
    access')

@section('content')
    <div class="manage-page">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="manage-settings-grid">

            {{-- =========================================================
             ADD / UPDATE USER
        ========================================================== --}}
            <div class="admin-card">

                <div class="admin-card-header">
                    <div>
                        <h3>{{ $editUser ? 'Update Administrator' : 'Add Administrator' }}</h3>
                        <p>
                            {{ $editUser
                                ? 'Update the administrator account and its page permissions.'
                                : 'Create a new administrator account and select the pages the user can access.' }}
                        </p>
                    </div>
                </div>

                <form method="POST"
                    action="{{ $editUser ? route('admin.users.update', $editUser->id) : route('admin.users.store') }}">

                    @csrf

                    @if ($editUser)
                        @method('PUT')
                    @endif

                    <div class="settings-section">
                        <h4>Administrator Account</h4>

                        <div class="form-grid">

                            <div class="form-group">
                                <label for="username">Username</label>

                                <input type="text" name="username" id="username" class="form-control"
                                    value="{{ old('username', $editUser->username ?? '') }}" autocomplete="username"
                                    placeholder="Enter Username" required>
                            </div>

                            <div class="form-group">
                                <label for="password">
                                    Password
                                    @if ($editUser)
                                        <small>(leave empty to keep current password)</small>
                                    @endif
                                </label>

                                <input type="password" name="password" id="password" class="form-control"
                                    placeholder="Enter User Password" autocomplete="new-password"
                                    {{ $editUser ? '' : 'required' }}>
                            </div>

                        </div>
                    </div>

                    <div class="settings-section">
                        <h4>Page Access</h4>

                        <div class="permission-select-toolbar">

                            <div class="status-info">
                                <strong>Administrator Permissions</strong>
                                <span>
                                    Select the administration pages this user is allowed to access.
                                </span>
                            </div>

                            <div class="permission-toolbar-actions">
                                <button type="button" class="btn-secondary btn-sm" id="selectAllPermissions">
                                    Select All
                                </button>

                                <button type="button" class="btn-secondary btn-sm" id="clearAllPermissions">
                                    Clear All
                                </button>
                            </div>

                        </div>

                        <div class="admin-permissions-grid">

                            @foreach ($menus as $menuKey => $menu)
                                <label class="admin-permission-item">

                                    <input type="checkbox" name="permissions[]" value="{{ $menuKey }}"
                                        class="permission-checkbox"
                                        {{ in_array($menuKey, $permissions ?? [], true) ? 'checked' : '' }}>

                                    <span class="admin-permission-check"></span>

                                    <span class="admin-permission-info">
                                        <strong>{{ $menu['label'] }}</strong>
                                        <small>{{ $menu['section'] ?? 'ADMINISTRATION' }}</small>
                                    </span>

                                </label>
                            @endforeach

                        </div>
                    </div>

                    <div class="form-actions">

                        @if ($editUser)
                            <a href="{{ route('admin.users') }}" class="btn-secondary">
                                Cancel
                            </a>
                        @endif

                        <button type="submit" class="btn-primary">
                            {{ $editUser ? 'Update User' : 'Add User' }}
                        </button>

                    </div>

                </form>
            </div>


            {{-- =========================================================
             ADMIN USERS
        ========================================================== --}}
            <div class="admin-card saved-settings-card">

                <div class="admin-card-header">
                    <div>
                        <h3>Administrator Users</h3>
                        <p>
                            Administrator accounts and their assigned page access.
                        </p>
                    </div>
                </div>

                @if ($users->isEmpty())

                    <div class="empty-state">
                        <p>No administrator users have been created yet.</p>
                    </div>
                @else
                    <div class="table-responsive">

                        <table class="settings-table">

                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Access</th>
                                    <th>Created</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach ($users as $user)
                                    @php
                                        $userPermissionCount = $user->adminPermissions
                                            ->where('can_view', true)
                                            ->count();

                                        $isCurrentUser = auth()->id() === $user->id;

                                        $isProtectedUser = strtolower(trim($user->username ?? '')) === 'prince';
                                    @endphp

                                    <tr>

                                        <td>
                                            <strong>{{ $user->username }}</strong>

                                            @if ($isCurrentUser)
                                                <div>
                                                    <span class="status-badge active">
                                                        Current User
                                                    </span>
                                                </div>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="status-badge active">
                                                Administrator
                                            </span>
                                        </td>

                                        <td>
                                            <span
                                                class="status-badge {{ $userPermissionCount > 0 ? 'active' : 'inactive' }}">
                                                {{ $userPermissionCount }}
                                                {{ $userPermissionCount === 1 ? 'Page' : 'Pages' }}
                                            </span>
                                        </td>

                                        <td>
                                            {{ optional($user->created_at)->format('d M Y') }}
                                        </td>

                                        <td class="actions-cell">
                                            <a href="{{ route('admin.users', ['edit' => $user->id]) }}"
                                                style="text-decoration:none;">
                                                <button class="btn-secondary btn-sm">Edit</button>
                                            </a>
                                        </td>

                                        <td class="actions-cell">

                                            @if ($isProtectedUser || $isCurrentUser)
                                                <span class="status-badge inactive">
                                                    Protected
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                                                    onsubmit="return confirm('Delete administrator {{ $user->username }}? This will also remove their page permissions.');">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="btn-danger btn-sm">
                                                        Delete
                                                    </button>

                                                </form>
                                            @endif

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

    @push('styles')
        <style>
            .permission-select-toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                margin-bottom: 18px;
                padding: 14px 16px;
                background: #faf7f4;
                border: 1px solid #eee4dc;
                border-radius: 10px;
            }

            .permission-toolbar-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-shrink: 0;
            }

            .admin-permissions-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .admin-permission-item {
                position: relative;

                display: flex;
                align-items: center;

                gap: 12px;

                min-height: 58px;

                padding: 11px 14px;

                background: #fff;

                border: 1px solid #e9e1d8;
                border-radius: 10px;

                cursor: pointer;

                transition:
                    border-color .2s ease,
                    background .2s ease,
                    box-shadow .2s ease;
            }

            .admin-permission-item:hover {
                border-color: rgba(217, 41, 30, .35);
                background: #fffaf8;
            }

            /*
                            |--------------------------------------------------------------------------
                            | Hide native checkbox
                            |--------------------------------------------------------------------------
                            */

            .admin-permission-item input {
                position: absolute;

                width: 1px;
                height: 1px;

                opacity: 0;

                pointer-events: none;
            }

            /*
                            |--------------------------------------------------------------------------
                            | Check indicator
                            |--------------------------------------------------------------------------
                            */

            .admin-permission-check {
                position: relative;

                width: 20px;
                height: 20px;

                flex: 0 0 20px;

                border: 2px solid #d8cec5;
                border-radius: 50%;

                background: #fff;

                transition:
                    background .2s ease,
                    border-color .2s ease,
                    transform .2s ease;
            }

            /*
                            |--------------------------------------------------------------------------
                            | Small dot / check when selected
                            |--------------------------------------------------------------------------
                            */

            .admin-permission-item input:checked+.admin-permission-check {
                border-color: var(--fu-red);
                background: var(--fu-red);

                transform: scale(1.02);
            }

            .admin-permission-item input:checked+.admin-permission-check::after {
                content: "";

                position: absolute;

                width: 5px;
                height: 9px;

                left: 6px;
                top: 3px;

                border: solid #fff;
                border-width: 0 2px 2px 0;

                transform: rotate(45deg);
            }

            /*
                            |--------------------------------------------------------------------------
                            | Permission information
                            |--------------------------------------------------------------------------
                            */

            .admin-permission-info {
                display: flex;
                flex-direction: column;

                gap: 3px;

                min-width: 0;
            }

            .admin-permission-info strong {
                color: #3c2b23;
                font-size: 14px;
                font-weight: 600;
            }

            .admin-permission-info small {
                color: #907d70;

                font-size: 10px;

                text-transform: uppercase;
                letter-spacing: .5px;
            }

            /*
                            |--------------------------------------------------------------------------
                            | Selected row
                            |--------------------------------------------------------------------------
                            */

            .admin-permission-item:has(input:checked) {
                border-color: rgba(217, 41, 30, .35);
                background: #fff8f6;

                box-shadow:
                    0 2px 8px rgba(90, 16, 24, .05);
            }

            /*
                            |--------------------------------------------------------------------------
                            | Buttons
                            |--------------------------------------------------------------------------
                            */

            .actions-cell form {
                margin: 0;
            }

            .btn-sm {
                min-height: 34px;

                padding: 7px 12px;

                font-size: 12px;
            }

            /*
                            |--------------------------------------------------------------------------
                            | Responsive
                            |--------------------------------------------------------------------------
                            */

            @media (max-width: 900px) {

                .admin-permissions-grid {
                    grid-template-columns: 1fr;
                }

                .permission-select-toolbar {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .permission-toolbar-actions {
                    width: 100%;
                }
            }

            @media (max-width: 600px) {

                .admin-permissions-grid {
                    grid-template-columns: 1fr;
                }

                .permission-toolbar-actions {
                    display: grid;

                    grid-template-columns: 1fr 1fr;

                    width: 100%;
                }

                .permission-toolbar-actions button {
                    width: 100%;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const selectAllButton = document.getElementById('selectAllPermissions');
                const clearAllButton = document.getElementById('clearAllPermissions');

                function getPermissionCheckboxes() {
                    return document.querySelectorAll('.permission-checkbox');
                }

                if (selectAllButton) {
                    selectAllButton.addEventListener('click', function() {
                        getPermissionCheckboxes().forEach(function(checkbox) {
                            checkbox.checked = true;
                        });
                    });
                }

                if (clearAllButton) {
                    clearAllButton.addEventListener('click', function() {
                        getPermissionCheckboxes().forEach(function(checkbox) {
                            checkbox.checked = false;
                        });
                    });
                }

            });
        </script>
    @endpush

@endsection
